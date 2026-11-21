<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'pagos';

    protected $fillable = [
        'venta_id',
        'metodo_pago',
        'monto',
        'numero_operacion',
        'fecha_pago',
        'user_id', 'cierre_caja_id',
    ];

    protected $casts = [
        'monto'      => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public const METODOS = [
        'efectivo'      => 'Efectivo',
        'tarjeta'       => 'Tarjeta',
        'yape'          => 'Yape',
        'plin'          => 'Plin',
        'transferencia' => 'Transferencia',
    ];

    // Sin caja abierta no se registran cobros (los seeders y comandos no se bloquean)
    public static function verificarCajaAbierta(): void
    {
        if (app()->runningInConsole() || ! \Illuminate\Support\Facades\Auth::check() || CierreCaja::abiertaHoy()) {
            return;
        }

        // El administrador puede registrar cobros sin caja propia
        if (\App\Support\Permisos::puede('historial')) {
            return;
        }

        \Filament\Notifications\Notification::make()
            ->danger()
            ->title('La caja está cerrada')
            ->body('Abre la caja del día para poder registrar cobros.')
            ->actions([
                \Filament\Notifications\Actions\Action::make('caja')->label('Ir a Caja')->button()
                    ->url(\App\Filament\Resources\CierreCajaResource::getUrl('index')),
            ])
            ->persistent()
            ->send();

        throw (new \Filament\Support\Exceptions\Halt())->rollBackDatabaseTransaction();
    }

    // Cada vez que se registra o elimina un pago, la venta actualiza su estado
    protected static function booted(): void
    {
        static::creating(function (Pago $pago) {
            self::verificarCajaAbierta();

            // Quién cobró y en qué caja (turno)
            if (! app()->runningInConsole() && \Illuminate\Support\Facades\Auth::check()) {
                $pago->user_id ??= \Illuminate\Support\Facades\Auth::id();
                $pago->cierre_caja_id ??= CierreCaja::abiertaHoy()?->id;
            }
        });
        static::saved(fn (Pago $pago) => $pago->venta?->recalcularEstado());
        static::deleted(fn (Pago $pago) => $pago->venta?->recalcularEstado());
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function bitacoraNombre(): string
    {
        return 'S/ ' . number_format((float) $this->monto, 2) . ' (' . (self::METODOS[$this->metodo_pago] ?? $this->metodo_pago) . ') de la venta ' . ($this->venta?->numero_comprobante ?? '#' . $this->venta_id);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class, 'cierre_caja_id');
    }
}
