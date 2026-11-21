<?php

namespace App\Filament\Resources\AutorResource\Pages;

use App\Filament\Resources\AutorResource;
use Filament\Resources\Pages\EditRecord;

class EditAutor extends EditRecord
{
    protected static string $resource = AutorResource::class;

    // Sin botón "Eliminar" en la edición (se elimina desde el listado)
    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
