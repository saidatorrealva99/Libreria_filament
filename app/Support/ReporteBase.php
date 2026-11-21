<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

/**
 * Base de los reportes: página con una tabla filtrable, botón Imprimir
 * y (opcional) widgets arriba que reciben los filtros de la tabla.
 */
abstract class ReporteBase extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.reporte';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    /**
     * Widgets que se muestran arriba de la tabla.
     * Formato: [ClaseDelWidget::class => columnas que ocupa (de 3)]
     */
    /** Módulo de permisos de este reporte (ver app/Support/Permisos.php) */
    protected static string $modulo = '';

    // Oculta el reporte del menú y bloquea la URL si el rol no tiene permiso
    public static function canAccess(): bool
    {
        return Permisos::puede(static::$modulo, 'ver');
    }

    public function getWidgetsReporte(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimir')
                ->label('Imprimir / PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->alpineClickHandler('window.print()'),
        ];
    }
}
