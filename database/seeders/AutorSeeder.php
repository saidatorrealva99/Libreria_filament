<?php

namespace Database\Seeders;

use App\Models\Autor;
use Illuminate\Database\Seeder;

class AutorSeeder extends Seeder
{
    public function run(): void
    {
        $autores = [
            ['Gabriel',    'García Márquez', 'Colombiana',     '1927-03-06'],
            ['Mario',      'Vargas Llosa',   'Peruana',        '1936-03-28'],
            ['César',      'Vallejo',        'Peruana',        '1892-03-16'],
            ['Isabel',     'Allende',        'Chilena',        '1942-08-02'],
            ['Julio',      'Cortázar',       'Argentina',      '1914-08-26'],
            ['José María', 'Arguedas',       'Peruana',        '1911-01-18'],
            ['Ciro',       'Alegría',        'Peruana',        '1909-11-04'],
            ['Ricardo',    'Palma',          'Peruana',        '1833-02-07'],
            ['Abraham',    'Silberschatz',   'Estadounidense', null],
            ['Robert C.',  'Martin',         'Estadounidense', null],
        ];

        foreach ($autores as [$nombres, $apellidos, $nacionalidad, $fecha]) {
            Autor::create([
                'nombres'          => $nombres,
                'apellidos'        => $apellidos,
                'nacionalidad'     => $nacionalidad,
                'fecha_nacimiento' => $fecha,
            ]);
        }
    }
}
