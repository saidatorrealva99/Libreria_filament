@extends('tienda.layout')

@section('titulo', "Hoja de reclamación {$r->codigo}")

@section('estilos')
    .hoja { max-width: 860px; margin: 2rem auto 0; background: #fff; border: 1px solid var(--borde); border-radius: 1.2rem; overflow: hidden; }
    .hoja-cab { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #7a1530, #4a0d1f); color: #fff; }
    .hoja-cab h1 { font-family: 'Fraunces', serif; margin: 0; font-size: 1.4rem; }
    .hoja-cab small { opacity: .8; }
    .hoja-num { margin-left: auto; text-align: right; }
    .hoja-num b { display: block; font-family: 'Fraunces', serif; font-size: 1.35rem; color: #f5d27a; }
    .bloque { padding: 1rem 1.5rem; border-top: 1px solid var(--borde); }
    .bloque h2 { font-size: .75rem; letter-spacing: .1em; text-transform: uppercase; color: var(--vino); margin: 0 0 .7rem; }
    .datos { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem 1rem; font-size: .9rem; }
    .datos span { display: block; font-size: .7rem; color: var(--gris); text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }
    .texto { white-space: pre-line; font-size: .92rem; line-height: 1.55; margin: 0; }
    .estado { display: inline-block; padding: .25rem .7rem; border-radius: 999px; font-weight: 700; font-size: .8rem; }
    .estado.pendiente { background: #fffbeb; color: #b45309; }
    .estado.en_proceso { background: #eff6ff; color: #1d4ed8; }
    .estado.atendido { background: #ecfdf5; color: var(--ok); }
    .respuesta { background: #ecfdf5; border-radius: .8rem; padding: 1rem; }
    .acciones { display: flex; gap: .75rem; justify-content: center; margin: 1.5rem 0; flex-wrap: wrap; }
    @media (max-width: 760px) { .datos { grid-template-columns: 1fr 1fr; } }
    @media print {
        .top, footer.pie-sitio, .acciones, .ch-btn, .ch-panel, .wa-btn, .franja, .aviso { display: none !important; }
        body { background: #fff; }
        .hoja { border: 1px solid #999; margin: 0; }
        .hoja-cab { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
@endsection

@section('contenido')
<div class="wrap">
    @if (session('nuevo'))
        <div class="aviso ok" style="max-width:860px;margin:1.5rem auto 0">
            ✓ Tu hoja fue registrada. Guarda el número <b>{{ $r->codigo }}</b>. Te enviamos una copia a {{ $r->email }}.
        </div>
    @endif

    <article class="hoja">
        <header class="hoja-cab">
            @include('tienda.reclamaciones._icono', ['tam' => 52])
            <div>
                <h1>Hoja de Reclamación</h1>
                <small>{{ config('libreria.razon_social') }} · RUC {{ config('libreria.ruc') }}</small>
            </div>
            <div class="hoja-num">
                N° <b>{{ $r->codigo }}</b>
                <small>{{ $r->created_at->format('d/m/Y H:i') }}</small>
            </div>
        </header>

        <section class="bloque">
            <h2>1. Consumidor</h2>
            <div class="datos">
                <div><span>Nombre</span>{{ $r->consumidor }}</div>
                <div><span>{{ \App\Models\Reclamacion::DOCUMENTOS[$r->tipo_documento] }}</span>{{ $r->numero_documento }}</div>
                <div><span>Teléfono</span>{{ $r->telefono }}</div>
                <div><span>Correo</span>{{ $r->email }}</div>
                <div style="grid-column: span 2"><span>Domicilio</span>{{ $r->direccion }}</div>
                @if ($r->menor_edad)
                    <div style="grid-column: 1 / -1"><span>Padre, madre o tutor</span>{{ $r->apoderado }}</div>
                @endif
            </div>
        </section>

        <section class="bloque">
            <h2>2. Bien contratado</h2>
            <div class="datos">
                <div><span>Tipo</span>{{ ucfirst($r->bien_tipo) }}</div>
                <div><span>Monto</span>{{ $r->monto !== null ? 'S/ ' . number_format((float) $r->monto, 2) : '—' }}</div>
                <div><span>Boleta / pedido</span>{{ $r->pedido_referencia ?: '—' }}</div>
                <div style="grid-column: 1 / -1"><span>Descripción</span>{{ $r->bien_descripcion }}</div>
            </div>
        </section>

        <section class="bloque">
            <h2>3. {{ \App\Models\Reclamacion::TIPOS[$r->tipo] }}</h2>
            <p class="texto"><b>Detalle:</b> {{ $r->detalle }}</p>
            <p class="texto" style="margin-top:.6rem"><b>Pedido:</b> {{ $r->pedido }}</p>
        </section>

        <section class="bloque">
            <h2>4. Respuesta del proveedor</h2>
            <p style="margin:0 0 .7rem">
                Estado: <span class="estado {{ $r->estado }}">{{ \App\Models\Reclamacion::ESTADOS[$r->estado] }}</span>
                @unless ($r->atendido())
                    · Fecha límite de respuesta: <b>{{ $r->vence_at->format('d/m/Y') }}</b> (15 días hábiles)
                @endunless
            </p>
            @if ($r->atendido())
                <div class="respuesta">
                    <p class="texto">{{ $r->respuesta }}</p>
                    <small style="color:var(--gris)">Respondido el {{ $r->respondido_at->format('d/m/Y H:i') }}</small>
                </div>
            @endif
        </section>

        <section class="bloque nota" style="font-size:.78rem;color:var(--gris)">
            * Reclamo: disconformidad relacionada a los productos o servicios. Queja: disconformidad no relacionada a los productos o servicios, o malestar respecto a la atención al público.<br>
            * La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.
        </section>
    </article>

    <div class="acciones">
        <button class="btn btn-vino" onclick="window.print()">🖨 Imprimir / guardar PDF</button>
        <a class="btn btn-borde" href="{{ route('inicio') }}">Volver a la tienda</a>
    </div>
</div>
@endsection
