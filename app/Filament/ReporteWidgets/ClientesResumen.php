<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteClientes;
use App\Support\Formato;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientesResumen extends StatsOverviewWidget
{
    public array $filtros = [];

    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $clientes = ReporteClientes::consulta($this->filtros)->get();

        $total   = (float) $clientes->sum('ventas_sum_total');
        $compras = (int) $clientes->sum('ventas_count');
        $mejor   = $clientes->sortByDesc('ventas_sum_total')->first();
        $n       = $clientes->count();

        return [
            Stat::make('Clientes con compras', (string) $n)
                ->description("{$compras} compras en total")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Total facturado', Formato::soles($total))
                ->description('Ventas no anuladas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Mejor cliente', $mejor ? Formato::soles($mejor->ventas_sum_total) : '—')
                ->description($mejor?->nombre_completo ?? 'Sin datos')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),

            Stat::make('Promedio por cliente', Formato::soles($n ? $total / $n : 0))
                ->description('Total ÷ N° de clientes')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),
        ];
    }
}
