<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

/**
 * Ofertas y descuentos por cantidad de ejemplo.
 *
 *   php artisan db:seed --class=OfertasSeeder
 */
class OfertasSeeder extends Seeder
{
    public function run(): void
    {
        // [nombre que contiene, % de oferta, días de vigencia]
        $ofertas = [
            ['Colores x 24', 20, 15],
            ['Mochila escolar', 15, 30],
            ['Plumones gruesos', 10, 10],
            ['Calculadora científica', 12, 20],
        ];

        foreach ($ofertas as [$nombre, $pct, $dias]) {
            $p = Producto::where('nombre', 'like', "%{$nombre}%")->first();
            $p?->update([
                'precio_oferta' => round((float) $p->precio_venta * (1 - $pct / 100), 2),
                'oferta_hasta'  => now()->addDays($dias)->toDateString(),
            ]);
        }

        // Descuento por cantidad: ideal para listas escolares
        $porMayor = [
            ['Cuaderno A4 cuadriculado', 12, 10],
            ['Cuaderno A4 rayado', 12, 10],
            ['Lapicero azul', 6, 8],
            ['Papel bond', 3, 5],
        ];

        foreach ($porMayor as [$nombre, $desde, $pct]) {
            Producto::where('nombre', 'like', "%{$nombre}%")->first()
                ?->update(['cantidad_mayor' => $desde, 'descuento_mayor' => $pct]);
        }
    }
}
