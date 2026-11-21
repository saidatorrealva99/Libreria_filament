{{-- Solo se carga en la página de login (renderHook con scopes). No requiere compilar Tailwind. --}}
<style>
    /* Fondo: tinta y vino, con renglones de cuaderno y luces cálidas */
    .fi-simple-layout {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        padding-bottom: 3.5rem;
        background:
            radial-gradient(50rem 34rem at 10% -10%, rgba(212, 160, 23, .30), transparent 60%),
            radial-gradient(45rem 32rem at 105% 110%, rgba(192, 38, 74, .45), transparent 60%),
            linear-gradient(140deg, #1c0a10 0%, #4a0d1f 45%, #7a1530 100%) !important;
    }
    .fi-simple-layout::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -1;
        background-image: repeating-linear-gradient(0deg, rgba(255, 255, 255, .06) 0 1px, transparent 1px 34px);
        -webkit-mask-image: radial-gradient(ellipse at center, #000 20%, transparent 75%);
        mask-image: radial-gradient(ellipse at center, #000 20%, transparent 75%);
    }
    /* Crédito inferior */
    .fi-simple-layout::after {
        content: "{{ addslashes(config('app.name')) }} · Sistema de ventas · SENATI · {{ date('Y') }}";
        position: absolute;
        left: 0; right: 0; bottom: 1.25rem;
        padding: 0 1rem;
        text-align: center;
        font-size: .75rem;
        letter-spacing: .04em;
        color: rgba(255, 255, 255, .65);
    }

    /* Tarjeta tipo "página de libro" */
    .fi-simple-main {
        position: relative;
        overflow: hidden;
        max-width: 28rem !important;
        padding: 2.75rem 2.1rem 2.25rem !important;
        border-radius: 1.25rem !important;
        background: #fffdf9 !important;
        box-shadow:
            0 40px 80px -30px rgba(0, 0, 0, .7),
            0 0 0 1px rgba(255, 255, 255, .35),
            10px 10px 0 -2px rgba(255, 253, 249, .12) !important;
    }
    .fi-simple-main::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 5px;
        background: linear-gradient(90deg, #c0264a, #7a1530 55%, #d4a017);
    }
    /* Cinta marcapáginas */
    .fi-simple-main::after {
        content: "";
        position: absolute;
        top: 0; right: 2rem;
        width: 1rem; height: 3.2rem;
        background: #d4a017;
        clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 78%, 0 100%);
        opacity: .9;
    }
    .dark .fi-simple-main {
        background: rgba(28, 25, 23, .94) !important;
        box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .8), 0 0 0 1px rgba(255, 255, 255, .08) !important;
    }

    /* Textos */
    .fi-simple-header { margin-bottom: 1.75rem; }
    .fi-simple-header-heading {
        font-family: 'Fraunces', Georgia, serif;
        font-weight: 800;
        font-size: 1.85rem;
        letter-spacing: -.02em;
    }
    .fi-simple-header-subheading { font-size: .9rem; }

    /* Campos */
    .fi-simple-main .fi-input-wrp {
        border-radius: .85rem;
        background: #faf7f2;
        transition: box-shadow .15s ease;
    }
    .dark .fi-simple-main .fi-input-wrp { background: rgba(255, 255, 255, .04); }
    .fi-simple-main .fi-input-wrp input { padding-block: .7rem; }

    /* Botón principal */
    .fi-simple-main .fi-btn.fi-color-primary {
        padding-block: .85rem;
        border-radius: .85rem;
        font-weight: 600;
        background: linear-gradient(135deg, #b8274a, #6b1027) !important;
        box-shadow: 0 12px 24px -10px rgba(107, 16, 39, .75);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .fi-simple-main .fi-btn.fi-color-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px -10px rgba(107, 16, 39, .85);
    }

    /* Enlace "Volver al inicio" */
    .lib-login-back { margin-top: 1.25rem; text-align: center; font-size: .85rem; }
    .lib-login-back a { color: #9f1d3a; font-weight: 600; text-decoration: none; }
    .lib-login-back a:hover { text-decoration: underline; }
    .dark .lib-login-back a { color: #f0b7c4; }

    @media (max-width: 640px) {
        .fi-simple-main { margin-inline: 1rem !important; padding-inline: 1.5rem !important; }
    }
    @media (prefers-reduced-motion: reduce) {
        .fi-simple-main .fi-btn.fi-color-primary { transition: none; }
        .fi-simple-main .fi-btn.fi-color-primary:hover { transform: none; }
    }
</style>
