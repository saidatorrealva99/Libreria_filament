<?php

namespace App\Support;

use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Análisis predictivo del inventario (sin IA: cálculo con el ritmo real de ventas).
 *
 *  - Promedio diario  = unidades vendidas en los últimos 30 días / 30
 *  - Días de cobertura = stock actual / promedio diario  ("¿para cuántos días me alcanza?")
 *  - Sugerido         = lo necesario para cubrir 30 días más el stock mínimo
 *  - Clasificación    = estrella (top 20 % en ventas), regular, lento, sin movimiento (60 días sin vender)
 */
class Inventario
{
    public const DIAS_ANALISIS  = 30;
    public const DIAS_OBJETIVO  = 30;   // stock deseado: para 30 días
    public const DIAS_ALERTA    = 15;   // menos de 15 días de cobertura = por agotarse

    public const ESTADOS = [
        'agotado'      => ['Agotado', 'danger'],
        'critico'      => ['Crítico', 'danger'],
        'por_agotarse' => ['Por agotarse', 'warning'],
        'normal'       => ['Normal', 'success'],
    ];

    public const CLASES = [
        'estrella'       => ['Estrella', 'warning', 'heroicon-m-star'],
        'regular'        => ['Regular', 'info', 'heroicon-m-arrow-path'],
        'lento'          => ['Lento', 'gray', 'heroicon-m-arrow-trending-down'],
        'sin_movimiento' => ['Sin movimiento', 'gray', 'heroicon-m-pause-circle'],
    ];

    private static ?Collection $memo = null;

    /** @return Collection<int, array> */
    public static function analisis(): Collection
    {
        // Se calcula una sola vez por petición (varios widgets lo usan)
        return self::$memo ??= self::calcular();
    }

    private static function calcular(): Collection
    {
        $validas = fn (int $dias) => fn ($q) => $q->whereHas('venta', fn ($v) => $v->validas()->where('fecha_venta', '>=', now()->subDays($dias)->startOfDay()));

        $productos = Producto::activos()
            ->with('categoria')
            ->withSum(['detalles as vendidas_30' => $validas(self::DIAS_ANALISIS)], 'cantidad')
            ->withSum(['detalles as vendidas_60' => $validas(60)], 'cantidad')
            ->get();

        // Umbral de "estrella": top 20 % de los productos que sí vendieron
        $conVentas = $productos->filter(fn ($p) => (int) $p->vendidas_30 > 0)->sortByDesc('vendidas_30')->values();
        $umbralEstrella = $conVentas->isEmpty() ? PHP_INT_MAX
            : (int) $conVentas->get(max(0, (int) ceil($conVentas->count() * 0.2) - 1))->vendidas_30;

        return $productos->map(function (Producto $p) use ($umbralEstrella) {
            $vendidas  = (int) $p->vendidas_30;
            $promedio  = $vendidas / self::DIAS_ANALISIS;
            $cobertura = $promedio > 0 ? (int) floor($p->stock / $promedio) : null;   // null = no se está vendiendo

            $estado = match (true) {
                $p->stock <= 0                                    => 'agotado',
                $p->stock <= $p->stock_minimo                     => 'critico',
                $cobertura !== null && $cobertura < self::DIAS_ALERTA => 'por_agotarse',
                default                                           => 'normal',
            };

            $clase = match (true) {
                $vendidas > 0 && $vendidas >= $umbralEstrella => 'estrella',
                $vendidas > 0                                 => 'regular',
                (int) $p->vendidas_60 > 0                     => 'lento',
                default                                       => 'sin_movimiento',
            };

            $objetivo = max((int) ceil($promedio * self::DIAS_OBJETIVO) + $p->stock_minimo, $p->stock_minimo * 2);
            $sugerido = $estado === 'normal' ? 0 : max(0, $objetivo - $p->stock);
            $costo    = (float) ($p->costo ?: round((float) $p->precio_venta * 0.65, 2));

            return [
                'producto'  => $p,
                'vendidas'  => $vendidas,
                'promedio'  => round($promedio, 2),
                'cobertura' => $cobertura,
                'estado'    => $estado,
                'clase'     => $clase,
                'sugerido'  => $sugerido,
                'costo'     => $costo,
                'inversion' => round($sugerido * $costo, 2),
            ];
        })
            // Lo más urgente primero
            ->sortBy(fn ($f) => [array_search($f['estado'], array_keys(self::ESTADOS)), $f['cobertura'] ?? PHP_INT_MAX])
            ->values();
    }

    public static function reponer(): Collection
    {
        return self::analisis()->where('sugerido', '>', 0)->values();
    }
}
