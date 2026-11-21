<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ofertas por producto, descuento por cantidad y delivery por distrito.
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------- Ofertas y descuento por cantidad ---------- */
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_oferta', 10, 2)->nullable()->after('precio_venta');
            $table->date('oferta_hasta')->nullable()->after('precio_oferta');
            $table->unsignedSmallInteger('cantidad_mayor')->nullable()->after('oferta_hasta');   // desde cuántas unidades
            $table->decimal('descuento_mayor', 5, 2)->nullable()->after('cantidad_mayor');      // % de descuento
        });
        DB::statement("ALTER TABLE productos
            ADD CONSTRAINT ck_productos_oferta CHECK (precio_oferta IS NULL OR (precio_oferta > 0 AND precio_oferta < precio_venta)),
            ADD CONSTRAINT ck_productos_mayor CHECK (
                (cantidad_mayor IS NULL AND descuento_mayor IS NULL)
                OR (cantidad_mayor >= 2 AND descuento_mayor > 0 AND descuento_mayor <= 50)
            )");

        /* ---------- Zonas de delivery ---------- */
        Schema::create('zonas_envio', function (Blueprint $table) {
            $table->id();
            $table->string('distrito', 60)->unique();
            $table->decimal('costo', 8, 2);
            $table->string('tiempo', 40)->default('24 a 48 horas');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE zonas_envio ADD CONSTRAINT ck_zonas_costo CHECK (costo >= 0)');

        /* ---------- Entrega del pedido ---------- */
        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('tipo_entrega', ['recojo', 'delivery'])->default('recojo')->after('canal');
            $table->foreignId('zona_envio_id')->nullable()->after('tipo_entrega')
                ->constrained('zonas_envio')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('direccion_entrega', 200)->nullable()->after('zona_envio_id');
            $table->string('referencia_entrega', 200)->nullable()->after('direccion_entrega');
            $table->decimal('costo_envio', 8, 2)->default(0)->after('descuento');
        });

        // El total ahora incluye el envío
        DB::statement('ALTER TABLE ventas DROP CONSTRAINT ck_ventas_montos');
        DB::statement("ALTER TABLE ventas
            ADD CONSTRAINT ck_ventas_montos CHECK (
                descuento >= 0 AND subtotal >= 0 AND costo_envio >= 0
                AND total = subtotal - descuento + costo_envio
                AND total >= 0
            ),
            ADD CONSTRAINT ck_ventas_entrega CHECK (
                tipo_entrega = 'recojo'
                OR (zona_envio_id IS NOT NULL AND direccion_entrega IS NOT NULL)
            )");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ventas DROP CONSTRAINT ck_ventas_entrega');
        DB::statement('ALTER TABLE ventas DROP CONSTRAINT ck_ventas_montos');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zona_envio_id');
            $table->dropColumn(['tipo_entrega', 'direccion_entrega', 'referencia_entrega', 'costo_envio']);
        });
        DB::statement("ALTER TABLE ventas ADD CONSTRAINT ck_ventas_montos CHECK (
            descuento >= 0 AND subtotal >= 0 AND total = subtotal - descuento AND total >= 0)");

        Schema::dropIfExists('zonas_envio');

        DB::statement('ALTER TABLE productos DROP CONSTRAINT ck_productos_mayor');
        DB::statement('ALTER TABLE productos DROP CONSTRAINT ck_productos_oferta');
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio_oferta', 'oferta_hasta', 'cantidad_mayor', 'descuento_mayor']);
        });
    }
};
