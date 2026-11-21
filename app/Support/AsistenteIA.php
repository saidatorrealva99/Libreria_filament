<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Empleado;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Asistente con IA (Google Gemini).
 *
 * Idea clave: la IA NO entra a la base de datos. Nosotros armamos un resumen
 * con datos reales (consultas Eloquent) y se lo enviamos junto con la pregunta.
 * Así responde solo con información de la librería y nunca puede modificar nada.
 */
class AsistenteIA
{
    // Modelos a probar en orden (si uno no existe, se usa el siguiente)
    private const MODELOS_RESPALDO = ['gemini-2.5-flash', 'gemini-flash-latest', 'gemini-2.0-flash', 'gemini-2.5-flash-lite'];

    /** Motor configurado: local (por defecto), gemini o auto */
    public static function motor(): string
    {
        $motor = strtolower(trim((string) (config('libreria.ia.motor') ?: env('IA_MOTOR', 'local'))));

        return in_array($motor, ['local', 'gemini', 'auto'], true) ? $motor : 'local';
    }

    /**
     * @param  array<int, array{rol: string, texto: string}>  $historial  conversación previa
     */
    public static function preguntar(string $pregunta, array $historial = [], string $modo = 'panel'): string
    {
        // Motor: 'local' = IA propia (sin internet) · 'gemini' = Google · 'auto' = Gemini y, si falla, la IA propia
        $motor = self::motor();
        $clave = config('libreria.ia.key');

        if ($motor === 'local' || blank($clave)) {
            return IALocal::responder($pregunta, $modo);
        }

        if ($motor === 'auto') {
            try {
                return self::preguntarGemini($pregunta, $historial, $modo);
            } catch (\Throwable) {
                return IALocal::responder($pregunta, $modo);
            }
        }

        return self::preguntarGemini($pregunta, $historial, $modo);
    }

    /** @param  array<int, array{rol: string, texto: string}>  $historial */
    private static function preguntarGemini(string $pregunta, array $historial, string $modo): string
    {
        $sistema = $modo === 'tienda' ? self::instruccionesTienda() : self::instruccionesPanel();

        // Conversación: últimos 8 mensajes + la pregunta nueva
        $contenido = collect($historial)
            ->take(-8)
            ->map(fn ($m) => [
                'role'  => $m['rol'] === 'usuario' ? 'user' : 'model',
                'parts' => [['text' => $m['texto']]],
            ])
            ->push(['role' => 'user', 'parts' => [['text' => $pregunta]]])
            ->values()
            ->all();

        $cuerpo = [
            'system_instruction' => ['parts' => [['text' => $sistema]]],
            'contents'           => $contenido,
            'generationConfig'   => ['temperature' => 0.4, 'maxOutputTokens' => 1024],
        ];

        return self::llamar($cuerpo, 'No pude generar una respuesta. Intenta preguntarlo de otra forma.');
    }

    /**
     * Pide a Gemini una respuesta en JSON (se usa en la Lista escolar).
     *
     * @param  array<int, array>  $partes  textos y/o imágenes: ['text' => …] o ['inline_data' => ['mime_type' => …, 'data' => base64]]
     */
    public static function generarJson(string $sistema, array $partes): array
    {
        if (blank(config('libreria.ia.key'))) {
            throw new RuntimeException('Falta configurar GEMINI_API_KEY en el archivo .env.');
        }

        $texto = self::llamar([
            'system_instruction' => ['parts' => [['text' => $sistema]]],
            'contents'           => [['role' => 'user', 'parts' => $partes]],
            'generationConfig'   => ['temperature' => 0.1, 'maxOutputTokens' => 8192, 'responseMimeType' => 'application/json'],
        ], '{}');

        // Por si el modelo envuelve el JSON en ```json … ```
        $texto = trim(preg_replace('/^```(?:json)?|```$/m', '', $texto));
        $json  = json_decode($texto, true);

        if (! is_array($json)) {
            throw new RuntimeException('La IA no devolvió un formato válido.');
        }

        return $json;
    }

