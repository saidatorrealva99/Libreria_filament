<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique('uq_categorias_nombre');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('estado')->default(true);   // 1 = activo, 0 = inactivo
            $table->timestamps();
        });

        // Las restricciones CHECK no existen en el Schema Builder: se agregan con SQL.
        // Solo en MySQL 8.0.16+ / MariaDB 10.4+ (XAMPP).
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE categorias ADD CONSTRAINT ck_categorias_estado CHECK (estado IN (0,1))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
