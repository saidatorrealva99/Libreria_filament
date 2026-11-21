{{-- Estilos globales del panel. Se inyectan con renderHook (HEAD_END); no hace falta compilar Tailwind. --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=fraunces:600,700,800&display=swap" rel="stylesheet">

<style>
    :root {
        --lib-vino: #9f1d3a;
        --lib-vino-oscuro: #6b1027;
        --lib-dorado: #d4a017;
        --lib-papel: #faf7f2;
    }

    /* Fondo cálido tipo papel (modo claro) */
    html:not(.dark) .fi-body { background-color: var(--lib-papel); }

    /* Títulos con tipografía editorial */
    .fi-header-heading,
    .fi-section-header-heading,
    .fi-wi-stats-overview-stat-value,
    .fi-modal-heading,
    .fi-ta-header-heading,
    .fi-wi-chart .fi-section-header-heading {
        font-family: 'Fraunces', Georgia, serif !important;
        letter-spacing: -.01em;
    }
    .fi-header-heading { font-weight: 700; }

    /* Barra lateral: franja dorada y grupo activo resaltado */
    html:not(.dark) .fi-sidebar { background: #fff; border-right: 1px solid #ece6dc; }
    .fi-sidebar-header { border-bottom: 3px solid transparent; border-image: linear-gradient(90deg, var(--lib-vino), var(--lib-dorado)) 1; }
    .fi-sidebar-group-label { letter-spacing: .08em; text-transform: uppercase; font-size: .7rem !important; }
    .fi-sidebar-item-active > a { box-shadow: inset 3px 0 0 var(--lib-dorado); }

    /* Tarjetas y secciones un poco más redondeadas y suaves */
    .fi-section, .fi-ta-ctn, .fi-wi-stats-overview-stat {
        border-radius: 1rem !important;
    }
    html:not(.dark) .fi-section,
    html:not(.dark) .fi-ta-ctn,
    html:not(.dark) .fi-wi-stats-overview-stat {
        box-shadow: 0 1px 2px rgba(60, 40, 20, .05), 0 8px 24px -12px rgba(60, 40, 20, .12) !important;
    }

    /* Estadísticas: línea de color arriba */
    .fi-wi-stats-overview-stat { position: relative; overflow: hidden; }
    .fi-wi-stats-overview-stat::before {
        content: ""; position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, var(--lib-vino), var(--lib-dorado));
    }

    /* Botón principal con degradado */
    .fi-btn.fi-color-primary:not(.fi-btn-outlined) {
        background-image: linear-gradient(135deg, #b8274a, var(--lib-vino-oscuro));
    }

    /* Pie de página */
    .lib-footer {
        padding: 1.25rem 1rem 1.75rem;
        text-align: center;
        font-size: .75rem;
        color: rgb(120 113 108);
    }

    /* ===== Impresión de reportes: solo el contenido ===== */
    @media print {
        .fi-sidebar, .fi-topbar, .fi-header-actions, .fi-ta-header-toolbar,
        .fi-ta-selection-cell, .fi-ta-actions, .fi-pagination, .fi-tabs, .lib-footer,
        .fi-ta-filter-indicators { display: none !important; }
        .fi-main { padding: 0 !important; max-width: 100% !important; }
        .fi-main-ctn { margin: 0 !important; }
        .fi-body { background: #fff !important; }
        .fi-section, .fi-ta-ctn { box-shadow: none !important; border: 1px solid #ddd !important; }
    }

    /* ===== Dashboard compacto ===== */
    .fi-dashboard-page > section { row-gap: 1rem !important; padding-top: 1rem !important; }
    .fi-dashboard-page .fi-wi { gap: 1rem !important; }
    .fi-dashboard-page .fi-wi-stats-overview-stats-ctn { gap: .75rem !important; }
    .fi-dashboard-page .fi-wi-stats-overview-stat { padding: .85rem 1.1rem !important; }
    .fi-dashboard-page .fi-wi-stats-overview-stat-value { font-size: 1.45rem !important; line-height: 1.9rem !important; }
    .fi-dashboard-page .fi-wi-stats-overview-stat-description { font-size: .78rem !important; }
    .fi-dashboard-page .fi-wi-stats-overview-stat .grid { row-gap: .25rem !important; }
    .fi-dashboard-page .fi-section-header { padding: .8rem 1.1rem !important; }
    .fi-dashboard-page .fi-section-content { padding: .9rem 1.1rem !important; }
    .fi-dashboard-page .fi-ta-header { padding: .8rem 1.1rem !important; }
    .fi-dashboard-page .fi-ta-text { padding-top: .55rem !important; padding-bottom: .55rem !important; }
    /* Filtro de periodo: una sola línea pequeña */
    .fi-dashboard-page .lib-filtro .fi-section-content { padding: .6rem 1rem !important; }
</style>
