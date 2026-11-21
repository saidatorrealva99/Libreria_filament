<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios del panel: el administrador general y un usuario por cada empleado,
 * con el rol de su cargo. Contraseña de todos: 12345678
 *
 *   php artisan db:seed --class=UsuarioSeeder
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Rol::pluck('id', 'nombre');

        User::updateOrCreate(['email' => 'admin@libreria.pe'], [
            'name'     => 'Administrador',
            'password' => Hash::make('12345678'),
            'rol_id'   => $roles[Rol::ADMIN] ?? null,
            'activo'   => true,
        ]);

        foreach (Empleado::all() as $e) {
            if (! isset($roles[$e->cargo])) {
                continue;
            }

            User::updateOrCreate(['email' => $e->email], [
                'name'        => "{$e->nombres} " . explode(' ', $e->apellidos)[0],
                'password'    => Hash::make('12345678'),
                'rol_id'      => $roles[$e->cargo],
                'empleado_id' => $e->id,
                'activo'      => true,
            ]);
        }
    }
}
