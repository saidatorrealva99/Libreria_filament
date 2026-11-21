@php
    $cfg      = config('libreria');
    $esFact   = $venta->tipo_comprobante === 'factura';
    $total    = (float) $venta->total;
    $gravada  = round($total / (1 + $cfg['igv']), 2);
    $igv      = round($total - $gravada, 2);
    $soles    = fn ($m) => 'S/ ' . number_format((float) $m, 2, '.', ',');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $venta->numero_comprobante }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,700|fraunces:700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #ece6dc; font-family: 'DM Sans', system-ui, sans-serif; color: #1c1917; font-size: 13px; }
        .toolbar { display: flex; justify-content: center; gap: .5rem; padding: 1rem; }
        .toolbar button, .toolbar a { border: 0; border-radius: .6rem; padding: .55rem 1rem; font: 600 .85rem 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; }
        .btn-print { background: #9f1d3a; color: #fff; }
        .btn-back { background: #fff; color: #44403c; }
        .hoja { width: 210mm; min-height: 148mm; margin: 0 auto 2rem; background: #fff; padding: 14mm 14mm 10mm; box-shadow: 0 20px 50px -25px rgba(0,0,0,.35); position: relative; }
        .hoja::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 6px; background: linear-gradient(90deg, #9f1d3a, #6b1027 60%, #d4a017); }
        .cabecera { display: grid; grid-template-columns: 1fr 72mm; gap: 8mm; align-items: start; }
        .marca { display: flex; gap: .8rem; align-items: center; }
        .logo { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; color: #fff; background: linear-gradient(140deg,#c0264a,#7a1530); box-shadow: inset 0 -3px 0 #d4a017; }
        .marca h1 { font-family: 'Fraunces', Georgia, serif; font-size: 22px; margin: 0; }
        .emisor { margin-top: .5rem; color: #57534e; line-height: 1.5; }
        .caja { border: 2px solid #9f1d3a; border-radius: 10px; text-align: center; padding: 10px; }
        .caja .ruc { font-weight: 700; font-size: 14px; }
        .caja .tipo { background: #9f1d3a; color: #fff; margin: 8px -10px; padding: 6px; font-weight: 700; letter-spacing: .04em; }
        .caja .num { font-family: ui-monospace, monospace; font-size: 16px; font-weight: 700; }
        .datos { margin-top: 7mm; display: grid; grid-template-columns: 1fr 1fr; gap: 2px 8mm; padding: 10px 12px; background: #faf7f2; border-radius: 8px; }
        .datos b { color: #57534e; font-weight: 500; display: inline-block; min-width: 92px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #78716c; border-bottom: 2px solid #1c1917; padding: 6px 4px; }
        td { padding: 7px 4px; border-bottom: 1px solid #e7e5e4; vertical-align: top; }
        .r { text-align: right; } .c { text-align: center; }
        .muted { color: #78716c; font-size: 11px; }
        .pie { display: grid; grid-template-columns: 1fr 70mm; gap: 8mm; margin-top: 5mm; }
        .letras { background: #faf7f2; border-radius: 8px; padding: 10px 12px; font-weight: 600; }
        .totales td { border: 0; padding: 3px 4px; }
        .totales .grande td { font-size: 16px; font-weight: 700; border-top: 2px solid #1c1917; padding-top: 7px; color: #9f1d3a; }
        .sello { display: inline-block; margin-top: 8px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .sello.pagada { background: #d1fae5; color: #065f46; }
        .sello.pendiente { background: #fef3c7; color: #92400e; }
        .sello.anulada { background: #fee2e2; color: #991b1b; }
        .anulada-marca { position: absolute; top: 45%; left: 50%; transform: translate(-50%,-50%) rotate(-18deg); font: 800 72px 'Fraunces', serif; color: rgba(220,38,38,.13); pointer-events: none; }
        .gracias { margin-top: 8mm; text-align: center; color: #78716c; font-size: 11px; border-top: 1px dashed #d6d3d1; padding-top: 4mm; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .hoja { box-shadow: none; margin: 0; width: auto; }
            @page { size: A4; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
        @auth
            <a class="btn-back" href="{{ \App\Filament\Resources\VentaResource::getUrl('view', ['record' => $venta]) }}">Volver a la venta</a>
        @else
            <a class="btn-back" href="{{ route('tienda.pedido', ['codigo' => $venta->codigo_pedido, 'documento' => $venta->cliente->numero_documento]) }}">Volver a mi pedido</a>
        @endauth
    </div>

    <div class="hoja">
        @if ($venta->estado === 'anulada')
            <div class="anulada-marca">ANULADA</div>
        @endif

        <div class="cabecera">
            <div>
                <div class="marca">
                    <div class="logo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <h1>{{ config('app.name') }}</h1>
                </div>
                <div class="emisor">
                    {{ $cfg['razon_social'] }}<br>
                    {{ $cfg['direccion'] }}<br>
                    Tel. {{ $cfg['telefono'] }} · {{ $cfg['email'] }}
                </div>
            </div>
            <div class="caja">
                <div class="ruc">R.U.C. {{ $cfg['ruc'] }}</div>
                <div class="tipo">{{ $esFact ? 'FACTURA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA' }}</div>
                <div class="num">{{ $venta->numero_comprobante }}</div>
                <span class="sello {{ $venta->estado }}">{{ \App\Models\Venta::ESTADOS[$venta->estado] }}</span>
            </div>
        </div>

        <div class="datos">
            <div><b>{{ $esFact ? 'Razón social:' : 'Cliente:' }}</b> {{ $venta->cliente->nombre_completo }}</div>
            <div><b>Fecha emisión:</b> {{ $venta->fecha_venta->format('d/m/Y H:i') }}</div>
            <div><b>{{ $venta->cliente->tipo_documento }}:</b> {{ $venta->cliente->numero_documento }}</div>
            <div><b>Moneda:</b> Soles (PEN)</div>
            <div><b>Dirección:</b> {{ $venta->cliente->direccion ?? '—' }}</div>
            <div><b>Atendido por:</b> {{ $venta->empleado?->nombre_completo ?? 'Tienda online' }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:8%" class="c">Cant.</th>
                    <th style="width:14%">Código</th>
                    <th>Descripción</th>
                    <th style="width:14%" class="r">P. unit.</th>
                    <th style="width:14%" class="r">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalles as $d)
                    <tr>
                        <td class="c">{{ $d->cantidad }}</td>
                        <td style="font-family:ui-monospace,monospace">{{ $d->producto->codigo }}</td>
                        <td>
                            {{ $d->producto->nombre }}
                            @if ($d->producto->marca_editorial)
                                <div class="muted">{{ $d->producto->marca_editorial }}</div>
                            @endif
                        </td>
                        <td class="r">{{ $soles($d->precio_unitario) }}</td>
                        <td class="r">{{ $soles($d->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pie">
            <div>
                <div class="letras">SON: {{ \App\Support\NumeroALetras::soles($total) }}</div>
                @if ($venta->pagos->isNotEmpty())
                    <p class="muted" style="margin-top:10px">
                        Pagos:
                        @foreach ($venta->pagos as $p)
                            {{ \App\Models\Pago::METODOS[$p->metodo_pago] }} {{ $soles($p->monto) }}@if ($p->numero_operacion) (Op. {{ $p->numero_operacion }})@endif{{ ! $loop->last ? ' · ' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>
            <table class="totales" style="margin-top:0">
                <tr><td>Subtotal</td><td class="r">{{ $soles($venta->subtotal) }}</td></tr>
                @if ((float) $venta->descuento > 0)
                    <tr><td>Descuento{{ $venta->cupon ? ' (cupón ' . $venta->cupon->codigo . ')' : '' }}</td><td class="r">- {{ $soles($venta->descuento) }}</td></tr>
                @endif
                @if ((float) $venta->costo_envio > 0)
                    <tr><td>Delivery ({{ $venta->zona?->distrito }})</td><td class="r">{{ $soles($venta->costo_envio) }}</td></tr>
                @endif
                <tr><td class="muted">Op. gravada</td><td class="r muted">{{ $soles($gravada) }}</td></tr>
                <tr><td class="muted">IGV (18%)</td><td class="r muted">{{ $soles($igv) }}</td></tr>
                <tr class="grande"><td>TOTAL</td><td class="r">{{ $soles($total) }}</td></tr>
            </table>
        </div>

        <div class="gracias">
            ¡Gracias por su compra! · Representación impresa del comprobante electrónico (documento de práctica, sin validez tributaria).
        </div>
    </div>

    <script>
        // Abre el diálogo de impresión automáticamente
        window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    </script>
</body>
</html>
