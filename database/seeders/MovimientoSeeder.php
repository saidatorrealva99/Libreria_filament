<?php

namespace Database\Seeders;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Database\Seeder;

/**
 * Registra el "Inventario inicial" de cada producto con su stock actual,
 * para que el kárdex tenga un punto de partida.
 *
 *   php artisan db:seed --class=MovimientoSeeder
 */
class MovimientoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Producto::all() as $producto) {
            // No repetir si ya tiene movimientos
            if ($producto->movimientos()->exists() || $producto->stock === 0) {
                continue;
            }

            MovimientoInventario::create([
                'producto_id'    => $producto->id,
                'tipo'           => 'entrada',
                'motivo'         => 'inicial',
                'cantidad'       => $producto->stock,
                'stock_anterior' => 0,
                'stock_nuevo'    => $producto->stock,
                'referencia'     => 'Apertura de kárdex',
            ]);
        }
    }
}
