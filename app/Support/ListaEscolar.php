<?php

namespace App\Support;

use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lista escolar inteligente: convierte una lista (texto o foto) en productos del catálogo.
 *
 *  - Con IA (Gemini): entiende abreviaturas, sinónimos y hasta listas escritas a mano en foto.
 *  - Sin IA (respaldo): busca por palabras clave. Funciona aunque no haya internet.
 */
class ListaEscolar
{
    public const EJEMPLO = "LISTA DE ÚTILES - 3er GRADO\n"
        . "- 3 cuadernos A4 cuadriculados de 100 hojas\n"
        . "- 2 cuadernos rayados\n"
        . "- 1 caja de colores x 24\n"
        . "- 1 caja de plumones gruesos\n"
        . "- 2 lápices 2B, 1 borrador y 1 tajador\n"
        . "- 1 tijera punta roma\n"
        . "- 2 gomas en barra\n"
        . "- 1 paquete de papel bond A4\n"
        . "- 1 témpera x 7\n"
        . "- 1 folder manila\n"
        . "- 1 mochila";

    /**
     * @return array{filas: Collection, motor: string, aviso: ?string}
     */
    public static function interpretar(?string $texto, ?UploadedFile $foto = null): array
    {
        $texto    = trim((string) $texto);
        $catalogo = Producto::activos()->where('stock', '>', 0)->with('categoria')->get();

        try {
            if (AsistenteIA::motor() === 'local') {
                throw new RuntimeException('se usa la IA propia');
            }
            $items = self::conIA($texto, $foto, $catalogo);
            $motor = 'ia';
            $aviso = null;
        } catch (\Throwable $e) {
            if ($texto === '') {
                throw new RuntimeException('Para leer fotos se necesita conexión con Gemini. Escribe o pega tu lista y nuestra IA la interpreta al instante.');
            }
            $items = self::sinIA($texto, $catalogo);
            $motor = 'basico';
            $aviso = 'Lista interpretada con nuestra IA propia: revisa las sugerencias antes de agregar.';
        }

        $filas = collect($items)->map(function (array $i) use ($catalogo) {
            $p   = $catalogo->firstWhere('id', (int) ($i['producto_id'] ?? 0));
            $qty = max(1, min(99, (int) ($i['cantidad'] ?? 1)));

            return [
                'pedido'    => Str::limit(trim((string) ($i['pedido'] ?? '')), 120),
                'producto'  => $p,
                'cantidad'  => $p ? min($qty, $p->stock) : $qty,
                'sin_stock' => $p && $qty > $p->stock,
                'nota'      => Str::limit(trim((string) ($i['nota'] ?? '')), 120),
            ];
        })->filter(fn ($f) => $f['pedido'] !== '' || $f['producto'])->values();

        return ['filas' => $filas, 'motor' => $motor, 'aviso' => $aviso];
    }

    /* ---------------------------- Con IA ---------------------------- */

    private static function conIA(string $texto, ?UploadedFile $foto, Collection $catalogo): array
    {
        $lineas = $catalogo->map(fn (Producto $p) => implode(' | ', [
            $p->id, $p->nombre, $p->marca_editorial ?: '-', $p->categoria?->nombre ?: '-', 'S/ ' . number_format($p->precioActual(), 2),
        ]))->implode("\n");

        $sistema = <<<TXT
        Eres el asistente de una librería y papelería en Perú. Recibes la LISTA ESCOLAR de un cliente
        (en texto o en una foto, a veces escrita a mano) y el CATÁLOGO con los productos en stock.

        Para CADA ítem de la lista:
        - "pedido": el ítem tal como aparece en la lista (corto).
        - "producto_id": el id del producto del catálogo que mejor corresponda, o null si no hay nada parecido.
        - "cantidad": número entero de unidades que pide la lista (1 si no lo dice).
        - "nota": máximo 10 palabras (ej. "no hay de 100 hojas, sugerimos el de 80" o "no lo tenemos").
        Si una línea pide varias cosas ("2 lápices, 1 borrador"), sepáralas en ítems distintos.
        Usa SOLO ids del catálogo. No inventes productos. Ignora títulos, nombres de alumno o del colegio.

        Responde SOLO con JSON: {"items":[{"pedido":"...","producto_id":12,"cantidad":2,"nota":"..."}]}

        CATÁLOGO (id | nombre | marca | categoría | precio):
        {$lineas}
        TXT;

        $partes = [];
        if ($foto) {
            $partes[] = ['inline_data' => [
                'mime_type' => $foto->getMimeType(),
                'data'      => base64_encode(file_get_contents($foto->getRealPath())),
            ]];
            $partes[] = ['text' => 'La lista escolar está en la imagen.' . ($texto !== '' ? "\nAdemás:\n{$texto}" : '')];
        } else {
            $partes[] = ['text' => "LISTA ESCOLAR:\n{$texto}"];
        }

        $json = AsistenteIA::generarJson($sistema, $partes);

        if (! isset($json['items']) || ! is_array($json['items'])) {
            throw new RuntimeException('La IA no devolvió ítems.');
        }

        return $json['items'];
    }

