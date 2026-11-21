<?php

namespace App\Support;

/**
 * Convierte un monto a letras para el comprobante:
 *   825.00  -> "OCHOCIENTOS VEINTICINCO CON 00/100 SOLES"
 */
class NumeroALetras
{
    private const UNIDADES = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
        'VEINTE', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];

    private const DECENAS = ['', '', '', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];

    private const CENTENAS = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public static function soles(float $monto): string
    {
        $entero   = (int) floor(round($monto, 2));
        $centimos = (int) round(($monto - $entero) * 100);

        $texto = $entero === 0 ? 'CERO' : self::convertir($entero);

        return sprintf('%s CON %02d/100 SOLES', $texto, $centimos);
    }

    private static function convertir(int $n): string
    {
        if ($n >= 1_000_000) {
            $millones = intdiv($n, 1_000_000);
            $resto    = $n % 1_000_000;
            $texto    = $millones === 1 ? 'UN MILLÓN' : self::apocopar(self::convertir($millones)) . ' MILLONES';

            return trim($texto . ' ' . ($resto ? self::convertir($resto) : ''));
        }

        if ($n >= 1000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $texto = $miles === 1 ? 'MIL' : self::apocopar(self::convertir($miles)) . ' MIL';

            return trim($texto . ' ' . ($resto ? self::convertir($resto) : ''));
        }

        if ($n >= 100) {
            if ($n === 100) {
                return 'CIEN';
            }

            return trim(self::CENTENAS[intdiv($n, 100)] . ' ' . self::convertir($n % 100));
        }

        if ($n < 30) {
            return self::UNIDADES[$n];
        }

        $u = $n % 10;

        return self::DECENAS[intdiv($n, 10)] . ($u ? ' Y ' . self::UNIDADES[$u] : '');
    }

    // "UNO" -> "UN" delante de MIL / MILLONES (veintiún mil, treinta y un mil)
    private static function apocopar(string $texto): string
    {
        return preg_replace(['/VEINTIUNO$/u', '/UNO$/u'], ['VEINTIÚN', 'UN'], $texto);
    }
}
