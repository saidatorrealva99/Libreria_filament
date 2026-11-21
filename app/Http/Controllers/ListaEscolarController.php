<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\Carrito;
use App\Support\ListaEscolar;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Lista escolar inteligente: el cliente pega su lista o le toma una foto
 * y recibe el carrito armado con los productos que hay en stock.
 */
class ListaEscolarController extends Controller
{
    public function formulario()
    {
        return view('tienda.lista-escolar', ['resultado' => null]);
    }

    public function procesar(Request $request)
    {
        $request->validate([
            'lista' => ['nullable', 'string', 'max:3000', 'required_without:foto'],
            'foto'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'lista.required_without' => 'Escribe tu lista o súbele una foto.',
            'foto.max'               => 'La foto debe pesar menos de 5 MB.',
        ]);

        try {
            $resultado = ListaEscolar::interpretar($request->input('lista'), $request->file('foto'));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($resultado['filas']->isEmpty()) {
            return back()->withInput()->with('error', 'No pudimos reconocer productos en la lista. Escribe un producto por línea.');
        }

        return view('tienda.lista-escolar', ['resultado' => $resultado]);
    }

    public function agregar(Request $request)
    {
        $data = $request->validate([
            'items'               => ['required', 'array'],
            'items.*.producto_id' => ['nullable', 'integer'],
            'items.*.cantidad'    => ['nullable', 'integer', 'min:1', 'max:99'],
            'items.*.incluir'     => ['nullable'],
        ]);

        $agregados = 0;
        foreach ($data['items'] as $item) {
            if (empty($item['incluir']) || empty($item['producto_id'])) {
                continue;
            }
            $producto = Producto::activos()->where('stock', '>', 0)->find($item['producto_id']);
            if ($producto) {
                Carrito::agregar($producto, (int) ($item['cantidad'] ?? 1));
                $agregados++;
            }
        }

        if (! $agregados) {
            return redirect()->route('tienda.lista')->with('error', 'No marcaste ningún producto.');
        }

        return redirect()->route('tienda.carrito')->with('ok', "¡Lista lista! Agregamos {$agregados} producto(s) a tu carrito.");
    }
}
