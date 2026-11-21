@extends('tienda.layout')

@section('titulo', 'Catálogo')

@section('estilos')
    .filtros { display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: .75rem; align-items: end; margin-bottom: 1.5rem; }
    .solo-stock { display: flex; align-items: center; gap: .45rem; margin: 0; padding: .8rem .2rem; font-weight: 600; white-space: nowrap; cursor: pointer; }
    .solo-stock input { width: auto; accent-color: var(--vino); }
    .chips-cat { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.5rem; }
    .chips-cat a { text-decoration: none; padding: .45rem .85rem; border-radius: 999px; background: #fff; border: 1px solid var(--borde); font-size: .85rem; font-weight: 600; }
    .chips-cat a.on { background: var(--vino); border-color: var(--vino); color: #fff; }
    .paginas { display: flex; justify-content: center; gap: .4rem; margin-top: 2rem; flex-wrap: wrap; }
    .paginas a, .paginas span { padding: .5rem .85rem; border-radius: .6rem; background: #fff; border: 1px solid var(--borde); text-decoration: none; font-weight: 600; font-size: .88rem; }
    .paginas .on { background: var(--vino); color: #fff; border-color: var(--vino); }
    @media (max-width: 760px) { .filtros { grid-template-columns: 1fr 1fr; } .filtros .busca { grid-column: 1 / -1; } }
@endsection

@section('contenido')
<div class="wrap">
    <h1 class="titulo-pag">Catálogo</h1>
    <p class="sub">{{ $productos->total() }} producto(s) {{ $q ? "para “{$q}”" : 'disponibles' }}</p>

    <form class="filtros" method="GET" action="{{ route('tienda.catalogo') }}">
        <div class="busca">
            <label for="q">Buscar</label>
            <input id="q" type="search" name="q" value="{{ $q }}" placeholder="Cuaderno, lapicero, título, autor o marca">
        </div>
        <div>
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria">
                <option value="">Todas</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}" @selected($categoria == $cat->id)>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="orden">Ordenar</label>
            <select id="orden" name="orden">
                <option value="recientes" @selected($orden === 'recientes')>Más recientes</option>
                <option value="vendidos" @selected($orden === 'vendidos')>Más vendidos</option>
                <option value="precio_asc" @selected($orden === 'precio_asc')>Precio: menor a mayor</option>
                <option value="precio_desc" @selected($orden === 'precio_desc')>Precio: mayor a menor</option>
                <option value="nombre" @selected($orden === 'nombre')>Nombre A–Z</option>
            </select>
        </div>
        <label class="solo-stock"><input type="checkbox" name="disponibles" value="1" @checked($disponibles)> Solo con stock</label>
        <button class="btn btn-vino">Filtrar</button>
    </form>

    <div class="chips-cat">
        <a href="{{ route('tienda.catalogo', ['q' => $q ?: null, 'orden' => $orden, 'disponibles' => $disponibles ?: null]) }}" class="{{ ! $categoria ? 'on' : '' }}">Todas</a>
        @foreach ($categorias as $cat)
            <a href="{{ route('tienda.catalogo', ['categoria' => $cat->id, 'q' => $q ?: null, 'orden' => $orden, 'disponibles' => $disponibles ?: null]) }}" class="{{ $categoria == $cat->id ? 'on' : '' }}">{{ $cat->nombre }}</a>
        @endforeach
    </div>

    @if ($productos->isEmpty())
        <div class="panel vacio">
            <div class="serif">No encontramos productos</div>
            <p>Prueba con otra palabra o quita los filtros.</p>
            <a class="btn btn-borde" href="{{ route('tienda.catalogo') }}">Ver todo</a>
        </div>
    @else
        <div class="grid-prod">
            @foreach ($productos as $producto)
                @include('tienda._tarjeta', ['producto' => $producto])
            @endforeach
        </div>

        @if ($productos->hasPages())
            <nav class="paginas" aria-label="Páginas">
                @if ($productos->previousPageUrl())
                    <a href="{{ $productos->previousPageUrl() }}">← Anterior</a>
                @endif
                @foreach ($productos->getUrlRange(1, $productos->lastPage()) as $num => $url)
                    <a href="{{ $url }}" class="{{ $num === $productos->currentPage() ? 'on' : '' }}">{{ $num }}</a>
                @endforeach
                @if ($productos->nextPageUrl())
                    <a href="{{ $productos->nextPageUrl() }}">Siguiente →</a>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
