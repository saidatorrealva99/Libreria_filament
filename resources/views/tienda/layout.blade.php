<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Tienda') · {{ config('app.name') }}</title>
    {{-- PWA: instalar como app --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#7a1530">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/pwa/apple-touch-icon.png') }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|fraunces:600,700,800&display=swap" rel="stylesheet">
    <style>
        :root { --vino:#9f1d3a; --vino-osc:#6b1027; --dorado:#d4a017; --papel:#faf7f2; --tinta:#1c1917; --gris:#78716c; --borde:#ece6dc; --ok:#047857; --mal:#b91c1c; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: 'DM Sans', system-ui, sans-serif; color: var(--tinta); background: var(--papel); }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 1.25rem; }
        .serif { font-family: 'Fraunces', Georgia, serif; }

        /* ===== Barra superior ===== */
        .top { position: sticky; top: 0; z-index: 50; background: rgba(250,247,242,.88); backdrop-filter: blur(10px); border-bottom: 1px solid var(--borde); }
        .top .wrap { display: flex; align-items: center; gap: 1.25rem; height: 70px; }
        .brand { display: flex; align-items: center; gap: .65rem; text-decoration: none; margin-right: auto; }
        .brand-ico { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; color: #fff; background: linear-gradient(140deg,#c0264a,#7a1530); box-shadow: 0 8px 18px -6px rgba(122,21,48,.6), inset 0 -3px 0 var(--dorado); }
        .brand strong { display: block; font-family: 'Fraunces', serif; font-size: 1.15rem; line-height: 1; }
        .brand small { font-size: .62rem; letter-spacing: .16em; text-transform: uppercase; color: var(--gris); font-weight: 600; }
        .menu { display: flex; align-items: center; gap: 1.25rem; font-weight: 600; font-size: .92rem; }
        .menu a { text-decoration: none; color: #44403c; }
        .menu a:hover, .menu a.activo { color: var(--vino); }
        .carrito-btn { position: relative; display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .9rem; border-radius: .8rem; background: var(--vino); color: #fff !important; }
        .btn-ingresar { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .85rem; border-radius: .8rem; border: 1.5px solid var(--vino); color: var(--vino) !important; background: #fff; }
        .btn-ingresar:hover, .ingresar[open] .btn-ingresar { background: var(--vino); color: #fff !important; }
        .ingresar { position: relative; }
        .ingresar summary { list-style: none; cursor: pointer; }
        .ingresar summary::-webkit-details-marker { display: none; }
        .ingresar-menu { position: absolute; right: 0; top: calc(100% + .5rem); width: 250px; background: #fff; border: 1px solid var(--borde); border-radius: 1rem; box-shadow: 0 20px 40px -15px rgba(0,0,0,.25); padding: .4rem; z-index: 60; }
        .ingresar-menu a { display: block; padding: .7rem .8rem; border-radius: .7rem; text-decoration: none; color: var(--tinta) !important; }
        .ingresar-menu a + a { border-top: 1px solid var(--borde); }
        .ingresar-menu a:hover { background: #faf5ef; }
        .ingresar-menu b { display: block; font-size: .92rem; }
        .ingresar-menu small { color: var(--gris); font-weight: 500; font-size: .78rem; }
        @media (max-width: 760px) { .btn-ingresar span { display: none; } .btn-ingresar { padding: .5rem .6rem; } }
        .carrito-btn .num { min-width: 1.35rem; height: 1.35rem; padding: 0 .35rem; border-radius: 999px; background: var(--dorado); color: #2a0a12; font-size: .75rem; display: grid; place-items: center; }

        /* ===== Botones y formularios ===== */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border: 0; cursor: pointer; border-radius: .85rem; padding: .8rem 1.25rem; font: 600 .95rem 'DM Sans', sans-serif; text-decoration: none; transition: transform .15s ease, box-shadow .15s ease, background .15s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-vino { color: #fff; background: linear-gradient(135deg,#b8274a,var(--vino-osc)); box-shadow: 0 12px 24px -12px rgba(107,16,39,.8); }
        .btn-borde { background: #fff; border: 1.5px solid #d6cfc4; color: var(--tinta); }
        .btn-dorado { background: var(--dorado); color: #2a0a12; }
        .btn-sm { padding: .5rem .8rem; font-size: .85rem; border-radius: .65rem; }
        .btn[disabled] { opacity: .5; cursor: not-allowed; transform: none; }
        label { display: block; font-size: .82rem; font-weight: 600; color: #44403c; margin-bottom: .35rem; }
        input, select { width: 100%; padding: .75rem .9rem; border-radius: .75rem; border: 1.5px solid #e2dbd0; background: #fff; font: 500 .95rem 'DM Sans', sans-serif; color: var(--tinta); }
        input:focus, select:focus { outline: none; border-color: var(--vino); box-shadow: 0 0 0 4px rgba(159,29,58,.12); }
        .campo { margin-bottom: 1rem; }
        .error-txt { color: var(--mal); font-size: .8rem; margin-top: .3rem; }
        .error-txt:empty { display: none; }
        input.invalido, textarea.invalido, select.invalido { border-color: var(--mal) !important; box-shadow: 0 0 0 3px rgba(220,38,38,.12) !important; background: #fffafa; }
        input.valido, textarea.valido { border-color: #16a34a !important; }

        /* ===== Avisos ===== */
        .aviso { margin: 1rem 0; padding: .9rem 1.1rem; border-radius: .9rem; font-weight: 600; font-size: .92rem; display: flex; gap: .6rem; align-items: center; }
        .aviso.ok { background: #ecfdf5; color: var(--ok); border: 1px solid #a7f3d0; }
        .aviso.mal { background: #fef2f2; color: var(--mal); border: 1px solid #fecaca; }

        /* ===== Tarjetas de producto ===== */
        .grid-prod { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
        .prod { background: #fff; border: 1px solid var(--borde); border-radius: 1.1rem; overflow: hidden; display: flex; flex-direction: column; transition: transform .2s ease, box-shadow .2s ease, border-color .2s; }
        .prod:hover { transform: translateY(-4px); border-color: #e2b8c3; box-shadow: 0 18px 36px -20px rgba(60,40,20,.35); }
        .prod .foto { display: block; background: #f3eee6; padding: 1.1rem 1.1rem 0; text-decoration: none; }
        .prod .info { padding: .9rem 1rem 1rem; display: flex; flex-direction: column; gap: .3rem; flex: 1; }
        .prod .cat { font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--vino); }
        .prod .nom { font-weight: 700; line-height: 1.25; text-decoration: none; }
        .prod .aut { font-size: .82rem; color: var(--gris); }
        .prod .pie { margin-top: auto; padding-top: .6rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .precio { font-family: 'Fraunces', serif; font-weight: 700; font-size: 1.25rem; color: var(--vino); }
        .chip { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 700; padding: .2rem .55rem; border-radius: 999px; }
        .chip.ok { background: #ecfdf5; color: var(--ok); }
        .chip.poco { background: #fffbeb; color: #b45309; }
        .chip.no { background: #fef2f2; color: var(--mal); }

        /* Portada generada (cuando no hay foto) */
        .portada { position: relative; aspect-ratio: 2/3; border-radius: 3px 9px 9px 3px; overflow: hidden; color: #fff; padding: .9rem .8rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: inset 6px 0 0 rgba(0,0,0,.18), 0 14px 24px -14px rgba(0,0,0,.55); }
        .portada::before { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(90deg, transparent 0 6px, rgba(255,255,255,.04) 6px 7px); }
        .portada * { position: relative; }
        .portada .p-cat { font-size: .55rem; letter-spacing: .14em; text-transform: uppercase; opacity: .85; }
        .portada .p-tit { font-family: 'Fraunces', serif; font-weight: 700; font-size: .95rem; line-height: 1.15; }
        .portada .p-fil { height: 2px; width: 40%; margin: .35rem 0; }
        .portada .p-aut { font-size: .68rem; opacity: .85; }
        .portada-img { aspect-ratio: 2/3; object-fit: cover; width: 100%; border-radius: 3px 9px 9px 3px; box-shadow: 0 14px 24px -14px rgba(0,0,0,.55); }

        /* ===== Paneles ===== */
        .panel { background: #fff; border: 1px solid var(--borde); border-radius: 1.2rem; padding: 1.5rem; }
        .titulo-pag { font-family: 'Fraunces', serif; font-size: clamp(1.8rem, 4vw, 2.5rem); letter-spacing: -.02em; margin: 2rem 0 .4rem; }
        .sub { color: var(--gris); margin: 0 0 1.5rem; }
        .vacio { text-align: center; padding: 3.5rem 1rem; color: var(--gris); }
        .vacio .serif { font-size: 1.4rem; color: var(--tinta); }

        /* ===== Pie ===== */
        footer.pie-sitio { margin-top: 4rem; background: #1c0a10; color: rgba(255,255,255,.75); padding: 2.5rem 0 1.5rem; font-size: .88rem; }
        footer.pie-sitio .cols { display: grid; grid-template-columns: 1.6fr 1fr 1fr 1.3fr; gap: 2rem; }
        footer.pie-sitio h4 { color: #fff; font-family: 'Fraunces', serif; margin: 0 0 .7rem; }
        footer.pie-sitio a { color: rgba(255,255,255,.75); text-decoration: none; display: block; margin-bottom: .35rem; }
        footer.pie-sitio a:hover { color: var(--dorado); }
        footer.pie-sitio .cr { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,.1); text-align: center; font-size: .78rem; }



        /* ===== Instalar app (PWA) ===== */
        .pwa-aviso { position: fixed; left: 50%; bottom: 1.25rem; transform: translate(-50%, 150%); z-index: 95; display: flex; align-items: center; gap: .8rem; width: min(440px, calc(100vw - 2rem)); padding: .75rem .9rem; border-radius: 1rem; background: #1c1917; color: #fff; box-shadow: 0 24px 48px -16px rgba(0,0,0,.6); transition: transform .35s cubic-bezier(.2,.9,.3,1.2); }
        .pwa-aviso.on { transform: translate(-50%, 0); }
        .pwa-aviso img { width: 44px; height: 44px; border-radius: 11px; }
        .pwa-aviso b { display: block; font-size: .92rem; }
        .pwa-aviso small { color: #d6d3d1; font-size: .78rem; }
        .pwa-aviso .btn { margin-left: auto; padding: .5rem .9rem; font-size: .85rem; }
        .pwa-aviso .x { background: none; border: 0; color: #a8a29e; font-size: 1.2rem; cursor: pointer; padding: 0 .2rem; }

        /* ===== Etiquetas y fichas de útiles ===== */
        .prod .foto { position: relative; }
        .etq { position: absolute; top: .7rem; left: .7rem; z-index: 2; font-size: .68rem; font-weight: 800; letter-spacing: .04em; padding: .25rem .55rem; border-radius: 999px; box-shadow: 0 6px 14px -6px rgba(0,0,0,.4); }
        .etq-top { background: var(--dorado); color: #2a0a12; }
        .etq-nuevo { background: var(--vino); color: #fff; }
        .etq-oferta { background: #dc2626; color: #fff; font-size: .78rem; }
        .antes { color: var(--gris); font-weight: 500; font-size: .85em; margin-right: .3rem; }
        .oferta { color: #dc2626; }
        .etq-inline { display: inline-block; vertical-align: middle; background: #dc2626; color: #fff; font: 800 .85rem 'DM Sans', sans-serif; padding: .2rem .55rem; border-radius: 999px; margin-left: .4rem; }
        .mayor { display: block; font-size: .72rem; color: #b45309; font-weight: 600; margin-top: .15rem; }
        .util { position: relative; aspect-ratio: 2/3; border-radius: 12px; display: grid; place-items: center; overflow: hidden; }
        .util-ico { font-size: clamp(3rem, 7vw, 4.5rem); filter: drop-shadow(0 10px 12px rgba(0,0,0,.18)); }
        .util-marca { position: absolute; left: .6rem; bottom: .6rem; max-width: calc(100% - 1.2rem); padding: .2rem .55rem; border-radius: .4rem; color: #fff; font-size: .68rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ===== Franja superior y WhatsApp ===== */
        .franja { background: var(--vino-osc); color: #fde9b8; font-size: .78rem; font-weight: 600; }
        .franja .wrap { display: flex; justify-content: center; gap: 1.75rem; padding-top: .45rem; padding-bottom: .45rem; flex-wrap: wrap; }
        .franja a { color: #fde9b8; text-decoration: none; }
        .wa-btn { position: fixed; left: 1.25rem; bottom: 1.25rem; z-index: 89; width: 54px; height: 54px; border-radius: 999px; display: grid; place-items: center; background: #25d366; color: #fff; box-shadow: 0 14px 28px -10px rgba(0,0,0,.45); transition: transform .15s; }
        .wa-btn:hover { transform: scale(1.07); }

        /* ===== Pie: libro de reclamaciones y pagos ===== */
        .lr-link { display: flex !important; align-items: center; gap: .7rem; padding: .7rem .8rem; border-radius: .8rem; background: #fff; color: var(--tinta) !important; font-weight: 700; line-height: 1.15; max-width: 240px; }
        .lr-link small { display: block; font-weight: 500; color: var(--gris); font-size: .72rem; }
        .pagos { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .9rem; }
        .pagos span { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: .4rem; padding: .2rem .5rem; font-size: .72rem; font-weight: 700; color: #fff; }

        @media (max-width: 980px) { .grid-prod { grid-template-columns: repeat(3, 1fr); } footer.pie-sitio .cols { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 760px) {
            .grid-prod { grid-template-columns: repeat(2, 1fr); gap: .9rem; }
            .menu .ocultar-movil { display: none; }
            .franja .wrap { gap: .3rem 1rem; }
            .franja .ocultar-movil { display: none; }
            footer.pie-sitio .cols { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
        @yield('estilos')
    </style>
</head>
<body>
    <div class="franja">
        <div class="wrap">
            <a href="{{ route('tienda.lista') }}">✨ Nuevo: arma tu lista escolar con IA</a>
            <span class="ocultar-movil">🏬 Compra online y recoge en tienda</span>
            <span>📱 Paga con Yape o Plin</span>
            <span class="ocultar-movil">🧾 Boleta o factura electrónica</span>
            <a class="ocultar-movil" href="{{ route('reclamaciones.formulario') }}">📕 Libro de Reclamaciones</a>
        </div>
    </div>
    <header class="top">
        <div class="wrap">
            <a href="{{ route('inicio') }}" class="brand">
                <span class="brand-ico">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" width="21" height="21" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                </span>
                <span><strong>{{ config('app.name') }}</strong><small>Libros · Útiles · Papelería</small></span>
            </a>
            <nav class="menu">
                <a href="{{ route('tienda.catalogo') }}" class="{{ request()->routeIs('tienda.catalogo', 'tienda.producto') ? 'activo' : '' }}">Catálogo</a>
                <a href="{{ route('tienda.lista') }}" class="{{ request()->routeIs('tienda.lista*') ? 'activo' : '' }}">✨ Lista escolar</a>
                <a href="{{ route('tienda.pagina', 'ayuda') }}" class="ocultar-movil {{ request()->routeIs('tienda.pagina') ? 'activo' : '' }}">Ayuda</a>
                <a href="{{ route('tienda.seguimiento') }}" class="ocultar-movil {{ request()->routeIs('tienda.seguimiento', 'tienda.pedido') ? 'activo' : '' }}">Mi pedido</a>
                <details class="ingresar">
                    <summary class="btn-ingresar" title="Ingresar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                        <span>Ingresar ▾</span>
                    </summary>
                    <div class="ingresar-menu">
                        <a href="{{ route('tienda.seguimiento') }}">
                            <b>🛍️ Soy cliente</b>
                            <small>Ver mi pedido con mi código y DNI</small>
                        </a>
                        <a href="{{ url('/admin') }}">
                            <b>🔐 Soy del personal</b>
                            <small>{{ auth()->check() ? 'Ir a mi panel' : 'Acceso al sistema' }}</small>
                        </a>
                    </div>
                </details>
                <a href="{{ route('tienda.carrito') }}" class="carrito-btn" aria-label="Carrito">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="19" height="19"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                    <span class="num">{{ \App\Support\Carrito::cantidad() }}</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="wrap">
        @if (session('ok'))
            <div class="aviso ok">✓ {{ session('ok') }}</div>
        @endif
        @if (session('error'))
            <div class="aviso mal">⚠ {{ session('error') }}</div>
        @endif
    </main>

    @yield('contenido')

    <footer class="pie-sitio">
        <div class="wrap">
            <div class="cols">
                <div>
                    <h4>{{ config('app.name') }}</h4>
                    {{ config('libreria.razon_social') }} · RUC {{ config('libreria.ruc') }}<br>
                    {{ config('libreria.direccion') }}<br>
                    Tel. {{ config('libreria.telefono') }} · {{ config('libreria.email') }}<br>
                    Atención: {{ config('libreria.horario') }}
                    <div class="pagos" aria-label="Medios de pago">
                        <span>Yape</span><span>Plin</span><span>Efectivo</span><span>Visa</span><span>Mastercard</span>
                    </div>
                </div>
                <div>
                    <h4>Tienda</h4>
                    <a href="{{ route('tienda.catalogo') }}">Catálogo</a>
                    <a href="{{ route('tienda.catalogo', ['orden' => 'vendidos']) }}">Más vendidos</a>
                    <a href="{{ route('tienda.carrito') }}">Carrito</a>
                    <a href="{{ route('tienda.seguimiento') }}">Seguir mi pedido</a>
                    <a href="{{ url('/admin') }}">Acceso al personal</a>
                </div>
                <div>
                    <h4>Ayuda</h4>
                    <a href="{{ route('tienda.pagina', 'ayuda') }}">Preguntas frecuentes</a>
                    <a href="{{ route('tienda.pagina', 'envios') }}">Recojo y entregas</a>
                    <a href="{{ route('tienda.pagina', 'cambios') }}">Cambios y devoluciones</a>
                    <a href="{{ route('tienda.pagina', 'terminos') }}">Términos y condiciones</a>
                    <a href="{{ route('tienda.pagina', 'privacidad') }}">Política de privacidad</a>
                </div>
                <div>
                    <h4>Atención al cliente</h4>
                    <a class="lr-link" href="{{ route('reclamaciones.formulario') }}">
                        @include('tienda.reclamaciones._icono', ['tam' => 40])
                        <span>Libro de Reclamaciones<small>Registra tu reclamo o queja</small></span>
                    </a>
                    <a href="{{ route('reclamaciones.consulta') }}" style="margin-top:.6rem">Consultar mi reclamo</a>
                </div>
            </div>
            <div class="cr">{{ config('app.name') }} · Laravel 11 + Filament 3 · SENATI {{ date('Y') }}</div>
        </div>
    </footer>

    {{-- WhatsApp --}}
    <a class="wa-btn" href="https://wa.me/{{ config('libreria.whatsapp') }}?text={{ rawurlencode('Hola, quisiera información sobre un producto.') }}" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>
    </a>

    {{-- Aviso "Instalar app" (aparece solo si el navegador lo permite) --}}
    <div class="pwa-aviso" id="pwa-aviso" role="dialog" aria-label="Instalar aplicación">
        <img src="{{ asset('img/pwa/icono-192.png') }}" alt="">
        <span><b>Instala {{ config('app.name') }}</b><small>Ábrela como app desde tu pantalla de inicio</small></span>
        <button class="btn btn-dorado" id="pwa-instalar">Instalar</button>
        <button class="x" id="pwa-cerrar" aria-label="Cerrar">✕</button>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
        (() => {
            let evento = null;
            const aviso = document.getElementById('pwa-aviso');
            const cerrado = () => { try { return sessionStorage.getItem('pwa-cerrado'); } catch (e) { return null; } };
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                evento = e;
                if (! cerrado()) setTimeout(() => aviso.classList.add('on'), 2500);
            });
            document.getElementById('pwa-instalar').addEventListener('click', async () => {
                aviso.classList.remove('on');
                if (evento) { evento.prompt(); await evento.userChoice; evento = null; }
            });
            document.getElementById('pwa-cerrar').addEventListener('click', () => {
                aviso.classList.remove('on');
                try { sessionStorage.setItem('pwa-cerrado', '1'); } catch (e) {}
            });
            window.addEventListener('appinstalled', () => aviso.classList.remove('on'));
        })();
    </script>

    <script src="{{ asset('js/validaciones.js') }}?v=2" defer></script>
    <script src="{{ asset('js/dictado.js') }}?v=1" defer></script>

    @include('tienda._asistente')
    @yield('scripts')
<script>
document.addEventListener('click', e => {
    document.querySelectorAll('details.ingresar[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); });
});
</script>
</body>
</html>
