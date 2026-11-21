<?php

namespace App\Filament\Widgets;

use App\Models\Venta;
use App\Support\Periodo;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\ChartWidget;

/**
 * Ingresos y N° de ventas del periodo elegido en el Dashboard.
 * Agrupa por hora (hoy), por día (hasta 2 meses) o por mes (periodos largos).
 */
class VentasChart extends ChartWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_negocio');
    }

    use InteractsWithPageFilters;

    protected static ?string $heading = 'Evolución de ventas';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '220px';
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public function getDescription(): ?string
    {
        return Periodo::desdeFiltros($this->filters)->etiqueta . ' · ventas no anuladas';
    }

    protected function getData(): array
    {
        $p    = Periodo::desdeFiltros($this->filters);
        $dias = $p->dias();

        // Formato de agrupación según la duración del periodo
        [$sqlGrupo, $tramos, $etiqueta] = match (true) {
            $dias <= 1 => [
                "DATE_FORMAT(fecha_venta, '%H')",
                collect(range(8, 21))->map(fn ($h) => str_pad($h, 2, '0', STR_PAD_LEFT)),
                fn ($k) => "{$k}:00",
            ],
            $dias <= 62 => [
                "DATE_FORMAT(fecha_venta, '%Y-%m-%d')",
                collect(range(0, $dias - 1))->map(fn ($i) => $p->desde->copy()->addDays($i)->format('Y-m-d')),
                fn ($k) => Carbon::parse($k)->format('d/m'),
            ],
            default => [
                "DATE_FORMAT(fecha_venta, '%Y-%m')",
                collect(range(0, $p->desde->copy()->startOfMonth()->diffInMonths($p->hasta->copy()->startOfMonth())))
                    ->map(fn ($i) => $p->desde->copy()->startOfMonth()->addMonthsNoOverflow($i)->format('Y-m')),
                fn ($k) => ucfirst(Carbon::parse($k . '-01')->locale('es')->translatedFormat('M Y')),
            ],
        };

        $filas = Venta::validas()
            ->whereBetween('fecha_venta', $p->rango())
            ->selectRaw("{$sqlGrupo} as grupo, SUM(total) as ingresos, COUNT(*) as cantidad")
            ->groupBy('grupo')
            ->get()
            ->keyBy('grupo');

        return [
            'datasets' => [
                [
                    'type'            => 'line',
                    'label'           => 'Ingresos (S/)',
                    'data'            => $tramos->map(fn ($k) => round((float) ($filas[$k]->ingresos ?? 0), 2))->all(),
                    'borderColor'     => '#9f1d3a',
                    'backgroundColor' => 'rgba(159, 29, 58, .12)',
                    'pointBackgroundColor' => '#d4a017',
                    'pointRadius'     => $tramos->count() > 31 ? 0 : 4,
                    'borderWidth'     => 3,
                    'tension'         => 0.35,
                    'fill'            => true,
                    'yAxisID'         => 'y',
                ],
                [
                    'type'            => 'bar',
                    'label'           => 'N° de ventas',
                    'data'            => $tramos->map(fn ($k) => (int) ($filas[$k]->cantidad ?? 0))->all(),
                    'backgroundColor' => 'rgba(212, 160, 23, .45)',
                    'borderRadius'    => 6,
                    'yAxisID'         => 'y1',
                ],
            ],
            'labels' => $tramos->map($etiqueta)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales'  => [
                'y'  => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
