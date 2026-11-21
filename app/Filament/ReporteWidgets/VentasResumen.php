<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteVentas;
use App\Support\Formato;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VentasResumen extends StatsOverviewWidget
{
    // Filtros que le pasa la página del reporte
    public array $filtros = [];

    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $query = ReporteVentas::consulta($this->filtros)->toBase();

        $cantidad  = (clone $query)->count();
        $total     = (float) (clone $query)->sum('total');
        $descuento = (float) (clone $query)->sum('descuento');
        $clientes  = (clone $query)->distinct()->count('cliente_id');

        return [
            Stat::make('Total vendido', Formato::soles($total))
                ->description('Según los filtros aplicados')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('N° de ventas', (string) $cantidad)
                ->description("{$clientes} cliente(s) distinto(s)")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Ticket promedio', Formato::soles($cantidad ? $total / $cantidad : 0))
                ->description('Total ÷ N° de ventas')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),

            Stat::make('Descuentos', Formato::soles($descuento))
                ->description($total > 0 ? round($descuento / ($total + $descuento) * 100, 1) . '% del subtotal' : '—')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('danger'),
        ];
    }
}
