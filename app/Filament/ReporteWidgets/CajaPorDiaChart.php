<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteCaja;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class CajaPorDiaChart extends ChartWidget
{
    public array $filtros = [];

    protected static ?string $heading = 'Cobros por día';
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $filas = ReporteCaja::consulta($this->filtros)->toBase()
            ->selectRaw('DATE(fecha_pago) as dia, SUM(monto) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Cobrado (S/)',
                'data'            => $filas->map(fn ($f) => round((float) $f->total, 2))->all(),
                'backgroundColor' => 'rgba(15, 118, 110, .55)',
                'borderColor'     => '#0f766e',
                'borderWidth'     => 1,
                'borderRadius'    => 6,
            ]],
            'labels' => $filas->map(fn ($f) => Carbon::parse($f->dia)->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => ['y' => ['beginAtZero' => true]],
        ];
    }
}
