<?php

namespace App\Filament\Resources\ReclamacionResource\Pages;

use App\Filament\Resources\ReclamacionResource;
use App\Models\Reclamacion;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReclamaciones extends ListRecords
{
    protected static string $resource = ReclamacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('publico')
                ->label('Ver formulario público')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('reclamaciones.formulario'), shouldOpenInNewTab: true),
        ];
    }

    public function getTabs(): array
    {
        return [
            'abiertos' => Tab::make('Por responder')
                ->icon('heroicon-m-clock')
                ->badge(Reclamacion::where('estado', '!=', 'atendido')->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('estado', '!=', 'atendido')->orderBy('vence_at')),
            'atendidos' => Tab::make('Atendidos')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('estado', 'atendido')),
            'todos' => Tab::make('Todos'),
        ];
    }
}
