<?php

namespace App\Filament\Resources\ZonaEnvioResource\Pages;

use App\Filament\Resources\ZonaEnvioResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageZonasEnvio extends ManageRecords
{
    protected static string $resource = ZonaEnvioResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nueva zona')->icon('heroicon-m-plus')];
    }
}
