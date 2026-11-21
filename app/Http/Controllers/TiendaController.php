<?php

namespace App\Http\Controllers;

use App\Filament\Resources\ClienteResource;
use App\Filament\Resources\VentaResource;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use App\Support\Carrito;
use App\Support\Reglas;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tienda online: lo que ve y hace el CLIENTE.
 * El personal atiende los pedidos desde el panel (/admin → Ventas → Pedidos web).
 */
class TiendaController extends Controller
{
    /* =========================================================
     *  PORTADA
     * ========================================================= */
    public function inicio()
    {
        // Más vendidos (ventas no anuladas)
        $masVendidos = Producto::activos()
            ->where('stock', '>', 0)
            ->with(['autor', 'categoria'])
            ->withSum(['detalles as vendidos' => fn ($q) => $q->whereHas('venta', fn ($v) => $v->validas())], 'cantidad')
            ->orderByDesc('vendidos')
            ->take(8)
            ->get();

        return view('welcome', [
            'novedades'   => Producto::activos()->whereNotNull('autor_id')->with(['autor', 'categoria'])->latest('id')->take(4)->get(),
            'masVendidos' => $masVendidos,
            'utiles'      => Producto::activos()->whereNull('autor_id')->where('stock', '>', 0)
                ->with('categoria')->inRandomOrder()->take(4)->get(),
            'categorias'  => Categoria::where('estado', true)->withCount(['productos' => fn ($q) => $q->activos()])->orderBy('nombre')->get(),
            'totales'     => [
                'libros'     => Producto::activos()->count(),
                'categorias' => Categoria::where('estado', true)->count(),
                'clientes'   => Cliente::count(),
            ],
        ]);
    }

