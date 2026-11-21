<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cupones de descuento, cierre de caja diario y bitácora de actividad.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------- Cupones ---------- */
        Schema::create('cupones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();                 // ESCOLAR10
            $table->string('descripcion', 150)->nullable();
            $table->enum('tipo', ['porcentaje', 'monto']);
            $table->decimal('valor', 10, 2);                         // 10 (%) o 5.00 (S/)
            $table->decimal('minimo_compra', 10, 2)->default(0);
            $table->unsignedInteger('usos_maximos')->nullable();     // null = ilimitado
            $table->unsignedInteger('usos')->default(0);
            $table->date('vence_at')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
        DB::statement("ALTER TABLE cupones
            ADD CONSTRAINT ck_cupones_valor CHECK (valor > 0),
            ADD CONSTRAINT ck_cupones_porcentaje CHECK (tipo <> 'porcentaje' OR valor <= 100),
            ADD CONSTRAINT ck_cupones_minimo CHECK (minimo_compra >= 0),
            ADD CONSTRAINT ck_cupones_codigo CHECK (codigo REGEXP '^[A-Z0-9]{3,20}$'),
            ADD CONSTRAINT ck_cupones_usos CHECK (usos_maximos IS NULL OR usos <= usos_maximos)");

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('cupon_id')->nullable()->after('descuento')
                ->constrained('cupones')->cascadeOnUpdate()->nullOnDelete();
        });

        /* ---------- Cierre de caja ---------- */
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();                         // un cierre por día
            $table->decimal('fondo_inicial', 10, 2)->default(0);     // sencillo con que abrió la caja
            $table->decimal('efectivo_sistema', 10, 2)->default(0);
            $table->decimal('tarjeta_sistema', 10, 2)->default(0);
            $table->decimal('yape_sistema', 10, 2)->default(0);
            $table->decimal('plin_sistema', 10, 2)->default(0);
            $table->decimal('transferencia_sistema', 10, 2)->default(0);
            $table->decimal('total_sistema', 10, 2)->default(0);
            $table->unsignedInteger('numero_pagos')->default(0);
            $table->decimal('efectivo_contado', 10, 2);              // lo que se contó físicamente
            $table->decimal('diferencia', 10, 2);                    // contado - (fondo + efectivo sistema)
            $table->string('observacion', 300)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE cierres_caja
            ADD CONSTRAINT ck_cierre_montos CHECK (fondo_inicial >= 0 AND efectivo_contado >= 0 AND total_sistema >= 0),
            ADD CONSTRAINT ck_cierre_diferencia CHECK (diferencia = efectivo_contado - (fondo_inicial + efectivo_sistema))");

        /* ---------- Bitácora ---------- */
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 30);                            // creó, actualizó, eliminó, anuló, inició sesión…
            $table->string('modulo', 40);                            // Venta, Producto…
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('descripcion', 255);
            $table->json('cambios')->nullable();                     // {"precio_venta": ["39.90", "42.90"]}
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['modulo', 'registro_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
        Schema::dropIfExists('cierres_caja');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cupon_id');
        });
        Schema::dropIfExists('cupones');
    }
};
