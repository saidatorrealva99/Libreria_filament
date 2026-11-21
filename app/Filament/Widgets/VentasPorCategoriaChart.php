<?php

namespace App\Filament\Widgets;

use App\Models\DetalleVenta;
use App\Support\Periodo;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class VentasPorCategoriaChart extends ChartWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_negocio');
    }

    use InteractsWithPageFilters;

    protected static ?string $heading = 'Ventas por categoría';
    protected static ?int $sort = 4;
    protected static ?string $pollingInterval = null;
    protected static ?string $maxHeight = '220px';
    protected int|string|array $columnSpan = 1;

    public const PALETA = ['#9f1d3a', '#d4a017', '#0f766e', '#475569', '#c2410c', '#65a30d', '#7c3aed', '#0369a1'];

    public function getDescription(): ?string
    {
        return 'Importe vendido (S/) · ' . Periodo::desdeFiltros($this->filters)->etiqueta;
    }

    protected function getData(): array
    {
        $filas = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('ventas.estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', Periodo::desdeFiltros($this->filters)->rango())
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('importe')
            ->selectRaw('categorias.nombre as categoria, SUM(detalle_ventas.subtotal) as importe')
            ->get();

        return [
            'datasets' => [[
                'label'           => 'Importe',
                'data'            => $filas->map(fn ($f) => round((float) $f->importe, 2))->all(),
                'backgroundColor' => array_slice(self::PALETA, 0, max(1, $filas->count())),
                'borderColor'     => 'rgba(255,255,255,.9)',
                'borderWidth'     => 2,
                'hoverOffset'     => 8,
            ]],
            'labels' => $filas->pluck('categoria')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout'  => '62%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]],
            'scales'  => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
