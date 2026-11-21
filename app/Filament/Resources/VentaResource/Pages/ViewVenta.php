<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewVenta extends ViewRecord
{
    protected static string $resource = VentaResource::class;

    public function getTitle(): string|Htmlable
    {
        return "Venta {$this->record->numero_comprobante}";
    }

    protected function getHeaderActions(): array
    {
        return [
            VentaResource::accionWhatsapp(Actions\Action::make('whatsapp')),
            VentaResource::accionImprimir(Actions\Action::make('imprimir')),
            VentaResource::accionVerificar(Actions\Action::make('verificar')),
            VentaResource::accionEntregar(Actions\Action::make('entregar')),
            VentaResource::accionRechazar(Actions\Action::make('rechazar')),
            VentaResource::accionPagar(Actions\Action::make('pagar')),
            VentaResource::accionAnular(Actions\Action::make('anular')),
        ];
    }
}
