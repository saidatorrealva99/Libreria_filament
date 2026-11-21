<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CierreCajaResource;
use App\Filament\Resources\VentaResource;
use App\Models\CierreCaja;
use App\Models\Pago;
use App\Models\Venta;
use App\Support\Permisos;
use Filament\Widgets\Widget;

/**
 * Inicio de la vendedora / cajera: solo SU turno de hoy (no ve los números del negocio).
 */
class MiTurnoWidget extends Widget
{
    protected static string $view = 'filament.widgets.mi-turno';
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Permisos::atiendeCaja();
    }

    protected function getViewData(): array
    {
        $caja    = CierreCaja::abiertaHoy() ?? CierreCaja::deHoy();   // MI caja
        $cobrado = $caja?->resumen() ?? ['efectivo' => 0, 'tarjeta' => 0, 'yape' => 0, 'plin' => 0, 'transferencia' => 0, 'total' => 0, 'pagos' => 0];
        $misVentasQ = $caja ? $caja->ventas()->where('estado', '!=', 'anulada') : null;
        $hoy     = Venta::whereDate('fecha_venta', today())->where('estado', '!=', 'anulada');

        $pendientes = (clone $hoy)->where('estado', 'pendiente')->get();
        $saldo      = $pendientes->sum(fn (Venta $v) => $v->saldo());

        return [
            'caja'          => $caja,
            'misVentas'     => $misVentasQ ? (clone $misVentasQ)->count() : 0,
            'misMonto'      => $misVentasQ ? (float) (clone $misVentasQ)->sum('total') : 0,
            'ventasHoy'     => (clone $hoy)->count(),
            'cobrado'       => $cobrado,
            'enCaja'        => (float) ($caja?->fondo_inicial ?? 0) + $cobrado['efectivo'],
            'pendientes'    => $pendientes->count(),
            'saldo'         => $saldo,
            'porVerificar'  => Venta::where('canal', 'web')->where('estado', 'pendiente')->whereNotNull('operacion_declarada')->count(),
            'urlNueva'      => VentaResource::getUrl('create'),
            'urlVentas'     => VentaResource::getUrl('index'),
            'urlCaja'       => $caja ? CierreCajaResource::getUrl('view', ['record' => $caja]) : CierreCajaResource::getUrl('index'),
            'metodos'       => Pago::METODOS,
        ];
    }
}
