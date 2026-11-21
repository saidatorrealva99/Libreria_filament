<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada usuario tiene un rol, puede estar vinculado a un empleado y puede desactivarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('password')
                ->constrained('roles')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('empleado_id')->nullable()->unique()->after('rol_id')
                ->constrained('empleados')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('activo')->default(true)->after('empleado_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empleado_id');
            $table->dropConstrainedForeignId('rol_id');
            $table->dropColumn('activo');
        });
    }
};
