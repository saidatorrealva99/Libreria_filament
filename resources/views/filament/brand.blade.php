{{-- Logo de marca: se usa en el login y en el panel. Estilos en línea a propósito (no requiere compilar). --}}
<div style="display:flex;align-items:center;gap:.7rem;">
    <span style="display:flex;align-items:center;justify-content:center;width:2.6rem;height:2.6rem;border-radius:.8rem;color:#fff;background:linear-gradient(140deg,#c0264a 0%,#7a1530 70%);box-shadow:0 8px 18px -6px rgba(122,21,48,.6), inset 0 -3px 0 rgba(212,160,23,.9);">
        {{-- Libro abierto --}}
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" width="22" height="22" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
        </svg>
    </span>
    <span style="display:flex;flex-direction:column;line-height:1.05;">
        <span style="font-family:'Fraunces',Georgia,serif;font-weight:700;font-size:1.2rem;letter-spacing:-.01em;">{{ config('app.name') }}</span>
        <span style="font-size:.66rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;opacity:.6;">Sistema de ventas</span>
    </span>
</div>
