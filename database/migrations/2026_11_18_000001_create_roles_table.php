<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles del sistema. Los 4 roles se insertan aquí porque el código depende de sus nombres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique();
            $table->string('descripcion', 200)->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE roles ADD CONSTRAINT ck_roles_nombre CHECK (nombre IN ('administrador', 'vendedor', 'cajero', 'almacenero'))");

        DB::table('roles')->insert([
            ['nombre' => 'administrador', 'descripcion' => 'Acceso total al sistema',                          'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'vendedor',      'descripcion' => 'Registra ventas y clientes',                       'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'cajero',        'descripcion' => 'Cobra ventas, verifica pagos y cierra caja',       'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'almacenero',    'descripcion' => 'Gestiona productos, compras e inventario',         'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
