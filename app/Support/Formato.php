<?php

namespace App\Support;

/**
 * Formatos de presentación reutilizables.
 *
 * Se usa number_format() en lugar de ->money() / ->numeric() de Filament
 * porque esos métodos requieren la extensión "intl" de PHP, que en XAMPP
 * suele venir desactivada.
 */
class Formato
{
    public static function soles(mixed $monto): string
    {
        return 'S/ ' . number_format((float) $monto, 2, '.', ',');
    }

    public static function entero(mixed $n): string
    {
        return number_format((int) $n, 0, '.', ',');
    }
}
