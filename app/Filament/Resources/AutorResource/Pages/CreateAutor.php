<?php

namespace App\Filament\Resources\AutorResource\Pages;

use App\Filament\Resources\AutorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutor extends CreateRecord
{
    protected static string $resource = AutorResource::class;

    // Al guardar, volver al listado
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
