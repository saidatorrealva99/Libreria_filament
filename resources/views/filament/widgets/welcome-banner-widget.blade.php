{{-- Banner de bienvenida. Usa su propio CSS (no depende de clases de Tailwind que Filament no incluya). --}}
<x-filament-widgets::widget>
    <style>
        .lib-banner {
            position: relative; overflow: hidden; border-radius: 1rem; padding: 1.1rem 1.5rem; color: #fff;
            background:
                radial-gradient(28rem 14rem at 100% 0%, rgba(212,160,23,.35), transparent 70%),
                linear-gradient(135deg, #7a1530 0%, #4a0d1f 100%);
            box-shadow: 0 18px 40px -22px rgba(74,13,31,.8);
        }
        .lib-banner__lines { position: absolute; inset: 0; pointer-events: none; opacity: .2;
            background-image: repeating-linear-gradient(0deg, rgba(255,255,255,.18) 0 1px, transparent 1px 30px); }
        .lib-banner__books { position: absolute; right: 1.75rem; bottom: -4px; display: none; align-items: flex-end; gap: 6px; pointer-events: none; }
        .lib-banner__books span { display: block; border-radius: 3px 3px 0 0; }
        .lib-banner__body { position: relative; display: flex; flex-direction: column; gap: .9rem; }
        .lib-banner__date { font-size: .72rem; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #f5d27a; margin: 0; }
        .lib-banner__title { font-family: 'Fraunces', Georgia, serif; font-size: 1.35rem; font-weight: 700; letter-spacing: -.015em; margin: .15rem 0; line-height: 1.15; }
        .lib-banner__text { font-size: .82rem; color: rgba(255,255,255,.78); margin: 0; }
        .lib-banner__actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
        .lib-banner__btn { display: inline-flex; align-items: center; gap: .5rem; border-radius: .65rem; padding: .4rem .75rem;
            font-size: .8rem; font-weight: 600; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease; }
        .lib-banner__btn:hover { transform: translateY(-2px); }
        .lib-banner__btn svg { width: 1rem; height: 1rem; }
        .lib-banner__btn--gold { background: #d4a017; color: #2a0a12; box-shadow: 0 10px 20px -10px rgba(0,0,0,.6); }
        .lib-banner__btn--ghost { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.28); color: #fff; }
        .lib-banner__pill { display: inline-flex; align-items: center; gap: .5rem; border-radius: .8rem; padding: .5rem .8rem;
            background: rgba(0,0,0,.22); font-size: .7rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.85); }
        .lib-banner__dot { width: .6rem; height: .6rem; border-radius: 999px; background: #34d399; animation: lib-pulse 1.6s ease-in-out infinite; }
        @keyframes lib-pulse { 50% { opacity: .35; } }
        @media (min-width: 768px) {
            .lib-banner { padding: 1.1rem 1.75rem; }
            .lib-banner__title { font-size: 1.55rem; }
            .lib-banner__body { flex-direction: row; align-items: center; justify-content: space-between; padding-right: 6rem; }
            .lib-banner__actions { justify-content: flex-end; max-width: 40rem; }
            .lib-banner__books { display: flex; }
        }
        @media (prefers-reduced-motion: reduce) { .lib-banner__dot { animation: none; } .lib-banner__btn { transition: none; } }
    </style>

    <div class="lib-banner">
        <div class="lib-banner__lines" aria-hidden="true"></div>

        {{-- Libros apilados decorativos --}}
        <div class="lib-banner__books" aria-hidden="true">
            <span style="width:14px;height:56px;background:#d4a017;"></span>
            <span style="width:18px;height:70px;background:#fffdf9;opacity:.92;"></span>
            <span style="width:12px;height:48px;background:#c0264a;box-shadow:inset 0 0 0 2px rgba(255,255,255,.25);"></span>
            <span style="width:16px;height:62px;background:#f5e6c8;transform:rotate(8deg);transform-origin:bottom left;"></span>
        </div>

        <div class="lib-banner__body">
            <div>
                <p class="lib-banner__date">{{ $fecha }}</p>
                <h2 class="lib-banner__title">{{ $saludo }}, {{ $nombre }}</h2>
                <p class="lib-banner__text">Ventas, compras, inventario y tienda online en un solo lugar.</p>
            </div>

            <div class="lib-banner__actions">
                @foreach ($acciones as $i => [$texto, $icono, $url, $principal])
                    <a href="{{ $url }}" @class(['lib-banner__btn', 'lib-banner__btn--gold' => $i === 0, 'lib-banner__btn--ghost' => $i > 0])
                       @if ($texto === 'Ver tienda') target="_blank" rel="noopener" @endif>
                        <x-filament::icon :icon="$icono" />
                        {{ $texto }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
