<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiquetas de productos · {{ config('app.name') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', system-ui, sans-serif; margin: 0; background: #f5f5f4; color: #1c1917; }
        .barra { display: flex; gap: .75rem; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: 1rem 1.5rem; background: #fff; border-bottom: 1px solid #e7e5e4; }
        .barra h1 { font-size: 1.1rem; margin: 0; }
        .barra small { color: #78716c; }
        .btn { border: 0; border-radius: .6rem; padding: .6rem 1rem; font-weight: 700; cursor: pointer; background: #9f1d3a; color: #fff; text-decoration: none; font-size: .9rem; }
        .btn.gris { background: #e7e5e4; color: #1c1917; }
        .hoja { display: grid; grid-template-columns: repeat(auto-fill, minmax(62mm, 1fr)); gap: 4mm; padding: 8mm; max-width: 210mm; margin: 1rem auto; background: #fff; }
        .eti { border: 1px dashed #d6d3d1; border-radius: 2mm; padding: 2.5mm 3mm; text-align: center; break-inside: avoid; }
        .eti .marca { font-size: 7pt; color: #78716c; text-transform: uppercase; letter-spacing: .06em; }
        .eti .nom { font-size: 8.5pt; font-weight: 700; line-height: 1.15; height: 2.3em; overflow: hidden; }
        .eti svg { width: 100%; height: 16mm; }
        .eti .precio { font-size: 12pt; font-weight: 800; color: #9f1d3a; }
        @media print {
            body { background: #fff; }
            .barra { display: none; }
            .hoja { margin: 0; padding: 5mm; max-width: none; }
            .eti { border-color: #bbb; }
            .eti .precio { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="barra">
        <div>
            <h1>🏷️ Etiquetas con código de barras</h1>
            <small>{{ $productos->count() }} producto(s) × {{ $copias }} copia(s). Pégalas en los productos y escanéalas en “Nueva venta”.</small>
        </div>
        <div style="display:flex;gap:.5rem">
            <a class="btn gris" href="{{ url()->previous() }}">← Volver</a>
            <button class="btn" onclick="window.print()">🖨 Imprimir</button>
        </div>
    </div>

    <div class="hoja">
        @foreach ($productos as $p)
            @for ($i = 0; $i < $copias; $i++)
                <div class="eti">
                    <div class="marca">{{ config('app.name') }} · {{ $p->marca_editorial ?: $p->categoria?->nombre }}</div>
                    <div class="nom">{{ $p->nombre }}</div>
                    <svg class="bc" data-codigo="{{ $p->codigo }}"></svg>
                    <div class="precio">S/ {{ number_format((float) $p->precio_venta, 2) }}</div>
                </div>
            @endfor
        @endforeach
    </div>

    <script>
        if (window.JsBarcode) {
            document.querySelectorAll('svg.bc').forEach(svg => JsBarcode(svg, svg.dataset.codigo, {
                format: 'CODE128', height: 50, width: 2, fontSize: 14, margin: 2, displayValue: true,
            }));
        } else {
            document.querySelectorAll('svg.bc').forEach(svg => svg.outerHTML = '<b style="font-family:monospace;font-size:14pt">' + svg.dataset.codigo + '</b><br><small>(sin internet: no se pudo dibujar el código de barras)</small>');
        }
    </script>
</body>
</html>
