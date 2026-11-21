<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteInventario;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

/**
 * Los 10 productos más cerca de agotarse: stock actual vs. stock mínimo.
 */
class StockCriticoChart extends ChartWidget
{
    public array $filtros = [];

    protected static ?string $heading = 'Stock actual vs. mínimo (10 más críticos)';
    protected static ?string $maxHeight = '280px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $productos = ReporteInventario::consulta($this->filtros)->get()
            ->sortBy(fn ($p) => $p->stock_minimo > 0 ? $p->stock / $p->stock_minimo : $p->stock)
            ->take(10)
            ->values();

        return [
            'datasets' => [
                [
                    'label'           => 'Stock actual',
                    'data'            => $productos->pluck('stock')->all(),
                    // rojo si está en mínimo o menos, verde si está bien
                    'backgroundColor' => $productos->map(fn ($p) => $p->tieneStockBajo() ? '#dc2626' : '#0f766e')->all(),
                    'borderRadius'    => 6,
                ],
                [
                    'label'           => 'Stock mínimo',
                    'data'            => $productos->pluck('stock_minimo')->all(),
                    'backgroundColor' => 'rgba(212, 160, 23, .55)',
                    'borderRadius'    => 6,
                ],
            ],
            'labels' => $productos->map(fn ($p) => Str::limit($p->nombre, 18))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
