<?php

namespace App\Filament\Widgets;

use App\Models\Pago;
use App\Support\Periodo;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MetodosPagoChart extends ChartWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_negocio');
    }

    use InteractsWithPageFilters;

    protected static ?string $heading = 'Métodos de pago';
    protected static ?int $sort = 8;
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '220px';
    protected int|string|array $columnSpan = 1;

    public function getDescription(): ?string
    {
        return 'Monto cobrado (S/) · ' . Periodo::desdeFiltros($this->filters)->etiqueta;
    }

    protected function getData(): array
    {
        $filas = Pago::query()
            ->whereHas('venta', fn ($q) => $q->validas())
            ->whereBetween('fecha_pago', Periodo::desdeFiltros($this->filters)->rango())
            ->groupBy('metodo_pago')
            ->selectRaw('metodo_pago, SUM(monto) as total')
            ->orderByDesc('total')
            ->get();

        $colores = [
            'efectivo'      => '#0f766e',
            'tarjeta'       => '#0369a1',
            'yape'          => '#7c3aed',
            'plin'          => '#06b6d4',
            'transferencia' => '#d4a017',
        ];

        return [
            'datasets' => [[
                'label'           => 'Monto',
                'data'            => $filas->map(fn ($f) => round((float) $f->total, 2))->all(),
                'backgroundColor' => $filas->map(fn ($f) => $colores[$f->metodo_pago] ?? '#94a3b8')->all(),
                'borderColor'     => 'rgba(255,255,255,.9)',
                'borderWidth'     => 2,
            ]],
            'labels' => $filas->map(fn ($f) => Pago::METODOS[$f->metodo_pago] ?? $f->metodo_pago)->all(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]],
            'scales'  => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
