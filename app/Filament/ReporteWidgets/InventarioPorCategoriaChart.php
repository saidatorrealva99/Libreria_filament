<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteInventario;
use Filament\Widgets\ChartWidget;

class InventarioPorCategoriaChart extends ChartWidget
{
    public array $filtros = [];

    protected static ?string $heading = 'Valor en stock por categoría';
    protected static ?string $maxHeight = '280px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $porCategoria = ReporteInventario::consulta($this->filtros)->get()
            ->groupBy(fn ($p) => $p->categoria?->nombre ?? 'Sin categoría')
            ->map(fn ($grupo) => round($grupo->sum(fn ($p) => $p->stock * (float) $p->precio_venta), 2))
            ->sortDesc();

        return [
            'datasets' => [[
                'label'           => 'Valor (S/)',
                'data'            => $porCategoria->values()->all(),
                'backgroundColor' => ['#9f1d3a', '#d4a017', '#0f766e', '#475569', '#c2410c', '#65a30d', '#7c3aed', '#0369a1', '#be185d', '#a16207'],
                'borderWidth'     => 2,
            ]],
            'labels' => $porCategoria->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]],
            'scales'  => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
