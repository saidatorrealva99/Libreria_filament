<?php

namespace App\Support;

use App\Models\DetalleVenta;
use App\Models\Producto;

/**
 * Datos de apoyo para la tienda online (etiquetas de las tarjetas).
 */
class Tienda
{
    private static ?array $top = null;

    /** IDs de los 8 productos más vendidos en los últimos 60 días */
    public static function masVendidosIds(): array
    {
        return self::$top ??= DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.fecha_venta', '>=', now()->subDays(60))
            ->groupBy('detalle_ventas.producto_id')
            ->orderByRaw('SUM(detalle_ventas.cantidad) DESC')
            ->limit(8)
            ->pluck('detalle_ventas.producto_id')
            ->all();
    }

    public static function esMasVendido(Producto $p): bool
    {
        return in_array($p->id, self::masVendidosIds(), true);
    }

    public static function esNuevo(Producto $p): bool
    {
        return $p->created_at !== null && $p->created_at->gte(now()->subDays(30));
    }

    /** Ícono para productos que no son libros, según su categoría */
    public static function icono(Producto $p): string
    {
        $texto = mb_strtolower(($p->categoria?->nombre ?? '') . ' ' . $p->nombre);

        return match (true) {
            str_contains($texto, 'cuaderno'), str_contains($texto, 'block'), str_contains($texto, 'agenda') => '📓',
            str_contains($texto, 'lapicero'), str_contains($texto, 'bolígrafo'), str_contains($texto, 'escritura'), str_contains($texto, 'lápiz') => '🖊️',
            str_contains($texto, 'color'), str_contains($texto, 'plumon'), str_contains($texto, 'plumón'), str_contains($texto, 'arte'), str_contains($texto, 'témpera') => '🎨',
            str_contains($texto, 'mochila'), str_contains($texto, 'cartuchera') => '🎒',
            str_contains($texto, 'calculadora'), str_contains($texto, 'tecnolog'), str_contains($texto, 'usb') => '🧮',
            str_contains($texto, 'papel'), str_contains($texto, 'hoja'), str_contains($texto, 'cartulina') => '📄',
            str_contains($texto, 'folder'), str_contains($texto, 'archivador'), str_contains($texto, 'oficina') => '🗂️',
            str_contains($texto, 'tijera'), str_contains($texto, 'goma'), str_contains($texto, 'pegamento'), str_contains($texto, 'regla') => '✂️',
            default => '✏️',
        };
    }
}
