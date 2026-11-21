<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Empleado;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Genera ventas realistas desde junio hasta HOY, para que el dashboard
 * y los reportes tengan datos en todos los meses.
 *
 *  - Agrega más productos y clientes (si no existen).
 *  - Crea ventas con boletas y facturas (factura solo a clientes RUC).
 *  - Descuenta stock, registra pagos (Yape, efectivo, tarjeta...).
 *  - Deja algunas ventas pendientes y algunas anuladas.
 *  - Al final renumera los comprobantes en orden de fecha.
 *
 *  Ejecutar:  php artisan db:seed --class=VentasDemoSeeder
 */
class VentasDemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026); // mismos datos cada vez que se ejecute

        DB::transaction(function () {
            $this->crearProductos();
            $this->crearClientes();
            $this->crearVentas();
            $this->renumerarComprobantes();
        });
    }

    /* ---------------------------------------------------------------
     *  1. Más productos (libros, útiles y papelería)
     * --------------------------------------------------------------- */
    private function crearProductos(): void
    {
        // codigo, nombre, descripcion, categoria, autor, isbn, marca, precio, stock, minimo
        $productos = [
            ['LIB-0009', 'La casa de los espíritus',        'Novela, edición de bolsillo',   1, 4,    '978-8401352423', 'Plaza & Janés',  54.90,  30,  5],
            ['LIB-0010', 'El mundo es ancho y ajeno',       'Clásico de la narrativa andina', 3, 7,    '978-9972402888', 'Peisa',          45.00,  25,  4],
            ['LIB-0011', 'Rayuela',                          'Edición crítica',               1, 5,    '978-8437604572', 'Cátedra',        62.00,  15,  3],
            ['LIB-0012', 'Álgebra de Baldor',                'Texto escolar y preuniversitario', 6, null, '978-6074387006', 'Patria',      85.00,  40,  6],
            ['LIB-0013', 'Historia del Perú contemporáneo',  'Desde la Independencia',        7, null, '978-9972516002', 'IEP',            70.00,  18,  3],
            ['LIB-0014', 'El principito',                    'Edición ilustrada',             8, null, '978-6124497044', 'Salamandra',     25.00,  60,  8],
            ['UTI-0002', 'Lapicero azul x12',                'Caja de 12 unidades',           9, null, null,             'Faber-Castell',  14.50, 220, 25],
            ['UTI-0003', 'Colores largos x24',               'Caja de 24 colores',            9, null, null,             'Faber-Castell',  22.90, 140, 15],
            ['UTI-0004', 'Cuaderno A4 rayado',               'Tapa dura, 100 hojas',          9, null, null,             'Standford',       8.50, 300, 30],
            ['PAP-0002', 'Papel bond A4 75 g x500',          'Paquete de 500 hojas',         10, null, null,             'Chamex',         21.90, 120, 12],
            ['PAP-0003', 'Archivador de palanca',            'Lomo ancho, tamaño oficio',    10, null, null,             'Artesco',         9.90, 110, 15],
        ];

        foreach ($productos as [$codigo, $nombre, $desc, $cat, $autor, $isbn, $marca, $precio, $stock, $min]) {
            Producto::firstOrCreate(['codigo' => $codigo], [
                'nombre'          => $nombre,
                'descripcion'     => $desc,
                'categoria_id'    => $cat,
                'autor_id'        => $autor,
                'isbn'            => $isbn,
                'marca_editorial' => $marca,
                'precio_venta'    => $precio,
                'stock'           => $stock,
                'stock_minimo'    => $min,
            ]);
        }

        // Reponer un poco el stock de los productos originales (llegó mercadería)
        Producto::whereIn('codigo', ['LIB-0001', 'LIB-0002', 'LIB-0003', 'LIB-0005', 'LIB-0008'])->increment('stock', 15);
        Producto::where('codigo', 'UTI-0001')->increment('stock', 150);
        Producto::where('codigo', 'PAP-0001')->increment('stock', 60);
    }

    /* ---------------------------------------------------------------
     *  2. Más clientes (personas y empresas)
     * --------------------------------------------------------------- */
    private function crearClientes(): void
    {
        $clientes = [
            ['DNI', '70123456',    'Andrea',              'Salazar Ríos',     'andrea.salazar@gmail.com',  '987654321', 'Av. Tacna 512, Cercado de Lima'],
            ['DNI', '45678123',    'Kevin',               'Huerta Pinedo',    'kevin.hp@hotmail.com',      '956781234', 'Jr. Huallaga 230, Cercado de Lima'],
            ['DNI', '72345678',    'Milagros',            'Quispe Mamani',    'milagrosqm@gmail.com',      '945612378', 'Av. Perú 1450, San Martín de Porres'],
            ['DNI', '47561234',    'Renzo',               'Castro Delgado',   'renzo.cd@outlook.com',      '932145678', 'Calle Las Begonias 120, San Isidro'],
            ['DNI', '74125896',    'Fiorella',            'Vega Torres',      'fiorevega@gmail.com',       '971234567', 'Av. Angamos 880, Surquillo'],
            ['DNI', '43216789',    'Javier',              'Ccahuana Ruiz',    'jccahuana@yahoo.com',       '918273645', 'Jr. Ancash 345, Rímac'],
            ['CE',  '001234567',   'John',                'Smith Taylor',     'john.smith@mail.com',       '990011223', 'Av. Larco 455, Miraflores'],
            ['RUC', '20512345678', 'Colegio Los Andes SAC',      null,        'logistica@losandes.edu.pe', '014567890', 'Av. Naranjal 1200, Los Olivos'],
            ['RUC', '20487654321', 'Academia Cima EIRL',         null,        'compras@academiacima.pe',   '017894561', 'Av. Venezuela 2150, Breña'],
            ['RUC', '10456789012', 'Bazar y Librería Pérez',     null,        'bazarperez@gmail.com',      '965432178', 'Mercado Central, puesto 45'],
        ];

        foreach ($clientes as [$tipo, $num, $nombres, $apellidos, $email, $tel, $dir]) {
            Cliente::firstOrCreate(['tipo_documento' => $tipo, 'numero_documento' => $num], [
                'nombres'   => $nombres,
                'apellidos' => $apellidos,
                'email'     => $email,
                'telefono'  => $tel,
                'direccion' => $dir,
            ]);
        }
    }

    /* ---------------------------------------------------------------
     *  3. Ventas desde junio hasta hoy
     * --------------------------------------------------------------- */
    private function crearVentas(): void
    {
        $hoy = now();

        // Ventas por mes (crece hacia fin de año: campaña escolar / navidad)
        $plan = [
            [2026,  6, 14],
            [2026,  7, 18],
            [2026,  8, 21],
            [2026,  9, 24],
            [2026, 10, 18],   // octubre ya tiene 10 ventas de VentaSeeder
            [2026, 11, 20],   // noviembre hasta hoy
        ];

        $personas  = Cliente::whereIn('tipo_documento', ['DNI', 'CE'])->pluck('id')->all();
        $empresas  = Cliente::where('tipo_documento', 'RUC')->pluck('id')->all();
        $vendedores = Empleado::whereIn('cargo', ['vendedor', 'cajero', 'administrador'])->pluck('id')->all();

        foreach ($plan as [$anio, $mes, $cantidad]) {
            $inicio = Carbon::create($anio, $mes, 1);
            $ultimoDia = ($anio === (int) $hoy->year && $mes === (int) $hoy->month)
                ? (int) $hoy->day
                : $inicio->daysInMonth;

            for ($i = 0; $i < $cantidad; $i++) {
                $fecha = Carbon::create($anio, $mes, mt_rand(1, $ultimoDia), mt_rand(9, 19), mt_rand(0, 59));
                if ($fecha->greaterThan($hoy)) {
                    $fecha = $hoy->copy()->subMinutes(mt_rand(30, 300));
                }

                $esEmpresa = mt_rand(1, 100) <= 12;
                $clienteId = $esEmpresa ? $empresas[array_rand($empresas)] : $personas[array_rand($personas)];

                $this->crearUnaVenta($fecha, $clienteId, $vendedores[array_rand($vendedores)], $esEmpresa);
            }
        }
    }

    private function crearUnaVenta(Carbon $fecha, int $clienteId, int $empleadoId, bool $esEmpresa): void
    {
        // Productos con stock disponible
        $disponibles = Producto::where('estado', true)->where('stock', '>', 0)->get();
        if ($disponibles->isEmpty()) {
            return;
        }

        // 1 a 3 productos distintos (empresas compran más útiles y papelería)
        $items = $disponibles->shuffle()->take(mt_rand(1, $esEmpresa ? 3 : 2));
        if ($esEmpresa) {
            $items = $items->merge($disponibles->filter(fn ($p) => ! str_starts_with($p->codigo, 'LIB'))->shuffle()->take(1))->unique('id');
        }

        $lineas = [];
        foreach ($items as $producto) {
            $esLibro  = str_starts_with($producto->codigo, 'LIB');
            $cantidad = $esEmpresa
                ? ($esLibro ? mt_rand(2, 6) : mt_rand(10, 40))
                : ($esLibro ? mt_rand(1, 2) : mt_rand(1, 6));
            $cantidad = min($cantidad, $producto->stock);

            if ($cantidad > 0) {
                $lineas[] = [$producto, $cantidad];
            }
        }

        if (empty($lineas)) {
            return;
        }

        $subtotal = round(collect($lineas)->sum(fn ($l) => $l[1] * (float) $l[0]->precio_venta), 2);

        // Descuento: 5% o 10% en compras grandes, a veces
        $descuento = 0.0;
        if ($subtotal >= 150 && mt_rand(1, 100) <= 40) {
            $descuento = round($subtotal * (mt_rand(0, 1) ? 0.05 : 0.10), 2);
        }
        $total = round($subtotal - $descuento, 2);

        // Estado: algunas recientes quedan pendientes, pocas anuladas
        $reciente = $fecha->greaterThan(now()->subDays(10));
        $azar     = mt_rand(1, 100);
        $estado   = match (true) {
            $reciente && $azar <= 30 => 'pendiente',
            $azar <= 4               => 'anulada',
            default                  => 'pagada',
        };

        $tipo  = $esEmpresa ? 'factura' : 'boleta';
        $venta = Venta::create([
            'tipo_comprobante'   => $tipo,
            'numero_comprobante' => Venta::siguienteNumero($tipo),   // se renumera al final
            'cliente_id'         => $clienteId,
            'empleado_id'        => $empleadoId,
            'fecha_venta'        => $fecha,
            'subtotal'           => $subtotal,
            'descuento'          => $descuento,
            'total'              => $total,
            'estado'             => $estado === 'anulada' ? 'anulada' : 'pendiente',
        ]);

        foreach ($lineas as [$producto, $cantidad]) {
            DetalleVenta::create([
                'venta_id'        => $venta->id,
                'producto_id'     => $producto->id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $producto->precio_venta,
                'subtotal'        => round($cantidad * (float) $producto->precio_venta, 2),
            ]);

            // Una venta anulada no descuenta stock
            if ($estado !== 'anulada') {
                $producto->decrement('stock', $cantidad);
            }
        }

        // Pagos
        if ($estado === 'pagada') {
            $this->pagar($venta, $total, $fecha, $esEmpresa);
        } elseif ($estado === 'pendiente' && mt_rand(0, 1)) {
            // pago a cuenta (la mitad)
            $this->pagar($venta, round($total / 2, 2), $fecha, $esEmpresa);
        }
    }

    private function pagar(Venta $venta, float $monto, Carbon $fecha, bool $esEmpresa): void
    {
        $metodo = $esEmpresa
            ? (mt_rand(1, 100) <= 70 ? 'transferencia' : 'tarjeta')
            : $this->metodoPersona();

        Pago::create([   // el modelo Pago actualiza el estado de la venta
            'venta_id'         => $venta->id,
            'metodo_pago'      => $metodo,
            'monto'            => $monto,
            'numero_operacion' => $this->numeroOperacion($metodo),
            'fecha_pago'       => $fecha->copy()->addMinutes(mt_rand(1, 5)),
        ]);
    }

    private function metodoPersona(): string
    {
        $r = mt_rand(1, 100);

        return match (true) {
            $r <= 35 => 'efectivo',
            $r <= 65 => 'yape',
            $r <= 85 => 'tarjeta',
            default  => 'plin',
        };
    }

    private function numeroOperacion(string $metodo): ?string
    {
        return match ($metodo) {
            'efectivo'      => null,
            'yape', 'plin'  => str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'tarjeta'       => (mt_rand(0, 1) ? 'VISA-' : 'MC-') . mt_rand(100000, 999999),
            'transferencia' => 'BCP-' . mt_rand(1000000000, 1999999999),
        };
    }

    /* ---------------------------------------------------------------
     *  4. Renumerar comprobantes en orden de fecha (B001-00000001...)
     * --------------------------------------------------------------- */
    private function renumerarComprobantes(): void
    {
        foreach (['boleta' => 'B', 'factura' => 'F'] as $tipo => $letra) {
            $ventas = Venta::where('tipo_comprobante', $tipo)->orderBy('fecha_venta')->orderBy('id')->get();

            // Paso 1: números temporales (serie 999) para no chocar con el UNIQUE
            foreach ($ventas as $v) {
                $v->updateQuietly(['numero_comprobante' => sprintf('%s999-%08d', $letra, $v->id)]);
            }

            // Paso 2: números definitivos en orden cronológico
            foreach ($ventas->values() as $i => $v) {
                $v->updateQuietly(['numero_comprobante' => sprintf('%s001-%08d', $letra, $i + 1)]);
            }
        }
    }
}
