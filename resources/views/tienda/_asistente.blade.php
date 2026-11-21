{{-- Asistente virtual de la tienda (botón flotante + chat) --}}
<style>
    .ch-btn { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 90; display: flex; align-items: center; gap: .55rem; border: 0; cursor: pointer; padding: .85rem 1.15rem; border-radius: 999px; color: #fff; font: 600 .92rem 'DM Sans', sans-serif; background: linear-gradient(135deg, #b8274a, #6b1027); box-shadow: 0 16px 32px -12px rgba(107,16,39,.8); transition: transform .15s; }
    .ch-btn:hover { transform: translateY(-2px); }
    .ch-btn .pulso { width: 9px; height: 9px; border-radius: 99px; background: #34d399; box-shadow: 0 0 0 0 rgba(52,211,153,.7); animation: ch-pulso 1.8s infinite; }
    .ch-panel { position: fixed; right: 1.25rem; bottom: 5.3rem; z-index: 91; width: min(380px, calc(100vw - 2.5rem)); height: min(560px, calc(100vh - 7rem)); display: none; flex-direction: column; overflow: hidden; border-radius: 1.2rem; background: #fff; border: 1px solid var(--borde); box-shadow: 0 30px 60px -25px rgba(0,0,0,.45); }
    .ch-panel.abierto { display: flex; animation: ch-abre .2s ease; }
    .ch-cab { display: flex; align-items: center; gap: .7rem; padding: .9rem 1rem; color: #fff; background: radial-gradient(16rem 6rem at 100% 0%, rgba(212,160,23,.35), transparent 70%), linear-gradient(135deg, #7a1530, #4a0d1f); }
    .ch-cab .av { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: rgba(255,255,255,.18); font-size: 1.1rem; }
    .ch-cab strong { font-family: 'Fraunces', serif; display: block; }
    .ch-cab small { opacity: .8; font-size: .75rem; }
    .ch-cab button { margin-left: auto; background: none; border: 0; color: #fff; font-size: 1.3rem; cursor: pointer; }
    .ch-msgs { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: .7rem; background: var(--papel); }
    .ch-m { max-width: 85%; padding: .7rem .9rem; border-radius: 1rem; font-size: .9rem; line-height: 1.5; animation: ch-abre .2s ease; }
    .ch-m p { margin: 0 0 .4rem; } .ch-m p:last-child { margin: 0; }
    .ch-m ul { margin: .2rem 0; padding-left: 1.1rem; }
    .ch-m.yo { align-self: flex-end; background: var(--vino); color: #fff; border-bottom-right-radius: .3rem; }
    .ch-m.ella { align-self: flex-start; background: #fff; border: 1px solid var(--borde); border-bottom-left-radius: .3rem; }
    .ch-prods { display: flex; flex-direction: column; gap: .4rem; margin-top: .55rem; }
    .ch-prods a { display: flex; justify-content: space-between; gap: .5rem; padding: .5rem .7rem; border-radius: .7rem; background: #fbf1f3; text-decoration: none; font-size: .82rem; font-weight: 600; color: var(--vino); }
    .ch-sugs { display: flex; flex-wrap: wrap; gap: .4rem; }
    .ch-sugs button { border: 1px solid var(--borde); background: #fff; border-radius: 999px; padding: .4rem .75rem; font: 500 .8rem 'DM Sans', sans-serif; cursor: pointer; }
    .ch-sugs button:hover { border-color: var(--dorado); }
    .ch-escribe { display: flex; gap: .3rem; padding: .3rem .1rem; }
    .ch-escribe span { width: 7px; height: 7px; border-radius: 99px; background: var(--vino); animation: ch-salta 1s infinite; }
    .ch-escribe span:nth-child(2) { animation-delay: .15s; } .ch-escribe span:nth-child(3) { animation-delay: .3s; }
    .ch-form { display: flex; gap: .5rem; padding: .7rem; border-top: 1px solid var(--borde); }
    .ch-form input { flex: 1; padding: .65rem .8rem; }
    .ch-form button { border: 0; border-radius: .7rem; padding: 0 1rem; color: #fff; background: var(--vino); font-weight: 700; cursor: pointer; }
    @keyframes ch-abre { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @keyframes ch-salta { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-5px); opacity: 1; } }
    @keyframes ch-pulso { 70% { box-shadow: 0 0 0 9px rgba(52,211,153,0); } 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); } }
    @media (prefers-reduced-motion: reduce) { .ch-btn .pulso, .ch-m, .ch-escribe span, .ch-panel.abierto { animation: none; } }
</style>

<button class="ch-btn" id="ch-btn" type="button" aria-expanded="false" aria-controls="ch-panel">
    <span class="pulso"></span> ¿Te ayudo a elegir?
</button>

<section class="ch-panel" id="ch-panel" aria-label="Asistente virtual">
    <div class="ch-cab">
        <div class="av">📚</div>
        <div><strong>Lucía · asistente virtual</strong><small>Te recomiendo libros de nuestro catálogo</small></div>
        <button type="button" id="ch-cerrar" aria-label="Cerrar">×</button>
    </div>
    <div class="ch-msgs" id="ch-msgs">
        <div class="ch-m ella">¡Hola! 👋 Soy Lucía. Cuéntame qué te gusta leer o qué necesitas y te recomiendo algo de nuestro catálogo.</div>
        <div class="ch-sugs" id="ch-sugs">
            <button type="button">Recomiéndame una novela peruana</button>
            <button type="button">Necesito un libro de programación</button>
            <button type="button">Útiles para el colegio</button>
            <button type="button">¿Cómo puedo pagar?</button>
        </div>
    </div>
    <form class="ch-form" id="ch-form">
        <input id="ch-input" maxlength="400" placeholder="Escribe tu mensaje…" autocomplete="off" aria-label="Mensaje">
        <button type="submit">➤</button>
    </form>
</section>

<script>
(() => {
    const btn = document.getElementById('ch-btn'), panel = document.getElementById('ch-panel'),
          msgs = document.getElementById('ch-msgs'), form = document.getElementById('ch-form'),
          input = document.getElementById('ch-input'), sugs = document.getElementById('ch-sugs');
    let ocupado = false;

    const abrir = (si) => { panel.classList.toggle('abierto', si); btn.setAttribute('aria-expanded', si); if (si) input.focus(); };
    btn.onclick = () => abrir(!panel.classList.contains('abierto'));
    document.getElementById('ch-cerrar').onclick = () => abrir(false);

    const burbuja = (clase, html) => {
        const d = document.createElement('div');
        d.className = 'ch-m ' + clase;
        d.innerHTML = html;
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
        return d;
    };
    const escapar = (t) => t.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    async function enviar(texto) {
        texto = texto.trim();
        if (!texto || ocupado) return;
        ocupado = true;
        sugs?.remove();
        burbuja('yo', escapar(texto));
        input.value = '';
        const espera = burbuja('ella', '<div class="ch-escribe"><span></span><span></span><span></span></div>');

        try {
            const r = await fetch(@json(route('tienda.asistente')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                body: JSON.stringify({ mensaje: texto }),
            });
            const data = await r.json();
            let html = data.html || 'No pude responder ahora. Intenta de nuevo.';
            if (data.productos?.length) {
                html += '<div class="ch-prods">' + data.productos.map(p =>
                    `<a href="${p.url}"><span>${escapar(p.nombre)}</span><span>${p.precio}</span></a>`).join('') + '</div>';
            }
            espera.innerHTML = html;
        } catch (e) {
            espera.textContent = 'Hubo un problema de conexión. Intenta de nuevo.';
        }
        msgs.scrollTop = msgs.scrollHeight;
        ocupado = false;
        input.focus();
    }

    form.onsubmit = (e) => { e.preventDefault(); enviar(input.value); };
    sugs.querySelectorAll('button').forEach(b => b.onclick = () => enviar(b.textContent));
})();
</script>
