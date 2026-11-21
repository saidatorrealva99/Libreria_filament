<?php

namespace App\Filament\Resources\ReclamacionResource\Pages;

use App\Filament\Resources\ReclamacionResource;
use App\Models\Reclamacion;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewReclamacion extends ViewRecord
{
    protected static string $resource = ReclamacionResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Hoja N° ' . $this->record->codigo;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('en_proceso')
                ->label('Marcar en proceso')
                ->icon('heroicon-m-arrow-path')
                ->color('info')
                ->visible(fn (Reclamacion $record) => $record->estado === 'pendiente')
                ->action(fn (Reclamacion $record) => $record->update(['estado' => 'en_proceso'])),
            ReclamacionResource::accionResponder(Actions\Action::make('responder')),
            Actions\Action::make('imprimir')
                ->label('Imprimir hoja')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->url(fn (Reclamacion $record) => route('reclamaciones.hoja', $record->codigo), shouldOpenInNewTab: true),
        ];
    }
}
