<?php

namespace App\Filament\Resources\PagoResource\Pages;

use App\Filament\Resources\PagoResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePago extends CreateRecord
{
    protected static string $resource = PagoResource::class;

    // Al guardar, volver al listado
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
