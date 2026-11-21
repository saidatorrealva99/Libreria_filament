<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CompraResource;
use App\Filament\Resources\VentaResource;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\DetalleVenta;
use App\Models\Pago;
use App\Models\Venta;
use App\Support\Formato;
use App\Support\Periodo;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * 8 indicadores del periodo elegido, cada uno comparado con el periodo anterior.
 */
class StatsOverviewWidget extends BaseWidget
{
    // Solo para quien puede ver estos números (ver app/Support/Permisos.php)
    public static function canView(): bool
    {
        return \App\Support\Permisos::puede('dashboard_negocio');
    }

    use InteractsWithPageFilters;

    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $p   = Periodo::desdeFiltros($this->filters);
        $act = $p->rango();
        $ant = $p->rangoAnterior();

        // 1-3. Ventas
        [$ingresos, $nVentas]       = $this->ventas($act);
        [$ingresosAnt, $nVentasAnt] = $this->ventas($ant);
        $ticket    = $nVentas ? $ingresos / $nVentas : 0;
        $ticketAnt = $nVentasAnt ? $ingresosAnt / $nVentasAnt : 0;

        // 4. Ganancia bruta (precio de venta - costo)
        [$ganancia, $margen] = $this->ganancia($act);
        [$gananciaAnt]       = $this->ganancia($ant);

        // 5. Por cobrar (siempre al día de hoy)
        $pendientes = Venta::where('estado', 'pendiente');
        $porCobrar  = (float) (clone $pendientes)->sum('total')
            - (float) Pago::whereHas('venta', fn ($q) => $q->where('estado', 'pendiente'))->sum('monto');
        $nPendientes = (clone $pendientes)->count();

        // 6. Pedidos web
        $web        = Venta::where('canal', 'web')->whereBetween('fecha_venta', $act)->count();
        $webAnt     = Venta::where('canal', 'web')->whereBetween('fecha_venta', $ant)->count();
        $porVerificar = Venta::where('canal', 'web')->where('estado', 'pendiente')->whereNotNull('operacion_declarada')->count();

        // 7. Compras a proveedores
        $compras    = $this->compras($act);
        $comprasAnt = $this->compras($ant);

        // 8. Clientes nuevos
        $clientes    = Cliente::whereBetween('created_at', $act)->count();
        $clientesAnt = Cliente::whereBetween('created_at', $ant)->count();

        return [
            $this->stat('Ingresos', Formato::soles($ingresos), $ingresos, $ingresosAnt)
                ->chart($this->tendencia($p))
                ->color('primary'),

            $this->stat('Ventas', Formato::entero($nVentas), $nVentas, $nVentasAnt)
                ->url(VentaResource::getUrl('index')),

            $this->stat('Ticket promedio', Formato::soles($ticket), $ticket, $ticketAnt),

            $this->stat('Ganancia bruta', Formato::soles($ganancia), $ganancia, $gananciaAnt)
                ->extraAttributes(['title' => "Margen {$margen}% · precio de venta menos costo de compra"]),

            Stat::make('Por cobrar', Formato::soles(max(0, $porCobrar)))
                ->description($nPendientes ? "{$nPendientes} venta(s) pendiente(s) de pago" : 'Todo cobrado')
                ->descriptionIcon($nPendientes ? 'heroicon-m-clock' : 'heroicon-m-check-badge')
                ->color($nPendientes ? 'warning' : 'success')
                ->url(VentaResource::getUrl('index', ['activeTab' => 'pendientes'])),

            $this->stat('Pedidos web', Formato::entero($web), $web, $webAnt)
                ->description($porVerificar ? "{$porVerificar} pago(s) por verificar" : $this->textoVariacion($web, $webAnt))
                ->descriptionIcon($porVerificar ? 'heroicon-m-bell-alert' : 'heroicon-m-globe-alt')
                ->color($porVerificar ? 'warning' : 'info')
                ->url(VentaResource::getUrl('index', ['activeTab' => 'web'])),

            $this->stat('Compras', Formato::soles($compras), $compras, $comprasAnt, neutral: true)
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->url(CompraResource::canViewAny() ? CompraResource::getUrl('index') : null),

            $this->stat('Clientes nuevos', Formato::entero($clientes), $clientes, $clientesAnt),
        ];
    }

    /* ---------------- consultas ---------------- */

    private function ventas(array $rango): array
    {
        $q = Venta::validas()->whereBetween('fecha_venta', $rango);

        return [(float) (clone $q)->sum('total'), (clone $q)->count()];
    }

    private function ganancia(array $rango): array
    {
        $fila = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->where('ventas.estado', '!=', 'anulada')
            ->whereBetween('ventas.fecha_venta', $rango)
            ->selectRaw('COALESCE(SUM(detalle_ventas.subtotal), 0) as venta,
                         COALESCE(SUM(detalle_ventas.cantidad * COALESCE(productos.costo, detalle_ventas.precio_unitario * 0.65)), 0) as costo')
            ->first();

        $venta    = (float) $fila->venta;
        $ganancia = $venta - (float) $fila->costo;

        return [$ganancia, $venta > 0 ? round($ganancia / $venta * 100, 1) : 0];
    }

    private function compras(array $rango): float
    {
        return (float) Compra::registradas()
            ->whereBetween('fecha_compra', [$rango[0]->toDateString(), $rango[1]->toDateString()])
            ->sum('total');
    }

    /** Ingresos en 7 tramos del periodo (minigráfico) */
    private function tendencia(Periodo $p): array
    {
        $hasta = $p->hasta->copy();
        $desde = $p->dias() < 7 ? $hasta->copy()->subDays(6)->startOfDay() : $p->desde->copy();
        $paso  = ($hasta->getTimestamp() - $desde->getTimestamp()) / 7;

        return collect(range(0, 6))->map(function (int $i) use ($desde, $paso) {
            $ini = Carbon::createFromTimestamp($desde->getTimestamp() + (int) ($paso * $i));
            $fin = Carbon::createFromTimestamp($desde->getTimestamp() + (int) ($paso * ($i + 1)));

            return round((float) Venta::validas()->where('fecha_venta', '>=', $ini)->where('fecha_venta', '<', $fin)->sum('total'), 2);
        })->all();
    }

    /* ---------------- presentación ---------------- */

    private function stat(string $titulo, string $valor, float $actual, float $anterior, bool $neutral = false): Stat
    {
        $var = Periodo::variacion($actual, $anterior);

        return Stat::make($titulo, $valor)
            ->description($this->textoVariacion($actual, $anterior))
            ->descriptionIcon($var === null ? 'heroicon-m-minus' : ($var >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'))
            ->color($neutral || $var === null ? 'gray' : ($var >= 0 ? 'success' : 'danger'));
    }

    private function textoVariacion(float $actual, float $anterior): string
    {
        $var = Periodo::variacion($actual, $anterior);

        return match (true) {
            $var === null && $actual > 0 => 'Nuevo vs. periodo anterior',
            $var === null                => 'Sin datos para comparar',
            $var >= 0                    => "+{$var}% vs. periodo anterior",
            default                      => "{$var}% vs. periodo anterior",
        };
    }
}
