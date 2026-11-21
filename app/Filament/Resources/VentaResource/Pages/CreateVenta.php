<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected static ?string $title = 'Nueva venta';

    protected static bool $canCreateAnother = false;

    /**
     * Escáner: llega el código leído (pistola lectora, cámara o tecleado).
     * Busca por código interno (UTI-0001) o ISBN y suma una unidad al detalle.
     */
    public function escanear(string $codigo): void
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return;
        }

        $soloDigitos = preg_replace('/[^0-9X]/', '', $codigo);

        $producto = Producto::activos()
            ->where(fn ($q) => $q->where('codigo', $codigo)
                ->orWhere('isbn', $codigo)
                ->when(strlen($soloDigitos) >= 10, fn ($q) => $q->orWhere('isbn', $soloDigitos)))
            ->first();

        if (! $producto) {
            Notification::make()->danger()->title('Código no encontrado')->body("No hay un producto con el código {$codigo}.")->send();
            $this->dispatch('escaner-resultado', ok: false);

            return;
        }

        // Quitar filas vacías del formulario
        $detalles = array_filter($this->data['detalles'] ?? [], fn ($i) => filled($i['producto_id'] ?? null));
        $clave    = collect($detalles)->search(fn ($i) => (int) $i['producto_id'] === $producto->id);
        $actual   = $clave !== false ? (int) $detalles[$clave]['cantidad'] : 0;

        if ($actual + 1 > $producto->stock) {
            Notification::make()->warning()->title('Sin stock suficiente')
                ->body("\"{$producto->nombre}\" solo tiene {$producto->stock} unidad(es).")->send();
            $this->dispatch('escaner-resultado', ok: false);

            return;
        }

        $precio = $producto->precioPara($actual + 1);
        $linea  = [
            'producto_id'     => $producto->id,
            'cantidad'        => $actual + 1,
            'precio_unitario' => number_format($precio, 2, '.', ''),
            'subtotal'        => number_format($precio * ($actual + 1), 2, '.', ''),
        ];

        if ($clave !== false) {
            $detalles[$clave] = $linea;
        } else {
            $detalles[(string) \Illuminate\Support\Str::uuid()] = $linea;
        }

        $this->data['detalles'] = $detalles;

        // Totales
        $subtotal  = collect($detalles)->sum(fn ($i) => (int) $i['cantidad'] * (float) $i['precio_unitario']);
        $descuento = min((float) ($this->data['descuento'] ?? 0), $subtotal);
        $this->data['subtotal'] = number_format($subtotal, 2, '.', '');
        $this->data['total']    = number_format($subtotal - $descuento, 2, '.', '');

        Notification::make()->success()->title('+1 ' . $producto->nombre)
            ->body('Cantidad en la venta: ' . ($actual + 1))->duration(1800)->send();
        $this->dispatch('escaner-resultado', ok: true);
    }

    /** Ítems del repeater tal como están en el formulario */
    protected function items(): array
    {
        return array_values($this->data['detalles'] ?? []);
    }

    /**
     * 1) Antes de guardar: ¿hay stock suficiente de cada producto?
     */
    protected function beforeCreate(): void
    {
        foreach ($this->items() as $item) {
            $producto = Producto::find($item['producto_id'] ?? null);
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if (! $producto || $producto->stock < $cantidad) {
                Notification::make()
                    ->danger()
                    ->title('Stock insuficiente')
                    ->body($producto
                        ? "\"{$producto->nombre}\" solo tiene {$producto->stock} unidades."
                        : 'Revisa los productos de la venta.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
    }

    /**
     * 2) Montos calculados en el servidor con los precios de la BD
     *    (así se cumple el CHECK total = subtotal - descuento).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $items   = collect($this->items());
        $productos = Producto::whereIn('id', $items->pluck('producto_id'))->get()->keyBy('id');

        // Precio con oferta y descuento por cantidad (igual que cada línea del detalle)
        $subtotal  = round($items->sum(fn ($i) => round((int) $i['cantidad'] * ($productos[$i['producto_id']]?->precioPara((int) $i['cantidad']) ?? 0), 2)), 2);
        $descuento = round(min(max((float) ($data['descuento'] ?? 0), 0), $subtotal), 2);

        $data['subtotal']           = $subtotal;
        $data['descuento']          = $descuento;
        $data['total']              = round($subtotal - $descuento, 2);
        $data['estado']             = 'pendiente';
        $data['numero_comprobante'] = Venta::siguienteNumero($data['tipo_comprobante']); // evita duplicados

                // Si no es administrador y está vinculado a un empleado, él es el vendedor
        /** @var \App\Models\User $usuario */
        $usuario = \Illuminate\Support\Facades\Auth::user();
        if (! $usuario->esAdmin() && $usuario->empleado_id) {
            $data['empleado_id'] = $usuario->empleado_id;
        }

        return $data;
    }

    /**
     * 3) Después de guardar la venta y su detalle: descontar stock y registrar el pago.
     */
    protected function afterCreate(): void
    {
        /** @var Venta $venta */
        $venta = $this->record;

        if ($sinStock = $venta->descontarStock()) {
            Notification::make()
                ->danger()
                ->title('Stock insuficiente')
                ->body("Otro usuario vendió \"{$sinStock}\" mientras registrabas. Intenta de nuevo.")
                ->send();

            // Deshace toda la venta (requiere ->databaseTransactions() en el panel)
            throw (new Halt())->rollBackDatabaseTransaction();
        }

        if ($this->data['registrar_pago'] ?? false) {
            $metodo = $this->data['pago_metodo'] ?? 'efectivo';

            Pago::create([
                'venta_id'         => $venta->id,
                'metodo_pago'      => $metodo,
                'monto'            => $venta->total,
                'numero_operacion' => $metodo === 'efectivo' ? null : ($this->data['pago_operacion'] ?? null),
                'fecha_pago'       => now(),
            ]);
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        $venta = $this->record->refresh();

        return Notification::make()
            ->success()
            ->title("Venta {$venta->numero_comprobante} registrada")
            ->body('Total: S/ ' . number_format((float) $venta->total, 2) . ' · Estado: ' . Venta::ESTADOS[$venta->estado]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
