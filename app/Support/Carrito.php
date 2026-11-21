<?php

namespace App\Support;

use App\Models\Cupon;
use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Carrito de compras de la tienda online.
 * Se guarda en la sesión como [producto_id => cantidad].
 */
class Carrito
{
    private const CLAVE = 'carrito';

    /** @return array<int, int> */
    public static function crudo(): array
    {
        return session(self::CLAVE, []);
    }

    /**
     * Ítems con su producto, cantidad e importe (precios siempre de la BD).
     * Quita los productos que ya no existen o no están disponibles.
     */
    public static function items(): Collection
    {
        $crudo = self::crudo();

        if (empty($crudo)) {
            return collect();
        }

        $productos = Producto::with(['autor', 'categoria'])
            ->whereIn('id', array_keys($crudo))
            ->where('estado', true)
            ->get()
            ->keyBy('id');

        return collect($crudo)
            ->filter(fn ($cantidad, $id) => $productos->has($id))
            ->map(function ($cantidad, $id) use ($productos) {
                $producto = $productos[$id];
                $cantidad = min((int) $cantidad, $producto->stock);

                $precio = $producto->precioPara($cantidad);   // oferta + descuento por cantidad

                return (object) [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio'   => $precio,
                    'normal'   => (float) $producto->precio_venta,
                    'mayor'    => $producto->aplicaPorMayor($cantidad),
                    'importe'  => round($cantidad * $precio, 2),
                    'ahorro'   => round($cantidad * ((float) $producto->precio_venta - $precio), 2),
                ];
            })
            ->filter(fn ($item) => $item->cantidad > 0)
            ->values();
    }

    public static function total(): float
    {
        return round(self::items()->sum('importe'), 2);
    }

    /** Lo que el cliente se ahorra por ofertas y descuentos por cantidad */
    public static function ahorro(): float
    {
        return round(self::items()->sum('ahorro'), 2);
    }

    public static function cantidad(): int
    {
        return (int) array_sum(self::crudo());
    }

    /** Agrega unidades sin pasar el stock. Devuelve la cantidad final en el carrito. */
    public static function agregar(Producto $producto, int $cantidad = 1): int
    {
        $crudo = self::crudo();
        $nueva = min(($crudo[$producto->id] ?? 0) + max(1, $cantidad), $producto->stock);

        $crudo[$producto->id] = $nueva;
        session([self::CLAVE => $crudo]);

        return $nueva;
    }

    public static function actualizar(int $productoId, int $cantidad): void
    {
        $crudo = self::crudo();

        if ($cantidad <= 0) {
            unset($crudo[$productoId]);
        } else {
            $stock = (int) Producto::whereKey($productoId)->value('stock');
            $crudo[$productoId] = min($cantidad, $stock);
        }

        session([self::CLAVE => $crudo]);
    }

    public static function quitar(int $productoId): void
    {
        self::actualizar($productoId, 0);
    }

    /* ----------------------------- Cupón ----------------------------- */

    /** Cupón aplicado y válido para el total actual (si dejó de ser válido, se quita) */
    public static function cupon(): ?Cupon
    {
        $cupon = Cupon::buscar(session('cupon'));

        if ($cupon && $cupon->motivoInvalido(self::total()) === null) {
            return $cupon;
        }

        session()->forget('cupon');

        return null;
    }

    public static function aplicarCupon(string $codigo): ?string
    {
        $cupon = Cupon::buscar($codigo);

        if (! $cupon) {
            return 'El cupón "' . strtoupper($codigo) . '" no existe.';
        }

        if ($motivo = $cupon->motivoInvalido(self::total())) {
            return $motivo;
        }

        session(['cupon' => $cupon->codigo]);

        return null;
    }

    public static function descuento(): float
    {
        return self::cupon()?->descuentoPara(self::total()) ?? 0.0;
    }

    public static function totalAPagar(): float
    {
        return round(self::total() - self::descuento(), 2);
    }

    public static function vaciar(): void
    {
        session()->forget(self::CLAVE);
        session()->forget('cupon');
    }
}
