@extends('tienda.layout')

@section('titulo', 'Lista escolar inteligente')

@section('estilos')
    .le-hero { display: grid; grid-template-columns: 1.1fr .9fr; gap: 2rem; align-items: center; margin: 2rem 0 1.5rem; }
    .le-hero h1 { font-family: 'Fraunces', serif; font-size: clamp(1.9rem, 4vw, 2.8rem); line-height: 1.05; margin: .4rem 0 .6rem; }
    .le-hero h1 em { color: var(--vino); font-style: italic; }
    .chip-ia { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .7rem; border-radius: 999px; background: linear-gradient(135deg, #9f1d3a, #d4a017); color: #fff; font-size: .75rem; font-weight: 800; letter-spacing: .05em; }
    .le-pasos { display: grid; gap: .6rem; }
    .le-paso { display: flex; gap: .8rem; align-items: center; background: #fff; border: 1px solid var(--borde); border-radius: .9rem; padding: .75rem 1rem; font-size: .92rem; }
    .le-paso b { width: 2rem; height: 2rem; flex: none; display: grid; place-items: center; border-radius: 999px; background: #fdf2f4; color: var(--vino); }
    .le-form { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1.25rem; }
    .le-form textarea { width: 100%; min-height: 260px; padding: 1rem; border-radius: 1rem; border: 1.5px solid #e2dbd0; font: 500 .95rem/1.6 'DM Sans', sans-serif; resize: vertical; background: repeating-linear-gradient(transparent, transparent 1.52rem, #efe7da 1.52rem, #efe7da 1.6rem) #fffdf8; }
    .le-form textarea:focus { outline: none; border-color: var(--vino); box-shadow: 0 0 0 4px rgba(159,29,58,.12); }
    .foto-caja { display: grid; place-items: center; text-align: center; gap: .5rem; min-height: 260px; border: 2px dashed #d6cbbb; border-radius: 1rem; background: #fff; padding: 1rem; cursor: pointer; position: relative; overflow: hidden; }
    .foto-caja:hover { border-color: var(--vino); }
    .foto-caja input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .foto-caja img { max-width: 100%; max-height: 220px; border-radius: .6rem; display: none; }
    .foto-caja .ico { font-size: 2.6rem; }
    .le-acc { display: flex; gap: .75rem; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-top: 1rem; }
    .cargando { display: none; align-items: center; gap: .6rem; font-weight: 600; color: var(--vino); }
    .cargando.on { display: flex; }
    .spin { width: 1.2rem; height: 1.2rem; border-radius: 999px; border: 3px solid #f3d6dd; border-top-color: var(--vino); animation: g 1s linear infinite; }
    @keyframes g { to { transform: rotate(360deg); } }

    .res { background: #fff; border: 1px solid var(--borde); border-radius: 1.2rem; overflow: hidden; }
    .res-fila { display: grid; grid-template-columns: 34px 1fr 1.4fr 110px 100px; gap: 1rem; align-items: center; padding: .85rem 1.1rem; border-top: 1px solid var(--borde); }
    .res-fila:first-child { border-top: 0; }
    .res-cab { background: #faf7f2; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--gris); font-weight: 700; }
    .res-fila input[type=checkbox] { width: 1.15rem; height: 1.15rem; accent-color: var(--vino); }
    .res-fila input[type=number] { width: 80px; padding: .45rem .5rem; }
    .pedido { font-size: .88rem; color: #57534e; font-style: italic; }
    .sug { display: flex; gap: .75rem; align-items: center; }
    .sug .mini { width: 44px; flex: none; }
    .sug b { display: block; font-size: .92rem; }
    .sug small { color: var(--gris); }
    .nota-ia { display: block; color: #b45309; font-size: .75rem; margin-top: .15rem; }
    .no-hay { color: var(--gris); font-size: .88rem; }
    .imp { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
    .res-pie { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.1rem; background: #faf7f2; border-top: 1px solid var(--borde); }
    .res-total { font-family: 'Fraunces', serif; font-size: 1.6rem; color: var(--vino); }
    .motor { font-size: .8rem; color: var(--gris); }
    @media (max-width: 860px) {
        .le-hero, .le-form { grid-template-columns: 1fr; }
        .res-cab { display: none; }
        .res-fila { grid-template-columns: 30px 1fr 80px; }
        .res-fila .pedido { grid-column: 2 / -1; }
        .res-fila .imp { grid-column: 2 / -1; text-align: left; }
    }
@endsection

@section('contenido')
<div class="wrap">
    @if (! $resultado)
        <section class="le-hero">
            <div>
                <span class="chip-ia">✨ CON INTELIGENCIA ARTIFICIAL</span>
                <h1>Pega tu <em>lista escolar</em> y nosotros armamos tu carrito.</h1>
                <p class="sub">Escríbela, cópiala del grupo de WhatsApp o tómale una foto (incluso escrita a mano). En segundos te mostramos los productos que tenemos y el total.</p>
            </div>
            <div class="le-pasos">
                <div class="le-paso"><b>1</b> Pega la lista o súbele una foto</div>
                <div class="le-paso"><b>2</b> La IA encuentra cada producto en nuestro catálogo</div>
                <div class="le-paso"><b>3</b> Revisa, ajusta cantidades y agrégalo todo al carrito</div>
            </div>
        </section>

        <form method="POST" action="{{ route('tienda.lista.procesar') }}" enctype="multipart/form-data" class="panel"
              onsubmit="document.getElementById('cargando').classList.add('on'); document.getElementById('btn-le').disabled = true;">
            @csrf
            <div class="le-form">
                <div>
                    <label for="lista" style="display:flex;justify-content:space-between;align-items:center">
                        <span>Tu lista</span>
                        <button type="button" class="btn btn-borde btn-sm" onclick="document.getElementById('lista').value = @js(\App\Support\ListaEscolar::EJEMPLO)">Usar un ejemplo</button>
                    </label>
                    <textarea id="lista" name="lista" placeholder="Ej.:&#10;- 3 cuadernos A4 cuadriculados&#10;- 1 caja de colores x 24&#10;- 2 lápices 2B">{{ old('lista') }}</textarea>
                    @error('lista') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>…o una foto de la lista</label>
                    <label class="foto-caja">
                        <input type="file" name="foto" accept="image/*" capture="environment"
                               onchange="const f = this.files[0]; const img = document.getElementById('vista-foto'); if (f) { img.src = URL.createObjectURL(f); img.style.display = 'block'; document.getElementById('foto-txt').style.display = 'none'; }">
                        <img id="vista-foto" alt="Vista previa de la lista">
                        <span id="foto-txt"><span class="ico">📸</span><br><b>Toca para tomar o subir una foto</b><br><small style="color:var(--gris)">JPG o PNG, máx. 5 MB</small></span>
                    </label>
                    @error('foto') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="le-acc">
                <span class="cargando" id="cargando"><span class="spin"></span> Leyendo tu lista con IA… (unos segundos)</span>
                <button class="btn btn-vino" id="btn-le" style="margin-left:auto">✨ Armar mi carrito</button>
            </div>
        </form>
    @else
        @php
            $encontrados = $resultado['filas']->filter(fn ($f) => $f['producto'])->count();
            $total = $resultado['filas']->sum(fn ($f) => $f['producto'] ? $f['cantidad'] * $f['producto']->precioPara($f['cantidad']) : 0);
        @endphp

        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;flex-wrap:wrap;margin:2rem 0 1rem">
            <div>
                <span class="chip-ia">✨ {{ $resultado['motor'] === 'ia' ? 'LEÍDO CON IA' : 'BÚSQUEDA BÁSICA' }}</span>
                <h1 class="titulo-pag" style="margin:.5rem 0 .2rem">Tu lista escolar</h1>
                <p class="sub" style="margin:0">Encontramos <b>{{ $encontrados }} de {{ $resultado['filas']->count() }}</b> productos. Revisa y ajusta antes de agregar.</p>
            </div>
            <a class="btn btn-borde" href="{{ route('tienda.lista') }}">← Probar otra lista</a>
        </div>

        @if ($resultado['aviso'])
            <div class="aviso">ℹ {{ $resultado['aviso'] }}</div>
        @endif

        <form method="POST" action="{{ route('tienda.lista.agregar') }}" class="res">
            @csrf
            <div class="res-fila res-cab">
                <span></span><span>Tu lista dice</span><span>Te sugerimos</span><span>Cantidad</span><span style="text-align:right">Importe</span>
            </div>

            @foreach ($resultado['filas'] as $i => $f)
                @php $p = $f['producto']; @endphp
                <div class="res-fila" data-precio="{{ $p ? $p->precioActual() : 0 }}" data-mayor="{{ $p?->cantidad_mayor ?? 0 }}" data-dscto="{{ (float) ($p?->descuento_mayor ?? 0) }}">
                    <input type="checkbox" name="items[{{ $i }}][incluir]" value="1" @checked($p) @disabled(! $p) onchange="leTotal()" aria-label="Incluir">
                    <span class="pedido">“{{ $f['pedido'] }}”</span>
                    @if ($p)
                        <div class="sug">
                            <div class="mini">@include('tienda._portada', ['producto' => $p])</div>
                            <div>
                                <b>{{ $p->nombre }}</b>
                                <small>{{ $p->marca_editorial }} · S/ {{ number_format($p->precioActual(), 2) }}{{ $p->enOferta() ? ' (oferta)' : '' }} · stock {{ $p->stock }}</small>
                                @if ($f['nota'] || $f['sin_stock'])
                                    <span class="nota-ia">{{ $f['sin_stock'] ? "Solo hay {$p->stock} en stock." : $f['nota'] }}</span>
                                @endif
                            </div>
                        </div>
                        <input type="hidden" name="items[{{ $i }}][producto_id]" value="{{ $p->id }}">
                        <input type="number" name="items[{{ $i }}][cantidad]" value="{{ $f['cantidad'] }}" min="1" max="{{ $p->stock }}" oninput="leTotal()" aria-label="Cantidad">
                        <span class="imp">S/ <span class="imp-v">{{ number_format($f['cantidad'] * $p->precioPara($f['cantidad']), 2) }}</span></span>
                    @else
                        <span class="no-hay">😕 {{ $f['nota'] ?: 'Por ahora no lo tenemos' }}</span>
                        <span></span><span class="imp" style="color:var(--gris)">—</span>
                    @endif
                </div>
            @endforeach

            <div class="res-pie">
                <span class="motor">Precios con IGV · puedes quitar o cambiar cantidades</span>
                <span>Total: <span class="res-total">S/ <span id="le-total">{{ number_format($total, 2) }}</span></span></span>
                <button class="btn btn-vino">🛒 Agregar al carrito</button>
            </div>
        </form>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function leTotal() {
        let total = 0;
        document.querySelectorAll('.res-fila[data-precio]').forEach(fila => {
            const base = parseFloat(fila.dataset.precio) || 0;
            const mayor = parseInt(fila.dataset.mayor) || 0, dscto = parseFloat(fila.dataset.dscto) || 0;
            const check = fila.querySelector('input[type=checkbox]');
            const cant = fila.querySelector('input[type=number]');
            const imp = fila.querySelector('.imp-v');
            const n = cant ? (parseInt(cant.value) || 0) : 0;
            const precio = mayor && n >= mayor ? Math.round(base * (1 - dscto / 100) * 100) / 100 : base;   // descuento por cantidad
            const valor = n * precio;
            if (imp) imp.textContent = valor.toFixed(2);
            if (check && check.checked) total += valor;
        });
        const t = document.getElementById('le-total');
        if (t) t.textContent = total.toFixed(2);
    }
</script>
@endsection
