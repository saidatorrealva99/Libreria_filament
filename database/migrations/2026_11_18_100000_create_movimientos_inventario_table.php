<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('tipo', ['entrada', 'salida']);
            // inicial = stock de arranque, compra = llegó mercadería, venta = se vendió,
            // anulacion = volvió por venta anulada, merma = daño/pérdida
            $table->enum('motivo', ['inicial', 'compra', 'venta', 'anulacion', 'merma']);
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('stock_anterior');
            $table->unsignedInteger('stock_nuevo');
            $table->string('referencia', 60)->nullable();   // N° de guía, B001-..., etc.
            $table->string('nota', 200)->nullable();

            $table->foreignId('venta_id')->nullable()
                ->constrained('ventas')->cascadeOnUpdate()->nullOnDelete();

            $table->foreignId('user_id')->nullable()            // quién lo registró
                ->constrained('users')->cascadeOnUpdate()->nullOnDelete();

            $table->timestamps();

            $table->index(['producto_id', 'created_at']);
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE movimientos_inventario
                ADD CONSTRAINT ck_mov_cantidad CHECK (cantidad > 0),
                ADD CONSTRAINT ck_mov_stock CHECK (
                       (tipo = 'entrada' AND stock_nuevo = stock_anterior + cantidad)
                    OR (tipo = 'salida'  AND stock_nuevo = stock_anterior - cantidad)
                )");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
