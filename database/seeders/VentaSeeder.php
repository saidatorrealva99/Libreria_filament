<?php

namespace Database\Seeders;

use App\Models\DetalleVenta;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    /**
     * Ventas: total = subtotal - descuento (cuadra con detalle_ventas y pagos).
     * El stock de ProductoSeeder ya es el stock actual, por eso aquí NO se descuenta.
     */
    public function run(): void
    {
        $ventas = [
            ['boleta',  'B001-00000001',  1,  2, '2026-10-01 10:15:00',  76.90,  0.00,  76.90],
            ['boleta',  'B001-00000002',  2,  3, '2026-10-02 11:32:00',  49.90,  0.00,  49.90],
            ['boleta',  'B001-00000003',  3,  6, '2026-10-03 16:47:00', 288.90, 18.90, 270.00],
            ['boleta',  'B001-00000004',  4,  8, '2026-10-05 09:21:00',  42.50,  0.00,  42.50],
            ['boleta',  'B001-00000005',  5,  3, '2026-10-07 12:08:00',  69.80,  0.00,  69.80],
            ['boleta',  'B001-00000006',  1, 10, '2026-10-10 17:43:00',  82.00,  0.00,  82.00],
            ['boleta',  'B001-00000007',  6,  6, '2026-10-12 15:10:00',  99.90,  0.00,  99.90],
            ['factura', 'F001-00000001',  7,  2, '2026-10-15 10:04:00', 870.00, 45.00, 825.00],
            ['boleta',  'B001-00000008',  8,  8, '2026-10-20 18:26:00',  55.40,  0.00,  55.40],
            ['boleta',  'B001-00000009', 10, 10, '2026-10-25 13:55:00', 109.80,  0.00, 109.80],
        ];

        foreach ($ventas as [$tipo, $numero, $cliente, $empleado, $fecha, $subtotal, $descuento, $total]) {
            Venta::create([
                'tipo_comprobante'   => $tipo,
                'numero_comprobante' => $numero,
                'cliente_id'         => $cliente,
                'empleado_id'        => $empleado,
                'fecha_venta'        => $fecha,
                'subtotal'           => $subtotal,
                'descuento'          => $descuento,
                'total'              => $total,
                'estado'             => 'pendiente',   // PagoSeeder la pasa a "pagada"
            ]);
        }

        // venta_id, producto_id, cantidad, precio_unitario
        $detalles = [
            [1,  1,  1,  59.90], [1,  9,  2,   8.50],
            [2,  2,  1,  49.90],
            [3,  7,  1, 189.00], [3,  8,  1,  99.90],
            [4,  4,  1,  42.50],
            [5,  5,  1,  39.90], [5,  3,  1,  29.90],
            [6,  6,  2,  35.00], [6, 10,  1,  12.00],
            [7,  8,  1,  99.90],
            [8,  9, 40,   8.50], [8, 10, 15,  12.00], [8,  6, 10,  35.00],
            [9,  3,  1,  29.90], [9,  9,  3,   8.50],
            [10, 1,  1,  59.90], [10, 2,  1,  49.90],
        ];

        foreach ($detalles as [$venta, $producto, $cantidad, $precio]) {
            DetalleVenta::create([
                'venta_id'        => $venta,
                'producto_id'     => $producto,
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'subtotal'        => round($cantidad * $precio, 2),
            ]);
        }
    }
}
