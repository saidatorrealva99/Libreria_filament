<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Venta;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva venta')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getSubheading(): ?string
    {
        return \App\Support\Permisos::puede('historial') ? null : 'Ventas de hoy, ' . now()->translatedFormat('l d \\d\\e F') . ' (y pedidos web pendientes).';
    }

    // Pestañas sobre la tabla: Todas / Pendientes / Pagadas / Anuladas
    public function getTabs(): array
    {
        return [
            'todas' => Tab::make(\App\Support\Permisos::puede('historial') ? 'Todas' : 'Hoy')
                ->icon('heroicon-m-queue-list'),
            'pendientes' => Tab::make('Pendientes')
                ->icon('heroicon-m-clock')
                ->badge(Venta::visibles()->where('estado', 'pendiente')->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'pendiente')),
            'web' => Tab::make('Pedidos web')
                ->icon('heroicon-m-globe-alt')
                ->badge(Venta::visibles()->where('canal', 'web')->where('estado', 'pendiente')->count() ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('canal', 'web')),
            'pagadas' => Tab::make('Pagadas')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'pagada')),
            'anuladas' => Tab::make('Anuladas')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('estado', 'anulada')),
        ];
    }
}
