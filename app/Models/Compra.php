<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Compra de mercadería a un proveedor: al registrarla el stock SUBE (kárdex: entrada por compra).
 */
class Compra extends Model
{
    // Bitácora: registra quién crea, cambia o elimina
    use \App\Models\Concerns\RegistraActividad;

    protected $table = 'compras';

    protected $fillable = ['proveedor_id', 'numero_documento', 'fecha_compra', 'total', 'estado', 'observacion', 'user_id'];

    protected $casts = [
        'fecha_compra' => 'date',
        'total'        => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeRegistradas(Builder $query): Builder
    {
        return $query->where('estado', 'registrada');
    }

    /**
     * Ingresa al stock lo comprado (queda en el kárdex) y actualiza el costo de cada producto.
     */
    public function ingresarStock(): void
    {
        DB::transaction(function () {
            foreach ($this->detalles as $detalle) {
                MovimientoInventario::registrar(
                    $detalle->producto_id, 'compra', $detalle->cantidad,
                    "Compra {$this->numero_documento}", null, $this->proveedor?->razon_social, $this->id
                );

                Producto::whereKey($detalle->producto_id)->update(['costo' => $detalle->costo_unitario]);
            }
        });
    }

    /**
     * Anula la compra: la mercadería sale del stock (devolución al proveedor).
     * Lanza RuntimeException si ya se vendió y no alcanza el stock.
     */
    public function anular(): void
    {
        DB::transaction(function () {
            foreach ($this->detalles as $detalle) {
                MovimientoInventario::registrar(
                    $detalle->producto_id, 'devolucion', $detalle->cantidad,
                    "Anula compra {$this->numero_documento}", null, $this->proveedor?->razon_social, $this->id
                );
            }

            $this->update(['estado' => 'anulada']);
        });
    }
}
