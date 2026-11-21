<?php

namespace App\Http\Middleware;

use App\Filament\Pages\AperturaCaja;
use App\Models\CierreCaja;
use App\Support\Permisos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quien atiende la caja (vendedora / cajera) no puede usar el panel
 * hasta abrir la caja del día. Si la caja de hoy ya se cerró, su turno terminó.
 */
class ExigirCajaAbierta
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Permisos::atiendeCaja() || CierreCaja::abiertaHoy()) {
            return $next($request);
        }

        // Páginas permitidas sin caja abierta: la de apertura, Mi cuenta y salir
        if ($request->routeIs('filament.admin.pages.abrir-caja', 'filament.admin.auth.*')) {
            return $next($request);
        }

        return redirect(AperturaCaja::getUrl());
    }
}
