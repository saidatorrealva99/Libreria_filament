{{-- Tarjeta de producto para catálogo, portada y relacionados --}}
<article class="prod">
    <a class="foto" href="{{ route('tienda.producto', $producto) }}">
        @if ($producto->enOferta())
            <span class="etq etq-oferta">-{{ $producto->porcentajeOferta() }}%</span>
        @elseif (\App\Support\Tienda::esMasVendido($producto))
            <span class="etq etq-top">★ Más vendido</span>
        @elseif (\App\Support\Tienda::esNuevo($producto))
            <span class="etq etq-nuevo">Nuevo</span>
        @endif
        @include('tienda._portada', ['producto' => $producto])
    </a>
    <div class="info">
        <span class="cat">{{ $producto->categoria?->nombre }}</span>
        <a class="nom" href="{{ route('tienda.producto', $producto) }}">{{ $producto->nombre }}</a>
        <span class="aut">{{ $producto->autor?->nombre_completo ?? $producto->marca_editorial }}</span>

        @if ($producto->stock < 1)
            <span class="chip no">Agotado</span>
        @elseif ($producto->stock <= $producto->stock_minimo)
            <span class="chip poco">¡Últimas {{ $producto->stock }} unidades!</span>
        @else
            <span class="chip ok">En stock</span>
        @endif

        @if ($producto->tieneDescuentoPorCantidad())
            <small class="mayor">📦 {{ $producto->textoPorMayor() }}</small>
        @endif

        <div class="pie">
            <span class="precio">
                @if ($producto->enOferta())
                    <s class="antes">S/ {{ number_format((float) $producto->precio_venta, 2) }}</s>
                    <span class="oferta">S/ {{ number_format($producto->precioActual(), 2) }}</span>
                @else
                    S/ {{ number_format((float) $producto->precio_venta, 2) }}
                @endif
            </span>
            <form method="POST" action="{{ route('tienda.agregar') }}">
                @csrf
                <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                <button class="btn btn-vino btn-sm" @disabled($producto->stock < 1) aria-label="Agregar {{ $producto->nombre }} al carrito">
                    + Agregar
                </button>
            </form>
        </div>
    </div>
</article>
