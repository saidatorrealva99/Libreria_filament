<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Kárdex: cada entrada o salida de stock queda registrada aquí.
 *
 * @property int $id
 * @property int $producto_id
 * @property string $tipo            entrada | salida
 * @property string $motivo          inicial | compra | venta | anulacion | merma | devolucion
 * @property int $cantidad
 * @property int $stock_anterior
 * @property int $stock_nuevo
 * @property string|null $referencia
 * @property string|null $nota
 * @property int|null $venta_id
 * @property int|null $compra_id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id', 'tipo', 'motivo', 'cantidad', 'stock_anterior', 'stock_nuevo',
        'referencia', 'nota', 'venta_id', 'compra_id', 'user_id',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'stock_anterior' => 'integer',
        'stock_nuevo'    => 'integer',
    ];

    public const MOTIVOS = [
        'inicial'   => 'Inventario inicial',
        'compra'    => 'Compra / ingreso',
        'venta'     => 'Venta',
        'anulacion' => 'Anulación de venta',
        'merma'     => 'Merma / daño',
        'devolucion' => 'Devolución a proveedor',
    ];

    // Qué tipo corresponde a cada motivo
    public const TIPO_POR_MOTIVO = [
        'inicial'   => 'entrada',
        'compra'    => 'entrada',
        'anulacion' => 'entrada',
        'venta'     => 'salida',
        'merma'     => 'salida',
        'devolucion' => 'salida',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Mueve el stock de un producto y deja el registro en el kárdex.
     *
     *   MovimientoInventario::registrar($productoId, 'compra', 20, 'Guía 001-4521');
     *
     * Lanza RuntimeException si una salida deja el stock en negativo.
     */
    public static function registrar(
        int $productoId,
        string $motivo,
        int $cantidad,
        ?string $referencia = null,
        ?int $ventaId = null,
        ?string $nota = null,
        ?int $compraId = null,
    ): self {
        return DB::transaction(function () use ($productoId, $motivo, $cantidad, $referencia, $ventaId, $nota, $compraId) {
            // Bloquea la fila del producto mientras se actualiza (evita ventas simultáneas)
            $producto = Producto::lockForUpdate()->findOrFail($productoId);

            $tipo     = self::TIPO_POR_MOTIVO[$motivo];
            $anterior = $producto->stock;
            $nuevo    = $tipo === 'entrada' ? $anterior + $cantidad : $anterior - $cantidad;

            if ($nuevo < 0) {
                throw new RuntimeException("Stock insuficiente de \"{$producto->nombre}\" (hay {$anterior}).");
            }

            $producto->update(['stock' => $nuevo]);

            return self::create([
                'producto_id'    => $producto->id,
                'tipo'           => $tipo,
                'motivo'         => $motivo,
                'cantidad'       => $cantidad,
                'stock_anterior' => $anterior,
                'stock_nuevo'    => $nuevo,
                'referencia'     => $referencia,
                'nota'           => $nota,
                'venta_id'       => $ventaId,
                'compra_id'      => $compraId,
                'user_id'        => Auth::id(),
            ]);
        });
    }
}
