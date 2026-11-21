{{-- Mi turno: lo que necesita la vendedora / cajera, sin datos sensibles del negocio --}}
<x-filament-widgets::widget>
    <style>
        .mt { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
        .mt-card { border-radius: .9rem; background: #fff; outline: 1px solid rgba(3,7,18,.06); box-shadow: 0 1px 2px rgba(0,0,0,.05); padding: 1rem 1.15rem; }
        .dark .mt-card { background: rgb(24 24 27); outline-color: rgba(255,255,255,.1); }
        .mt-card p { margin: 0; font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: #78716c; font-weight: 600; }
        .mt-card strong { display: block; font-size: 1.55rem; margin: .2rem 0; font-variant-numeric: tabular-nums; }
        .mt-card small { color: #78716c; font-size: .8rem; }
        .mt-caja { border-top: 4px solid #16a34a; }
        .mt-alerta { border-top: 4px solid #d4a017; }
        .mt-metodos { display: grid; grid-template-columns: 1fr auto; gap: .2rem .8rem; font-size: .85rem; margin-top: .4rem; }
        .mt-metodos span:nth-child(even) { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        .mt-acc { display: flex; gap: .6rem; flex-wrap: wrap; margin-top: 1rem; }
        .mt-btn { display: inline-flex; align-items: center; gap: .4rem; padding: .65rem 1rem; border-radius: .7rem; font-weight: 700; font-size: .9rem; text-decoration: none; border: 1px solid rgba(120,113,108,.35); color: inherit; }
        .mt-btn.principal { background: #9f1d3a; color: #fff; border-color: #9f1d3a; }
        .mt-btn.cerrar { border-color: #dc2626; color: #dc2626; }
        @media (max-width: 640px) { .mt-btn { flex: 1 1 100%; justify-content: center; } }
    </style>

    <div class="mt">
        <div class="mt-card mt-caja">
            <p>🟢 Caja {{ $caja?->estado === 'abierta' ? 'abierta' : 'cerrada' }}</p>
            <strong>S/ {{ number_format($enCaja, 2) }}</strong>
            <small>Efectivo que debería haber
                @if ($caja?->abierta_at) · desde las {{ $caja->abierta_at->format('H:i') }} (fondo S/ {{ number_format((float) $caja->fondo_inicial, 2) }}) @endif
            </small>
        </div>
        <div class="mt-card">
            <p>Mis ventas del turno</p>
            <strong>{{ $misVentas }}</strong>
            <small>S/ {{ number_format($misMonto, 2) }} · {{ $ventasHoy }} ventas en total hoy</small>
        </div>
        <div class="mt-card">
            <p>Cobré en mi turno</p>
            <strong>S/ {{ number_format($cobrado['total'], 2) }}</strong>
            <div class="mt-metodos">
                @foreach ($metodos as $clave => $nombre)
                    @if ($cobrado[$clave] > 0)
                        <span>{{ $nombre }}</span><span>S/ {{ number_format($cobrado[$clave], 2) }}</span>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="mt-card {{ $pendientes || $porVerificar ? 'mt-alerta' : '' }}">
            <p>Por cobrar hoy</p>
            <strong>{{ $pendientes }}</strong>
            <small>S/ {{ number_format($saldo, 2) }} pendiente{{ $porVerificar ? " · {$porVerificar} pago(s) web por verificar" : '' }}</small>
        </div>
    </div>

    <div class="mt-acc">
        <a class="mt-btn principal" href="{{ $urlNueva }}">🛒 Nueva venta</a>
        <a class="mt-btn" href="{{ $urlVentas }}">📋 Ventas de hoy</a>
        @if ($caja)
            <a class="mt-btn" href="{{ $urlCaja }}">🧾 Historial de mi turno</a>
        @endif
        @if ($caja?->estado === 'abierta')
            <a class="mt-btn cerrar" href="{{ \App\Filament\Resources\CierreCajaResource::getUrl('index') }}">🔒 Cerrar caja</a>
        @endif
    </div>
</x-filament-widgets::widget>
