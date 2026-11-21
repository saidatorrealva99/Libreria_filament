<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteClientes;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class TopClientesChart extends ChartWidget
{
    public array $filtros = [];

    protected static ?string $heading = 'Top 5 clientes';
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $top = ReporteClientes::consulta($this->filtros)->get()
            ->sortByDesc('ventas_sum_total')
            ->take(5)
            ->values();

        return [
            'datasets' => [[
                'label'           => 'Total comprado (S/)',
                'data'            => $top->map(fn ($c) => round((float) $c->ventas_sum_total, 2))->all(),
                'backgroundColor' => ['#d4a017', '#9f1d3a', '#b8274a', '#c94a68', '#e0b84a'],
                'borderRadius'    => 8,
                'barThickness'    => 26,
            ]],
            'labels' => $top->map(fn ($c) => Str::limit($c->nombre_completo, 24))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins'   => ['legend' => ['display' => false]],
            'scales'    => ['x' => ['beginAtZero' => true]],
        ];
    }
}
