<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Reposicion;
use App\Support\Inventario;
use Filament\Widgets\Widget;

/**
 * Los productos más urgentes de reponer, con sus días de cobertura y la cantidad sugerida.
 */
class ReposicionWidget extends Widget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_inventario');
    }

    protected static string $view = 'filament.widgets.reposicion-widget';
    protected static ?int $sort = 7;
    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        $reponer = Inventario::reponer();

        return [
            'filas'     => $reponer->take(5),
            'total'     => $reponer->count(),
            'inversion' => $reponer->sum('inversion'),
            'url'       => Reposicion::canAccess() ? Reposicion::getUrl() : null,
        ];
    }
}
