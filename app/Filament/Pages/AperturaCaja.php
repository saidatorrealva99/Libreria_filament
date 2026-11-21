<?php

namespace App\Filament\Pages;

use App\Models\CierreCaja;
use App\Support\Formato;
use App\Support\Permisos;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Pantalla que ve la vendedora / cajera al entrar: primero abre la caja.
 */
class AperturaCaja extends Page
{
    protected static string $view = 'filament.pages.apertura-caja';
    protected static ?string $slug = 'abrir-caja';
    protected static bool $shouldRegisterNavigation = false;

    public string $fondo = '100.00';

    public static function canAccess(): bool
    {
        return Permisos::puede('cierres', 'crear');
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function mount(): void
    {
        // Si ya está abierta, al trabajo
        if (CierreCaja::abiertaHoy()) {
            $this->redirect(Filament::getUrl());
        }
    }

    public function abrir(): void
    {
        $this->validate(
            ['fondo' => ['required', 'numeric', 'min:0', 'max:5000']],
            ['fondo.required' => 'Escribe con cuánto sencillo empiezas.', 'fondo.numeric' => 'Escribe solo números, por ejemplo 100.', 'fondo.max' => 'El fondo no puede pasar de S/ 5,000.']
        );

        if (CierreCaja::deHoy()) {
            Notification::make()->warning()->title('La caja de hoy ya fue abierta o cerrada.')->send();

            return;
        }

        CierreCaja::abrir((float) $this->fondo);

        Notification::make()->success()->title('¡Caja abierta!')
            ->body('Fondo inicial: ' . Formato::soles($this->fondo) . '. ¡Buenas ventas!')->send();

        $this->redirect(Filament::getUrl());
    }

    protected function getViewData(): array
    {
        $ultimo = CierreCaja::where('estado', 'cerrada')->whereDate('fecha', '<', today())->latest('fecha')->first();

        return [
            'cajaHoy' => CierreCaja::deHoy(),
            'nombre'  => explode(' ', (string) \Illuminate\Support\Facades\Auth::user()->name)[0],
            'ultimo'  => $ultimo,
        ];
    }
}
