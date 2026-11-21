{{-- Foto del producto o, si no tiene, una portada generada con sus datos --}}
@php
    $paletas = [
        ['#7a1530', '#c0264a', '#f5d27a'],
        ['#0f3d3a', '#0f766e', '#f5e6c8'],
        ['#1e293b', '#475569', '#d4a017'],
        ['#7c2d12', '#c2410c', '#fde68a'],
        ['#3b0764', '#7e22ce', '#f5d27a'],
        ['#14532d', '#15803d', '#fef3c7'],
    ];
    [$c1, $c2, $acento] = $paletas[$producto->id % count($paletas)];
@endphp

@if ($producto->imagen_url)
    <img class="portada-img" src="{{ $producto->imagen_url }}" alt="Portada de {{ $producto->nombre }}" loading="lazy">
@elseif (! $producto->esLibro())
    <div class="util" @style(["background: radial-gradient(circle at 30% 20%, #fff 0, #fff 30%, transparent 31%), linear-gradient(160deg, {$c2}22, {$c1}33)"])>
        <span class="util-ico" aria-hidden="true">{{ \App\Support\Tienda::icono($producto) }}</span>
        <span class="util-marca" @style(["background: $c1"])>{{ $producto->marca_editorial ?: $producto->categoria?->nombre }}</span>
    </div>
@else
    <div class="portada" @style(["background: linear-gradient(160deg, $c2, $c1)"])>
        <span class="p-cat">{{ $producto->categoria?->nombre }}</span>
        <div>
            <div class="p-tit">{{ $producto->nombre }}</div>
            <div class="p-fil" @style(["background: $acento"])></div>
            <div class="p-aut">{{ $producto->autor?->nombre_completo ?? $producto->marca_editorial }}</div>
        </div>
    </div>
@endif
