{{-- Salud del inventario (CSS propio, no depende del build de Tailwind) --}}
<x-filament-widgets::widget>
    <style>
        .inv-card { height: 100%; border-radius: .75rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.05); outline: 1px solid rgba(3,7,18,.06); padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .75rem; }
        .dark .inv-card { background: rgb(24 24 27); outline-color: rgba(255,255,255,.1); }
        .inv-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem; }
        .inv-title { font-size: 1rem; font-weight: 600; margin: 0; }
        .inv-sub { font-size: .8rem; color: #78716c; margin: .15rem 0 0; }
        .inv-link { font-size: .8rem; font-weight: 600; color: #9f1d3a; text-decoration: none; white-space: nowrap; }
        .dark .inv-link { color: #f5d27a; }
        .inv-stack { display: flex; height: .8rem; border-radius: 999px; overflow: hidden; background: #f5f5f4; }
        .dark .inv-stack { background: rgba(255,255,255,.08); }
        .inv-stack span { display: block; height: 100%; }
        .inv-list { display: grid; gap: .55rem; margin: 0; padding: 0; list-style: none; }
        .inv-row { display: flex; align-items: center; gap: .6rem; font-size: .875rem; }
        .inv-dot { width: .7rem; height: .7rem; border-radius: 999px; flex: none; }
        .inv-row b { margin-left: auto; font-variant-numeric: tabular-nums; }
        .inv-row small { color: #a8a29e; width: 2.6rem; text-align: right; font-variant-numeric: tabular-nums; }
        .inv-foot { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-top: auto; }
        .inv-box { border-radius: .6rem; background: #faf7f2; padding: .6rem .75rem; }
        .dark .inv-box { background: rgba(255,255,255,.05); }
        .inv-box p { margin: 0; font-size: .7rem; color: #78716c; text-transform: uppercase; letter-spacing: .06em; }
        .inv-box strong { font-size: 1rem; font-variant-numeric: tabular-nums; }
        .inv-tags { display: flex; gap: .5rem; flex-wrap: wrap; font-size: .75rem; color: #57534e; }
        .dark .inv-tags { color: #d6d3d1; }
    </style>

    @php
        $colores = ['agotado' => '#7f1d1d', 'critico' => '#dc2626', 'por_agotarse' => '#d4a017', 'normal' => '#16a34a'];
    @endphp

    <div class="inv-card">
        <div class="inv-head">
            <div>
                <h3 class="inv-title">Salud del inventario</h3>
                <p class="inv-sub">{{ $productos }} productos activos · ritmo de los últimos 30 días</p>
            </div>
            @if ($url)
                <a href="{{ $url }}" class="inv-link">Reposición →</a>
            @endif
        </div>

        <div class="inv-stack" role="img" aria-label="Distribución del estado del inventario">
            @foreach ($estados as $e)
                @if ($e['cantidad'])
                    <span @style(["width: {$e['pct']}%", "background: {$colores[$e['clave']]}"]) title="{{ $e['nombre'] }}: {{ $e['cantidad'] }}"></span>
                @endif
            @endforeach
        </div>

        <ul class="inv-list">
            @foreach ($estados as $e)
                <li class="inv-row">
                    <span class="inv-dot" @style(["background: {$colores[$e['clave']]}"])></span>
                    {{ $e['nombre'] }}
                    <b>{{ $e['cantidad'] }}</b>
                    <small>{{ $e['pct'] }}%</small>
                </li>
            @endforeach
        </ul>

        <div class="inv-tags">
            <span>⭐ {{ $estrellas }} estrella(s)</span>
            <span>⏸ {{ $sinMovimiento }} sin movimiento (60 días)</span>
        </div>

        <div class="inv-foot">
            <div class="inv-box">
                <p>Valor a costo</p>
                <strong>{{ \App\Support\Formato::soles($valorCosto) }}</strong>
            </div>
            <div class="inv-box">
                <p>Valor a precio venta</p>
                <strong>{{ \App\Support\Formato::soles($valorVenta) }}</strong>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