    /* ------------------------ Sin IA (respaldo) ------------------------ */

    private const IGNORAR = ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'con', 'para', 'un', 'una', 'unos', 'unas',
        'caja', 'paquete', 'paq', 'und', 'unid', 'unidad', 'unidades', 'por', 'x', 'en', 'o', 'tamano'];

    private static function sinIA(string $texto, Collection $catalogo): array
    {
        $indice = $catalogo->map(fn (Producto $p) => [
            'p'      => $p,
            'tokens' => self::tokens($p->nombre . ' ' . $p->marca_editorial . ' ' . $p->categoria?->nombre),
        ]);

        $items = [];

        foreach (preg_split('/\r\n|\r|\n/', $texto) as $linea) {
            // "2 lápices 2B, 1 borrador y 1 tajador" → tres partes
            foreach (preg_split('/,|;|\s+y\s+(?=\d)/u', $linea) as $parte) {
                $parte = preg_replace('/^[\s\-\*•·]+/u', '', trim($parte));          // viñetas "-" "•"
                $parte = trim(preg_replace('/^\d{1,2}[\.\)]\s+/u', '', $parte));    // numeración "1." "2)"
                if (mb_strlen($parte) < 3 || preg_match('/^(lista|grado|nivel|alumno|colegio|nombre)/iu', Str::ascii($parte))) {
                    continue;
                }

                $cantidad = preg_match('/^(\d{1,2})\s/', $parte, $m) ? (int) $m[1] : 1;
                $buscar   = self::tokens(preg_replace('/^\d{1,2}\s/', '', $parte));

                $mejor = $indice->map(function ($fila) use ($buscar) {
                    $puntos = 0;
                    foreach ($buscar as $t) {
                        foreach ($fila['tokens'] as $pt) {
                            if ($t === $pt) {
                                $puntos += ctype_digit($t) ? 2 : 4;   // palabra exacta
                                break;
                            }
                            if (mb_strlen($t) >= 4 && mb_strlen($pt) >= 4 && mb_substr($t, 0, 4) === mb_substr($pt, 0, 4)) {
                                $puntos += 3;                          // misma raíz: cuadern-o / cuadern-os
                                break;
                            }
                        }
                    }

                    return ['p' => $fila['p'], 'puntos' => $puntos];
                })->filter(fn ($r) => $r['puntos'] >= 3)
                    ->sortBy([['puntos', 'desc'], fn ($a, $b) => (float) $a['p']->precio_venta <=> (float) $b['p']->precio_venta])
                    ->first();

                $items[] = [
                    'pedido'      => $parte,
                    'producto_id' => $mejor['p']->id ?? null,
                    'cantidad'    => $cantidad,
                    'nota'        => $mejor ? '' : 'No encontramos este producto',
                ];
            }
        }

        return $items;
    }

    /** Palabras normalizadas: minúsculas, sin tildes, sin plurales simples */
    private static function tokens(string $texto): array
    {
        $texto = Str::lower(Str::ascii($texto));
        $palabras = preg_split('/[^a-z0-9]+/', $texto, -1, PREG_SPLIT_NO_EMPTY);

        return collect($palabras)
            ->reject(fn ($w) => in_array($w, self::IGNORAR, true) || (mb_strlen($w) < 3 && ! ctype_digit($w)))
            ->map(fn ($w) => ctype_digit($w) ? $w : preg_replace('/(es|s)$/', '', preg_replace('/ces$/', 'z', $w)))   // lápices → lapiz
            ->unique()
            ->values()
            ->all();
    }
}
