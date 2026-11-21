<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Los 4 roles
        $roles = [
            Rol::ADMIN      => 'Acceso total al sistema',
            Rol::VENDEDOR   => 'Registra ventas y clientes',
            Rol::CAJERO     => 'Cobra ventas y maneja la caja',
            Rol::ALMACENERO => 'Gestiona productos e inventario',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Rol::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }

        // 2. Los usuarios que ya existen pasan a ser administradores
        User::whereNull('rol_id')->update(['rol_id' => Rol::where('nombre', Rol::ADMIN)->value('id')]);

        // 3. Usuarios de prueba vinculados a empleados (clave: 12345678)
        $usuarios = [
            ['patricia.medina@libreria.pe', Rol::VENDEDOR],
            ['sofia.ortiz@libreria.pe',     Rol::CAJERO],
            ['fernando.rios@libreria.pe',   Rol::ALMACENERO],
        ];

        foreach ($usuarios as [$email, $rol]) {
            $empleado = Empleado::where('email', $email)->first();

            User::updateOrCreate(['email' => $email], [
                'name'        => $empleado?->nombre_completo ?? $email,
                'password'    => Hash::make('12345678'),
                'rol_id'      => Rol::where('nombre', $rol)->value('id'),
                'empleado_id' => $empleado?->id,
                'activo'      => true,
            ]);
        }
    }
}