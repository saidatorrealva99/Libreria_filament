<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Venta extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'ventas';

    protected $fillable = [
        'tipo_comprobante',
        'canal',
        'tipo_entrega',
        'zona_envio_id',
        'direccion_entrega',
        'referencia_entrega',
        'costo_envio',
        'codigo_pedido',
        'numero_comprobante',
        'cliente_id',
        'empleado_id',
        'fecha_venta',
        'subtotal',
        'descuento',
        'total',
        'estado',
        'metodo_declarado',
        'operacion_declarada',
        'entregado_at',
        'cupon_id',
        'cierre_caja_id',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal'    => 'decimal:2',
        'descuento'   => 'decimal:2',
        'costo_envio' => 'decimal:2',
        'total'       => 'decimal:2',
        'entregado_at' => 'datetime',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'pagada'    => 'Pagada',
        'anulada'   => 'Anulada',
    ];

    public const METODOS_WEB = [
        'yape'   => 'Yape',
        'plin'   => 'Plin',
        'recojo' => 'Pago al recoger / contra entrega',
    ];

    /**
     * Ventas que puede ver el usuario: el admin, todas;
     * los demás, solo las de HOY y los pedidos web que siguen pendientes.
     */
    public function scopeVisibles(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        if (\App\Support\Permisos::puede('historial')) {
            return $q;
        }

        return $q->where(fn ($w) => $w->whereDate('fecha_venta', today())
            ->orWhere(fn ($web) => $web->where('canal', 'web')->where('estado', 'pendiente')));
    }

    public function caja(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CierreCaja::class, 'cierre_caja_id');
    }

    public function esDelivery(): bool
    {
        return $this->tipo_entrega === 'delivery';
    }

    public function zona(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_envio_id');
    }

    public function esWeb(): bool
    {
        return $this->canal === 'web';
    }

    // Estado del pedido para el cliente (seguimiento)
    public function estadoPedido(): string
    {
        return match (true) {
            $this->estado === 'anulada'  => 'Cancelado',
            $this->entregado_at !== null => 'Entregado',
            $this->estado === 'pagada'   => $this->esDelivery() ? 'Listo para enviar' : 'Listo para recoger',
            $this->operacion_declarada   => 'Verificando pago',
            default                      => 'Pendiente de pago',
        };
    }

    public static function nuevoCodigoPedido(): string
    {
        do {
            $codigo = 'WEB-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::where('codigo_pedido', $codigo)->exists());

        return $codigo;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    // Ventas válidas (no anuladas) para reportes y dashboard
    public function scopeValidas(Builder $query): Builder
    {
        return $query->where('estado', '!=', 'anulada');
    }

    public function totalPagado(): float
    {
        return (float) $this->pagos()->sum('monto');
    }

    public function saldo(): float
    {
        return max(0, round((float) $this->total - $this->totalPagado(), 2));
    }

    /**
     * Genera el siguiente número de comprobante: B001-00000010 / F001-00000002
     */
    public static function siguienteNumero(string $tipo, string $serie = '001'): string
    {
        $letra  = $tipo === 'factura' ? 'F' : 'B';
        $prefijo = "{$letra}{$serie}-";

        $ultimo = static::where('numero_comprobante', 'like', "{$prefijo}%")
            ->orderByDesc('numero_comprobante')
            ->value('numero_comprobante');

        $n = $ultimo ? ((int) substr($ultimo, 5)) + 1 : 1;

        return $prefijo . str_pad((string) $n, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Si los pagos cubren el total → pagada; si no → pendiente.
     * Una venta anulada no cambia de estado.
     */
    public function recalcularEstado(): void
    {
        if ($this->estado === 'anulada') {
            return;
        }

        $estado = round($this->totalPagado(), 2) >= round((float) $this->total, 2) ? 'pagada' : 'pendiente';

        if ($estado !== $this->estado) {
            $this->update(['estado' => $estado]);
        }
    }

    /**
     * Descuenta del stock los productos vendidos.
     * Devuelve el nombre del producto sin stock suficiente, o null si todo salió bien.
     */
    public function descontarStock(): ?string
    {
        foreach ($this->detalles()->with('producto')->get() as $detalle) {
            try {
                // Resta el stock y lo registra en el kárdex (salida por venta)
                MovimientoInventario::registrar(
                    $detalle->producto_id, 'venta', $detalle->cantidad, $this->numero_comprobante, $this->id
                );
            } catch (\RuntimeException) {
                return $detalle->producto->nombre;
            }
        }

        return null;
    }

    /**
     * Anula la venta y devuelve los productos al stock (queda en el kárdex).
     */
    public function anular(): void
    {
        DB::transaction(function () {
            foreach ($this->detalles as $detalle) {
                MovimientoInventario::registrar(
                    $detalle->producto_id, 'anulacion', $detalle->cantidad, $this->numero_comprobante, $this->id
                );
            }

            $this->update(['estado' => 'anulada']);
        });
    }

    public function cupon(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cupon::class);
    }

    // Al registrar una venta en el panel, queda en la caja (turno) de quien la hizo
    protected static function booted(): void
    {
        static::creating(function (Venta $v) {
            if (! app()->runningInConsole() && \Illuminate\Support\Facades\Auth::check() && $v->canal !== 'web') {
                $v->cierre_caja_id ??= CierreCaja::abiertaHoy()?->id;
            }
        });
    }
}
