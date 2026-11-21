<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Días hábiles en Perú: sin sábados, domingos ni feriados nacionales de fecha fija.
 * (Semana Santa cambia cada año: se puede agregar en EXTRA.)
 */
class DiasHabiles
{
    public const FERIADOS = [
        '01-01', '05-01', '06-07', '06-29', '07-23', '07-28', '07-29',
        '08-06', '08-30', '10-08', '11-01', '12-08', '12-09', '12-25',
    ];

    public static function esHabil(Carbon $dia): bool
    {
        return ! $dia->isWeekend() && ! in_array($dia->format('m-d'), self::FERIADOS, true);
    }

    /** Fecha que resulta de sumar N días hábiles (se cuenta desde el día siguiente) */
    public static function sumar(Carbon $desde, int $dias): Carbon
    {
        $fecha = $desde->copy()->startOfDay();

        while ($dias > 0) {
            $fecha->addDay();
            if (self::esHabil($fecha)) {
                $dias--;
            }
        }

        return $fecha;
    }

    /** Días hábiles que faltan hasta una fecha (negativo si ya pasó) */
    public static function entre(Carbon $desde, Carbon $hasta): int
    {
        $a = $desde->copy()->startOfDay();
        $b = $hasta->copy()->startOfDay();

        if ($a->equalTo($b)) {
            return 0;
        }

        $signo = $b->greaterThan($a) ? 1 : -1;
        [$ini, $fin] = $signo > 0 ? [$a, $b] : [$b, $a];

        $n = 0;
        for ($d = $ini->copy()->addDay(); $d->lte($fin); $d->addDay()) {
            if (self::esHabil($d)) {
                $n++;
            }
        }

        return $n * $signo;
    }
}
