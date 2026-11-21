<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Caja por persona: cada vendedora/cajera abre SU caja, y el cierre muestra
 * solo las ventas y cobros que ella atendió en ese turno.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ya no es "una caja por día": cada persona tiene la suya
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropUnique(['fecha']);
            $table->index(['fecha', 'user_apertura_id']);
        });

        // Cada cobro guarda quién lo hizo y en qué caja
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('venta_id')
                ->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('cierre_caja_id')->nullable()->after('user_id')
                ->constrained('cierres_caja')->cascadeOnUpdate()->nullOnDelete();
        });

        // Cada venta guarda en qué caja (turno) se registró
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('cierre_caja_id')->nullable()->after('empleado_id')
                ->constrained('cierres_caja')->cascadeOnUpdate()->nullOnDelete();
        });

        // Lo que ya existía: se asigna a la caja de ese día
        DB::statement('UPDATE pagos p JOIN cierres_caja c ON DATE(p.fecha_pago) = c.fecha SET p.cierre_caja_id = c.id');
        DB::statement("UPDATE ventas v JOIN cierres_caja c ON DATE(v.fecha_venta) = c.fecha SET v.cierre_caja_id = c.id WHERE v.canal = 'tienda'");
    }

    public function down(): void
    {
        Schema::table('ventas', fn (Blueprint $t) => $t->dropConstrainedForeignId('cierre_caja_id'));
        Schema::table('pagos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('cierre_caja_id');
            $t->dropConstrainedForeignId('user_id');
        });
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropIndex(['fecha', 'user_apertura_id']);
            $table->unique('fecha');
        });
    }
};
