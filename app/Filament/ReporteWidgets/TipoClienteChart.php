<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteClientes;
use Filament\Widgets\ChartWidget;

class TipoClienteChart extends ChartWidget
{
    public array $filtros = [];

    protected static ?string $heading = 'Personas vs. empresas';
    protected static ?string $maxHeight = '260px';
    protected static ?string $pollingInterval = null;

    public const NOMBRES = ['DNI' => 'Personas (DNI)', 'RUC' => 'Empresas (RUC)', 'CE' => 'Extranjeros (CE)'];
    public const COLORES = ['DNI' => '#9f1d3a', 'RUC' => '#d4a017', 'CE' => '#0f766e'];

    protected function getData(): array
    {
        $porTipo = ReporteClientes::consulta($this->filtros)->get()
            ->groupBy('tipo_documento')
            ->map(fn ($grupo) => round((float) $grupo->sum('ventas_sum_total'), 2));

        return [
            'datasets' => [[
                'label'           => 'Total (S/)',
                'data'            => $porTipo->values()->all(),
                'backgroundColor' => $porTipo->keys()->map(fn ($t) => self::COLORES[$t] ?? '#94a3b8')->all(),
                'borderWidth'     => 2,
            ]],
            'labels' => $porTipo->keys()->map(fn ($t) => self::NOMBRES[$t] ?? $t)->all(),
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
