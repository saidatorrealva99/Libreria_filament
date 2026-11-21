@extends('tienda.layout')

@section('titulo', 'Inicio')

@section('estilos')
    .hero { display: grid; grid-template-columns: 1.05fr .95fr; gap: 3rem; align-items: center; padding: 3rem 0 4rem; }
    .kicker { display: inline-flex; gap: .5rem; font-size: .74rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--vino); background: #f6e4e8; padding: .4rem .8rem; border-radius: 999px; }
    .hero h1 { font-family: 'Fraunces', serif; font-weight: 800; font-size: clamp(2.3rem, 5vw, 3.8rem); line-height: 1.03; letter-spacing: -.03em; margin: 1rem 0; }
    .hero h1 em { font-style: italic; color: var(--vino); }
    .hero .lead { font-size: 1.08rem; line-height: 1.65; color: #57534e; max-width: 33rem; }
    .buscador { display: flex; gap: .5rem; margin-top: 1.6rem; max-width: 32rem; }
    .buscador input { flex: 1; }
    .cifras { display: flex; gap: 2.25rem; margin-top: 2rem; padding-top: 1.4rem; border-top: 1px solid #e7e0d5; }
    .cifras strong { display: block; font-family: 'Fraunces', serif; font-size: 1.9rem; color: var(--vino); }
    .cifras span { font-size: .82rem; color: var(--gris); }
    .estante { position: relative; padding: 2rem 1.5rem 0; }
    .estante::after { content: ""; position: absolute; left: 0; right: 0; bottom: -14px; height: 14px; border-radius: 4px; background: linear-gradient(#8a6a4a, #5c4330); box-shadow: 0 18px 30px -12px rgba(60,40,20,.6); }
    .libros { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; align-items: end; }
    .libros a { display: block; transition: transform .25s ease; }
    .libros a:nth-child(odd) { transform: translateY(-14px); }
    .libros a:hover { transform: translateY(-24px) rotate(-1.5deg); }
    .seccion { padding: 3.5rem 0 0; }
    .seccion-cab { display: flex; align-items: end; justify-content: space-between; gap: 1rem; margin-bottom: 1.4rem; }
    .seccion h2 { font-family: 'Fraunces', serif; font-size: 2rem; letter-spacing: -.02em; margin: 0; }
    .cats { display: grid; grid-template-columns: repeat(5, 1fr); gap: .9rem; }
    .cat-item { background: #fff; border: 1px solid var(--borde); border-radius: 1rem; padding: 1rem 1.1rem; text-decoration: none; transition: border-color .2s, transform .2s; }
    .cat-item:hover { border-color: #e2b8c3; transform: translateY(-3px); }
    .cat-item strong { display: block; }
    .cat-item span { font-size: .8rem; color: var(--gris); }
    .pasos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }
    .paso { background: #fff; border: 1px solid var(--borde); border-radius: 1.1rem; padding: 1.5rem; }
    .paso .n { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: #f6e4e8; color: var(--vino); font-family: 'Fraunces', serif; font-weight: 800; font-size: 1.2rem; margin-bottom: .9rem; }
    .paso h3 { margin: 0 0 .35rem; }
    .paso p { margin: 0; color: #57534e; font-size: .92rem; line-height: 1.55; }
    @media (max-width: 900px) { .hero { grid-template-columns: 1fr; } .cats { grid-template-columns: repeat(2, 1fr); } .pasos { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { .libros { grid-template-columns: repeat(2, 1fr); } .cifras { gap: 1.2rem; } }
@endsection

@section('contenido')
<div class="wrap">
    {{-- ===== PORTADA ===== --}}
    <section class="hero">
        <div>
            <span class="kicker">● Compra online · Recoge en tienda</span>
            <h1>Todo para <em>leer, estudiar</em> y crear.</h1>
            <p class="lead">
                Libros, cuadernos, lapiceros, colores, mochilas, papelería y más.
                Arma tu pedido, paga con Yape o Plin y recógelo listo en tienda.
            </p>
            <form class="buscador" action="{{ route('tienda.catalogo') }}">
                <input type="search" name="q" placeholder="Busca cuadernos, colores, un libro o autor…" aria-label="Buscar">
                <button class="btn btn-vino">Buscar</button>
            </form>
            <a class="btn btn-borde" href="{{ route('tienda.lista') }}" style="margin:-.25rem 0 1.25rem;display:inline-flex">✨ ¿Tienes la lista escolar? Arma tu carrito con IA →</a>
            <div class="cifras">
                <div><strong>{{ $totales['libros'] }}</strong><span>productos</span></div>
                <div><strong>{{ $totales['categorias'] }}</strong><span>categorías</span></div>
                <div><strong>{{ $totales['clientes'] }}</strong><span>clientes</span></div>
            </div>
        </div>

        <div class="estante" aria-label="Novedades">
            <div class="libros">
                @foreach ($novedades as $libro)
                    <a href="{{ route('tienda.producto', $libro) }}" title="{{ $libro->nombre }}">
                        @include('tienda._portada', ['producto' => $libro])
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== CATEGORÍAS ===== --}}
    <section class="seccion">
        <div class="seccion-cab">
            <h2>Explora por categoría</h2>
            <a class="btn btn-borde btn-sm" href="{{ route('tienda.catalogo') }}">Ver todo el catálogo →</a>
        </div>
        <div class="cats">
            @foreach ($categorias as $cat)
                <a class="cat-item" href="{{ route('tienda.catalogo', ['categoria' => $cat->id]) }}">
                    <strong>{{ $cat->nombre }}</strong>
                    <span>{{ $cat->productos_count }} producto(s)</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===== MÁS VENDIDOS ===== --}}
    @if ($utiles->isNotEmpty())
    <section class="seccion">
        <div class="seccion-cab">
            <h2>Todo para el cole y la oficina</h2>
            <a class="btn btn-borde btn-sm" href="{{ route('tienda.catalogo', ['orden' => 'vendidos']) }}">Ver más →</a>
        </div>
        <div class="grid-prod">
            @foreach ($utiles as $producto)
                @include('tienda._tarjeta', ['producto' => $producto])
            @endforeach
        </div>
    </section>
    @endif

    <section class="seccion">
        <div class="seccion-cab">
            <h2>Los más vendidos</h2>
        </div>
        @if ($masVendidos->isEmpty())
            <div class="panel vacio">Pronto tendremos nuestros favoritos.</div>
        @else
            <div class="grid-prod">
                @foreach ($masVendidos as $producto)
                    @include('tienda._tarjeta', ['producto' => $producto])
                @endforeach
            </div>
        @endif
    </section>

    {{-- ===== CÓMO COMPRAR ===== --}}
    <section class="seccion">
        <div class="seccion-cab"><h2>Así de fácil</h2></div>
        <div class="pasos">
            <div class="paso">
                <div class="n">1</div>
                <h3>Arma tu pedido</h3>
                <p>Busca en el catálogo y agrega al carrito. Ves el stock disponible en tiempo real.</p>
            </div>
            <div class="paso">
                <div class="n">2</div>
                <h3>Paga con Yape o Plin</h3>
                <p>Escribe tu número de operación o elige pagar al recoger. Con RUC te emitimos factura.</p>
            </div>
            <div class="paso">
                <div class="n">3</div>
                <h3>Recoge en tienda</h3>
                <p>Sigue tu pedido con su código. Cuando esté pagado descargas tu boleta y lo recoges.</p>
            </div>
        </div>
    </section>
</div>
@endsection
