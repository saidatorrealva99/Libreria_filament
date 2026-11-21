<?php

namespace App\Filament\ReporteWidgets;

use App\Filament\Pages\ReporteInventario;
use App\Support\Formato;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventarioResumen extends StatsOverviewWidget
{
    public array $filtros = [];

    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $productos = ReporteInventario::consulta($this->filtros)->get();

        $valor     = $productos->sum(fn ($p) => $p->stock * (float) $p->precio_venta);
        $unidades  = (int) $productos->sum('stock');
        $bajos     = $productos->filter(fn ($p) => $p->tieneStockBajo())->count();
        $masVendido = $productos->sortByDesc('unidades_vendidas')->first();

        return [
            Stat::make('Valor del inventario', Formato::soles($valor))
                ->description('Stock × precio de venta')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('primary'),

            Stat::make('Unidades en stock', number_format($unidades))
                ->description($productos->count() . ' productos')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Por reponer', (string) $bajos)
                ->description($bajos ? 'En stock mínimo o menos' : 'Inventario en orden')
                ->descriptionIcon($bajos ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($bajos ? 'danger' : 'success'),

            Stat::make('Más vendido', $masVendido ? ((int) $masVendido->unidades_vendidas) . ' und.' : '—')
                ->description($masVendido?->nombre ?? 'Sin ventas')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),
        ];
    }
}
