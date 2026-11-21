<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            ['DNI', '70482915',    'Lucía',                  'Ramos Quispe',    'lucia.ramos@gmail.com',    '987412536', 'Av. Arequipa 1234, Lince'],
            ['DNI', '45819273',    'Carlos',                 'Mendoza Torres',  'cmendoza@hotmail.com',     '954318207', 'Jr. Junín 456, Cercado de Lima'],
            ['DNI', '72936104',    'Ana',                    'Flores Huamán',   'ana.floresh@gmail.com',    '962875140', 'Av. Brasil 789, Pueblo Libre'],
            ['DNI', '41057382',    'Jorge',                  'Castillo Vega',   'jcastillo@outlook.com',    '913640582', 'Calle Los Pinos 321, Surco'],
            ['DNI', '76240519',    'María',                  'Gutiérrez Rojas', 'mariagr@gmail.com',        '948201736', 'Av. La Marina 654, San Miguel'],
            ['DNI', '48391726',    'Luis',                   'Paredes Salas',   'lparedes@yahoo.com',       '976053821', 'Jr. Cusco 147, Breña'],
            ['RUC', '20601847352', 'Colegio San Martín SAC', null,              'compras@sanmartin.edu.pe', '015284937', 'Av. Universitaria 2580, Los Olivos'],
            ['DNI', '73158204',    'Rosa',                   'Chávez León',     'rosachavez@gmail.com',     '931764205', 'Av. Benavides 963, Miraflores'],
            ['DNI', '46927031',    'Diego',                  'Vargas Soto',     'diego.vs@hotmail.com',     '925417368', 'Calle Las Flores 258, La Molina'],
            ['DNI', '71804563',    'Valeria',                'Núñez Campos',    'valenunez@gmail.com',      '968302419', 'Av. Javier Prado 741, San Isidro'],
        ];

        foreach ($clientes as [$tipo, $num, $nombres, $apellidos, $email, $tel, $dir]) {
            Cliente::create([
                'tipo_documento'   => $tipo,
                'numero_documento' => $num,
                'nombres'          => $nombres,
                'apellidos'        => $apellidos,
                'email'            => $email,
                'telefono'         => $tel,
                'direccion'        => $dir,
            ]);
        }
    }
}
