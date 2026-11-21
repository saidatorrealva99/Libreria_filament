<?php

namespace Database\Seeders;

use App\Models\Pago;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    /**
     * Al guardar cada pago, el modelo Pago recalcula el estado de su venta → "pagada".
     */
    public function run(): void
    {
        $pagos = [
            [1,  'yape',           76.90, '04518327',       '2026-10-01 10:16:00'],
            [2,  'efectivo',       49.90, null,             '2026-10-02 11:33:00'],
            [3,  'tarjeta',       270.00, 'VISA-731904',    '2026-10-03 16:49:00'],
            [4,  'plin',           42.50, '88214065',       '2026-10-05 09:22:00'],
            [5,  'efectivo',       69.80, null,             '2026-10-07 12:09:00'],
            [6,  'efectivo',       82.00, null,             '2026-10-10 17:44:00'],
            [7,  'tarjeta',        99.90, 'MC-260517',      '2026-10-12 15:12:00'],
            [8,  'transferencia', 825.00, 'BCP-0193847265', '2026-10-16 09:30:00'],
            [9,  'yape',           55.40, '07391842',       '2026-10-20 18:27:00'],
            [10, 'tarjeta',       109.80, 'VISA-418653',    '2026-10-25 13:57:00'],
        ];

        foreach ($pagos as [$venta, $metodo, $monto, $operacion, $fecha]) {
            Pago::create([
                'venta_id'         => $venta,
                'metodo_pago'      => $metodo,
                'monto'            => $monto,
                'numero_operacion' => $operacion,
                'fecha_pago'       => $fecha,
            ]);
        }
    }
}
