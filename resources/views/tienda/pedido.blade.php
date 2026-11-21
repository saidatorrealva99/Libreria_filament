@extends('tienda.layout')

@section('titulo', 'Pedido ' . $venta->codigo_pedido)

@section('estilos')
    .exito { text-align: center; padding: 2rem 1rem 1rem; }
    .exito .check { width: 64px; height: 64px; border-radius: 999px; margin: 0 auto 1rem; display: grid; place-items: center; background: #ecfdf5; color: var(--ok); font-size: 2rem; }
    .codigo { display: inline-block; font-family: ui-monospace, monospace; font-size: 1.5rem; font-weight: 800; letter-spacing: .08em; background: #fff; border: 2px dashed var(--vino); color: var(--vino); padding: .5rem 1rem; border-radius: .8rem; }
    .linea-tiempo { display: grid; grid-template-columns: repeat(4, 1fr); margin: 2rem 0; position: relative; }
    .linea-tiempo::before { content: ""; position: absolute; top: 17px; left: 12%; right: 12%; height: 3px; background: var(--borde); }
    .etapa { text-align: center; position: relative; font-size: .82rem; font-weight: 600; color: var(--gris); }
    .etapa .bola { width: 36px; height: 36px; border-radius: 999px; margin: 0 auto .5rem; display: grid; place-items: center; background: #fff; border: 3px solid var(--borde); font-weight: 800; }
    .etapa.hecha { color: var(--tinta); }
    .etapa.hecha .bola { background: var(--vino); border-color: var(--vino); color: #fff; }
    .etapa.actual .bola { border-color: var(--dorado); box-shadow: 0 0 0 6px rgba(212,160,23,.2); }
    .cols-p { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start; }
    .item-r { display: flex; justify-content: space-between; gap: 1rem; padding: .6rem 0; border-bottom: 1px solid var(--borde); }
    .cancelado { background: #fef2f2; color: var(--mal); border: 1px solid #fecaca; padding: 1rem; border-radius: 1rem; font-weight: 600; text-align: center; }
    @media (max-width: 860px) { .cols-p { grid-template-columns: 1fr; } .linea-tiempo { font-size: .7rem; } }
@endsection

@section('contenido')
@php
    $estado  = $venta->estadoPedido();
    $etapas  = ['Pedido recibido', 'Pago verificado', $venta->esDelivery() ? 'Preparando envío' : 'Listo para recoger', 'Entregado'];
    $nivel   = match ($estado) {
        'Pendiente de pago', 'Verificando pago' => 1,
        'Listo para recoger', 'Listo para enviar' => 3,
        'Entregado'          => 4,
        default              => 0,
    };
@endphp
<div class="wrap" style="max-width:980px">
    @if (session('nuevo'))
        <div class="exito">
            <div class="check">✓</div>
            <h1 class="titulo-pag" style="margin-top:0">¡Gracias por tu compra!</h1>
            <p class="sub">Guarda este código para seguir tu pedido:</p>
            <div class="codigo">{{ $venta->codigo_pedido }}</div>
        </div>
    @else
        <h1 class="titulo-pag">Pedido <span style="color:var(--vino)">{{ $venta->codigo_pedido }}</span></h1>
        <p class="sub">Hecho el {{ $venta->fecha_venta->format('d/m/Y H:i') }} · {{ $venta->cliente->nombre_completo }}</p>
    @endif

    @if ($estado === 'Cancelado')
        <div class="cancelado">Este pedido fue cancelado. Si crees que es un error, comunícate al {{ config('libreria.telefono') }}.</div>
    @else
        <div class="linea-tiempo" aria-label="Estado del pedido: {{ $estado }}">
            @foreach ($etapas as $i => $etapa)
                <div class="etapa {{ $i + 1 <= $nivel ? 'hecha' : '' }} {{ $i + 1 === $nivel + 1 ? 'actual' : '' }}">
                    <div class="bola">{{ $i + 1 <= $nivel ? '✓' : $i + 1 }}</div>
                    {{ $etapa }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="cols-p">
        <div class="panel">
            <h3 class="serif" style="margin-top:0">Productos</h3>
            @foreach ($venta->detalles as $d)
                <div class="item-r">
                    <span>{{ $d->cantidad }} × {{ $d->producto->nombre }}</span>
                    <strong>S/ {{ number_format((float) $d->subtotal, 2) }}</strong>
                </div>
            @endforeach
            @php $normal = $venta->detalles->sum(fn ($d) => $d->cantidad * (float) $d->producto->precio_venta); @endphp
            @if ($normal - (float) $venta->subtotal >= 0.01)
                <div class="item-r" style="color:var(--ok)"><span>🏷️ Ahorro por ofertas</span><strong>− S/ {{ number_format($normal - (float) $venta->subtotal, 2) }}</strong></div>
            @endif
            <div class="item-r"><span>Subtotal</span><strong>S/ {{ number_format((float) $venta->subtotal, 2) }}</strong></div>
            @if ((float) $venta->descuento > 0)
                <div class="item-r" style="color:var(--ok)"><span>🎟️ Descuento{{ $venta->cupon ? ' (cupón ' . $venta->cupon->codigo . ')' : '' }}</span><strong>− S/ {{ number_format((float) $venta->descuento, 2) }}</strong></div>
            @endif
            @if ($venta->esDelivery())
                <div class="item-r"><span>🛵 Delivery ({{ $venta->zona?->distrito }})</span><strong>S/ {{ number_format((float) $venta->costo_envio, 2) }}</strong></div>
            @endif
            <div class="item-r" style="border:0;font-family:'Fraunces',serif;font-size:1.4rem;color:var(--vino)">
                <span>Total</span><span>S/ {{ number_format((float) $venta->total, 2) }}</span>
            </div>
        </div>

        <aside class="panel">
            <h3 class="serif" style="margin-top:0">Estado: {{ $estado }}</h3>

            @switch($estado)
                @case('Verificando pago')
                    <p>Recibimos tu código de operación <strong>{{ $venta->operacion_declarada }}</strong>.
                       Nuestra cajera lo está verificando. Vuelve a esta página en unos minutos.</p>
                    @break
                @case('Pendiente de pago')
                    @if ($venta->esDelivery())
                        <p>Preparamos tu pedido para enviarlo. Pagas al recibirlo.</p>
                    @else
                        <p>Separamos tus productos. Paga al recogerlo en tienda.<br>
                           <small style="color:var(--gris)">{{ config('libreria.horario') }}</small></p>
                    @endif
                    @break
                @case('Listo para enviar')
                    <p>¡Tu pago fue confirmado! Estamos preparando tu delivery a <strong>{{ $venta->zona?->distrito }}</strong> ({{ $venta->zona?->tiempo }}).</p>
                    @break
                @case('Listo para recoger')
                    <p>¡Tu pago fue confirmado! Acércate a <strong>{{ config('libreria.direccion') }}</strong> con tu documento y este código.</p>
                    @break
                @case('Entregado')
                    <p>Entregamos tu pedido el {{ $venta->entregado_at->format('d/m/Y H:i') }}. ¡Gracias por tu compra!</p>
                    @break
            @endswitch

            <p style="font-size:.88rem;background:#faf7f2;border-radius:.7rem;padding:.7rem .85rem">
                @if ($venta->esDelivery())
                    🛵 <b>Delivery</b> a {{ $venta->direccion_entrega }}, {{ $venta->zona?->distrito }}
                    @if ($venta->referencia_entrega)<br><small style="color:var(--gris)">Ref.: {{ $venta->referencia_entrega }}</small>@endif
                @else
                    🏬 <b>Recojo en tienda</b>: {{ config('libreria.direccion') }}
                @endif
            </p>
            <p style="font-size:.85rem;color:var(--gris)">
                {{ ucfirst($venta->tipo_comprobante) }} {{ $venta->numero_comprobante }}<br>
                Pago: {{ \App\Models\Venta::METODOS_WEB[$venta->metodo_declarado] ?? '—' }}
            </p>

            @if ($venta->estado === 'pagada')
                <a class="btn btn-dorado" style="width:100%" target="_blank"
                   href="{{ route('tienda.comprobante', ['codigo' => $venta->codigo_pedido, 'documento' => $venta->cliente->numero_documento]) }}">
                    Descargar mi {{ $venta->tipo_comprobante }}
                </a>
            @endif

            <a class="btn btn-borde" style="width:100%;margin-top:.6rem"
               href="{{ route('tienda.pedido', ['codigo' => $venta->codigo_pedido, 'documento' => $venta->cliente->numero_documento]) }}">
                ↻ Actualizar estado
            </a>
            <a class="btn btn-borde" style="width:100%;margin-top:.6rem" href="{{ route('tienda.catalogo') }}">Seguir comprando</a>
        </aside>
    </div>
</div>
@endsection
