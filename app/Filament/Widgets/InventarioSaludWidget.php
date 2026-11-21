<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Reposicion;
use App\Models\Producto;
use App\Support\Inventario;
use Filament\Widgets\Widget;

/**
 * Estado del inventario: agotados, críticos, por agotarse y normales + valor del stock.
 */
class InventarioSaludWidget extends Widget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_inventario');
    }

    protected static string $view = 'filament.widgets.inventario-salud';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        $analisis = Inventario::analisis();
        $total    = max(1, $analisis->count());

        $estados = collect(Inventario::ESTADOS)->map(fn ($e, $clave) => [
            'nombre'   => $e[0],
            'clave'    => $clave,
            'cantidad' => $cantidad = $analisis->where('estado', $clave)->count(),
            'pct'      => round($cantidad / $total * 100),
        ]);

        $activos = Producto::activos();

        return [
            'estados'       => $estados,
            'productos'     => $analisis->count(),
            'valorCosto'    => (float) (clone $activos)->selectRaw('SUM(stock * COALESCE(costo, precio_venta * 0.65)) as v')->value('v'),
            'valorVenta'    => (float) (clone $activos)->selectRaw('SUM(stock * precio_venta) as v')->value('v'),
            'estrellas'     => $analisis->where('clase', 'estrella')->count(),
            'sinMovimiento' => $analisis->where('clase', 'sin_movimiento')->count(),
            'url'           => Reposicion::canAccess() ? Reposicion::getUrl() : null,
        ];
    }
}
