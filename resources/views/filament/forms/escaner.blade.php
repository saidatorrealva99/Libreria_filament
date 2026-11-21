{{-- Escáner de código de barras para Nueva venta.
     Funciona con: pistola lectora USB (escribe el código + Enter), la cámara (html5-qrcode) o tecleando. --}}
<script>
    window.escanerVenta = () => ({
        abierta: false, lector: null, ultimo: '', ultimoT: 0, estado: '', ok: null,
        init() {
            window.addEventListener('escaner-resultado', (e) => {
                this.ok = e.detail.ok;
                setTimeout(() => this.ok = null, 900);
            });
        },
        enviar(codigo) {
            codigo = (codigo || '').trim();
            if (codigo) this.$wire.escanear(codigo);
        },
        async abrir() {
            this.abierta = true;
            this.estado = 'Cargando cámara…';
            if (! window.Html5Qrcode) {
                await new Promise((ok, mal) => {
                    const s = document.createElement('script');
                    s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                    s.onload = ok; s.onerror = mal;
                    document.head.appendChild(s);
                }).catch(() => this.estado = 'No se pudo cargar el lector (¿sin internet?). Usa la pistola o escribe el código.');
                if (! window.Html5Qrcode) return;
            }
            await this.$nextTick();
            this.lector = new Html5Qrcode('lector-camara');
            try {
                await this.lector.start(
                    { facingMode: 'environment' },
                    { fps: 12, qrbox: { width: 300, height: 130 } },
                    (texto) => {
                        const t = Date.now();
                        if (texto === this.ultimo && t - this.ultimoT < 2500) return;   // no repetir el mismo código
                        this.ultimo = texto; this.ultimoT = t;
                        this.pitido();
                        this.enviar(texto);
                    },
                );
                this.estado = 'Apunta el código de barras a la cámara 📷';
            } catch (e) {
                this.estado = 'No se pudo abrir la cámara. Revisa el permiso del navegador (la cámara solo funciona en localhost o https).';
            }
        },
        async cerrar() {
            if (this.lector) {
                try { await this.lector.stop(); this.lector.clear(); } catch (e) {}
                this.lector = null;
            }
            this.abierta = false;
        },
        pitido() {
            try {
                const a = new (window.AudioContext || window.webkitAudioContext)();
                const o = a.createOscillator();
                o.frequency.value = 1250; o.connect(a.destination); o.start();
                setTimeout(() => { o.stop(); a.close(); }, 120);
            } catch (e) {}
        },
    });
</script>

<style>
    .esc { border-radius: .75rem; padding: .8rem; background: linear-gradient(135deg, rgba(159,29,58,.06), rgba(212,160,23,.08)); border: 1px dashed rgba(159,29,58,.35); }
    .esc-fila { display: flex; gap: .6rem; align-items: center; }
    .esc-ico { font-size: 1.4rem; }
    .esc input { flex: 1; min-width: 0; border-radius: .5rem; border: 1px solid rgba(120,113,108,.35); padding: .55rem .75rem; font-size: .95rem; font-family: ui-monospace, monospace; background: #fff; color: #1c1917; transition: box-shadow .2s, border-color .2s; }
    .dark .esc input { background: rgba(255,255,255,.05); color: #fff; }
    .esc input:focus { outline: none; border-color: #9f1d3a; box-shadow: 0 0 0 3px rgba(159,29,58,.2); }
    .esc input.bien { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.25); }
    .esc input.mal { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.25); }
    .esc-btn { display: inline-flex; align-items: center; gap: .35rem; border: 0; cursor: pointer; border-radius: .5rem; padding: .55rem .85rem; font-weight: 600; font-size: .85rem; color: #fff; background: #9f1d3a; white-space: nowrap; }
    .esc-btn.gris { background: #57534e; }
    .esc-ayuda { font-size: .75rem; color: #78716c; margin: .45rem 0 0; }
    .esc-cam { margin-top: .75rem; }
    #lector-camara { width: 100%; max-width: 440px; margin: 0 auto; border-radius: .6rem; overflow: hidden; background: #000; }
    .esc-estado { text-align: center; font-size: .8rem; color: #78716c; margin: .4rem 0 0; }
</style>

<div class="esc" x-data="escanerVenta()" wire:ignore>
    <div class="esc-fila">
        <span class="esc-ico" aria-hidden="true">▥</span>
        <input type="text" x-ref="campo" autocomplete="off" autofocus
               placeholder="Escanea o escribe el código (UTI-0001 o ISBN) y presiona Enter"
               :class="{ 'bien': ok === true, 'mal': ok === false }"
               x-on:keydown.enter.prevent="enviar($refs.campo.value); $refs.campo.value = ''">
        <button type="button" class="esc-btn" x-show="! abierta" x-on:click="abrir()">📷 Cámara</button>
        <button type="button" class="esc-btn gris" x-show="abierta" x-on:click="cerrar()">✕ Cerrar</button>
    </div>
    <p class="esc-ayuda">Cada lectura suma 1 unidad. También puedes elegir los productos abajo como siempre.</p>

    <div class="esc-cam" x-show="abierta" x-cloak>
        <div id="lector-camara"></div>
        <p class="esc-estado" x-text="estado"></p>
    </div>
</div>
