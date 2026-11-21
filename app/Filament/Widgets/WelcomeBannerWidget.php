<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Asistente;
use App\Filament\Pages\Reposicion;
use App\Filament\Resources\CompraResource;
use App\Filament\Resources\VentaResource;
use Filament\Widgets\Widget;
use App\Support\Permisos;
use Illuminate\Support\Facades\Auth;

class WelcomeBannerWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-banner-widget';
    protected static ?int $sort = 0;
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $hora = (int) now()->format('G');

        return [
            'saludo'        => $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches'),
            'nombre'        => Auth::user()->name ?? 'Administrador',
            'fecha'         => ucfirst(now()->locale('es')->translatedFormat('l, j \\d\\e F \\d\\e Y')),
            'acciones'      => array_values(array_filter([
                // Si la caja de hoy no se abrió, es lo primero que hay que hacer
                Permisos::puede('cierres', 'crear') && ! \App\Models\CierreCaja::deHoy()
                    ? ['Abrir caja', 'heroicon-m-lock-open', \App\Filament\Resources\CierreCajaResource::getUrl('index'), true] : null,
                Permisos::puede('ventas', 'crear')   ? ['Nueva venta', 'heroicon-m-shopping-cart', VentaResource::getUrl('create'), true] : null,
                Permisos::puede('compras', 'crear')  ? ['Registrar compra', 'heroicon-m-inbox-arrow-down', CompraResource::getUrl('create'), false] : null,
                Permisos::puede('reposicion')        ? ['Reposición', 'heroicon-m-light-bulb', Reposicion::getUrl(), false] : null,
                Asistente::canAccess() ? ['Asistente IA', 'heroicon-m-sparkles', Asistente::getUrl(), false] : null,
                ['Ver tienda', 'heroicon-m-globe-alt', url('/'), false],
            ])),
        ];
    }
}
