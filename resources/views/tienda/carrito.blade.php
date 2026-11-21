@extends('tienda.layout')

@section('titulo', 'Carrito')

@section('estilos')
    .carro { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start; }
    .fila { display: grid; grid-template-columns: 70px 1fr 110px 110px 40px; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--borde); }
    .fila:last-child { border-bottom: 0; }
    .fila .mini { width: 70px; }
    .fila .mini .portada { padding: .4rem; }
    .fila .mini .portada .p-tit { font-size: .55rem; }
    .fila .mini .portada .p-cat, .fila .mini .portada .p-aut { display: none; }
    .fila input { padding: .5rem; text-align: center; }
    .quitar { background: none; border: 0; cursor: pointer; color: var(--gris); font-size: 1.2rem; }
    .quitar:hover { color: var(--mal); }
    .resumen .linea { display: flex; justify-content: space-between; margin: .5rem 0; }
    .resumen .total { font-family: 'Fraunces', serif; font-size: 1.6rem; color: var(--vino); border-top: 1px solid var(--borde); padding-top: .8rem; margin-top: .8rem; }
    @media (max-width: 860px) {
        .carro { grid-template-columns: 1fr; }
        .fila { grid-template-columns: 56px 1fr 80px 32px; }
        .fila .imp { display: none; }
    }
@endsection

@section('contenido')
<div class="wrap">
    <h1 class="titulo-pag">Tu carrito</h1>

    @if ($items->isEmpty())
        <div class="panel vacio">
            <div class="serif">Tu carrito está vacío</div>
            <p>Explora el catálogo y agrega tus libros favoritos.</p>
            <a class="btn btn-vino" href="{{ route('tienda.catalogo') }}">Ir al catálogo</a>
        </div>
    @else
        <div class="carro">
            <div class="panel">
                <form id="form-carrito" method="POST" action="{{ route('tienda.actualizar') }}">
                    @csrf
                    @foreach ($items as $item)
                        <div class="fila">
                            <div class="mini">@include('tienda._portada', ['producto' => $item->producto])</div>
                            <div>
                                <a href="{{ route('tienda.producto', $item->producto) }}" style="font-weight:700;text-decoration:none">{{ $item->producto->nombre }}</a>
                                <div style="font-size:.82rem;color:var(--gris)">
                                    @if ($item->precio < $item->normal)
                                        <s>S/ {{ number_format($item->normal, 2) }}</s>
                                        <b style="color:var(--ok)">S/ {{ number_format($item->precio, 2) }}</b> c/u
                                        @if ($item->mayor) <span style="color:#b45309;font-weight:700">· precio por cantidad 📦</span> @endif
                                    @else
                                        S/ {{ number_format($item->precio, 2) }} c/u
                                    @endif
                                    · stock {{ $item->producto->stock }}
                                    @if (! $item->mayor && $item->producto->tieneDescuentoPorCantidad())
                                        <br><span style="color:#b45309">📦 {{ $item->producto->textoPorMayor() }}</span>
                                    @endif
                                </div>
                            </div>
                            <input type="number" name="cantidades[{{ $item->producto->id }}]" value="{{ $item->cantidad }}"
                                   min="0" max="{{ $item->producto->stock }}" aria-label="Cantidad"
                                   onchange="document.getElementById('form-carrito').submit()">
                            <strong class="imp" style="text-align:right">S/ {{ number_format($item->importe, 2) }}</strong>
                            <button class="quitar" type="submit" form="quitar-{{ $item->producto->id }}" title="Quitar">✕</button>
                        </div>
                    @endforeach
                    <noscript><button class="btn btn-borde btn-sm" style="margin-top:1rem">Actualizar</button></noscript>
                </form>

                @foreach ($items as $item)
                    <form id="quitar-{{ $item->producto->id }}" method="POST" action="{{ route('tienda.quitar', $item->producto) }}">@csrf</form>
                @endforeach
            </div>

            <aside class="panel resumen">
                <h3 class="serif" style="margin-top:0">Resumen</h3>
                <div class="linea"><span>Productos</span><span>{{ $items->sum('cantidad') }}</span></div>
                <div class="linea"><span>Recojo en tienda</span><span style="color:var(--ok);font-weight:600">Gratis</span></div>
                <div class="linea"><span>Subtotal</span><span>S/ {{ number_format($total, 2) }}</span></div>
                @if ($cupon)
                    <div class="linea" style="color:var(--ok);font-weight:700">
                        <span>🎟️ {{ $cupon->codigo }}</span><span>− S/ {{ number_format($descuento, 2) }}</span>
                    </div>
                    <form method="POST" action="{{ route('tienda.cupon.quitar') }}" style="text-align:right;margin:-.3rem 0 .3rem">@csrf
                        <button style="background:none;border:0;color:var(--gris);font-size:.78rem;text-decoration:underline;cursor:pointer;padding:0">Quitar cupón</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('tienda.cupon') }}" style="display:flex;gap:.4rem;margin:.6rem 0">
                        @csrf
                        <input name="cupon" placeholder="¿Tienes un cupón?" maxlength="20" required data-v="cupon" data-msg="Escribe el código del cupón." style="text-transform:uppercase;padding:.55rem .7rem" aria-label="Código de cupón">
                        <button class="btn btn-borde btn-sm" style="white-space:nowrap">Aplicar</button>
                    </form>
                @endif
                <div class="linea total"><span>Total</span><span>S/ {{ number_format($total - $descuento, 2) }}</span></div>
                <p style="font-size:.8rem;color:var(--gris)">Precios incluyen IGV.</p>
                <a class="btn btn-vino" style="width:100%" href="{{ route('tienda.checkout') }}">Continuar con el pago →</a>
                <a class="btn btn-borde" style="width:100%;margin-top:.6rem" href="{{ route('tienda.catalogo') }}">Seguir comprando</a>
            </aside>
        </div>
    @endif
</div>
@endsection
