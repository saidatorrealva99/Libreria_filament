<?php

namespace App\Support;

use App\Models\CierreCaja;
use App\Models\Producto;
use App\Models\ZonaEnvio;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * IA PROPIA de Mundo de Letras (funciona sin internet y sin claves).
 *
 * Cómo "piensa":
 *  1. Normaliza la pregunta (minúsculas, sin tildes ni signos).
 *  2. Detecta la INTENCIÓN comparando con un banco de palabras clave
 *     (tolera faltas de ortografía con distancia de Levenshtein).
 *  3. Si nombra un producto, lo encuentra por similitud de palabras.
 *  4. Consulta la base de datos en ese momento y redacta la respuesta.
 */
class IALocal
{
    /* =========================================================
     *  BANCO DE INTENCIONES — PANEL (personal de la librería)
     * ========================================================= */
    private const PANEL = [
        'saludo'      => ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'hey', 'que tal'],
        'gracias'     => ['gracias', 'muchas gracias', 'genial', 'excelente', 'perfecto', 'chevere'],
        'ayuda'       => ['ayuda', 'que puedes hacer', 'que sabes', 'como funcionas', 'opciones', 'comandos'],
        'ventas_hoy'  => ['hoy', 'dia de hoy', 'ventas de hoy', 'vendimos hoy', 'cuanto vendimos hoy'],
        'ventas_semana' => ['semana', 'ultimos 7 dias', 'esta semana', 'semanal'],
        'ventas_mes'  => ['mes', 'este mes', 'mensual', 'comparado', 'mes anterior', 'mes pasado', 'como van las ventas', 'ventas'],
        'tendencia'   => ['tendencia', 'meses', 'ultimos meses', 'evolucion', 'historial', 'crecimiento', 'grafico'],
        'top'         => ['mas vendidos', 'mas vendido', 'top', 'populares', 'exito', 'best seller', 'estrella', 'mejores productos', 'libros mas vendidos'],
        'reponer'     => ['reponer', 'reposicion', 'comprar', 'pedir', 'abastecer', 'que debo comprar', 'falta mercaderia'],
        'stock_bajo'  => ['stock bajo', 'poco stock', 'se acaba', 'agotado', 'agotados', 'minimo', 'quedan pocos'],
        'sin_ventas'  => ['no se venden', 'no se estan vendiendo', 'sin ventas', 'estancados', 'no rotan', 'baja rotacion', 'muertos'],
        'vendedores'  => ['vendedor', 'vendedora', 'vendedores', 'empleado', 'trabajador', 'quien vendio mas', 'vendio mas', 'ranking'],
        'categorias'  => ['categoria', 'categorias', 'rubro', 'linea', 'por tipo'],
        'pagos'       => ['metodo de pago', 'metodos', 'yape', 'plin', 'efectivo', 'tarjeta', 'cobros', 'como pagan'],
        'pendientes'  => ['pendiente', 'pendientes', 'deben', 'deuda', 'saldo', 'por cobrar', 'sin pagar', 'pedidos web'],
        'clientes'    => ['cliente', 'clientes', 'mejores clientes', 'quien compra mas', 'frecuentes'],
        'inventario'  => ['inventario', 'valor del inventario', 'cuantos productos', 'unidades', 'almacen'],
        'caja'        => ['caja', 'apertura', 'cierre', 'turno', 'cajas abiertas'],
        'resumen'     => ['resumen', 'como esta el negocio', 'como vamos', 'reporte', 'diagnostico', 'recomendacion', 'consejo', 'que me recomiendas'],
        'producto'    => ['precio', 'cuesta', 'cuanto cuesta', 'stock de', 'cuantos quedan', 'hay', 'tenemos', 'existe', 'informacion de'],
    ];

    /* =========================================================
     *  BANCO DE INTENCIONES — TIENDA (clientes)
     * ========================================================= */
    private const TIENDA = [
        'saludo'    => ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'hey', 'que tal'],
        'gracias'   => ['gracias', 'muchas gracias', 'genial', 'perfecto', 'chevere', 'ok gracias'],
        'envio'     => ['delivery', 'envio', 'envian', 'despacho', 'llevan', 'a domicilio', 'distrito', 'costo de envio', 'cuanto demora'],
        'pago'      => ['pago', 'pagar', 'yape', 'plin', 'tarjeta', 'efectivo', 'contra entrega', 'formas de pago'],
        'horario'   => ['horario', 'hora', 'abren', 'cierran', 'atienden', 'direccion', 'donde quedan', 'ubicacion', 'tienda fisica', 'recojo'],
        'pedido'    => ['mi pedido', 'estado', 'seguimiento', 'donde esta mi pedido', 'codigo de pedido', 'llego'],
        'reclamo'   => ['reclamo', 'queja', 'libro de reclamaciones', 'problema', 'devolucion', 'cambio'],
        'ofertas'   => ['oferta', 'ofertas', 'descuento', 'promocion', 'rebaja', 'cupon', 'barato', 'baratos', 'economico'],
        'lista'     => ['lista escolar', 'lista de utiles', 'utiles del colegio', 'lista del colegio'],
        'ayuda'     => ['ayuda', 'que puedes hacer', 'quien eres', 'como funciona'],
    ];

    // Palabras genéricas: suman poco para no "ganarle" a una intención más específica
    private const DEBILES = ['hoy', 'mes', 'este mes', 'ventas', 'semana', 'meses', 'hay', 'tenemos', 'estado', 'hora', 'linea', 'minimo', 'existe'];

    // Palabras que no aportan para buscar productos
    private const VACIAS = ['el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'para', 'por', 'con', 'sin', 'que', 'y', 'o', 'a', 'en',
        'me', 'mi', 'tu', 'su', 'es', 'son', 'hay', 'tienen', 'tienes', 'quiero', 'busco', 'necesito', 'algun', 'alguna', 'algo', 'cual', 'cuales',
        'recomienda', 'recomiendas', 'recomiendame', 'recomendar', 'libro', 'libros', 'producto', 'productos', 'precio', 'cuesta', 'cuanto',
        'stock', 'quedan', 'cuantos', 'tenemos', 'venden', 'vende', 'puedes', 'podrias', 'favor', 'porfa', 'hola', 'ver', 'muestrame', 'dame', 'sobre', 'mas', 'muy', 'menos', 'soles', 'sol', 'hasta', 'maximo', 'presupuesto', 'quisiera', 'gustaria', 'tienda', 'comprar'];

    /* =========================================================
     *  PUNTO DE ENTRADA
     * ========================================================= */
    public static function responder(string $pregunta, string $modo = 'panel'): string
    {
        $texto = self::normalizar($pregunta);

        if ($texto === '') {
            return 'Escríbeme tu pregunta y te ayudo 🙂';
        }

        return $modo === 'tienda' ? self::tienda($texto) : self::panel($texto);
    }

    /* =========================================================
     *  PANEL
     * ========================================================= */
    private static function panel(string $t): string
    {
        $intencion = self::detectar($t, self::PANEL);

        // ¿Nombra un producto concreto? (precio / stock de X)
        if (in_array($intencion, ['producto', null], true) || preg_match('/\b(precio|cuesta|stock de|quedan de|hay de)\b/', $t)) {
            $p = self::buscarProductos($t, 1)->first();
            if ($p) {
                return self::fichaProducto($p, true);
            }
        }

        $d = AsistenteIA::datosNegocio();
        $s = fn ($n) => 'S/ ' . number_format((float) $n, 2);

        return match ($intencion) {
            'saludo'  => "¡Hola! 👋 Soy el **asistente de " . config('app.name') . "**.\n\nPregúntame por ventas, productos más vendidos, qué reponer, vendedores, clientes, caja o el precio y stock de cualquier producto.",
            'gracias' => '¡Con gusto! 😊 Si necesitas otro dato del negocio, aquí estoy.',
            'ayuda'   => self::ayudaPanel(),

            'ventas_hoy' => "**Ventas de hoy ({$d['fecha_actual']}):**\n\n- Ventas: **{$d['ventas_hoy']['ventas']}**\n- Total: **{$s($d['ventas_hoy']['total'])}**"
                . ($d['ventas_hoy']['ventas'] > 0 ? "\n- Ticket promedio: **" . $s($d['ventas_hoy']['total'] / $d['ventas_hoy']['ventas']) . '**' : "\n\nAún no hay ventas registradas hoy."),

            'ventas_semana' => "**Últimos 7 días:**\n\n- Ventas: **{$d['ventas_ultimos_7_dias']['ventas']}**\n- Total: **{$s($d['ventas_ultimos_7_dias']['total'])}**\n- Promedio diario: **" . $s($d['ventas_ultimos_7_dias']['total'] / 7) . '**',

            'ventas_mes' => self::ventasMes($d, $s),
            'tendencia'  => self::tendencia($d, $s),

            'top' => $d['productos_mas_vendidos_90_dias']->isEmpty()
                ? 'Todavía no hay ventas en los últimos 90 días.'
                : "**Top productos más vendidos (90 días):**\n\n" . $d['productos_mas_vendidos_90_dias']->take(5)->values()
                    ->map(fn ($f, $i) => ($i + 1) . ". **{$f['producto']}** — {$f['unidades_90_dias']} und. ({$s($f['importe'])}) · stock {$f['stock_actual']}")
                    ->implode("\n")
                    . "\n\n💡 Mantén buen stock de estos: son los que más ingresos generan.",

            'reponer' => $d['reposicion_sugerida']->isEmpty()
                ? '✅ No hay productos urgentes por reponer. El stock cubre la demanda actual.'
                : "**Reposición sugerida (según ventas de 30 días):**\n\n" . $d['reposicion_sugerida']->take(6)
                    ->map(fn ($f) => "- **{$f['producto']}**: stock {$f['stock']}, cubre ~{$f['dias_de_cobertura']} días → comprar **{$f['comprar_sugerido']} und.**")
                    ->implode("\n")
                    . "\n\nRegístralo en **Compras** para que el stock se actualice solo.",

            'stock_bajo' => $d['productos_stock_bajo']->isEmpty()
                ? '✅ Ningún producto está por debajo de su stock mínimo.'
                : "**Productos con stock bajo ({$d['productos_stock_bajo']->count()}):**\n\n" . $d['productos_stock_bajo']->take(8)
                    ->map(fn ($f) => "- **{$f['producto']}**: {$f['stock']} und. (mínimo {$f['minimo']})")
                    ->implode("\n"),

            'sin_ventas' => collect($d['productos_sin_ventas_90_dias'])->isEmpty()
                ? '✅ Todos los productos tuvieron al menos una venta en los últimos 90 días.'
                : "**Productos sin ventas en 90 días (" . count($d['productos_sin_ventas_90_dias']) . "):**\n\n"
                    . collect($d['productos_sin_ventas_90_dias'])->take(8)->map(fn ($n) => "- {$n}")->implode("\n")
                    . "\n\n💡 Ponles una **oferta** o un **cupón** para moverlos, o ubícalos en vitrina.",

            'vendedores' => $d['ventas_por_vendedor_mes']->isEmpty()
                ? 'Aún no hay ventas este mes.'
                : "**Ventas por vendedor (este mes):**\n\n" . $d['ventas_por_vendedor_mes']->sortByDesc('total')->values()
                    ->map(fn ($f, $i) => ($i === 0 ? '🏆 ' : '- ') . "**{$f['vendedor']}**: {$f['ventas']} ventas · {$s($f['total'])}")
                    ->implode("\n"),

            'categorias' => collect($d['ventas_por_categoria_mes'])->isEmpty()
                ? 'Aún no hay ventas por categoría este mes.'
                : "**Ventas por categoría (este mes):**\n\n" . collect($d['ventas_por_categoria_mes'])
                    ->map(fn ($v, $k) => "- **{$k}**: {$s($v)}")->implode("\n"),

            'pagos' => collect($d['cobros_por_metodo_mes'])->isEmpty()
                ? 'Aún no hay cobros registrados este mes.'
                : "**Cobros por método de pago (este mes):**\n\n" . collect($d['cobros_por_metodo_mes'])->sortDesc()
                    ->map(fn ($v, $k) => '- **' . ucfirst($k) . "**: {$s($v)}")->implode("\n"),

            'pendientes' => $d['ventas_pendientes_de_pago']->isEmpty()
                ? '✅ No hay ventas ni pedidos pendientes de pago.'
                : "**Pendientes de pago ({$d['ventas_pendientes_de_pago']->count()}):**\n\n" . $d['ventas_pendientes_de_pago']
                    ->map(fn ($f) => '- ' . ($f['pedido_web'] ? "Pedido web **{$f['pedido_web']}**" : "**{$f['comprobante']}**") . " — {$f['cliente']} · saldo {$s($f['saldo'])}")
                    ->implode("\n"),

            'clientes' => "**Mejores clientes (total comprado):**\n\n" . $d['mejores_clientes']->values()
                ->map(fn ($f, $i) => ($i + 1) . ". **{$f['cliente']}** — {$s($f['total_comprado'])}")->implode("\n")
                . "\n\n💡 Puedes enviarles un **cupón** de agradecimiento.",

            'inventario' => "**Inventario actual:**\n\n- Productos activos: **{$d['inventario']['productos_activos']}**\n- Unidades en stock: **{$d['inventario']['unidades']}**\n- Valor a precio de venta: **{$s($d['inventario']['valor_a_precio_venta'])}**\n- Con stock bajo: **{$d['productos_stock_bajo']->count()}**",

            'caja'    => self::caja($s),
            'resumen' => self::resumen($d, $s),

            default => "No estoy seguro de haber entendido 🤔.\n\n" . self::ayudaPanel(),
        };
    }

    private static function ventasMes(array $d, \Closure $s): string
    {
        $act = $d['ventas_mes_actual'];
        $ant = $d['ventas_mes_anterior'];
        $var = $ant['total'] > 0 ? round(($act['total'] - $ant['total']) / $ant['total'] * 100, 1) : null;

        $txt = "**Ventas del mes:**\n\n- Este mes: **{$act['ventas']} ventas · {$s($act['total'])}**\n- Mes anterior: {$ant['ventas']} ventas · {$s($ant['total'])}";

        if ($var !== null) {
            $txt .= "\n- Variación: **" . ($var >= 0 ? "▲ +{$var}%" : "▼ {$var}%") . '**';
            $txt .= $var >= 0
                ? "\n\n📈 Vamos mejor que el mes pasado. ¡Sigue así!"
                : "\n\n📉 Vamos por debajo del mes pasado (el mes aún no termina). 💡 Activa ofertas o cupones en los productos estrella.";
        }

        return $txt;
    }

    private static function tendencia(array $d, \Closure $s): string
    {
        $meses = collect($d['ingresos_por_mes']);
        $max   = max(1, $meses->max());
        $nom   = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];

        $lineas = $meses->map(function ($v, $k) use ($max, $nom, $s) {
            $barra = str_repeat('█', (int) round($v / $max * 12)) ?: '·';

            return '- **' . $nom[substr($k, 5, 2)] . ' ' . substr($k, 0, 4) . "**: {$barra} {$s($v)}";
        })->implode("\n");

        $mejor = $meses->sortDesc()->keys()->first();

        return "**Ingresos de los últimos 6 meses:**\n\n{$lineas}\n\n🏆 Mejor mes: **" . $nom[substr($mejor, 5, 2)] . ' ' . substr($mejor, 0, 4) . '**.';
    }

    private static function caja(\Closure $s): string
    {
        $cajas = CierreCaja::with('abrio')->whereDate('fecha', today())->get();

        if ($cajas->isEmpty()) {
            return 'Hoy todavía **no se ha abierto ninguna caja**. La vendedora la abre al ingresar (fondo inicial).';
        }

        return "**Cajas de hoy:**\n\n" . $cajas->map(function (CierreCaja $c) use ($s) {
            $total = $c->resumen()['total'];

            return '- **' . ($c->abrio?->name ?? 'Usuario') . '**: ' . ($c->estado === 'abierta' ? '🟢 abierta' : '🔒 cerrada') . " · cobrado {$s($total)}";
        })->implode("\n");
    }

    private static function resumen(array $d, \Closure $s): string
    {
        $act = $d['ventas_mes_actual'];
        $ant = $d['ventas_mes_anterior'];
        $top = $d['productos_mas_vendidos_90_dias']->first();
        $recs = [];

        if ($d['reposicion_sugerida']->isNotEmpty()) {
            $recs[] = 'Reponer **' . $d['reposicion_sugerida']->first()['producto'] . '** (se está acabando y se vende bien).';
        }
        if (count($d['productos_sin_ventas_90_dias']) > 0) {
            $recs[] = 'Poner en oferta los **' . count($d['productos_sin_ventas_90_dias']) . ' productos sin ventas** en 90 días.';
        }
        if ($d['ventas_pendientes_de_pago']->isNotEmpty()) {
            $recs[] = 'Cobrar las **' . $d['ventas_pendientes_de_pago']->count() . ' ventas pendientes**.';
        }
        if (! $recs) {
            $recs[] = 'Todo en orden: mantén el stock de los productos estrella.';
        }

        return "**Resumen del negocio:**\n\n"
            . "- Hoy: {$d['ventas_hoy']['ventas']} ventas · {$s($d['ventas_hoy']['total'])}\n"
            . "- Este mes: {$act['ventas']} ventas · **{$s($act['total'])}** (mes anterior {$s($ant['total'])})\n"
            . ($top ? "- Producto estrella: **{$top['producto']}** ({$top['unidades_90_dias']} und. en 90 días)\n" : '')
            . "- Stock bajo: {$d['productos_stock_bajo']->count()} productos\n\n"
            . "**Mis recomendaciones:**\n\n" . collect($recs)->map(fn ($r) => "- {$r}")->implode("\n");
    }

    private static function ayudaPanel(): string
    {
        return "Puedo responderte, por ejemplo:\n\n"
            . "- ¿Cuánto vendimos hoy? / ¿Cómo van las ventas este mes?\n"
            . "- ¿Cuáles son los más vendidos?\n"
            . "- ¿Qué debo reponer? / ¿Qué tiene stock bajo?\n"
            . "- ¿Qué productos no se venden?\n"
            . "- ¿Qué vendedor vendió más?\n"
            . "- ¿Hay ventas pendientes de pago?\n"
            . "- ¿Cuánto cuesta el cuaderno Standford?\n"
            . "- Dame un resumen del negocio";
    }

    /* =========================================================
     *  TIENDA (Lucía, asistente para clientes)
     * ========================================================= */
    private static function tienda(string $t): string
    {
        $intencion = self::detectar($t, self::TIENDA);
        $tel = config('libreria.telefono');

        switch ($intencion) {
            case 'saludo':
                // "hola, busco un cuaderno" → si además pide algo, lo buscamos
                if (str_word_count($t) <= 3) {
                    return "¡Hola! 👋 Soy **Lucía**, tu asistente de " . config('app.name') . ".\n\nDime qué buscas (un libro, cuadernos, colores, un regalo…) y tu presupuesto, y te recomiendo opciones.";
                }
                break;

            case 'gracias':
                return '¡Gracias a ti! 😊 Si necesitas algo más, aquí estoy.';

            case 'ayuda':
                return "Soy **Lucía** 🤖, la asistente de la tienda.\n\nPuedo recomendarte libros y útiles, decirte precios y stock, explicarte el delivery, las formas de pago y cómo ver tu pedido.";

            case 'envio':
                $zonas = ZonaEnvio::where('estado', true)->orderBy('costo')->get();
                $lista = $zonas->take(6)->map(fn ($z) => "- {$z->distrito}: S/ " . number_format((float) $z->costo, 2) . " ({$z->tiempo})")->implode("\n");

                return "🛵 **Delivery** con costo según el distrito:\n\n{$lista}"
                    . ($zonas->count() > 6 ? "\n- …y " . ($zonas->count() - 6) . ' distritos más.' : '')
                    . "\n\n🏪 También puedes **recoger gratis en tienda**.";

            case 'pago':
                return "💳 Aceptamos **Yape, Plin**, pago al **recoger en tienda** o **contra entrega** (delivery).\n\nNunca te pediremos datos de tarjeta por chat.";

            case 'horario':
                return "🏪 Atendemos **" . config('libreria.horario') . "**.\n\n📍 " . config('libreria.direccion') . "\n📞 {$tel}\n\nEl **recojo en tienda es gratis**.";

            case 'pedido':
                return "📦 Para ver tu pedido, entra a **Mi pedido** (menú de arriba) e ingresa tu **código de pedido** y tu **documento**.\n\nAhí verás si está pendiente, listo para recoger o entregado.";

            case 'reclamo':
                return "Lamentamos el inconveniente 😔.\n\nPuedes registrarlo en nuestro **Libro de Reclamaciones** (al pie de la página). Te daremos un código para seguirlo y respondemos en máximo 15 días hábiles.\n\nO llámanos al {$tel}.";

            case 'lista':
                return "✨ Usa nuestra **Lista escolar**: pega o fotografía la lista del colegio y te armamos el carrito con todos los útiles en segundos.\n\nLa encuentras en el menú de arriba.";

            case 'ofertas':
                // "cuadernos baratos" → buscar cuadernos (no solo ofertas)
                if (self::buscarProductos($t, 1)->isNotEmpty()) {
                    return self::recomendar($t);
                }
                $ofertas = Producto::activos()->where('stock', '>', 0)->get()->filter(fn (Producto $p) => $p->enOferta())->take(4);
                if ($ofertas->isNotEmpty()) {
                    return "🔥 **Ofertas de hoy:**\n\n" . $ofertas->map(fn (Producto $p) => "- [[{$p->id}]] a **S/ " . number_format((float) $p->precioActual(), 2) . '** (-' . $p->porcentajeOferta() . '%)')->implode("\n")
                        . "\n\nAdemás, algunos productos tienen **descuento por cantidad**.";
                }
                $baratos = Producto::activos()->where('stock', '>', 0)->orderBy('precio_venta')->take(4)->get();

                return "Ahora no tenemos ofertas activas, pero estas son opciones **económicas**:\n\n" . $baratos->map(fn ($p) => "- [[{$p->id}]] S/ " . number_format((float) $p->precioActual(), 2))->implode("\n");
        }

        // Recomendación / búsqueda en el catálogo
        return self::recomendar($t);
    }

    private static function recomendar(string $t): string
    {
        // Presupuesto: "menos de 30", "hasta 20 soles", "s/ 15"
        $tope = null;
        if (preg_match('/(?:menos de|hasta|maximo|no mas de|s\/|presupuesto de?)\s*(\d+(?:[.,]\d+)?)/', $t, $m)) {
            $tope = (float) str_replace(',', '.', $m[1]);
        }

        // Pistas de categoría según palabras comunes
        $pistas = [
            'Infantil'           => ['nino', 'nina', 'ninos', 'hijo', 'hija', 'infantil', 'cuento para', 'pequeno', 'bebe', 'sobrino'],
            'Útiles escolares'   => ['colegio', 'escolar', 'utiles', 'cuaderno', 'lapicero', 'lapiz', 'borrador', 'regla', 'tajador'],
            'Arte y manualidades'=> ['colores', 'plumon', 'tempera', 'pintar', 'dibujar', 'arte', 'manualidad'],
            'Novela'             => ['novela', 'leer', 'historia de amor', 'ficcion', 'entretenido'],
            'Literatura peruana' => ['peruano', 'peruana', 'peru', 'vargas llosa', 'arguedas', 'vallejo', 'palma'],
            'Informática'        => ['programacion', 'programar', 'informatica', 'computacion', 'sistemas', 'base de datos', 'python', 'java'],
            'Matemáticas'        => ['matematica', 'algebra', 'calculo', 'estadistica'],
            'Historia'           => ['historia', 'incas', 'independencia'],
            'Poesía'             => ['poesia', 'poemas', 'versos'],
            'Papelería'          => ['hojas', 'folder', 'archivador', 'papel', 'oficina'],
        ];

        $categoria = null;
        foreach ($pistas as $cat => $palabras) {
            foreach ($palabras as $p) {
                if (str_contains($t, $p)) {
                    $categoria = $cat;
                    break 2;
                }
            }
        }

        $encontrados = self::buscarProductos($t, 4, $tope);

        // Si no hubo coincidencia por nombre, usar la categoría detectada
        if ($encontrados->isEmpty() && $categoria) {
            $encontrados = Producto::activos()->where('stock', '>', 0)
                ->whereHas('categoria', fn ($q) => $q->where('nombre', $categoria))
                ->when($tope, fn ($q) => $q->where('precio_venta', '<=', $tope))
                ->inRandomOrder()->take(4)->get();
        }

        // ¿Es regalo? → los más vendidos
        if ($encontrados->isEmpty() && preg_match('/\b(regalo|regalar|cumpleanos|sorpresa|recomienda|recomiendame|sugerencia)\b/', $t)) {
            $encontrados = Producto::activos()->where('stock', '>', 0)
                ->when($tope, fn ($q) => $q->where('precio_venta', '<=', $tope))
                ->withCount('detalles')->orderByDesc('detalles_count')->take(4)->get();
        }

        if ($encontrados->isEmpty()) {
            return "No encontré eso en el catálogo 😕.\n\nPrueba con otras palabras (por ejemplo: *novela*, *cuaderno A4*, *colores*, *libro para niños*) o dime tu presupuesto."
                . "\n\nTambién puedes escribirnos al " . config('libreria.telefono') . '.';
        }

        if ($encontrados->count() === 1) {
            return self::fichaProducto($encontrados->first(), false);
        }

        $intro = $tope ? 'Opciones de hasta **S/ ' . number_format($tope, 2) . '**:' : '¡Mira estas opciones! 📚';

        return "{$intro}\n\n" . $encontrados->map(function (Producto $p) {
            $precio = 'S/ ' . number_format((float) $p->precioActual(), 2);
            $extra  = $p->enOferta() ? ' 🔥 oferta' : '';

            return $p->stock > 0 ? "- [[{$p->id}]] — **{$precio}**{$extra}" : "- [[{$p->id}]] — agotado por ahora";
        })->implode("\n") . "\n\n¿Quieres que te recomiende algo más?";
    }

    /* =========================================================
     *  UTILIDADES
     * ========================================================= */

    /** Ficha de un producto (panel con nombre en texto, tienda con [[ID]]) */
    private static function fichaProducto(Producto $p, bool $panel): string
    {
        $nombre = $panel ? "**{$p->nombre}**" : "[[{$p->id}]]";
        $precio = 'S/ ' . number_format((float) $p->precioActual(), 2);
        $lineas = ["{$nombre}", "- Precio: **{$precio}**" . ($p->enOferta() ? ' 🔥 (antes S/ ' . number_format((float) $p->precio_venta, 2) . ')' : '')];

        if ($p->tieneDescuentoPorCantidad()) {
            $lineas[] = '- ' . $p->textoPorMayor();
        }

        if ($panel) {
            $lineas[] = "- Stock: **{$p->stock} und.** (mínimo {$p->stock_minimo})" . ($p->stock <= $p->stock_minimo ? ' ⚠️ reponer' : '');
            $lineas[] = '- Categoría: ' . ($p->categoria?->nombre ?? '—');
            if ($p->codigo) {
                $lineas[] = "- Código: {$p->codigo}";
            }
        } else {
            $lineas[] = $p->stock > 0
                ? ($p->stock <= 5 ? "- ⚠️ ¡Quedan solo **{$p->stock}**!" : '- ✅ Disponible')
                : '- ❌ Agotado por ahora';
            $lineas[] = "\nPuedes agregarlo al carrito desde su ficha 🛒";
        }

        return implode("\n", $lineas);
    }

    /** Busca productos por similitud de palabras (nombre, autor, marca, categoría) */
    private static function buscarProductos(string $t, int $max, ?float $tope = null): Collection
    {
        $palabras = collect(explode(' ', $t))
            ->filter(fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, self::VACIAS, true))
            ->values();

        if ($palabras->isEmpty()) {
            return collect();
        }

        return Producto::activos()->with(['autor', 'categoria'])
            ->when($tope, fn ($q) => $q->where('precio_venta', '<=', $tope))
            ->get()
            ->map(function (Producto $p) use ($palabras) {
                $campo = self::normalizar(implode(' ', [$p->nombre, $p->codigo, $p->marca_editorial, $p->autor?->nombre_completo, $p->categoria?->nombre]));
                $tokens = explode(' ', $campo);
                $puntos = 0;

                foreach ($palabras as $w) {
                    foreach ($tokens as $tk) {
                        if ($tk === $w) {
                            $puntos += 3;
                            break;
                        }
                        if (mb_strlen($tk) >= 4 && (str_starts_with($tk, $w) || str_starts_with($w, $tk))) {
                            $puntos += 2;
                            break;
                        }
                        if (mb_strlen($w) >= 5 && mb_strlen($tk) >= 5 && levenshtein($w, $tk) <= (mb_strlen($w) >= 7 ? 2 : 1)) {
                            $puntos += 2;   // tolera faltas de ortografía: "cuadreno" ≈ "cuaderno"
                            break;
                        }
                    }
                }

                $p->setAttribute('puntaje_ia', $puntos + ($p->stock > 0 ? 0.5 : 0));

                return $p;
            })
            ->filter(fn ($p) => $p->puntaje_ia >= 2.5)
            ->sortByDesc('puntaje_ia')
            ->take($max)
            ->values();
    }

    /** Elige la intención con más coincidencias (frases largas pesan más) */
    private static function detectar(string $t, array $banco): ?string
    {
        $mejor = null;
        $max   = 0;
        $palabras = explode(' ', $t);

        foreach ($banco as $intencion => $claves) {
            $puntos = 0;
            foreach ($claves as $clave) {
                if (preg_match('/\b' . preg_quote($clave, '/') . '\b/', $t)) {
                    $puntos += in_array($clave, self::DEBILES, true)
                        ? 0.4
                        : 1 + substr_count($clave, ' ');   // frases de varias palabras valen más
                } elseif (! str_contains($clave, ' ') && mb_strlen($clave) >= 6) {
                    foreach ($palabras as $w) {
                        if (mb_strlen($w) >= 5 && levenshtein($w, $clave) <= (mb_strlen($clave) >= 8 ? 2 : 1)) {
                            $puntos += 0.8;   // palabra mal escrita
                            break;
                        }
                    }
                }
            }
            if ($puntos > $max) {
                $max   = $puntos;
                $mejor = $intencion;
            }
        }

        return $mejor;
    }

    /** minúsculas, sin tildes ni signos */
    public static function normalizar(string $texto): string
    {
        $texto = Str::lower(Str::ascii($texto));
        $texto = preg_replace('/[^a-z0-9\/.,\s]/', ' ', $texto);
        $texto = preg_replace('/(?<!\d)[.,]|[.,](?!\d)/', ' ', $texto);

        return trim(preg_replace('/\s+/', ' ', $texto));
    }
}
