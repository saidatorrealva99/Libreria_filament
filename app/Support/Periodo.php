<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Rango de fechas elegido en el filtro del Dashboard y el periodo anterior
 * de la misma duración (para calcular el % de variación).
 */
class Periodo
{
    public const OPCIONES = [
        'hoy'          => 'Hoy',
        '7d'           => 'Últimos 7 días',
        'mes'          => 'Este mes',
        'mes_anterior' => 'Mes anterior',
        '30d'          => 'Últimos 30 días',
        'anio'         => 'Este año',
        'personalizado' => 'Personalizado',
    ];

    public function __construct(
        public Carbon $desde,
        public Carbon $hasta,
        public Carbon $anteriorDesde,
        public Carbon $anteriorHasta,
        public string $etiqueta,
    ) {}

    public static function desdeFiltros(?array $filtros): self
    {
        $tipo = $filtros['periodo'] ?? 'mes';
        $hoy  = now();

        [$desde, $hasta] = match ($tipo) {
            'hoy'          => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
            '7d'           => [$hoy->copy()->subDays(6)->startOfDay(), $hoy->copy()->endOfDay()],
            '30d'          => [$hoy->copy()->subDays(29)->startOfDay(), $hoy->copy()->endOfDay()],
            'mes_anterior' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()],
            'anio'         => [$hoy->copy()->startOfYear(), $hoy->copy()->endOfDay()],
            'personalizado' => [
                Carbon::parse($filtros['desde'] ?? $hoy->copy()->startOfMonth())->startOfDay(),
                Carbon::parse($filtros['hasta'] ?? $hoy)->endOfDay(),
            ],
            default        => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfDay()],
        };

        if ($hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        // Periodo anterior comparable
        [$antDesde, $antHasta] = match ($tipo) {
            'mes'          => [$desde->copy()->subMonthNoOverflow(), $hasta->copy()->subMonthNoOverflow()],
            'mes_anterior' => [$desde->copy()->subMonthNoOverflow()->startOfMonth(), $desde->copy()->subMonthNoOverflow()->endOfMonth()],
            'anio'         => [$desde->copy()->subYear(), $hasta->copy()->subYear()],
            default        => (function () use ($desde, $hasta) {
                $dias = $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1;

                return [$desde->copy()->subDays($dias), $hasta->copy()->subDays($dias)];
            })(),
        };

        $etiqueta = $tipo === 'personalizado'
            ? $desde->format('d/m/Y') . ' – ' . $hasta->format('d/m/Y')
            : self::OPCIONES[$tipo] ?? 'Este mes';

        return new self($desde, $hasta, $antDesde, $antHasta, $etiqueta);
    }

    public function dias(): int
    {
        return (int) $this->desde->copy()->startOfDay()->diffInDays($this->hasta->copy()->startOfDay()) + 1;
    }

    public function rango(): array
    {
        return [$this->desde, $this->hasta];
    }

    public function rangoAnterior(): array
    {
        return [$this->anteriorDesde, $this->anteriorHasta];
    }

    /** % de variación (null si el periodo anterior es 0) */
    public static function variacion(float $actual, float $anterior): ?float
    {
        return $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : null;
    }
}
