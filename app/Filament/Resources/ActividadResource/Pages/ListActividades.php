<?php

namespace App\Filament\Resources\ActividadResource\Pages;

use App\Filament\Resources\ActividadResource;
use Filament\Resources\Pages\ListRecords;

class ListActividades extends ListRecords
{
    protected static string $resource = ActividadResource::class;

    public function getSubheading(): ?string
    {
        return 'Quién hizo qué y cuándo: ventas, anulaciones, cambios de precio, inicios de sesión… No se puede editar ni borrar.';
    }
}
