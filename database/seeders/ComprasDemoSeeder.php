<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Proveedores y compras de ejemplo (el stock sube y queda en el kárdex).
 *
 *   php artisan db:seed --class=ComprasDemoSeeder
 */
class ComprasDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Proveedores (empresas ficticias)
        $proveedores = [
            'andina'   => ['20545678901', 'Distribuidora Editorial Andina S.A.C.', 'Rocío Paredes', '014423344', 'ventas@editorialandina.pe', 'Av. Colonial 1450, Cercado de Lima'],
            'pacifico' => ['20100234569', 'Ediciones Pacífico S.A.',                'Martín Ruiz',   '016179000', 'pedidos@edpacifico.pe',     'Av. Javier Prado Este 2560, San Borja'],
            'utiles'   => ['20512398765', 'Útiles Escolares del Perú S.A.C.',      'Karen Soto',    '013305566', 'mayorista@utilesperu.pe',   'Jr. Ica 455, Cercado de Lima'],
            'papelera' => ['20601234567', 'Papelera Lima E.I.R.L.',                 'Hugo Zavala',   '987123456', 'hugo@papeleralima.pe',      'Av. Argentina 3020, Callao'],
        ];

        $ids = [];
        foreach ($proveedores as $clave => [$ruc, $razon, $contacto, $tel, $email, $dir]) {
            $ids[$clave] = Proveedor::firstOrCreate(['ruc' => $ruc], [
                'razon_social' => $razon,
                'contacto'     => $contacto,
                'telefono'     => $tel,
                'email'        => $email,
                'direccion'    => $dir,
            ])->id;
        }

        // 2. Compras: proveedor, documento, fecha, [código => [cantidad, costo]]
        $compras = [
            ['andina',   'F001-004521', '2026-09-05', ['LIB-0001' => [10, 38.90], 'LIB-0002' => [10, 32.40], 'LIB-0005' => [8, 25.90]]],
            ['pacifico', 'F002-000871', '2026-09-20', ['LIB-0007' => [5, 122.00], 'LIB-0008' => [8, 64.90]]],
            ['utiles',   'F001-010233', '2026-10-02', ['UTI-0001' => [100, 5.20], 'UTI-0002' => [50, 9.10], 'UTI-0003' => [40, 14.50]]],
            ['papelera', 'F001-002210', '2026-10-18', ['PAP-0001' => [40, 7.50], 'PAP-0002' => [60, 14.20], 'PAP-0003' => [50, 6.10]]],
            ['andina',   'F001-004688', '2026-11-10', ['LIB-0003' => [10, 18.90], 'LIB-0006' => [12, 22.00], 'LIB-0009' => [10, 35.60]]],
        ];

        foreach ($compras as [$prov, $documento, $fecha, $items]) {
            if (Compra::where('proveedor_id', $ids[$prov])->where('numero_documento', $documento)->exists()) {
                continue;   // ya registrada
            }

            $productos = Producto::whereIn('codigo', array_keys($items))->get()->keyBy('codigo');
            if ($productos->isEmpty()) {
                continue;
            }

            $compra = Compra::create([
                'proveedor_id'     => $ids[$prov],
                'numero_documento' => $documento,
                'fecha_compra'     => $fecha,
                'total'            => 0,
                'estado'           => 'registrada',
            ]);

            $total = 0;
            foreach ($items as $codigo => [$cantidad, $costo]) {
                if (! isset($productos[$codigo])) {
                    continue;
                }
                $subtotal = round($cantidad * $costo, 2);
                $total   += $subtotal;

                DetalleCompra::create([
                    'compra_id'      => $compra->id,
                    'producto_id'    => $productos[$codigo]->id,
                    'cantidad'       => $cantidad,
                    'costo_unitario' => $costo,
                    'subtotal'       => $subtotal,
                ]);
            }

            $compra->update(['total' => round($total, 2)]);
            $compra->load(['detalles', 'proveedor'])->ingresarStock();

            // Los movimientos del kárdex con la fecha real de la compra
            $f = Carbon::parse($fecha)->setTime(10, 30);
            MovimientoInventario::where('compra_id', $compra->id)->update(['created_at' => $f, 'updated_at' => $f]);
        }

        // 3. Productos que nunca se compraron: costo estimado (65% del precio)
        Producto::whereNull('costo')->get()->each(
            fn (Producto $p) => $p->update(['costo' => round((float) $p->precio_venta * 0.65, 2)])
        );
    }
}
