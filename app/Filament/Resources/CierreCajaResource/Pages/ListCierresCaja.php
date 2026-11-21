<?php

namespace App\Filament\Resources\CierreCajaResource\Pages;

use App\Filament\Resources\CierreCajaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCierresCaja extends ListRecords
{
    protected static string $resource = CierreCajaResource::class;

    public function getSubheading(): ?string
    {
        $caja = \App\Models\CierreCaja::deHoy();

        return match (true) {
            ! $caja                       => '🔴 La caja de hoy está cerrada. Ábrela para poder registrar cobros.',
            $caja->estado === 'abierta'   => '🟢 Caja abierta desde las ' . $caja->abierta_at?->format('H:i') . ' con un fondo de S/ ' . number_format((float) $caja->fondo_inicial, 2) . '.',
            default                       => '✔ La caja de hoy ya fue cerrada a las ' . $caja->cerrada_at?->format('H:i') . '.',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            CierreCajaResource::accionAbrir(Actions\Action::make('abrir')),
            CierreCajaResource::accionCerrar(Actions\Action::make('cerrar')),
        ];
    }
}
