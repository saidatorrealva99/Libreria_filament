/**
 * Dictado por voz: agrega un botón 🎤 a cada cuadro de texto largo (observaciones,
 * detalle de reclamo, respuesta, etc.). Al hablar, el texto se va escribiendo solo.
 *
 * Usa el reconocimiento de voz del navegador (Chrome / Edge, en español de Perú).
 * Funciona en http://127.0.0.1 o localhost y en sitios https.
 */
(() => {
    const Reconocedor = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (! Reconocedor) return;   // navegador sin soporte (ej. Firefox): no se muestra el botón

    const css = document.createElement('style');
    css.textContent = `
        .dictado-btn { position: absolute; right: .5rem; bottom: .5rem; z-index: 5; width: 2.1rem; height: 2.1rem;
            border-radius: 999px; border: 0; cursor: pointer; display: grid; place-items: center; font-size: 1rem;
            background: #f5f5f4; color: #57534e; box-shadow: 0 2px 6px rgba(0,0,0,.15); transition: transform .15s; }
        .dictado-btn:hover { transform: scale(1.08); }
        .dictado-btn.on { background: #dc2626; color: #fff; animation: dictado-pulso 1.2s infinite; }
        .dark .dictado-btn:not(.on) { background: #3f3f46; color: #e7e5e4; }
        @keyframes dictado-pulso { 0% { box-shadow: 0 0 0 0 rgba(220,38,38,.55); } 70% { box-shadow: 0 0 0 12px rgba(220,38,38,0); } 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0); } }
        textarea.dictando { border-color: #dc2626 !important; }
    `;
    document.head.appendChild(css);

    let activo = null;   // { campo, rec, boton }

    const escribir = (campo, texto) => {
        campo.value = texto;
        // Avisar a Livewire / Alpine / validaciones que el valor cambió
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const detener = () => {
        if (! activo) return;
        try { activo.rec.stop(); } catch (e) {}
        activo.boton.classList.remove('on');
        activo.boton.title = 'Dictar por voz';
        activo.campo.classList.remove('dictando');
        activo.campo.dispatchEvent(new Event('change', { bubbles: true }));
        activo = null;
    };

    const iniciar = (campo, boton) => {
        detener();

        const rec = new Reconocedor();
        rec.lang = 'es-PE';
        rec.continuous = true;
        rec.interimResults = true;

        const base = campo.value ? campo.value.replace(/\s*$/, ' ') : '';
        let finales = '';

        rec.onresult = (e) => {
            let provisional = '';
            for (let i = e.resultIndex; i < e.results.length; i++) {
                const t = e.results[i][0].transcript;
                if (e.results[i].isFinal) finales += t.trim() + ' ';
                else provisional += t;
            }
            let texto = base + finales + provisional;
            texto = texto.charAt(0).toUpperCase() + texto.slice(1);   // primera letra en mayúscula
            const max = parseInt(campo.getAttribute('maxlength') || '0', 10);
            escribir(campo, max ? texto.slice(0, max) : texto);
        };
        rec.onerror = (e) => {
            if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
                alert('Permite el uso del micrófono para dictar (candado junto a la dirección de la página).');
            }
            detener();
        };
        rec.onend = () => { if (activo && activo.rec === rec) detener(); };

        rec.start();
        activo = { campo, rec, boton };
        boton.classList.add('on');
        boton.title = 'Detener dictado';
        campo.classList.add('dictando');
        campo.focus();
    };

    const preparar = (campo) => {
        if (campo.dataset.dictado === 'no' || campo.readOnly || campo.disabled) return;
        const padre = campo.parentElement;
        if (! padre || padre.querySelector(':scope > .dictado-btn')) return;

        if (getComputedStyle(padre).position === 'static') padre.style.position = 'relative';
        campo.style.paddingRight = '2.8rem';

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'dictado-btn';
        boton.title = 'Dictar por voz';
        boton.setAttribute('aria-label', 'Dictar por voz');
        boton.textContent = '🎤';
        boton.addEventListener('click', (e) => {
            e.preventDefault();
            activo && activo.campo === campo ? detener() : iniciar(campo, boton);
        });
        padre.appendChild(boton);
    };

    const revisar = () => document.querySelectorAll('textarea, input[data-dictado]').forEach(preparar);

    // Los formularios y modales de Filament se dibujan después: vigilar la página
    let pendiente = false;
    new MutationObserver(() => {
        if (pendiente) return;
        pendiente = true;
        requestAnimationFrame(() => { pendiente = false; revisar(); });
    }).observe(document.body, { childList: true, subtree: true });

    revisar();
})();