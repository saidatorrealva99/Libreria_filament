<x-filament-panels::page>
    @php
        $widgets = $this->getWidgetsReporte();
        $filtros = $this->tableFilters ?? [];
        // Clave que cambia cuando cambian los filtros → los widgets se recargan
        $clave = md5(json_encode($filtros));
    @endphp

    <div style="font-size:.8rem;color:rgb(120 113 108);">
        Generado: {{ now()->format('d/m/Y H:i') }} · Usuario: {{ auth()->user()?->name }}
    </div>

    {{-- Widgets del reporte (tarjetas y gráficos) --}}
    @if (count($widgets))
        <div class="lib-reporte-grid">
            @foreach ($widgets as $widget => $columnas)
                <div class="lib-reporte-celda" style="--span: {{ $columnas }};">
                    @livewire($widget, ['filtros' => $filtros], key($widget . '-' . $clave))
                </div>
            @endforeach
        </div>
    @endif

    {{ $this->table }}

    <style>
        .lib-reporte-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 1024px) {
            .lib-reporte-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .lib-reporte-celda { grid-column: span var(--span) / span var(--span); }
        }
        /* Al imprimir, ocultar menú y botones */
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header-actions, .fi-ta-header-toolbar,
            .fi-pagination, .fi-ta-filters-above-content-ctn { display: none !important; }
            .fi-main { padding: 0 !important; max-width: 100% !important; }
            .fi-main-ctn { margin: 0 !important; }
        }
    </style>
</x-filament-panels::page>
