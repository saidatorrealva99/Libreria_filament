<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Carga TODO el sistema con datos de demostración:
 *
 *   php artisan migrate:fresh --seed
 *
 * El orden importa: primero catálogos, luego usuarios y procesos.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Catálogos base
            AutorSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            ClienteSeeder::class,
            EmpleadoSeeder::class,

            // Usuarios del panel (admin + un usuario por empleado según su cargo)
            UsuarioSeeder::class,

            // Ventas históricas y sus pagos
            VentaSeeder::class,
            PagoSeeder::class,

            // Kárdex: inventario inicial de cada producto
            MovimientoSeeder::class,

            // Tienda: útiles, papelería, arte, oficina, mochilas, tecnología
            ProductosTiendaSeeder::class,

            // Proveedores y compras (el stock sube y queda en el kárdex)
            ComprasDemoSeeder::class,

            // Ventas de los últimos 60 días (para el dashboard y la reposición)
            DemoVentasSeeder::class,

            // Cupones de descuento
            CuponesSeeder::class,

            // Delivery por distrito y ofertas de ejemplo
            ZonaEnvioSeeder::class,
            OfertasSeeder::class,
        ]);
    }
}
