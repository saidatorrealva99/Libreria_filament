@extends('tienda.layout')

@section('titulo', $producto->nombre)

@section('estilos')
    .ficha { display: grid; grid-template-columns: 320px 1fr; gap: 3rem; margin-top: 2rem; align-items: start; }
    .ficha .foto { background: #f3eee6; border-radius: 1.2rem; padding: 2rem; }
    .migas { font-size: .85rem; color: var(--gris); margin-top: 1.5rem; }
    .migas a { text-decoration: none; }
    .ficha h1 { font-family: 'Fraunces', serif; font-size: clamp(1.8rem, 4vw, 2.6rem); line-height: 1.1; letter-spacing: -.02em; margin: .5rem 0; }
    .ficha .autor { font-size: 1.05rem; color: #57534e; }
    .ficha .precio { font-size: 2.2rem; display: block; margin: 1.2rem 0 .6rem; }
    .datos { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem 2rem; margin: 1.5rem 0; padding: 1.2rem 0; border-top: 1px solid var(--borde); border-bottom: 1px solid var(--borde); font-size: .92rem; }
    .datos b { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--gris); }
    .comprar { display: flex; gap: .75rem; align-items: end; flex-wrap: wrap; }
    .comprar input { width: 110px; }
    @media (max-width: 760px) { .ficha { grid-template-columns: 1fr; gap: 1.5rem; } .ficha .foto { max-width: 260px; } }
@endsection

@section('contenido')
<div class="wrap">
    <div class="migas">
        <a href="{{ route('tienda.catalogo') }}">Catálogo</a> ›
        <a href="{{ route('tienda.catalogo', ['categoria' => $producto->categoria_id]) }}">{{ $producto->categoria?->nombre }}</a> ›
        {{ $producto->nombre }}
    </div>

    <div class="ficha">
        <div class="foto">@include('tienda._portada', ['producto' => $producto])</div>

        <div>
            <span class="chip ok" style="background:#f6e4e8;color:var(--vino)">{{ $producto->categoria?->nombre }}</span>
            <h1>{{ $producto->nombre }}</h1>
            @if ($producto->autor)
                <div class="autor">de <strong>{{ $producto->autor->nombre_completo }}</strong></div>
            @endif
            @if ($producto->descripcion)
                <p style="color:#57534e;line-height:1.6">{{ $producto->descripcion }}</p>
            @endif

            <span class="precio">
                @if ($producto->enOferta())
                    <s class="antes" style="font-size:1.2rem">S/ {{ number_format((float) $producto->precio_venta, 2) }}</s>
                    <span class="oferta">S/ {{ number_format($producto->precioActual(), 2) }}</span>
                    <span class="etq-inline">-{{ $producto->porcentajeOferta() }}%</span>
                    @if ($producto->oferta_hasta)
                        <small style="display:block;font-size:.85rem;color:var(--gris);font-family:'DM Sans',sans-serif;margin-top:.3rem">⏰ Oferta válida hasta el {{ $producto->oferta_hasta->format('d/m/Y') }}</small>
                    @endif
                @else
                    S/ {{ number_format((float) $producto->precio_venta, 2) }}
                @endif
            </span>
            @if ($producto->tieneDescuentoPorCantidad())
                <div class="aviso" style="margin:0 0 1rem;background:#fffbeb;border-color:#fde68a;color:#92400e">📦 <b>Precio por cantidad:</b> {{ $producto->textoPorMayor() }} (S/ {{ number_format($producto->precioPara($producto->cantidad_mayor), 2) }} c/u)</div>
            @endif

            @if ($producto->stock < 1)
                <span class="chip no">Agotado</span>
            @elseif ($producto->stock <= $producto->stock_minimo)
                <span class="chip poco">¡Solo quedan {{ $producto->stock }}!</span>
            @else
                <span class="chip ok">{{ $producto->stock }} disponibles</span>
            @endif

            <div class="datos">
                <div><b>Código</b>{{ $producto->codigo }}</div>
                <div><b>{{ $producto->esLibro() ? 'Editorial' : 'Marca' }}</b>{{ $producto->marca_editorial ?? '—' }}</div>
                @if ($producto->isbn)
                    <div><b>ISBN</b>{{ $producto->isbn }}</div>
                @endif
                @if ($producto->autor?->nacionalidad)
                    <div><b>Nacionalidad del autor</b>{{ $producto->autor->nacionalidad }}</div>
                @endif
            </div>

            @if ($producto->stock > 0)
                <form class="comprar" method="POST" action="{{ route('tienda.agregar') }}">
                    @csrf
                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                    <div>
                        <label for="cantidad">Cantidad</label>
                        <input id="cantidad" type="number" name="cantidad" value="1" min="1" max="{{ $producto->stock }}">
                    </div>
                    <button class="btn btn-vino">Agregar al carrito</button>
                    <a class="btn btn-borde" href="{{ route('tienda.carrito') }}">Ver carrito</a>
                </form>
            @endif
        </div>
    </div>

    @if ($relacionados->isNotEmpty())
        <h2 class="titulo-pag" style="font-size:1.6rem;margin-top:3.5rem">También te puede interesar</h2>
        <div class="grid-prod">
            @foreach ($relacionados as $rel)
                @include('tienda._tarjeta', ['producto' => $rel])
            @endforeach
        </div>
    @endif
</div>
@endsection
