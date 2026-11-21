<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        // codigo, nombre, descripcion, categoria_id, autor_id, isbn, marca_editorial, precio, stock, stock_minimo
        $productos = [
            ['LIB-0001', 'Cien años de soledad',          'Edición de bolsillo',                        1, 1,    '978-6124215037', 'Sudamericana',        59.90,  24,  5],
            ['LIB-0002', 'La ciudad y los perros',        'Edición del cincuentenario',                 1, 2,    '978-6123190458', 'Alfaguara',           49.90,  31,  6],
            ['LIB-0003', 'Los heraldos negros',           'Poemario completo con prólogo',              2, 3,    '978-9972402871', 'Peisa',               29.90,  17,  4],
            ['LIB-0004', 'Bestiario',                     'Ocho relatos breves',                        4, 5,    '978-8466331593', 'Punto de Lectura',    42.50,   9,  3],
            ['LIB-0005', 'Los ríos profundos',            'Colección Biblioteca Peruana',               1, 6,    '978-9972216645', 'Losada',              39.90,  20,  5],
            ['LIB-0006', 'Tradiciones peruanas',          'Selección escolar anotada',                  3, 8,    '978-6124063829', 'Editorial Universo',  35.00,  13,  4],
            ['LIB-0007', 'Fundamentos de bases de datos', 'Sexta edición, texto universitario',         5, 9,    '978-8448190330', 'McGraw-Hill',        189.00,   6,  2],
            ['LIB-0008', 'Código limpio',                 'Manual de buenas prácticas de programación', 5, 10,   '978-8441532107', 'Anaya Multimedia',    99.90,  11,  3],
            ['UTI-0001', 'Cuaderno A4 cuadriculado',      'Tapa dura, 100 hojas',                       9, null, null,             'Standford',            8.50, 180, 30],
            ['PAP-0001', 'Folder manila A4 x25',          'Paquete de 25 unidades',                    10, null, null,             'Artesco',             12.00,  60, 15],
        ];

        foreach ($productos as [$codigo, $nombre, $desc, $cat, $autor, $isbn, $marca, $precio, $stock, $min]) {
            Producto::create([
                'codigo'          => $codigo,
                'nombre'          => $nombre,
                'descripcion'     => $desc,
                'categoria_id'    => $cat,
                'autor_id'        => $autor,
                'isbn'            => $isbn,
                'marca_editorial' => $marca,
                'precio_venta'    => $precio,
                'stock'           => $stock,
                'stock_minimo'    => $min,
            ]);
        }
    }
}
