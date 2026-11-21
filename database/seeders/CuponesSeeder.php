<?php

namespace Database\Seeders;

use App\Models\Cupon;
use Illuminate\Database\Seeder;

/**
 *   php artisan db:seed --class=CuponesSeeder
 */
class CuponesSeeder extends Seeder
{
    public function run(): void
    {
        $cupones = [
            ['ESCOLAR10', 'Campaña escolar: 10% en tu lista', 'porcentaje', 10, 30, null, '2027-03-31'],
            ['BIENVENIDO5', 'S/ 5 en tu primera compra online', 'monto', 5, 40, 100, null],
            ['LECTOR15', '15% en compras desde S/ 100', 'porcentaje', 15, 100, 50, now()->addMonths(2)->toDateString()],
        ];

        foreach ($cupones as [$codigo, $desc, $tipo, $valor, $minimo, $usos, $vence]) {
            Cupon::firstOrCreate(['codigo' => $codigo], [
                'descripcion'   => $desc,
                'tipo'          => $tipo,
                'valor'         => $valor,
                'minimo_compra' => $minimo,
                'usos_maximos'  => $usos,
                'vence_at'      => $vence,
                'estado'        => true,
            ]);
        }
    }
}
