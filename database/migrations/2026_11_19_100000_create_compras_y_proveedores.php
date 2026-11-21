<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Compras a proveedores:
 *  - proveedores
 *  - compras (cabecera) y detalle_compras
 *  - productos.costo (último costo de compra → para calcular ganancia)
 *  - kárdex: se enlaza con la compra y se agrega el motivo "devolucion"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->char('ruc', 11)->unique();
            $table->string('razon_social', 150);
            $table->string('contacto', 100)->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('numero_documento', 20);            // factura o guía del proveedor: F001-4521
            $table->date('fecha_compra');
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('estado', ['registrada', 'anulada'])->default('registrada');
            $table->string('observacion', 200)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->unique(['proveedor_id', 'numero_documento']);   // no registrar dos veces la misma factura
        });

        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('costo_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->unique(['compra_id', 'producto_id']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('costo', 10, 2)->nullable()->after('precio_venta');   // último costo de compra
        });

        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('compra_id')->nullable()->after('venta_id')
                ->constrained('compras')->cascadeOnUpdate()->nullOnDelete();
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE movimientos_inventario
                MODIFY motivo ENUM('inicial','compra','venta','anulacion','merma','devolucion') NOT NULL");

            DB::statement("ALTER TABLE proveedores
                ADD CONSTRAINT ck_proveedores_ruc CHECK (ruc REGEXP '^(10|15|17|20)[0-9]{9}$'),
                ADD CONSTRAINT ck_proveedores_tel CHECK (telefono IS NULL OR telefono REGEXP '^[0-9]{7,9}$')");

            DB::statement("ALTER TABLE compras ADD CONSTRAINT ck_compras_total CHECK (total >= 0)");

            DB::statement('ALTER TABLE detalle_compras
                ADD CONSTRAINT ck_dcompra_cantidad CHECK (cantidad > 0),
                ADD CONSTRAINT ck_dcompra_costo    CHECK (costo_unitario > 0),
                ADD CONSTRAINT ck_dcompra_subtotal CHECK (subtotal = cantidad * costo_unitario)');

            DB::statement('ALTER TABLE productos ADD CONSTRAINT ck_productos_costo CHECK (costo IS NULL OR costo > 0)');
        }
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('compra_id');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE productos DROP CONSTRAINT ck_productos_costo');
        }

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('costo');
        });

        Schema::dropIfExists('detalle_compras');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');
    }
};
