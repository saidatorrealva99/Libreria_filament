<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apertura de caja: la caja se abre con un fondo inicial y se cierra con el arqueo.
 * Mientras la caja del día no esté abierta, no se pueden registrar cobros.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE cierres_caja DROP CONSTRAINT ck_cierre_diferencia');

        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->enum('estado', ['abierta', 'cerrada'])->default('cerrada')->after('fecha');
            $table->timestamp('abierta_at')->nullable()->after('estado');
            $table->timestamp('cerrada_at')->nullable()->after('abierta_at');
            $table->foreignId('user_apertura_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->decimal('efectivo_contado', 10, 2)->nullable()->change();
            $table->decimal('diferencia', 10, 2)->nullable()->change();
        });

        // Si está cerrada, el arqueo debe estar completo y bien calculado
        DB::statement("ALTER TABLE cierres_caja ADD CONSTRAINT ck_cierre_diferencia CHECK (
            estado = 'abierta'
            OR (efectivo_contado IS NOT NULL AND diferencia = efectivo_contado - (fondo_inicial + efectivo_sistema))
        )");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cierres_caja DROP CONSTRAINT ck_cierre_diferencia');
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_apertura_id');
            $table->dropColumn(['estado', 'abierta_at', 'cerrada_at']);
        });
        DB::statement('ALTER TABLE cierres_caja ADD CONSTRAINT ck_cierre_diferencia CHECK (diferencia = efectivo_contado - (fondo_inicial + efectivo_sistema))');
    }
};
