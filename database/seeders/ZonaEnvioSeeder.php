<?php

namespace Database\Seeders;

use App\Models\ZonaEnvio;
use Illuminate\Database\Seeder;

/**
 *   php artisan db:seed --class=ZonaEnvioSeeder
 */
class ZonaEnvioSeeder extends Seeder
{
    public function run(): void
    {
        $zonas = [
            ['Cercado de Lima', 6.00, 'El mismo día'],
            ['Breña', 6.00, 'El mismo día'],
            ['Jesús María', 7.00, 'El mismo día'],
            ['Lince', 7.00, 'El mismo día'],
            ['La Victoria', 7.00, 'El mismo día'],
            ['Pueblo Libre', 8.00, '24 horas'],
            ['San Miguel', 8.00, '24 horas'],
            ['Magdalena del Mar', 8.00, '24 horas'],
            ['San Isidro', 9.00, '24 horas'],
            ['Miraflores', 9.00, '24 horas'],
            ['Surquillo', 9.00, '24 horas'],
            ['San Borja', 9.00, '24 horas'],
            ['Rímac', 8.00, '24 horas'],
            ['San Martín de Porres', 10.00, '24 a 48 horas'],
            ['Los Olivos', 12.00, '24 a 48 horas'],
            ['Santiago de Surco', 12.00, '24 a 48 horas'],
            ['San Juan de Lurigancho', 12.00, '24 a 48 horas'],
            ['Ate', 13.00, '24 a 48 horas'],
            ['Comas', 14.00, '48 horas'],
            ['Villa El Salvador', 15.00, '48 horas'],
            ['Callao', 10.00, '24 a 48 horas'],
        ];

        foreach ($zonas as [$distrito, $costo, $tiempo]) {
            ZonaEnvio::firstOrCreate(['distrito' => $distrito], ['costo' => $costo, 'tiempo' => $tiempo, 'estado' => true]);
        }
    }
}
