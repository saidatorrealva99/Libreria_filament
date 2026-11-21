<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteVentas;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class VentasPorDiaChart extends ChartWidget
{
    // Filtros que le pasa la página del reporte
    public array $filtros = [];

    protected static ?string $heading = 'Ventas por día';
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $filas = ReporteVentas::consulta($this->filtros)->toBase()
            ->selectRaw('DATE(fecha_venta) as dia, SUM(total) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        return [
            'datasets' => [[
                'label'                => 'Total (S/)',
                'data'                 => $filas->map(fn ($f) => round((float) $f->total, 2))->all(),
                'borderColor'          => '#9f1d3a',
                'backgroundColor'      => 'rgba(159, 29, 58, .15)',
                'pointBackgroundColor' => '#d4a017',
                'pointRadius'          => 5,
                'borderWidth'          => 3,
                'tension'              => 0.35,
                'fill'                 => true,
            ]],
            'labels' => $filas->map(fn ($f) => Carbon::parse($f->dia)->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => ['y' => ['beginAtZero' => true]],
        ];
    }
}
