<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteCaja;
use App\Models\Pago;
use App\Support\Formato;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CajaResumen extends StatsOverviewWidget
{
    public array $filtros = [];

    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $query = ReporteCaja::consulta($this->filtros)->toBase();

        $total    = (float) (clone $query)->sum('monto');
        $cantidad = (clone $query)->count();
        $efectivo = (float) (clone $query)->where('metodo_pago', 'efectivo')->sum('monto');
        $digital  = (float) (clone $query)->whereIn('metodo_pago', ['yape', 'plin'])->sum('monto');

        $top = (clone $query)
            ->selectRaw('metodo_pago, SUM(monto) as total')
            ->groupBy('metodo_pago')
            ->orderByDesc('total')
            ->first();

        $porc = fn (float $m) => $total > 0 ? round($m / $total * 100, 1) . '% del total' : '—';

        return [
            Stat::make('Total cobrado', Formato::soles($total))
                ->description("{$cantidad} pagos registrados")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('En efectivo', Formato::soles($efectivo))
                ->description($porc($efectivo))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Yape + Plin', Formato::soles($digital))
                ->description($porc($digital))
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('info'),

            Stat::make('Método más usado', $top ? (Pago::METODOS[$top->metodo_pago] ?? $top->metodo_pago) : '—')
                ->description($top ? Formato::soles($top->total) : 'Sin datos')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
        ];
    }
}
