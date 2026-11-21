<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Empleado;
use App\Models\MovimientoInventario;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Ventas realistas de los últimos 60 días (para que el Dashboard y la Reposición tengan datos).
 * Cada venta descuenta stock y queda en el kárdex con su fecha real.
 *
 *   php artisan db:seed --class=DemoVentasSeeder
 */
class DemoVentasSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026);   // siempre los mismos datos

        $clientes   = Cliente::pluck('tipo_documento', 'id');
        $empleados  = Empleado::activos()->whereIn('cargo', ['vendedor', 'cajero', 'administrador'])->pluck('id');
        $productos  = Producto::activos()->get();

        if ($clientes->isEmpty() || $empleados->isEmpty() || $productos->isEmpty()) {
            $this->command?->warn('Faltan clientes, empleados o productos.');

            return;
        }

        // Los útiles y la papelería se venden más que los libros
        $pesos = $productos->mapWithKeys(fn (Producto $p) => [$p->id => $p->esLibro() ? 2 : 5]);
        $metodos = ['efectivo', 'efectivo', 'efectivo', 'yape', 'yape', 'plin', 'tarjeta'];
        $creadas = 0;

        for ($d = 60; $d >= 0; $d--) {
            $dia = now()->subDays($d)->startOfDay();

            // Más ventas los sábados y una tendencia que crece hacia hoy
            $cantidad = mt_rand(1, 3) + (int) round((60 - $d) / 25) + ($dia->isSaturday() ? 2 : 0) - ($dia->isSunday() ? 2 : 0);

            for ($v = 0; $v < max(0, $cantidad); $v++) {
                $fecha = $dia->copy()->setTime(mt_rand(9, 20), mt_rand(0, 59));
                if ($fecha->isFuture()) {
                    continue;
                }

                // Productos de la venta (1 a 3, sin repetir, con stock)
                $items = [];
                foreach (range(1, mt_rand(1, 3)) as $_) {
                    $p   = $productos->find($this->elegir($pesos));
                    $qty = $p->esLibro() ? 1 : mt_rand(1, 4);
                    $p->refresh();
                    if (isset($items[$p->id]) || $p->stock < $qty) {
                        continue;
                    }
                    $items[$p->id] = [$p, $qty];
                }
                if (! $items) {
                    continue;
                }

                $clienteId = $clientes->keys()->random();
                $tipo      = $clientes[$clienteId] === 'RUC' ? 'factura' : 'boleta';
                $total     = round(collect($items)->sum(fn ($i) => $i[1] * (float) $i[0]->precio_venta), 2);

                $venta = Venta::create([
                    'tipo_comprobante'   => $tipo,
                    'numero_comprobante' => Venta::siguienteNumero($tipo),
                    'cliente_id'         => $clienteId,
                    'empleado_id'        => $empleados->random(),
                    'fecha_venta'        => $fecha,
                    'subtotal'           => $total,
                    'descuento'          => 0,
                    'total'              => $total,
                    'estado'             => 'pendiente',
                ]);

                foreach ($items as [$p, $qty]) {
                    DetalleVenta::create([
                        'venta_id'        => $venta->id,
                        'producto_id'     => $p->id,
                        'cantidad'        => $qty,
                        'precio_unitario' => $p->precio_venta,
                        'subtotal'        => round($qty * (float) $p->precio_venta, 2),
                    ]);
                }

                $venta->descontarStock();
                MovimientoInventario::where('venta_id', $venta->id)->update(['created_at' => $fecha, 'updated_at' => $fecha]);

                // Casi todas se cobran al momento; unas pocas quedan pendientes
                if ($d > 3 || mt_rand(1, 4) > 1) {
                    $metodo = $metodos[array_rand($metodos)];
                    Pago::create([
                        'venta_id'         => $venta->id,
                        'metodo_pago'      => $metodo,
                        'monto'            => $total,
                        'numero_operacion' => in_array($metodo, ['yape', 'plin', 'tarjeta']) ? (string) mt_rand(10000000, 99999999) : null,
                        'fecha_pago'       => $fecha,
                    ]);
                }

                $venta->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->saveQuietly();
                $creadas++;
            }
        }

        $this->command?->info("{$creadas} ventas de demostración creadas.");
    }

    /** Elige un id al azar según su peso */
    private function elegir($pesos): int
    {
        $r = mt_rand(1, $pesos->sum());
        foreach ($pesos as $id => $peso) {
            if (($r -= $peso) <= 0) {
                return $id;
            }
        }

        return $pesos->keys()->first();
    }
}
