<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\AsistenteIA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Asistente de la tienda: recomienda productos del catálogo a los clientes.
 */
class AsistenteController extends Controller
{
    private const SESION = 'asistente_tienda';

    public function tienda(Request $request): JsonResponse
    {
        $data = $request->validate(['mensaje' => ['required', 'string', 'max:400']]);

        $historial = session(self::SESION, []);

        try {
            $respuesta = AsistenteIA::preguntar($data['mensaje'], $historial, 'tienda');
        } catch (RuntimeException $e) {
            return response()->json([
                'html'      => e('Ahora mismo no puedo responder 😔. Puedes buscar en el catálogo o escribirnos al ' . config('libreria.telefono') . '.'),
                'productos' => [],
                'error'     => $e->getMessage(),
            ]);
        }

        // Guarda la conversación (últimos 8 mensajes)
        $historial[] = ['rol' => 'usuario', 'texto' => $data['mensaje']];
        $historial[] = ['rol' => 'ia', 'texto' => $respuesta];
        session([self::SESION => array_slice($historial, -8)]);

        // Los códigos [[ID]] se convierten en nombres y tarjetas con enlace
        preg_match_all('/\[\[(\d+)\]\]/', $respuesta, $coincidencias);
        $productos = Producto::activos()->whereIn('id', array_unique($coincidencias[1]))->get()->keyBy('id');

        $texto = preg_replace_callback('/\[\[(\d+)\]\]/', function ($m) use ($productos) {
            return isset($productos[$m[1]]) ? '**' . $productos[$m[1]]->nombre . '**' : '';
        }, $respuesta);

        return response()->json([
            'html'      => Str::markdown($texto, ['html_input' => 'escape', 'allow_unsafe_links' => false]),
            'productos' => $productos->values()->map(fn (Producto $p) => [
                'nombre' => $p->nombre,
                'precio' => 'S/ ' . number_format((float) $p->precio_venta, 2),
                'url'    => route('tienda.producto', $p),
                'stock'  => $p->stock,
            ]),
        ]);
    }
}
