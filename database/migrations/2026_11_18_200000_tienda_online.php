<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tienda online:
 *  - productos.imagen: portada / foto del producto
 *  - ventas: canal (tienda / web), código de pedido, pago declarado por el cliente,
 *            fecha de entrega, y empleado opcional (los pedidos web no tienen vendedor)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('marca_editorial');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('canal', ['tienda', 'web'])->default('tienda')->after('tipo_comprobante');
            $table->string('codigo_pedido', 12)->nullable()->unique()->after('canal');      // WEB-7K2P9Q
            $table->string('metodo_declarado', 20)->nullable()->after('estado');            // yape, plin, recojo
            $table->string('operacion_declarada', 30)->nullable()->after('metodo_declarado');
            $table->timestamp('entregado_at')->nullable()->after('operacion_declarada');
        });

        // Los pedidos web no tienen vendedor
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('empleado_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['canal', 'codigo_pedido', 'metodo_declarado', 'operacion_declarada', 'entregado_at']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
