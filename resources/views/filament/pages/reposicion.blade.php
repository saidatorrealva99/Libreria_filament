<x-filament-panels::page>
    <style>
        .rp-kpis { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .rp-kpi { border-radius: .75rem; background: #fff; outline: 1px solid rgba(3,7,18,.06); box-shadow: 0 1px 2px rgba(0,0,0,.05); padding: 1rem 1.25rem; border-top: 3px solid #9f1d3a; }
        .rp-kpi:nth-child(2n) { border-top-color: #d4a017; }
        .dark .rp-kpi { background: rgb(24 24 27); outline-color: rgba(255,255,255,.1); }
        .rp-kpi p { margin: 0; font-size: .75rem; color: #78716c; text-transform: uppercase; letter-spacing: .06em; }
        .rp-kpi strong { display: block; font-size: 1.5rem; margin: .2rem 0; font-variant-numeric: tabular-nums; }
        .rp-kpi small { color: #a8a29e; font-size: .75rem; }
        .rp-bar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; }
        .rp-search { min-width: 240px; border-radius: .5rem; border: 1px solid rgba(120,113,108,.35); padding: .5rem .75rem; font-size: .875rem; background: transparent; }
        .rp-wrap { overflow-x: auto; border-radius: .75rem; background: #fff; outline: 1px solid rgba(3,7,18,.06); box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .dark .rp-wrap { background: rgb(24 24 27); outline-color: rgba(255,255,255,.1); }
        .rp-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .rp-table th { text-align: left; font-size: .75rem; font-weight: 600; color: #57534e; background: #faf7f2; padding: .7rem 1rem; white-space: nowrap; }
        .dark .rp-table th { background: rgba(255,255,255,.04); color: #d6d3d1; }
        .rp-table td { padding: .7rem 1rem; border-top: 1px solid rgba(120,113,108,.15); vertical-align: middle; }
        .rp-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .rp-prod b { display: block; }
        .rp-prod span { font-size: .75rem; color: #a8a29e; }
        .rp-cov { display: flex; align-items: center; gap: .5rem; min-width: 150px; }
        .rp-cov i { flex: 1; height: .4rem; border-radius: 999px; background: #f5f5f4; overflow: hidden; display: block; }
        .dark .rp-cov i { background: rgba(255,255,255,.08); }
        .rp-cov i span { display: block; height: 100%; border-radius: 999px; }
        .rp-cov small { width: 4.5rem; text-align: right; font-variant-numeric: tabular-nums; }
        .rp-sug { font-weight: 700; color: #9f1d3a; font-size: 1rem; }
        .dark .rp-sug { color: #f5d27a; }
        .rp-empty { padding: 2.5rem; text-align: center; color: #78716c; }
        .rp-note { font-size: .78rem; color: #78716c; }
    </style>

    {{-- Indicadores --}}
    <div class="rp-kpis">
        @foreach ($kpis as [$titulo, $valor, $nota])
            <div class="rp-kpi">
                <p>{{ $titulo }}</p>
                <strong>{{ $valor }}</strong>
                <small>{{ $nota }}</small>
            </div>
        @endforeach
    </div>

    {{-- Pestañas + buscador --}}
    <div class="rp-bar">
        <x-filament::tabs>
            <x-filament::tabs.item :active="$vista === 'reponer'" wire:click="$set('vista', 'reponer')" icon="heroicon-m-exclamation-triangle" :badge="$conteo['reponer']">
                Por reponer
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$vista === 'estrellas'" wire:click="$set('vista', 'estrellas')" icon="heroicon-m-star" :badge="$conteo['estrellas']">
                Estrellas
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$vista === 'sin_movimiento'" wire:click="$set('vista', 'sin_movimiento')" icon="heroicon-m-pause-circle" :badge="$conteo['sin_movimiento']">
                Lentos / sin movimiento
            </x-filament::tabs.item>
            <x-filament::tabs.item :active="$vista === 'todos'" wire:click="$set('vista', 'todos')" icon="heroicon-m-squares-2x2" :badge="$conteo['todos']">
                Todos
            </x-filament::tabs.item>
        </x-filament::tabs>

        <input type="search" class="rp-search" placeholder="Buscar producto o código…" wire:model.live.debounce.400ms="buscar">
    </div>

    {{-- Tabla de análisis --}}
    <div class="rp-wrap">
        <table class="rp-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Estado</th>
                    <th>Clasificación</th>
                    <th class="rp-num">Stock / mín.</th>
                    <th class="rp-num">Vendidas 30 d</th>
                    <th class="rp-num">Prom. diario</th>
                    <th>Cobertura</th>
                    <th class="rp-num">Sugerido</th>
                    <th class="rp-num">Inversión</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $f)
                    @php
                        $p     = $f['producto'];
                        $dias  = $f['cobertura'];
                        $color = match ($f['estado']) { 'agotado' => '#7f1d1d', 'critico' => '#dc2626', 'por_agotarse' => '#d4a017', default => '#16a34a' };
                        [$estadoTxt, $estadoColor] = \App\Support\Inventario::ESTADOS[$f['estado']];
                        [$claseTxt, $claseColor, $claseIcono] = \App\Support\Inventario::CLASES[$f['clase']];
                    @endphp
                    <tr wire:key="rp-{{ $p->id }}">
                        <td class="rp-prod">
                            <b>{{ $p->nombre }}</b>
                            <span>{{ $p->codigo }} · {{ $p->categoria?->nombre }}</span>
                        </td>
                        <td><x-filament::badge :color="$estadoColor">{{ $estadoTxt }}</x-filament::badge></td>
                        <td><x-filament::badge :color="$claseColor" :icon="$claseIcono">{{ $claseTxt }}</x-filament::badge></td>
                        <td class="rp-num">{{ $p->stock }} / {{ $p->stock_minimo }}</td>
                        <td class="rp-num">{{ $f['vendidas'] }}</td>
                        <td class="rp-num">{{ number_format($f['promedio'], 2) }}</td>
                        <td>
                            <div class="rp-cov">
                                <i><span @style(["width: " . ($dias === null ? ($p->stock > 0 ? 100 : 0) : max(3, min(100, round($dias / 30 * 100)))) . "%", "background: {$color}"])></span></i>
                                <small>{{ $dias === null ? ($p->stock > 0 ? 'sin ventas' : '—') : ($dias > 365 ? '+365 días' : $dias . ' días') }}</small>
                            </div>
                        </td>
                        <td class="rp-num">
                            @if ($f['sugerido'] > 0)
                                <span class="rp-sug">+{{ $f['sugerido'] }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="rp-num">{{ $f['inversion'] > 0 ? \App\Support\Formato::soles($f['inversion']) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="rp-empty">✔ No hay productos en esta vista.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="rp-note">
        <b>Cómo se calcula:</b> promedio diario = unidades vendidas en 30 días ÷ 30 ·
        cobertura = stock ÷ promedio diario · sugerido = lo necesario para {{ \App\Support\Inventario::DIAS_OBJETIVO }} días + stock mínimo ·
        estrella = top 20 % en ventas · sin movimiento = 60 días sin vender.
    </p>
</x-filament-panels::page>