    /** Llamada HTTP a Gemini con modelos de respaldo y mensajes de error claros */
    private static function llamar(array $cuerpo, string $siVacio): string
    {
        $clave = config('libreria.ia.key');
        $modelo  = trim(str_replace('GEMINI_MODEL=', '', (string) config('libreria.ia.modelo')));
        $modelos = array_unique(array_filter([$modelo, ...self::MODELOS_RESPALDO]));

        $ultimoError = null;

        foreach ($modelos as $modelo) {
            // Los modelos 2.5 "piensan" antes de responder: lo apagamos para que sea rápido
            $datos = $cuerpo;
            if (str_contains($modelo, '2.5')) {
                $datos['generationConfig']['thinkingConfig'] = ['thinkingBudget' => 0];
            }

            try {
                $respuesta = Http::connectTimeout(15)->timeout(60)
                    ->acceptJson()
                    ->withHeaders(['x-goog-api-key' => $clave])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent", $datos);
            } catch (ConnectionException $e) {
                $detalle = $e->getMessage();
                if (str_contains($detalle, 'SSL') || str_contains($detalle, 'certificate')) {
                    throw new RuntimeException('Error de certificado SSL en XAMPP. Configura curl.cainfo en php.ini (ver guía).');
                }
                // Tiempo agotado o corte momentáneo → probar el siguiente modelo
                $ultimoError = 'sin conexión: ' . (preg_match('/cURL error \d+: [^(]+/', $detalle, $m) ? trim($m[0]) : mb_substr($detalle, 0, 120));
                continue;
            }

            if ($respuesta->status() === 404) {
                continue;   // ese modelo no existe → probar el siguiente
            }

            if (in_array($respuesta->status(), [500, 502, 503, 504])) {
                $ultimoError = $respuesta->status();   // Google saturado → probar otro modelo
                usleep(400000);
                continue;
            }

            if ($respuesta->status() === 429) {
                throw new RuntimeException('Se alcanzó el límite gratuito de consultas. Espera un minuto e intenta de nuevo.');
            }

            if ($respuesta->status() === 400 && ! str_contains(strtolower($respuesta->body()), 'api key')) {
                continue;   // nombre de modelo mal escrito u otro detalle → probar el siguiente
            }

            if (in_array($respuesta->status(), [400, 401, 403])) {
                throw new RuntimeException('La API key de Gemini no es válida o no tiene permisos.');
            }

            if ($respuesta->failed()) {
                throw new RuntimeException('El servicio de IA no respondió (error ' . $respuesta->status() . ').');
            }

            $texto = collect($respuesta->json('candidates.0.content.parts', []))->pluck('text')->implode('');

            return trim($texto) !== '' ? trim($texto) : $siVacio;
        }

        if (is_string($ultimoError)) {
            throw new RuntimeException('No hay conexión con el servicio de IA (' . $ultimoError . '). Revisa tu internet.');
        }

        if ($ultimoError) {
            throw new RuntimeException('Gemini está saturado en este momento (error ' . $ultimoError . '). Intenta de nuevo en unos segundos.');
        }

        throw new RuntimeException('No se encontró un modelo de Gemini disponible. Revisa GEMINI_MODEL en .env.');
    }

