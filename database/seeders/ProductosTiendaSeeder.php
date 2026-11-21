<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Database\Seeder;

/**
 * Catálogo de útiles, papelería, arte, oficina, mochilas y tecnología escolar.
 * Cada producto entra con su stock inicial registrado en el kárdex.
 * Se puede ejecutar varias veces: no duplica productos.
 *
 *   php artisan db:seed --class=ProductosTiendaSeeder
 */
class ProductosTiendaSeeder extends Seeder
{
    public function run(): void
    {
        // categoría => [prefijo, descripción, productos: [nombre, marca, precio, stock, mínimo, nuevo?]]
        $catalogo = [
            'Útiles escolares' => ['UTI', 'Cuadernos, lapiceros, lápices y todo para el colegio', [
                ['Cuaderno A4 cuadriculado 100 hojas', 'Standford', 6.50, 120, 30],
                ['Cuaderno A4 rayado 100 hojas', 'Standford', 6.50, 100, 30],
                ['Cuaderno triple renglón A4 80 hojas', 'Justus', 5.90, 60, 15],
                ['Cuaderno espiral A5 cuadriculado', 'Alpha', 7.90, 45, 10, true],
                ['Lapicero azul punta fina x 3', 'Faber-Castell', 4.50, 150, 40],
                ['Lapicero negro gel 0.7 mm', 'Pilot', 3.80, 90, 25],
                ['Lápiz grafito 2B x 12', 'Faber-Castell', 9.90, 70, 15],
                ['Borrador blanco grande', 'Pelikan', 1.50, 200, 40],
                ['Tajador con depósito', 'Maped', 3.20, 80, 20],
                ['Regla de 30 cm transparente', 'Artesco', 2.00, 90, 20],
                ['Corrector tipo lapicero', 'Pelikan', 4.90, 55, 15],
                ['Resaltador x 4 colores pastel', 'Stabilo', 14.90, 40, 10, true],
            ]],
            'Arte y manualidades' => ['ART', 'Colores, plumones, témperas y materiales de arte', [
                ['Colores x 24 largos', 'Faber-Castell', 18.90, 50, 12],
                ['Colores x 12 jumbo', 'Artesco', 12.50, 35, 10],
                ['Plumones gruesos x 12', 'Faber-Castell', 15.90, 40, 10],
                ['Témperas x 7 frascos', 'Artesco', 13.50, 30, 8],
                ['Plastilina x 12 barras', 'Play-Doh', 8.90, 45, 10],
                ['Pincel escolar set x 6', 'Artesco', 7.50, 30, 8],
                ['Silicona líquida 100 ml', 'Artesco', 4.90, 60, 15],
                ['Tijera escolar punta roma', 'Maped', 5.50, 50, 12],
                ['Goma en barra 21 g', 'UHU', 4.20, 80, 20],
            ]],
            'Papelería' => ['PAP', 'Papel bond, cartulinas, folders y archivo', [
                ['Papel bond A4 75 g x 500 hojas', 'Atlas', 22.90, 60, 15],
                ['Cartulina blanca x 10', 'Layconsa', 6.90, 50, 12],
                ['Papel lustre x 20 colores', 'Layconsa', 5.50, 40, 10],
                ['Folder manila A4 x 25', 'Atlas', 12.90, 35, 8],
                ['Sobre manila A4 x 10', 'Atlas', 4.90, 50, 12],
                ['Archivador palanca lomo ancho', 'Alpha', 9.90, 30, 8],
            ]],
            'Oficina' => ['OFI', 'Artículos para oficina y estudio', [
                ['Engrapador metálico 26/6', 'Rapid', 19.90, 20, 5],
                ['Grapas 26/6 x 5000', 'Rapid', 6.50, 40, 10],
                ['Post-it 3x3 x 4 blocks', 'Post-it', 16.90, 30, 8, true],
                ['Clips metálicos x 100', 'Alpha', 2.90, 60, 15],
                ['Agenda 2027 tapa dura', 'Standford', 29.90, 25, 6, true],
            ]],
            'Mochilas y cartucheras' => ['MOC', 'Mochilas escolares, loncheras y cartucheras', [
                ['Mochila escolar 18" con ruedas', 'Xtrem', 119.90, 12, 3],
                ['Mochila juvenil 2 compartimentos', 'Porta', 79.90, 15, 4, true],
                ['Cartuchera doble cierre', 'Porta', 24.90, 30, 8],
                ['Lonchera térmica', 'Xtrem', 34.90, 18, 5],
            ]],
            'Tecnología escolar' => ['TEC', 'Calculadoras, USB y accesorios', [
                ['Calculadora científica 240 funciones', 'Casio', 69.90, 15, 4],
                ['Calculadora básica 12 dígitos', 'Casio', 24.90, 25, 6],
                ['Memoria USB 32 GB', 'Kingston', 29.90, 20, 5],
                ['Audífonos con micrófono', 'Philips', 39.90, 12, 3, true],
            ]],
        ];

        $creados = 0;

        foreach ($catalogo as $nombreCat => [$prefijo, $descCat, $productos]) {
            $categoria = Categoria::firstOrCreate(['nombre' => $nombreCat], ['descripcion' => $descCat, 'estado' => true]);

            foreach ($productos as $fila) {
                [$nombre, $marca, $precio, $stock, $minimo] = $fila;
                $nuevo = $fila[5] ?? false;

                if (Producto::where('nombre', $nombre)->exists()) {
                    continue;
                }

                $producto = Producto::create([
                    'codigo'          => Producto::siguienteCodigo($prefijo),
                    'nombre'          => $nombre,
                    'descripcion'     => "{$nombre} marca {$marca}. Ideal para el colegio, la universidad o la oficina.",
                    'categoria_id'    => $categoria->id,
                    'autor_id'        => null,
                    'isbn'            => null,
                    'marca_editorial' => $marca,
                    'precio_venta'    => $precio,
                    'costo'           => round($precio * 0.62, 2),
                    'stock'           => 0,
                    'stock_minimo'    => $minimo,
                    'estado'          => true,
                ]);

                // Stock inicial en el kárdex
                MovimientoInventario::registrar($producto->id, 'inicial', $stock, 'Inventario inicial', null, 'Carga del catálogo de útiles');

                // Solo algunos quedan como "Nuevo" en la tienda
                if (! $nuevo) {
                    $fecha = now()->subDays(45);
                    $producto->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->saveQuietly();
                    MovimientoInventario::where('producto_id', $producto->id)->update(['created_at' => $fecha, 'updated_at' => $fecha]);
                }

                $creados++;
            }
        }

        $this->command?->info("{$creados} productos agregados a la tienda.");
    }
}
