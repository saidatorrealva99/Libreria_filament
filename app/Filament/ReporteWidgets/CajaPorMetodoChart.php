<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteCaja;
use App\Models\Pago;
use Filament\Widgets\ChartWidget;

class CajaPorMetodoChart extends ChartWidget
{
    public array $filtros = [];

    protected static ?string $heading = 'Por método de pago';
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;

    public const COLORES = [
        'efectivo'      => '#0f766e',
        'tarjeta'       => '#0369a1',
        'yape'          => '#7c3aed',
        'plin'          => '#06b6d4',
        'transferencia' => '#d4a017',
    ];

    protected function getData(): array
    {
        $filas = ReporteCaja::consulta($this->filtros)->toBase()
            ->selectRaw('metodo_pago, SUM(monto) as total')
            ->groupBy('metodo_pago')
            ->orderByDesc('total')
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Monto (S/)',
                'data'            => $filas->map(fn ($f) => round((float) $f->total, 2))->all(),
                'backgroundColor' => $filas->map(fn ($f) => self::COLORES[$f->metodo_pago] ?? '#94a3b8')->all(),
                'borderWidth'     => 2,
            ]],
            'labels' => $filas->map(fn ($f) => Pago::METODOS[$f->metodo_pago] ?? $f->metodo_pago)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales'  => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
