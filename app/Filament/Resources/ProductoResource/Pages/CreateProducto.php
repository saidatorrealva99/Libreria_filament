<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProducto extends CreateRecord
{
    protected static string $resource = ProductoResource::class;

    // Al guardar, volver al listado
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
