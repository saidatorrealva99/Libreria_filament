<x-filament-panels::page>
    <style>
        .ac { max-width: 520px; margin: 0 auto; width: 100%; }
        .ac-card { border-radius: 1.25rem; overflow: hidden; background: #fff; box-shadow: 0 20px 50px -25px rgba(0,0,0,.35); outline: 1px solid rgba(3,7,18,.06); }
        .dark .ac-card { background: rgb(24 24 27); outline-color: rgba(255,255,255,.1); }
        .ac-cab { padding: 1.6rem 1.75rem; color: #fff; background: radial-gradient(circle at 85% 10%, rgba(212,160,23,.4), transparent 45%), linear-gradient(135deg, #9f1d3a, #4a0d1f); }
        .ac-cab small { text-transform: uppercase; letter-spacing: .12em; font-weight: 700; font-size: .7rem; color: #f5d27a; }
        .ac-cab h2 { font-family: 'Fraunces', Georgia, serif; font-size: 1.7rem; margin: .3rem 0 .2rem; }
        .ac-cab p { margin: 0; opacity: .85; font-size: .9rem; }
        .ac-cuerpo { padding: 1.5rem 1.75rem 1.75rem; display: grid; gap: 1rem; }
        .ac-label { font-weight: 600; font-size: .9rem; }
        .ac-input { display: flex; align-items: center; border: 2px solid rgba(120,113,108,.3); border-radius: .9rem; overflow: hidden; }
        .ac-input:focus-within { border-color: #9f1d3a; box-shadow: 0 0 0 4px rgba(159,29,58,.15); }
        .ac-input span { padding: 0 .9rem; font-weight: 700; color: #78716c; }
        .ac-input input { flex: 1; min-width: 0; border: 0; outline: 0; padding: .9rem .9rem .9rem 0; font-size: 1.6rem; font-weight: 800; background: transparent; color: inherit; font-variant-numeric: tabular-nums; }
        .ac-rapidos { display: flex; gap: .5rem; flex-wrap: wrap; }
        .ac-rapidos button { border: 1px solid rgba(120,113,108,.35); background: transparent; border-radius: 999px; padding: .35rem .8rem; font-weight: 600; font-size: .82rem; cursor: pointer; color: inherit; }
        .ac-rapidos button:hover { border-color: #9f1d3a; color: #9f1d3a; }
        .ac-btn { width: 100%; border: 0; border-radius: .9rem; padding: 1rem; font-size: 1.05rem; font-weight: 800; color: #2a0a12; background: #d4a017; cursor: pointer; box-shadow: 0 12px 24px -12px rgba(212,160,23,.9); }
        .ac-btn:hover { filter: brightness(1.05); }
        .ac-nota { font-size: .8rem; color: #78716c; text-align: center; margin: 0; }
        .ac-error { color: #dc2626; font-size: .82rem; }
        .ac-fin { text-align: center; padding: 1rem 0; }
        .ac-fin div { font-size: 3rem; }
        .ac-salir { display: block; width: 100%; text-align: center; border: 1px solid rgba(120,113,108,.35); border-radius: .9rem; padding: .8rem; font-weight: 700; background: transparent; cursor: pointer; color: inherit; }
    </style>

    <div class="ac">
        <div class="ac-card">
            <div class="ac-cab">
                <small>{{ now()->translatedFormat('l d \d\e F') }}</small>
                @if ($cajaHoy && $cajaHoy->estado === 'cerrada')
                    <h2>Turno terminado</h2>
                    <p>La caja de hoy se cerró a las {{ $cajaHoy->cerrada_at?->format('H:i') }}.</p>
                @else
                    <h2>¡Hola, {{ $nombre }}! 👋</h2>
                    <p>Antes de empezar a vender, abre la caja del día.</p>
                @endif
            </div>

            <div class="ac-cuerpo">
                @if ($cajaHoy && $cajaHoy->estado === 'cerrada')
                    <div class="ac-fin">
                        <div>🔒</div>
                        <p>Resultado del arqueo: <b>{{ $cajaHoy->estadoCuadre() }}</b>.<br>Hasta mañana.</p>
                    </div>
                @else
                    <form wire:submit="abrir" style="display:grid;gap:1rem">
                        <label class="ac-label" for="fondo">¿Con cuánto sencillo empiezas? (fondo inicial)</label>
                        <div class="ac-input">
                            <span>S/</span>
                            <input id="fondo" type="text" inputmode="decimal" wire:model="fondo" autofocus autocomplete="off">
                        </div>
                        @error('fondo') <div class="ac-error">{{ $message }}</div> @enderror
                        <div class="ac-rapidos">
                            @foreach ([50, 100, 150, 200] as $m)
                                <button type="button" wire:click="$set('fondo', '{{ number_format($m, 2, '.', '') }}')">S/ {{ $m }}</button>
                            @endforeach
                        </div>
                        <button class="ac-btn" type="submit" wire:loading.attr="disabled">🔓 Abrir caja y empezar</button>
                    </form>
                    @if ($ultimo)
                        <p class="ac-nota">Último cierre: {{ $ultimo->fecha->translatedFormat('l d/m') }} · {{ $ultimo->estadoCuadre() }}</p>
                    @endif
                @endif

                <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                    @csrf
                    <button class="ac-salir" type="submit">Salir</button>
                </form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
