<?php

namespace App\Filament\Widgets;

use App\Models\DetalleVenta;
use App\Support\Periodo;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Str;

class TopProductosChart extends ChartWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_negocio');
    }

    use InteractsWithPageFilters;

    protected static ?string $heading = 'Productos más vendidos';
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '220px';
    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public function getDescription(): ?string
    {
        return 'Top 6 por unidades · ' . Periodo::desdeFiltros($this->filters)->etiqueta;
    }

    protected function getData(): array
    {
        $filas = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->where('ventas.estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', Periodo::desdeFiltros($this->filters)->rango())
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('unidades')
            ->limit(6)
            ->selectRaw('productos.nombre as producto, SUM(detalle_ventas.cantidad) as unidades')
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Unidades',
                'data'            => $filas->map(fn ($f) => (int) $f->unidades)->all(),
                'backgroundColor' => ['#9f1d3a', '#b8274a', '#c94a68', '#d4a017', '#e0b84a', '#ebcf86'],
                'borderRadius'    => 8,
                'barThickness'    => 22,
            ]],
            'labels' => $filas->map(fn ($f) => Str::limit($f->producto, 28))->all(),
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
            'scales'    => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
