/**
 * Validación en vivo de los formularios de la tienda.
 * Las mismas reglas que el servidor y que los CHECK de la base de datos.
 *
 * Uso en un campo:  data-v="dni|doc|celular|email|nombre|operacion|cupon|pedido|hoja|texto|monto"
 *   data-tipo="tipo_documento"  → (en data-v="doc" y "nombre") nombre del campo con el tipo de documento
 *   data-min="15"               → (en data-v="texto") mínimo de caracteres
 * El campo se valida al salir de él, mientras escribes (después del primer error) y al enviar.
 */
(() => {
    const LETRAS = "A-Za-zÁÉÍÓÚÜÑáéíóúüñ";

    const DOCUMENTOS = {
        DNI:       { re: /^[0-9]{8}$/,                msg: 'El DNI debe tener 8 dígitos.', max: 8, num: true },
        RUC:       { re: /^(10|15|17|20)[0-9]{9}$/,   msg: 'El RUC tiene 11 dígitos y empieza con 10, 15, 17 o 20.', max: 11, num: true },
        CE:        { re: /^[0-9]{9,12}$/,             msg: 'El carné de extranjería tiene de 9 a 12 dígitos.', max: 12, num: true },
        PASAPORTE: { re: /^[A-Za-z0-9]{6,12}$/,       msg: 'El pasaporte tiene de 6 a 12 letras o números.', max: 12, num: false },
    };

    const REGLAS = {
        documento: { re: /^[0-9]{8,12}$/, msg: 'Escribe tu DNI, RUC o carné (de 8 a 12 dígitos).', filtro: /[^0-9]/g },
        celular:   { re: /^[0-9]{7,9}$/, msg: 'Escribe de 7 a 9 dígitos, solo números.', filtro: /[^0-9]/g },
        email:     { re: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/, msg: 'Escribe un correo válido, por ejemplo nombre@gmail.com.', filtro: /\s/g },
        nombre:    { re: new RegExp(`^[${LETRAS}][${LETRAS}' .-]{1,99}$`), msg: 'Solo letras y espacios (mínimo 2 letras).', filtro: new RegExp(`[^${LETRAS}' .-]`, 'g') },
        razon:     { re: new RegExp(`^[${LETRAS}0-9][${LETRAS}0-9&'., ()-]{2,149}$`), msg: 'Escribe la razón social (mínimo 3 caracteres).' },
        operacion: { re: /^[A-Za-z0-9-]{4,30}$/, msg: 'De 4 a 30 letras o números (sin espacios).', filtro: /[^A-Za-z0-9-]/g },
        cupon:     { re: /^[A-Za-z0-9]{3,20}$/, msg: 'El cupón solo tiene letras y números.', filtro: /[^A-Za-z0-9]/g, mayus: true },
        pedido:    { re: /^WEB-[A-Z0-9]{6}$/i, msg: 'El código tiene la forma WEB-AB12CD.', mayus: true },
        hoja:      { re: /^[0-9]{6}-[0-9]{4}$/, msg: 'El número de hoja tiene la forma 000001-2026.' },
        monto:     { re: /^\d{1,5}(\.\d{1,2})?$/, msg: 'Escribe un monto válido, por ejemplo 25.50.' },
    };

    const tipoDe = (campo) => {
        const nombre = campo.dataset.tipo;
        if (! nombre) return null;
        const el = campo.form.querySelector(`[name="${nombre}"]:checked`) || campo.form.querySelector(`select[name="${nombre}"]`);
        return el ? el.value : null;
    };

    const regla = (campo) => {
        const v = campo.dataset.v;
        if (v === 'doc' || v === 'dni') return DOCUMENTOS[v === 'dni' ? 'DNI' : (tipoDe(campo) || 'DNI')];
        if (v === 'nombre' && tipoDe(campo) === 'RUC') return REGLAS.razon;
        return REGLAS[v] || null;
    };

    const visible = (campo) => campo.offsetParent !== null || campo.type === 'hidden';

    /** Mensaje de error del campo o null si está bien */
    const revisar = (campo) => {
        if (! visible(campo) || campo.disabled) return null;
        const valor = (campo.value || '').trim();

        if (campo.type === 'checkbox') return campo.required && ! campo.checked ? (campo.dataset.msg || 'Debes marcar esta casilla.') : null;
        if (valor === '') return campo.required ? (campo.dataset.msg || 'Este campo es obligatorio.') : null;

        if (campo.dataset.v === 'texto') {
            const min = parseInt(campo.dataset.min || '1', 10);
            return valor.length < min ? `Escribe al menos ${min} caracteres (llevas ${valor.length}).` : null;
        }

        const r = regla(campo);
        return r && ! r.re.test(valor) ? r.msg : null;
    };

    const caja = (campo) => {
        const cont = campo.closest('.campo') || campo.parentElement;
        let el = cont.querySelector('.error-txt');
        if (! el) {
            el = document.createElement('div');
            el.className = 'error-txt';
            cont.appendChild(el);
        }
        return el;
    };

    const pintar = (campo) => {
        const error = revisar(campo);
        const el = caja(campo);
        el.textContent = error || '';
        el.style.display = error ? '' : 'none';
        campo.classList.toggle('invalido', !! error);
        campo.classList.toggle('valido', ! error && (campo.value || '').trim() !== '' && campo.type !== 'checkbox');
        campo.setAttribute('aria-invalid', error ? 'true' : 'false');
        return ! error;
    };

    const campos = (form) => [...form.querySelectorAll('[data-v], [required]')]
        .filter((c) => ['INPUT', 'TEXTAREA', 'SELECT'].includes(c.tagName) && c.type !== 'radio');

    // Mientras escribe: filtra caracteres no permitidos y revalida si ya tenía error
    document.addEventListener('input', (e) => {
        const c = e.target;
        if (! c.form || ! c.matches('[data-v], [required]')) return;

        const r = regla(c);
        if (r) {
            let v = c.value;
            if (c.dataset.v === 'doc' || c.dataset.v === 'dni') {
                if (r.num) v = v.replace(/[^0-9]/g, '');
                v = v.slice(0, r.max);
            } else if (r.filtro) {
                v = v.replace(r.filtro, '');
            }
            if (r.mayus) v = v.toUpperCase();
            if (v !== c.value) c.value = v;
        }
        if (c.classList.contains('invalido') || c.classList.contains('valido')) pintar(c);
    });

    document.addEventListener('focusout', (e) => {
        const c = e.target;
        if (c.form && c.matches('[data-v], [required]') && c.type !== 'radio') pintar(c);
    });

    // Al cambiar el tipo de documento: revalida documento y nombre
    document.addEventListener('change', (e) => {
        const c = e.target;
        if (! c.form) return;
        if (c.type === 'checkbox' && c.matches('[required]')) pintar(c);
        c.form.querySelectorAll(`[data-tipo="${c.name}"]`).forEach((campo) => {
            const r = regla(campo);
            if (r && r.max) campo.maxLength = r.max;
            if ((campo.value || '').trim() !== '') pintar(campo);
        });
    });

    // Al enviar: si algo está mal, no se envía y se va al primer error
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.hasAttribute('data-sin-validar')) return;
        const malos = campos(form).filter((c) => ! pintar(c));
        if (malos.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            malos[0].focus({ preventScroll: true });
            malos[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            let aviso = form.querySelector('.aviso-form');
            if (! aviso) {
                aviso = document.createElement('div');
                aviso.className = 'aviso mal aviso-form';
                form.prepend(aviso);
            }
            aviso.textContent = `⚠ Revisa ${malos.length === 1 ? 'el campo marcado' : `los ${malos.length} campos marcados`} en rojo.`;
        }
    }, true);

    // La validación la hace este script (así un campo oculto, como el número de operación
    // cuando se paga al recoger, no bloquea el envío del formulario)
    document.querySelectorAll('form').forEach((f) => {
        if (f.querySelector('[data-v], [required]')) f.noValidate = true;
    });

    // Ajusta el largo máximo del documento al cargar
    document.querySelectorAll('[data-tipo]').forEach((c) => {
        const r = regla(c);
        if (r && r.max && c.dataset.v !== 'nombre') c.maxLength = r.max;
    });
})();
