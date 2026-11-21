<?php

use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\ListaEscolarController;
use App\Http\Controllers\ReclamacionController;
use App\Http\Controllers\TiendaController;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TIENDA ONLINE (lo que ve el cliente)
|--------------------------------------------------------------------------
*/
Route::controller(TiendaController::class)->group(function () {
    Route::get('/', 'inicio')->name('inicio');

    Route::get('/tienda', 'catalogo')->name('tienda.catalogo');
    Route::get('/tienda/producto/{producto}', 'producto')->name('tienda.producto');

    Route::get('/carrito', 'carrito')->name('tienda.carrito');
    Route::post('/carrito/agregar', 'agregar')->name('tienda.agregar');
    Route::post('/carrito/actualizar', 'actualizar')->name('tienda.actualizar');
    Route::post('/carrito/quitar/{producto}', 'quitar')->name('tienda.quitar');

    Route::get('/checkout', 'checkout')->name('tienda.checkout');
    Route::post('/checkout', 'confirmar')->name('tienda.confirmar')->middleware('throttle:10,1');

    Route::get('/seguimiento', 'seguimiento')->name('tienda.seguimiento');
    Route::post('/seguimiento', 'buscarPedido')->name('tienda.buscar');
    Route::get('/pedido/{codigo}', 'pedido')->name('tienda.pedido');
    Route::get('/pedido/{codigo}/comprobante', 'comprobante')->name('tienda.comprobante');

    // Cupón de descuento
    Route::post('/carrito/cupon', 'aplicarCupon')->name('tienda.cupon')->middleware('throttle:10,1');
    Route::post('/carrito/cupon/quitar', 'quitarCupon')->name('tienda.cupon.quitar');

    // Páginas informativas: ayuda, envíos, cambios, términos, privacidad
    Route::get('/info/{pagina}', 'pagina')->name('tienda.pagina')
        ->whereIn('pagina', ['ayuda', 'envios', 'cambios', 'terminos', 'privacidad']);
});

/*
|--------------------------------------------------------------------------
| LISTA ESCOLAR INTELIGENTE (IA)
|--------------------------------------------------------------------------
*/
Route::controller(ListaEscolarController::class)->prefix('lista-escolar')->group(function () {
    Route::get('/', 'formulario')->name('tienda.lista');
    Route::post('/', 'procesar')->name('tienda.lista.procesar')->middleware('throttle:8,1');
    Route::post('/agregar', 'agregar')->name('tienda.lista.agregar');
});

/*
|--------------------------------------------------------------------------
| LIBRO DE RECLAMACIONES VIRTUAL
|--------------------------------------------------------------------------
*/
Route::controller(ReclamacionController::class)->prefix('libro-de-reclamaciones')->group(function () {
    Route::get('/', 'formulario')->name('reclamaciones.formulario');
    Route::post('/', 'guardar')->name('reclamaciones.guardar')->middleware('throttle:5,1');
    Route::get('/consultar', 'consulta')->name('reclamaciones.consulta');
    Route::post('/consultar', 'buscar')->name('reclamaciones.buscar')->middleware('throttle:10,1');
    Route::get('/hoja/{codigo}', 'hoja')->name('reclamaciones.hoja');
});

// Asistente virtual de la tienda (IA). Máximo 15 mensajes por minuto por visitante.
Route::post('/asistente', [AsistenteController::class, 'tienda'])
    ->name('tienda.asistente')
    ->middleware('throttle:15,1');

/*
|--------------------------------------------------------------------------
| Comprobante imprimible desde el panel (solo personal logueado)
|--------------------------------------------------------------------------
*/
// PWA: manifiesto para instalar la tienda como app en el celular
Route::get('/manifest.webmanifest', fn () => response()->json([
    'name'             => config('app.name') . ' · Tienda',
    'short_name'       => config('app.name'),
    'description'      => 'Libros, útiles escolares y papelería. Compra online y recoge en tienda.',
    'start_url'        => '/?app=1',
    'scope'            => '/',
    'display'          => 'standalone',
    'orientation'      => 'portrait',
    'background_color' => '#7a1530',
    'theme_color'      => '#7a1530',
    'lang'             => 'es-PE',
    'icons'            => [
        ['src' => '/img/pwa/icono-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/img/pwa/icono-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/img/pwa/icono-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts'        => [
        ['name' => 'Lista escolar con IA', 'url' => '/lista-escolar', 'icons' => [['src' => '/img/pwa/icono-192.png', 'sizes' => '192x192']]],
        ['name' => 'Mi carrito', 'url' => '/carrito', 'icons' => [['src' => '/img/pwa/icono-192.png', 'sizes' => '192x192']]],
    ],
], 200, ['Content-Type' => 'application/manifest+json']))->name('pwa.manifest');

// Etiquetas con código de barras (solo personal)
Route::get('/etiquetas', function (\Illuminate\Http\Request $request) {
    $ids = collect(explode(',', (string) $request->query('ids')))->filter(fn ($id) => ctype_digit($id))->take(200);

    return view('comprobantes.etiquetas', [
        'productos' => \App\Models\Producto::with('categoria')
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q->activos())
            ->orderBy('nombre')->get(),
        'copias'    => max(1, min(10, (int) $request->query('copias', 1))),
    ]);
})->middleware('auth')->name('etiquetas');

Route::get('/comprobante/{venta}', function (Venta $venta) {
    if (! Auth::check()) {
        return redirect()->route('filament.admin.auth.login');
    }

    return view('comprobantes.venta', [
        'venta' => $venta->load(['cliente', 'empleado', 'detalles.producto', 'pagos']),
    ]);
})->name('comprobante');