    /* =========================================================
     *  PANEL: asistente del negocio (para el personal)
     * ========================================================= */
    private static function instruccionesPanel(): string
    {
        $datos = json_encode(self::datosNegocio(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $app   = config('app.name');

        return <<<TXT
        Eres el asistente de gestión de la librería "{$app}".
        Ayudas al personal (administrador, vendedores, cajeros y almaceneros) a entender el negocio.

        REGLAS:
        - Responde SIEMPRE en español, claro y breve (máximo 8 líneas o una lista corta).
        - Usa SOLO los datos de abajo. Si algo no está en los datos, dilo con honestidad; no inventes cifras.
        - Montos en soles con el formato S/ 1,234.50.
        - Cuando ayude, da una recomendación práctica (por ejemplo, qué reponer o qué promocionar).
        - No puedes modificar datos ni registrar operaciones: si te lo piden, indica en qué módulo del sistema se hace.
        - Puedes usar **negritas** y listas con guiones.

        DATOS ACTUALES DEL NEGOCIO (consultados ahora de la base de datos):
        {$datos}
        TXT;
    }

    public static function datosNegocio(): array
    {
        $hoy        = now();
        $inicioMes  = $hoy->copy()->startOfMonth();
        $mesPasado  = [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()];
        $hace90     = $hoy->copy()->subDays(90);

        $validas = fn () => Venta::validas();

        $resumenVentas = fn ($q) => ['ventas' => (clone $q)->count(), 'total' => round((float) (clone $q)->sum('total'), 2)];

        // Productos más vendidos (90 días)
        $top = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.fecha_venta', '>=', $hace90)
            ->groupBy('productos.id', 'productos.nombre', 'productos.stock')
            ->selectRaw('productos.nombre, productos.stock, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.subtotal) as importe')
            ->orderByDesc('unidades')
            ->limit(10)
            ->get()
            ->map(fn ($f) => [
                'producto'         => $f->nombre,
                'unidades_90_dias' => (int) $f->unidades,
                'importe'          => round((float) $f->importe, 2),
                'stock_actual'     => (int) $f->stock,
                'dias_de_stock'    => $f->unidades > 0 ? round($f->stock / ($f->unidades / 90)) : null,
            ]);

        // Ventas por categoría (mes actual)
        $porCategoria = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('ventas.estado', '!=', 'anulada')
            ->where('ventas.fecha_venta', '>=', $inicioMes)
            ->groupBy('categorias.id', 'categorias.nombre')
            ->selectRaw('categorias.nombre, SUM(detalle_ventas.subtotal) as importe')
            ->orderByDesc('importe')
            ->get()
            ->mapWithKeys(fn ($f) => [$f->nombre => round((float) $f->importe, 2)]);

        // Ventas por vendedor (mes actual)
        $porVendedor = $validas()->where('fecha_venta', '>=', $inicioMes)
            ->selectRaw('empleado_id, COUNT(*) as n, SUM(total) as total')
            ->groupBy('empleado_id')
            ->get()
            ->map(fn ($f) => [
                'vendedor' => $f->empleado_id ? (Empleado::find($f->empleado_id)?->nombre_completo ?? '—') : 'Tienda online',
                'ventas'   => (int) $f->n,
                'total'    => round((float) $f->total, 2),
            ]);

        // Últimos 6 meses
        $porMes = collect(range(5, 0))->mapWithKeys(function ($i) use ($hoy) {
            $mes = $hoy->copy()->subMonthsNoOverflow($i);

            return [$mes->format('Y-m') => round((float) Venta::validas()
                ->whereBetween('fecha_venta', [$mes->copy()->startOfMonth(), $mes->copy()->endOfMonth()])
                ->sum('total'), 2)];
        });

        return [
            'fecha_actual'         => $hoy->format('d/m/Y H:i'),
            'ventas_hoy'           => $resumenVentas($validas()->whereDate('fecha_venta', $hoy->toDateString())),
            'ventas_ultimos_7_dias' => $resumenVentas($validas()->where('fecha_venta', '>=', $hoy->copy()->subDays(7))),
            'ventas_mes_actual'    => $resumenVentas($validas()->where('fecha_venta', '>=', $inicioMes)),
            'ventas_mes_anterior'  => $resumenVentas($validas()->whereBetween('fecha_venta', $mesPasado)),
            'ingresos_por_mes'     => $porMes,
            'ventas_por_categoria_mes' => $porCategoria,
            'ventas_por_vendedor_mes'  => $porVendedor,
            'cobros_por_metodo_mes' => Pago::whereHas('venta', fn ($q) => $q->validas())
                ->where('fecha_pago', '>=', $inicioMes)
                ->selectRaw('metodo_pago, SUM(monto) as total')
                ->groupBy('metodo_pago')
                ->pluck('total', 'metodo_pago')
                ->map(fn ($t) => round((float) $t, 2)),
            'productos_mas_vendidos_90_dias' => $top,
            'productos_stock_bajo' => Producto::activos()->stockBajo()->orderBy('stock')->get()
                ->map(fn ($p) => ['producto' => $p->nombre, 'stock' => $p->stock, 'minimo' => $p->stock_minimo]),
            'reposicion_sugerida' => \App\Support\Inventario::reponer()->take(10)->map(fn ($f) => [
                'producto'          => $f['producto']->nombre,
                'stock'             => $f['producto']->stock,
                'vendidas_30_dias'  => $f['vendidas'],
                'dias_de_cobertura' => $f['cobertura'],
                'comprar_sugerido'  => $f['sugerido'],
            ])->values(),
            'productos_sin_ventas_90_dias' => Producto::activos()
                ->whereDoesntHave('detalles', fn ($q) => $q->whereHas('venta', fn ($v) => $v->validas()->where('fecha_venta', '>=', $hace90)))
                ->pluck('nombre'),
            'inventario' => [
                'productos_activos' => Producto::activos()->count(),
                'unidades'          => (int) Producto::activos()->sum('stock'),
                'valor_a_precio_venta' => round((float) Producto::activos()->selectRaw('SUM(stock * precio_venta) as v')->value('v'), 2),
            ],
            'ventas_pendientes_de_pago' => Venta::where('estado', 'pendiente')->with('cliente')->latest('fecha_venta')->take(10)->get()
                ->map(fn ($v) => [
                    'comprobante' => $v->numero_comprobante,
                    'pedido_web'  => $v->codigo_pedido,
                    'cliente'     => $v->cliente->nombre_completo,
                    'total'       => (float) $v->total,
                    'saldo'       => $v->saldo(),
                ]),
            'mejores_clientes' => Cliente::withSum(['ventas' => fn ($q) => $q->validas()], 'total')
                ->orderByDesc('ventas_sum_total')->take(5)->get()
                ->map(fn ($c) => ['cliente' => $c->nombre_completo, 'total_comprado' => round((float) $c->ventas_sum_total, 2)]),
            'categorias_activas' => Categoria::where('estado', true)->pluck('nombre'),
        ];
    }

    /* =========================================================
     *  TIENDA: asistente para clientes (recomienda libros)
     * ========================================================= */
    private static function instruccionesTienda(): string
    {
        $catalogo = Producto::activos()
            ->with(['autor', 'categoria'])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $p) => sprintf(
                '[[%d]] %s | %s | %s | S/ %s | %s',
                $p->id,
                $p->nombre,
                $p->autor?->nombre_completo ?? ($p->marca_editorial ?? '—'),
                $p->categoria?->nombre,
                number_format((float) $p->precio_venta, 2),
                $p->stock > 0 ? "stock {$p->stock}" : 'AGOTADO'
            ))
            ->implode("\n");

        $horario = config('libreria.horario');
        $app     = config('app.name');

        return <<<TXT
        Eres "Lucía", la asistente virtual de la librería "{$app}". Atiendes a clientes en la tienda online.

        REGLAS:
        - Responde en español, cálida y breve (máximo 5 líneas).
        - Recomienda SOLO productos del catálogo de abajo. Nunca inventes títulos, precios ni stock.
        - Cuando menciones un producto escribe su código exactamente así: [[ID]] (por ejemplo [[3]]). El sistema lo convierte en un enlace.
        - No recomiendes productos AGOTADOS (puedes decir que está agotado si lo preguntan).
        - Formas de pago: Yape, Plin, pago al recoger o contra entrega. Entrega: recojo gratis en tienda ({$horario}) o delivery con costo según el distrito (ver /info/envios).
        - Para saber el estado de un pedido, indica que use la opción "Mi pedido" del menú con su código y documento.
        - No pidas datos personales ni de tarjetas.

        CATÁLOGO (ID | nombre | autor o marca | categoría | precio | stock):
        {$catalogo}
        TXT;
    }
}
