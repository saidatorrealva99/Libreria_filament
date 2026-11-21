<x-filament-panels::page>
    <style>
        .ia-wrap { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; align-items: start; }
        .ia-chat { display: flex; flex-direction: column; height: calc(100vh - 230px); min-height: 460px; border-radius: 1.1rem; overflow: hidden; background: #fff; border: 1px solid #ece6dc; box-shadow: 0 8px 24px -16px rgba(60,40,20,.25); }
        .dark .ia-chat { background: rgb(28 25 23); border-color: rgba(255,255,255,.08); }
        .ia-cab { display: flex; align-items: center; gap: .8rem; padding: 1rem 1.2rem; color: #fff; background: radial-gradient(20rem 8rem at 100% 0%, rgba(212,160,23,.35), transparent 70%), linear-gradient(135deg, #7a1530, #4a0d1f); }
        .ia-avatar { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: rgba(255,255,255,.15); }
        .ia-cab strong { font-family: 'Fraunces', serif; font-size: 1.1rem; display: block; }
        .ia-cab small { opacity: .8; }
        .ia-punto { width: 8px; height: 8px; border-radius: 99px; background: #34d399; display: inline-block; margin-right: .3rem; }
        .ia-msgs { flex: 1; overflow-y: auto; padding: 1.2rem; display: flex; flex-direction: column; gap: .9rem; scroll-behavior: smooth; }
        .ia-msg { max-width: 82%; padding: .8rem 1rem; border-radius: 1rem; line-height: 1.55; font-size: .93rem; animation: ia-entra .25s ease; }
        .ia-msg.usuario { align-self: flex-end; background: #9f1d3a; color: #fff; border-bottom-right-radius: .3rem; }
        .ia-msg.ia { align-self: flex-start; background: #faf7f2; border: 1px solid #ece6dc; border-bottom-left-radius: .3rem; }
        .dark .ia-msg.ia { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.08); }
        .ia-msg.error { align-self: flex-start; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .ia-msg p { margin: 0 0 .5rem; } .ia-msg p:last-child { margin: 0; }
        .ia-msg ul, .ia-msg ol { margin: .3rem 0; padding-left: 1.2rem; }
        .ia-msg strong { color: inherit; }
        .ia-vacio { margin: auto; text-align: center; color: rgb(120 113 108); max-width: 26rem; }
        .ia-vacio .big { font-family: 'Fraunces', serif; font-size: 1.4rem; color: inherit; margin: .6rem 0 .3rem; }
        .ia-escribe { display: flex; gap: .3rem; padding: .9rem 1rem; }
        .ia-escribe span { width: 7px; height: 7px; border-radius: 99px; background: #9f1d3a; animation: ia-salta 1s infinite; }
        .ia-escribe span:nth-child(2) { animation-delay: .15s; } .ia-escribe span:nth-child(3) { animation-delay: .3s; }
        .ia-form { display: flex; gap: .6rem; padding: .9rem; border-top: 1px solid #ece6dc; }
        .dark .ia-form { border-color: rgba(255,255,255,.08); }
        .ia-form input { flex: 1; border-radius: .8rem; border: 1.5px solid #e2dbd0; padding: .75rem .9rem; font-size: .95rem; background: transparent; color: inherit; }
        .ia-form input:focus { outline: none; border-color: #9f1d3a; box-shadow: 0 0 0 4px rgba(159,29,58,.12); }
        .ia-btn { border: 0; cursor: pointer; border-radius: .8rem; padding: .75rem 1.1rem; font-weight: 600; color: #fff; background: linear-gradient(135deg,#b8274a,#6b1027); }
        .ia-btn[disabled] { opacity: .5; cursor: wait; }
        .ia-lado { display: flex; flex-direction: column; gap: .6rem; }
        .ia-lado h3 { font-weight: 700; font-size: .8rem; text-transform: uppercase; letter-spacing: .08em; color: rgb(120 113 108); margin: 0 0 .2rem; }
        .ia-sug { text-align: left; border: 1px solid #ece6dc; background: #fff; border-radius: .9rem; padding: .7rem .9rem; font-size: .88rem; cursor: pointer; transition: border-color .15s, transform .15s; color: inherit; }
        .dark .ia-sug { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.08); }
        .ia-sug:hover { border-color: #d4a017; transform: translateX(3px); }
        .ia-nota { font-size: .78rem; color: rgb(120 113 108); line-height: 1.5; padding: .8rem; border-radius: .8rem; background: rgba(212,160,23,.1); }
        @keyframes ia-entra { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        @keyframes ia-salta { 0%, 60%, 100% { transform: translateY(0); opacity: .5; } 30% { transform: translateY(-5px); opacity: 1; } }
        @media (max-width: 1024px) { .ia-wrap { grid-template-columns: 1fr; } }
        @media (prefers-reduced-motion: reduce) { .ia-msg, .ia-escribe span { animation: none; } }
    </style>

    <div class="ia-wrap">
        <div class="ia-chat">
            <div class="ia-cab">
                <div class="ia-avatar">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" style="width:1.5rem;height:1.5rem" />
                </div>
                <div style="flex:1">
                    <strong>Asistente de {{ config('app.name') }}</strong>
                    <small><span class="ia-punto"></span>Responde con los datos reales del sistema</small>
                </div>
                @if (count($mensajes))
                    <x-filament::button color="gray" size="sm" wire:click="limpiar">Nueva conversación</x-filament::button>
                @endif
            </div>

            <div class="ia-msgs" id="ia-msgs">
                @forelse ($mensajes as $m)
                    <div class="ia-msg {{ $m['rol'] }}" wire:key="m-{{ $loop->index }}">
                        @if ($m['rol'] === 'ia')
                            {!! $this->html($m['texto']) !!}
                        @else
                            {{ $m['texto'] }}
                        @endif
                    </div>
                @empty
                    <div class="ia-vacio">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" style="width:3rem;height:3rem;margin:0 auto;color:#d4a017" />
                        <div class="big">¿En qué te ayudo hoy?</div>
                        Pregúntame por ventas, inventario, clientes o pagos. Elige una sugerencia o escribe tu pregunta.
                    </div>
                @endforelse

                {{-- Indicador "escribiendo…" mientras espera la respuesta --}}
                <div class="ia-msg ia" wire:loading.flex wire:target="enviar, preguntarSugerencia, responder" style="padding:0">
                    <div class="ia-escribe"><span></span><span></span><span></span></div>
                </div>
            </div>

            <form class="ia-form" wire:submit="enviar">
                <input type="text" wire:model="pregunta" placeholder="Ej.: ¿qué libro se vendió más esta semana?" maxlength="500"
                       autocomplete="off" wire:loading.attr="disabled" wire:target="enviar, preguntarSugerencia, responder">
                <button class="ia-btn" type="submit" wire:loading.attr="disabled" wire:target="enviar, preguntarSugerencia, responder">
                    Enviar
                </button>
            </form>
        </div>

        <aside class="ia-lado">
            <h3>Prueba preguntando</h3>
            @foreach (\App\Filament\Pages\Asistente::SUGERENCIAS as $i => $sug)
                <button type="button" class="ia-sug" wire:click="preguntarSugerencia({{ $i }})" wire:loading.attr="disabled">
                    {{ $sug }}
                </button>
            @endforeach
            <div class="ia-nota">
                🔒 La IA no entra a la base de datos ni puede modificar nada: el sistema le envía un resumen
                de los datos actuales y ella solo responde.
            </div>
        </aside>
    </div>

    <script>
        // Baja el chat al último mensaje
        document.addEventListener('livewire:init', () => {
            Livewire.on('asistente-respondio', () => {
                requestAnimationFrame(() => {
                    const c = document.getElementById('ia-msgs');
                    if (c) c.scrollTop = c.scrollHeight;
                });
            });
        });
    </script>
</x-filament-panels::page>
