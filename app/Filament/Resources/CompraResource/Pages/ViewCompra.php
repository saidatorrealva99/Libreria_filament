<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCompra extends ViewRecord
{
    protected static string $resource = CompraResource::class;

    public function getTitle(): string|Htmlable
    {
        return "Compra {$this->record->numero_documento}";
    }

    protected function getHeaderActions(): array
    {
        return [
            CompraResource::accionAnular(Actions\Action::make('anular')),
        ];
    }
}
