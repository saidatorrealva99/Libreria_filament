<?php

namespace App\Filament\Resources\EmpleadoResource\Pages;

use App\Filament\Resources\EmpleadoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    // Al guardar, volver al listado
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
