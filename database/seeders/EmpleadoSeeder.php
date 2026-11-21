<?php

namespace Database\Seeders;

use App\Models\Empleado;
use Illuminate\Database\Seeder;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        $empleados = [
            ['09584217', 'Roberto',  'Salazar Díaz',  'administrador', 'roberto.salazar@libreria.pe', '997215843', 3200.00, '2019-02-11'],
            ['43172058', 'Patricia', 'Medina Cruz',   'vendedor',      'patricia.medina@libreria.pe', '945083617', 1650.00, '2020-07-01'],
            ['47920361', 'Miguel',   'Herrera Ponce', 'vendedor',      'miguel.herrera@libreria.pe',  '936458201', 1580.00, '2021-03-15'],
            ['70315842', 'Sofía',    'Ortiz Luna',    'cajero',        'sofia.ortiz@libreria.pe',     '958710364', 1420.00, '2021-11-08'],
            ['44608193', 'Fernando', 'Ríos Aguilar',  'almacenero',    'fernando.rios@libreria.pe',   '912846370', 1350.00, '2022-05-23'],
            ['72851406', 'Gabriela', 'Torres Peña',   'vendedor',      'gabriela.torres@libreria.pe', '973520148', 1700.00, '2022-09-12'],
            ['46239715', 'Andrés',   'Morales Ruiz',  'cajero',        'andres.morales@libreria.pe',  '921637054', 1460.00, '2023-02-06'],
            ['75094328', 'Camila',   'Silva Romero',  'vendedor',      'camila.silva@libreria.pe',    '984302576', 1525.00, '2023-08-21'],
            ['41783962', 'Ricardo',  'Benítez Lara',  'almacenero',    'ricardo.benitez@libreria.pe', '940618235', 1380.00, '2024-01-29'],
            ['73620481', 'Daniela',  'Campos Ibarra', 'vendedor',      'daniela.campos@libreria.pe',  '967194802', 1610.00, '2024-06-17'],
        ];

        foreach ($empleados as [$dni, $nombres, $apellidos, $cargo, $email, $tel, $sueldo, $fecha]) {
            Empleado::create([
                'dni'                => $dni,
                'nombres'            => $nombres,
                'apellidos'          => $apellidos,
                'cargo'              => $cargo,
                'email'              => $email,
                'telefono'           => $tel,
                'sueldo'             => $sueldo,
                'fecha_contratacion' => $fecha,
            ]);
        }
    }
}
