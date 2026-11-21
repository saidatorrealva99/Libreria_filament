<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['Novela',             'Narrativa extensa de ficción'],
            ['Poesía',             'Obras en verso'],
            ['Literatura peruana', 'Tradiciones, cuentos y crónicas del Perú'],
            ['Cuento',             'Narrativa breve'],
            ['Informática',        'Programación, bases de datos y sistemas'],
            ['Matemáticas',        'Textos de álgebra, cálculo y estadística'],
            ['Historia',           'Textos de historia universal y del Perú'],
            ['Infantil',           'Libros para niños'],
            ['Útiles escolares',   'Cuadernos, lapiceros, colores y similares'],
            ['Papelería',          'Hojas, folders, archivadores'],
        ];

        foreach ($categorias as [$nombre, $descripcion]) {
            Categoria::create(['nombre' => $nombre, 'descripcion' => $descripcion]);
        }
    }
}
