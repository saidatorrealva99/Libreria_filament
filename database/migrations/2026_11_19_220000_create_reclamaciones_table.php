<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Libro de Reclamaciones virtual (Código de Protección y Defensa del Consumidor,
 * D.S. 011-2011-PCM y modificatorias): hojas correlativas, respuesta en 15 días hábiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero');                 // correlativo del año
            $table->unsignedSmallInteger('anio');
            $table->string('codigo', 15)->unique();            // 000001-2026

            // 1. Consumidor
            $table->enum('tipo_documento', ['DNI', 'CE', 'PASAPORTE', 'RUC']);
            $table->string('numero_documento', 15);
            $table->string('nombres', 100);
            $table->string('apellidos', 100)->nullable();
            $table->string('telefono', 9);
            $table->string('email', 120);
            $table->string('direccion', 200);
            $table->boolean('menor_edad')->default(false);
            $table->string('apoderado', 150)->nullable();       // padre, madre o tutor

            // 2. Bien contratado
            $table->enum('bien_tipo', ['producto', 'servicio']);
            $table->string('bien_descripcion', 200);
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('pedido_referencia', 30)->nullable(); // boleta o código de pedido

            // 3. Detalle
            $table->enum('tipo', ['reclamo', 'queja']);
            $table->text('detalle');
            $table->text('pedido');                              // lo que solicita el consumidor

            // 4. Atención del proveedor
            $table->enum('estado', ['pendiente', 'en_proceso', 'atendido'])->default('pendiente');
            $table->date('vence_at');
            $table->text('respuesta')->nullable();
            $table->timestamp('respondido_at')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('acepta_terminos')->default(false);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['anio', 'numero']);
            $table->index(['estado', 'vence_at']);
        });

        DB::statement("ALTER TABLE reclamaciones ADD CONSTRAINT ck_recl_monto CHECK (monto IS NULL OR monto >= 0)");
        DB::statement("ALTER TABLE reclamaciones ADD CONSTRAINT ck_recl_menor CHECK (menor_edad = 0 OR apoderado IS NOT NULL)");
        DB::statement("ALTER TABLE reclamaciones ADD CONSTRAINT ck_recl_respuesta CHECK (estado <> 'atendido' OR (respuesta IS NOT NULL AND respondido_at IS NOT NULL))");
        DB::statement("ALTER TABLE reclamaciones ADD CONSTRAINT ck_recl_telefono CHECK (telefono REGEXP '^[0-9]{7,9}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamaciones');
    }
};
