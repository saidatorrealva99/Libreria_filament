<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use App\Models\Compra;
use App\Support\Formato;
use App\Support\Inventario;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCompra extends CreateRecord
{
    protected static string $resource = CompraResource::class;

    protected static ?string $title = 'Registrar compra';

    protected static bool $canCreateAnother = false;

    // Desde "Reposición inteligente": llega con ?sugerido=1 y se llena el detalle solo
    public function mount(): void
    {
        parent::mount();

        if (! request()->boolean('sugerido')) {
            return;
        }

        $items = Inventario::reponer()->take(20)->mapWithKeys(fn (array $f) => [
            (string) Str::uuid() => [
                'producto_id'    => $f['producto']->id,
                'cantidad'       => $f['sugerido'],
                'costo_unitario' => number_format($f['costo'], 2, '.', ''),
                'subtotal'       => number_format($f['sugerido'] * $f['costo'], 2, '.', ''),
            ],
        ])->all();

        if ($items) {
            $this->form->fill([
                'fecha_compra' => now()->toDateString(),
                'observacion'  => 'Generada desde Reposición inteligente',
                'detalles'     => $items,
                'total'        => number_format(collect($items)->sum(fn ($i) => (float) $i['subtotal']), 2, '.', ''),
            ]);

            \Filament\Notifications\Notification::make()->info()
                ->title('Compra sugerida cargada')
                ->body('Elige el proveedor y quita los productos que no le compras a él.')
                ->send();
        }
    }

    // Total calculado en el servidor y usuario que registra
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = round(collect($this->data['detalles'] ?? [])
            ->sum(fn ($i) => (int) ($i['cantidad'] ?? 0) * (float) ($i['costo_unitario'] ?? 0)), 2);
        $data['estado']  = 'registrada';
        $data['user_id'] = \Illuminate\Support\Facades\Auth::id();

        return $data;
    }

    // Después de guardar la compra y su detalle: el stock sube (kárdex) y se actualizan los costos
    protected function afterCreate(): void
    {
        /** @var Compra $compra */
        $compra = $this->record->load(['detalles', 'proveedor']);
        $compra->ingresarStock();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Compra registrada')
            ->body('Total ' . Formato::soles($this->record->total) . '. El stock fue actualizado.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
