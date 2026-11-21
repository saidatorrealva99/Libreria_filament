<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteVentas;
use App\Models\Empleado;
use Filament\Widgets\ChartWidget;

class VentasPorVendedorChart extends ChartWidget
{
    // Filtros que le pasa la página del reporte
    public array $filtros = [];

    protected static ?string $heading = 'Ventas por vendedor';
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $filas = ReporteVentas::consulta($this->filtros)->toBase()
            ->selectRaw('empleado_id, SUM(total) as total')
            ->groupBy('empleado_id')
            ->orderByDesc('total')
            ->get();

        $nombres = Empleado::whereIn('id', $filas->pluck('empleado_id'))->get()->keyBy('id');

        return [
            'datasets' => [[
                'label'           => 'Total (S/)',
                'data'            => $filas->map(fn ($f) => round((float) $f->total, 2))->all(),
                'backgroundColor' => ['#9f1d3a', '#b8274a', '#c94a68', '#d4a017', '#e0b84a', '#ebcf86', '#0f766e', '#475569'],
                'borderRadius'    => 8,
            ]],
            'labels' => $filas->map(fn ($f) => $nombres->get($f->empleado_id)?->nombres ?? '—')->all(),
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
