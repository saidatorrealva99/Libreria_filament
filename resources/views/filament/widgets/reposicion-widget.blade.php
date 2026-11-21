{{-- Reposición sugerida: lo más urgente --}}
<x-filament-widgets::widget>
    <style>
        .rw-card { height: 100%; border-radius: .75rem; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.05); outline: 1px solid rgba(3,7,18,.06); padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .9rem; }
        .dark .rw-card { background: rgb(24 24 27); outline-color: rgba(255,255,255,.1); }
        .rw-title { font-size: 1rem; font-weight: 600; margin: 0; }
        .rw-sub { font-size: .8rem; color: #78716c; margin: .15rem 0 0; }
        .rw-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .7rem; }
        .rw-item { display: grid; grid-template-columns: 1fr auto; gap: .15rem .75rem; align-items: center; }
        .rw-name { font-size: .875rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rw-meta { font-size: .75rem; color: #78716c; }
        .rw-qty { grid-row: span 2; text-align: right; }
        .rw-qty strong { display: block; font-size: 1rem; color: #9f1d3a; font-variant-numeric: tabular-nums; }
        .dark .rw-qty strong { color: #f5d27a; }
        .rw-qty span { font-size: .7rem; color: #a8a29e; }
        .rw-bar { grid-column: 1 / -1; height: .3rem; border-radius: 999px; background: #f5f5f4; overflow: hidden; }
        .dark .rw-bar { background: rgba(255,255,255,.08); }
        .rw-bar i { display: block; height: 100%; border-radius: 999px; }
        .rw-foot { margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: .5rem; border-top: 1px solid rgba(120,113,108,.18); padding-top: .8rem; font-size: .8rem; }
        .rw-link { font-weight: 600; color: #9f1d3a; text-decoration: none; }
        .dark .rw-link { color: #f5d27a; }
        .rw-ok { text-align: center; color: #16a34a; font-size: .9rem; padding: 1.5rem 0; }
    </style>

    <div class="rw-card">
        <div>
            <h3 class="rw-title">Reposición sugerida</h3>
            <p class="rw-sub">Según el ritmo de ventas de los últimos 30 días</p>
        </div>

        @if ($filas->isEmpty())
            <p class="rw-ok">✔ Ningún producto necesita reposición.</p>
        @else
            <ul class="rw-list">
                @foreach ($filas as $f)
                    @php
                        $dias  = $f['cobertura'];
                        $pct   = $dias === null ? 100 : min(100, round($dias / 30 * 100));
                        $color = match ($f['estado']) { 'agotado' => '#7f1d1d', 'critico' => '#dc2626', default => '#d4a017' };
                        $texto = match (true) {
                            $f['estado'] === 'agotado' => 'Agotado',
                            $dias === null             => "Stock {$f['producto']->stock} / mín. {$f['producto']->stock_minimo}",
                            default                    => "Alcanza para {$dias} día(s) · stock {$f['producto']->stock}",
                        };
                    @endphp
                    <li class="rw-item">
                        <span class="rw-name" title="{{ $f['producto']->nombre }}">{{ $f['producto']->nombre }}</span>
                        <span class="rw-qty"><strong>+{{ $f['sugerido'] }}</strong><span>sugerido</span></span>
                        <span class="rw-meta">{{ $texto }}</span>
                        <span class="rw-bar"><i @style(["width: " . max(4, $pct) . "%", "background: {$color}"])></i></span>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="rw-foot">
            <span>{{ $total }} producto(s) · inversión aprox. <b>{{ \App\Support\Formato::soles($inversion) }}</b></span>
            @if ($url)
                <a href="{{ $url }}" class="rw-link">Ver todo →</a>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