    /* =========================================================
     *  CATÁLOGO Y PRODUCTO
     * ========================================================= */
    public function catalogo(Request $request)
    {
        $q         = trim((string) $request->query('q'));
        $categoria = $request->integer('categoria') ?: null;
        $orden     = $request->query('orden', 'recientes');
        $stock     = $request->boolean('disponibles');

        $productos = Producto::activos()
            ->with(['autor', 'categoria'])
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$q}%")
                ->orWhere('marca_editorial', 'like', "%{$q}%")
                ->orWhere('isbn', 'like', "%{$q}%")
                ->orWhereHas('autor', fn ($a) => $a->where('nombres', 'like', "%{$q}%")->orWhere('apellidos', 'like', "%{$q}%"))))
            ->when($categoria, fn ($query) => $query->where('categoria_id', $categoria))
            ->when($stock, fn ($query) => $query->where('stock', '>', 0))
            ->when($orden === 'vendidos', fn ($query) => $query
                ->withSum(['detalles as vendidos' => fn ($q) => $q->whereHas('venta', fn ($v) => $v->validas())], 'cantidad')
                ->orderByDesc('vendidos'))
            ->when($orden === 'precio_asc', fn ($query) => $query->orderBy('precio_venta'))
            ->when($orden === 'precio_desc', fn ($query) => $query->orderByDesc('precio_venta'))
            ->when($orden === 'nombre', fn ($query) => $query->orderBy('nombre'))
            ->when($orden === 'recientes', fn ($query) => $query->latest('id'))
            ->paginate(12)
            ->withQueryString();

        return view('tienda.catalogo', [
            'productos'  => $productos,
            'categorias' => Categoria::where('estado', true)->orderBy('nombre')->get(),
            'q'          => $q,
            'categoria'  => $categoria,
            'orden'      => $orden,
            'disponibles' => $stock,
        ]);
    }

    public function producto(Producto $producto)
    {
        abort_unless($producto->estado, 404);

        return view('tienda.producto', [
            'producto'     => $producto->load(['autor', 'categoria']),
            'relacionados' => Producto::activos()
                ->where('categoria_id', $producto->categoria_id)
                ->whereKeyNot($producto->id)
                ->with(['autor', 'categoria'])
                ->inRandomOrder()
                ->take(4)
                ->get(),
        ]);
    }

    /* =========================================================
     *  CARRITO
     * ========================================================= */
    public function carrito()
    {
        return view('tienda.carrito', [
            'items'     => Carrito::items(),
            'total'     => Carrito::total(),
            'cupon'     => Carrito::cupon(),
            'descuento' => Carrito::descuento(),
        ]);
    }

    public function aplicarCupon(Request $request)
    {
        $request->validate(['cupon' => ['required', 'string', 'max:20']]);

        if ($error = Carrito::aplicarCupon($request->input('cupon'))) {
            return back()->with('error', $error);
        }

        return back()->with('ok', '¡Cupón aplicado! ' . Carrito::cupon()?->etiqueta());
    }

    public function quitarCupon()
    {
        session()->forget('cupon');

        return back()->with('ok', 'Quitamos el cupón.');
    }

    public function agregar(Request $request)
    {
        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad'    => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $producto = Producto::findOrFail($data['producto_id']);

        if (! $producto->estado || $producto->stock < 1) {
            return back()->with('error', "\"{$producto->nombre}\" está agotado.");
        }

        Carrito::agregar($producto, (int) ($data['cantidad'] ?? 1));

        return back()->with('ok', "\"{$producto->nombre}\" se agregó al carrito.");
    }

    public function actualizar(Request $request)
    {
        foreach ((array) $request->input('cantidades', []) as $id => $cantidad) {
            Carrito::actualizar((int) $id, (int) $cantidad);
        }

        return redirect()->route('tienda.carrito')->with('ok', 'Carrito actualizado.');
    }

    public function quitar(Producto $producto)
    {
        Carrito::quitar($producto->id);

        return redirect()->route('tienda.carrito')->with('ok', 'Producto quitado del carrito.');
    }

    /* =========================================================
     *  CHECKOUT (datos + pago)
     * ========================================================= */
    public function checkout()
    {
        $items = Carrito::items();

        if ($items->isEmpty()) {
            return redirect()->route('tienda.catalogo')->with('error', 'Tu carrito está vacío.');
        }

        return view('tienda.checkout', [
            'items'  => $items,
            'total'  => Carrito::total(),
            'cupon'     => Carrito::cupon(),
            'descuento' => Carrito::descuento(),
            'ahorro'    => Carrito::ahorro(),
            'zonas'     => \App\Models\ZonaEnvio::activas()->get(),
            'pago'   => config('libreria.pago_movil'),
        ]);
    }

    public function confirmar(Request $request)
    {
        $items = Carrito::items();

        if ($items->isEmpty()) {
            return redirect()->route('tienda.catalogo')->with('error', 'Tu carrito está vacío.');
        }

        $tipo    = $request->input('tipo_documento');
        $formato = ClienteResource::FORMATOS[$tipo] ?? ClienteResource::FORMATOS['DNI'];

        // Mismas reglas que la BD (CHECK) y que el panel
        $data = $request->validate([
            'tipo_documento'   => ['required', Rule::in(['DNI', 'RUC', 'CE'])],
            'numero_documento' => ['required', 'string', 'regex:' . $formato['regex']],
            'nombres'          => ['required', 'string', 'max:100', 'regex:' . ($tipo === 'RUC' ? Reglas::RAZON : Reglas::NOMBRE)],
            'apellidos'        => [Rule::requiredIf($tipo !== 'RUC'), 'nullable', 'string', 'max:100', 'regex:' . Reglas::NOMBRE],
            'email'            => ['nullable', 'email', 'max:120', 'regex:' . Reglas::EMAIL],
            'telefono'         => ['required', 'regex:/^[0-9]{7,9}$/'],
            'metodo'           => ['required', Rule::in(array_keys(Venta::METODOS_WEB))],
            'operacion'        => [Rule::requiredIf(in_array($request->input('metodo'), ['yape', 'plin'])), 'nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'acepta_terminos'  => ['accepted'],
            'tipo_entrega'       => ['required', Rule::in(['recojo', 'delivery'])],
            'zona_envio_id'      => ['required_if:tipo_entrega,delivery', 'nullable', Rule::exists('zonas_envio', 'id')->where('estado', true)],
            'direccion_entrega'  => ['required_if:tipo_entrega,delivery', 'nullable', 'string', 'min:8', 'max:200'],
            'referencia_entrega' => ['nullable', 'string', 'max:200'],
        ], [
            'zona_envio_id.required_if'     => 'Elige tu distrito para el delivery.',
            'direccion_entrega.required_if' => 'Escribe la dirección de entrega.',
            'direccion_entrega.min'         => 'Escribe la dirección completa (calle, número, dpto.).',
            'acepta_terminos.accepted' => 'Debes aceptar los términos y la política de privacidad.',
            'numero_documento.regex' => $formato['mensaje'],
            'telefono.regex'         => 'El teléfono debe tener de 7 a 9 dígitos.',
            'operacion.required'     => 'Escribe el número de operación que aparece en tu app.',
            'operacion.regex'        => 'El número de operación solo puede tener letras y números.',
            'apellidos.required'     => 'Escribe tus apellidos.',
            'nombres.regex'          => $tipo === 'RUC' ? Reglas::MSG_RAZON : Reglas::MSG_NOMBRE,
            'apellidos.regex'        => Reglas::MSG_NOMBRE,
            'email.regex'            => Reglas::MSG_EMAIL,
            'email.email'            => Reglas::MSG_EMAIL,
        ]);

        $codigoCupon = Carrito::cupon()?->codigo;

        $venta = DB::transaction(function () use ($data, $items, $codigoCupon) {
            // 1. Cliente: si ya existe (mismo documento) se reutiliza
            $cliente = Cliente::firstOrNew([
                'tipo_documento'   => $data['tipo_documento'],
                'numero_documento' => $data['numero_documento'],
            ]);

            if (! $cliente->exists) {
                $emailLibre = $data['email'] && ! Cliente::where('email', $data['email'])->exists();

                $cliente->fill([
                    'nombres'   => $data['nombres'],
                    'apellidos' => $data['apellidos'] ?? null,
                    'email'     => $emailLibre ? $data['email'] : null,
                    'telefono'  => $data['telefono'],
                ])->save();
            }

            // 2. Venta (pedido web) con precios de la BD
            $subtotal = round($items->sum('importe'), 2);
            $tipoComp = $cliente->tipo_documento === 'RUC' ? 'factura' : 'boleta';

            // Cupón: se vuelve a validar con bloqueo (para no pasar el límite de usos)
            $cupon     = $codigoCupon ? \App\Models\Cupon::where('codigo', $codigoCupon)->lockForUpdate()->first() : null;
            $cupon     = $cupon && $cupon->motivoInvalido($subtotal) === null ? $cupon : null;
            $descuento = $cupon ? $cupon->descuentoPara($subtotal) : 0;

            // Entrega: recojo gratis o delivery con el costo del distrito (precio de la BD)
            $delivery = $data['tipo_entrega'] === 'delivery';
            $zona     = $delivery ? \App\Models\ZonaEnvio::activas()->findOrFail($data['zona_envio_id']) : null;
            $envio    = $zona ? (float) $zona->costo : 0;

            $venta = Venta::create([
                'tipo_comprobante'    => $tipoComp,
                'numero_comprobante'  => Venta::siguienteNumero($tipoComp),
                'canal'               => 'web',
                'codigo_pedido'       => Venta::nuevoCodigoPedido(),
                'cliente_id'          => $cliente->id,
                'empleado_id'         => null,
                'fecha_venta'         => now(),
                'subtotal'            => $subtotal,
                'descuento'           => $descuento,
                'cupon_id'            => $cupon?->id,
                'costo_envio'         => $envio,
                'total'               => round($subtotal - $descuento + $envio, 2),
                'tipo_entrega'        => $data['tipo_entrega'],
                'zona_envio_id'       => $zona?->id,
                'direccion_entrega'   => $delivery ? trim($data['direccion_entrega']) : null,
                'referencia_entrega'  => $delivery ? ($data['referencia_entrega'] ?? null) : null,
                'estado'              => 'pendiente',
                'metodo_declarado'    => $data['metodo'],
                'operacion_declarada' => $data['operacion'] ?? null,
            ]);

            foreach ($items as $item) {
                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $item->producto->id,
                    'cantidad'        => $item->cantidad,
                    'precio_unitario' => $item->precio,
                    'subtotal'        => $item->importe,
                ]);
            }

            $cupon?->increment('usos');

            // 3. Reserva el stock (queda en el kárdex). Si algo se agotó, se deshace todo.
            if ($agotado = $venta->descontarStock()) {
                throw ValidationException::withMessages([
                    'carrito' => "Lo sentimos, \"{$agotado}\" ya no tiene stock suficiente. Revisa tu carrito.",
                ]);
            }

            return $venta;
        });

        Carrito::vaciar();
        $this->avisarAlPersonal($venta);

        return redirect()
            ->route('tienda.pedido', ['codigo' => $venta->codigo_pedido, 'documento' => $venta->cliente->numero_documento])
            ->with('nuevo', true);
    }

    /** Campanita en el panel para administradores y cajeros. */
    private function avisarAlPersonal(Venta $venta): void
    {
        $destinatarios = User::where('activo', true)
            ->whereHas('rol', fn ($q) => $q->whereIn('nombre', [Rol::ADMIN, Rol::CAJERO]))
            ->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        Notification::make()
            ->title("Nuevo pedido web {$venta->codigo_pedido}")
            ->body($venta->cliente->nombre_completo . ' · S/ ' . number_format((float) $venta->total, 2)
                . ' · ' . (Venta::METODOS_WEB[$venta->metodo_declarado] ?? ''))
            ->icon('heroicon-o-shopping-bag')
            ->iconColor('info')
            ->actions([
                NotificationAction::make('ver')
                    ->label('Ver pedido')
                    ->url(VentaResource::getUrl('view', ['record' => $venta], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($destinatarios);
    }

    /* =========================================================
     *  PÁGINAS INFORMATIVAS
     * ========================================================= */
    public function pagina(string $pagina)
    {
        return view('tienda.pagina', ['pagina' => $pagina]);
    }

    /* =========================================================
     *  SEGUIMIENTO DEL PEDIDO
     * ========================================================= */
    public function seguimiento()
    {
        return view('tienda.seguimiento');
    }

    public function buscarPedido(Request $request)
    {
        $data = $request->validate([
            'codigo'    => ['required', 'string', 'max:12'],
            'documento' => ['required', 'string', 'max:12'],
        ]);

        return redirect()->route('tienda.pedido', [
            'codigo'    => strtoupper(trim($data['codigo'])),
            'documento' => trim($data['documento']),
        ]);
    }

    public function pedido(Request $request, string $codigo)
    {
        $venta = $this->pedidoDelCliente($codigo, (string) $request->query('documento'));

        if (! $venta) {
            return redirect()->route('tienda.seguimiento')
                ->with('error', 'No encontramos un pedido con ese código y documento.');
        }

        return view('tienda.pedido', [
            'venta' => $venta->load(['cliente', 'detalles.producto', 'pagos']),
            'pago'  => config('libreria.pago_movil'),
        ]);
    }

    public function comprobante(Request $request, string $codigo)
    {
        $venta = $this->pedidoDelCliente($codigo, (string) $request->query('documento'));

        abort_unless($venta && $venta->estado === 'pagada', 404);

        return view('comprobantes.venta', [
            'venta' => $venta->load(['cliente', 'empleado', 'detalles.producto', 'pagos']),
        ]);
    }

    /** Solo el dueño del pedido (código + su documento) puede verlo. */
    private function pedidoDelCliente(string $codigo, string $documento): ?Venta
    {
        return Venta::where('canal', 'web')
            ->where('codigo_pedido', strtoupper($codigo))
            ->whereHas('cliente', fn ($q) => $q->where('numero_documento', $documento))
            ->first();
    }
}
