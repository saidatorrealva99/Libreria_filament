<?php

namespace App\Filament\Resources\CierreCajaResource\Pages;

use App\Filament\Resources\CierreCajaResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCierreCaja extends ViewRecord
{
    protected static string $resource = CierreCajaResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Caja de ' . ($this->record->abrio?->name ?? '—') . ' · ' . $this->record->fecha->translatedFormat('l d \d\e F');
    }

    protected function getHeaderActions(): array
    {
        return [
            CierreCajaResource::accionCerrar(\Filament\Actions\Action::make('cerrar'), $this->record),
        ];
    }
}
