@extends('tienda.layout')

@section('titulo', 'Finalizar compra')

@section('estilos')
    .pago { display: grid; grid-template-columns: 1fr 360px; gap: 2rem; align-items: start; }
    .dos { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
    .paso-t { font-family: 'Fraunces', serif; font-size: 1.3rem; margin: 0 0 1rem; display: flex; gap: .6rem; align-items: center; }
    .paso-t span { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; background: #f6e4e8; color: var(--vino); font-size: 1rem; }
    .tipos { display: flex; gap: .5rem; margin-bottom: 1rem; }
    .tipos label, .metodos label { cursor: pointer; margin: 0; }
    .tipos input, .metodos input { position: absolute; opacity: 0; width: 1px; }
    .tipos .opc { padding: .55rem 1rem; border-radius: .7rem; border: 1.5px solid #e2dbd0; background: #fff; font-weight: 600; }
    .tipos input:checked + .opc, .metodos input:checked + .opc { border-color: var(--vino); background: #fbf1f3; color: var(--vino); box-shadow: 0 0 0 4px rgba(159,29,58,.1); }
    .metodos { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-bottom: 1rem; }
    .metodos .opc { display: block; padding: 1rem; border-radius: .9rem; border: 1.5px solid #e2dbd0; background: #fff; text-align: center; font-weight: 700; }
    .metodos .opc small { display: block; font-weight: 500; color: var(--gris); font-size: .75rem; margin-top: .2rem; }
    .caja-yape { display: grid; grid-template-columns: 140px 1fr; gap: 1.2rem; align-items: center; padding: 1.2rem; border-radius: 1rem; background: linear-gradient(135deg, #5b1d73, #742284); color: #fff; margin-bottom: 1rem; }
    .caja-yape .qr { background: #fff; border-radius: .7rem; padding: .5rem; aspect-ratio: 1; display: grid; place-items: center; color: #742284; font-weight: 800; text-align: center; font-size: .8rem; }
    .caja-yape .monto { font-family: 'Fraunces', serif; font-size: 1.8rem; }
    .caja-recojo { padding: 1.2rem; border-radius: 1rem; background: #ecfdf5; border: 1px solid #a7f3d0; color: var(--ok); margin-bottom: 1rem; font-weight: 600; }
    .item-r { display: flex; justify-content: space-between; gap: 1rem; font-size: .9rem; margin: .45rem 0; }
    .total-r { font-family: 'Fraunces', serif; font-size: 1.6rem; color: var(--vino); display: flex; justify-content: space-between; border-top: 1px solid var(--borde); padding-top: .8rem; margin-top: .8rem; }
    @media (max-width: 860px) { .pago { grid-template-columns: 1fr; } .dos { grid-template-columns: 1fr; } .metodos { grid-template-columns: 1fr; } .caja-yape { grid-template-columns: 1fr; } .caja-yape .qr { max-width: 160px; } }
@endsection

@section('contenido')
<div class="wrap">
    <h1 class="titulo-pag">Finalizar compra</h1>
    <p class="sub">Completa tus datos y elige cómo pagar. Recoges tu pedido en tienda.</p>

    @if ($errors->has('carrito'))
        <div class="aviso mal">⚠ {{ $errors->first('carrito') }} <a href="{{ route('tienda.carrito') }}">Ir al carrito</a></div>
    @endif

    <form class="pago" method="POST" action="{{ route('tienda.confirmar') }}" id="form-pago">
        @csrf
        <div>
            {{-- 1. DATOS --}}
            <div class="panel" style="margin-bottom:1.5rem">
                <h2 class="paso-t"><span>1</span> Tus datos</h2>

                @php $tipo = old('tipo_documento', 'DNI'); @endphp
                <div class="tipos">
                    @foreach (['DNI' => 'DNI', 'RUC' => 'RUC (factura)', 'CE' => 'Carné ext.'] as $valor => $texto)
                        <label><input type="radio" name="tipo_documento" value="{{ $valor }}" @checked($tipo === $valor)><span class="opc">{{ $texto }}</span></label>
                    @endforeach
                </div>

                <div class="dos">
                    <div class="campo">
                        <label for="numero_documento">Número de documento</label>
                        <input id="numero_documento" name="numero_documento" value="{{ old('numero_documento') }}" inputmode="numeric" maxlength="12" required data-v="doc" data-tipo="tipo_documento" autocomplete="off" data-msg="Escribe tu número de documento.">
                        @error('numero_documento') <div class="error-txt">{{ $message }}</div> @enderror
                    </div>
                    <div class="campo">
                        <label for="telefono">Celular</label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" inputmode="numeric" maxlength="9" placeholder="987654321" required data-v="celular" autocomplete="tel" data-msg="Escribe tu celular para avisarte.">
                        @error('telefono') <div class="error-txt">{{ $message }}</div> @enderror
                    </div>
                    <div class="campo">
                        <label for="nombres" id="lbl-nombres">{{ $tipo === 'RUC' ? 'Razón social' : 'Nombres' }}</label>
                        <input id="nombres" name="nombres" value="{{ old('nombres') }}" maxlength="100" required data-v="nombre" data-tipo="tipo_documento" autocomplete="given-name" data-msg="Escribe tu nombre.">
                        @error('nombres') <div class="error-txt">{{ $message }}</div> @enderror
                    </div>
                    <div class="campo" id="campo-apellidos" @style(['display:none' => $tipo === 'RUC'])>
                        <label for="apellidos">Apellidos</label>
                        <input id="apellidos" name="apellidos" value="{{ old('apellidos') }}" maxlength="100" required data-v="nombre" autocomplete="family-name" data-msg="Escribe tus apellidos.">
                        @error('apellidos') <div class="error-txt">{{ $message }}</div> @enderror
                    </div>
                    <div class="campo" style="grid-column:1/-1">
                        <label for="email">Correo (opcional)</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="120" data-v="email" autocomplete="email">
                        @error('email') <div class="error-txt">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- 2. ENTREGA --}}
            @php $entrega = old('tipo_entrega', 'recojo'); @endphp
            <div class="panel" style="margin-bottom:1.5rem">
                <h2 class="paso-t"><span>2</span> ¿Cómo recibes tu pedido?</h2>
                <div class="metodos" style="grid-template-columns:1fr 1fr">
                    <label><input type="radio" name="tipo_entrega" value="recojo" @checked($entrega === 'recojo')><span class="opc">🏬 Recojo en tienda<small>Gratis · {{ config('libreria.direccion') }}</small></span></label>
                    <label><input type="radio" name="tipo_entrega" value="delivery" @checked($entrega === 'delivery') @disabled($zonas->isEmpty())><span class="opc">🛵 Delivery<small>{{ $zonas->isEmpty() ? 'No disponible' : 'Desde S/ ' . number_format((float) $zonas->min('costo'), 2) . ' según tu distrito' }}</small></span></label>
                </div>

                <div id="caja-delivery" @style(['display:none' => $entrega !== 'delivery', 'margin-top:1rem'])>
                    <div class="dos">
                        <div class="campo">
                            <label for="zona_envio_id">Distrito</label>
                            <select id="zona_envio_id" name="zona_envio_id" required data-msg="Elige tu distrito.">
                                <option value="">Elige tu distrito…</option>
                                @foreach ($zonas as $z)
                                    <option value="{{ $z->id }}" data-costo="{{ (float) $z->costo }}" @selected((string) old('zona_envio_id') === (string) $z->id)>
                                        {{ $z->distrito }} — S/ {{ number_format((float) $z->costo, 2) }} · {{ $z->tiempo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('zona_envio_id') <div class="error-txt">{{ $message }}</div> @enderror
                        </div>
                        <div class="campo">
                            <label for="direccion_entrega">Dirección</label>
                            <input id="direccion_entrega" name="direccion_entrega" value="{{ old('direccion_entrega') }}" maxlength="200" required data-v="texto" data-min="8" placeholder="Av. Arequipa 1234, dpto. 502" autocomplete="street-address" data-msg="Escribe la dirección de entrega.">
                            @error('direccion_entrega') <div class="error-txt">{{ $message }}</div> @enderror
                        </div>
                        <div class="campo" style="grid-column:1/-1">
                            <label for="referencia_entrega">Referencia (opcional)</label>
                            <input id="referencia_entrega" name="referencia_entrega" value="{{ old('referencia_entrega') }}" maxlength="200" placeholder="Frente al parque, portón verde">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. PAGO --}}
            <div class="panel">
                <h2 class="paso-t"><span>3</span> Forma de pago</h2>

                @php $metodo = old('metodo', 'yape'); @endphp
                <div class="metodos">
                    <label><input type="radio" name="metodo" value="yape" @checked($metodo === 'yape')><span class="opc">Yape<small>Desde tu celular</small></span></label>
                    <label><input type="radio" name="metodo" value="plin" @checked($metodo === 'plin')><span class="opc">Plin<small>Desde tu celular</small></span></label>
                    <label><input type="radio" name="metodo" value="recojo" @checked($metodo === 'recojo')><span class="opc" id="opc-recojo">{{ $entrega === 'delivery' ? 'Contra entrega' : 'En tienda' }}<small>{{ $entrega === 'delivery' ? 'Pagas al recibir' : 'Pagas al recoger' }}</small></span></label>
                </div>

                <div id="caja-movil" @style(['display:none' => $metodo === 'recojo'])>
                    <div class="caja-yape">
                        <div class="qr">
                            @if (file_exists(public_path($pago['qr'])))
                                <img src="{{ asset($pago['qr']) }}" alt="QR de pago">
                            @else
                                Escanea<br>o yapea al<br>{{ $pago['numero'] }}
                            @endif
                        </div>
                        <div>
                            <div style="opacity:.85;font-size:.85rem">Yapea o plinea exactamente</div>
                            <div class="monto">S/ <span class="js-total">{{ number_format($total - $descuento, 2) }}</span></div>
                            <div style="margin-top:.4rem">al <strong>{{ $pago['numero'] }}</strong></div>
                            <div style="opacity:.85;font-size:.85rem">a nombre de {{ $pago['titular'] }}</div>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="operacion">Número de operación</label>
                        <input id="operacion" name="operacion" value="{{ old('operacion') }}" maxlength="30" placeholder="Aparece en tu app después de pagar" required data-v="operacion" autocomplete="off" data-msg="Escribe el número de operación de tu Yape o Plin.">
                        @error('operacion') <div class="error-txt">{{ $message }}</div> @enderror
                        <div style="font-size:.8rem;color:var(--gris);margin-top:.35rem">Nuestra cajera verifica el pago y te avisamos en el seguimiento del pedido.</div>
                    </div>
                </div>

                <div id="caja-recojo" class="caja-recojo" @style(['display:none' => $metodo !== 'recojo'])>
                    <span id="txt-recojo">{{ $entrega === 'delivery' ? 'Pagas en efectivo, Yape o Plin al recibir tu pedido.' : 'Separamos tu pedido y pagas al recogerlo en tienda (efectivo, tarjeta, Yape o Plin).' }}</span><br>
                    <span style="font-weight:500">{{ config('libreria.horario') }}</span>
                </div>
            </div>
        </div>

        {{-- RESUMEN --}}
        <aside class="panel" style="position:sticky;top:90px">
            <h3 class="serif" style="margin-top:0">Tu pedido</h3>
            @foreach ($items as $item)
                <div class="item-r">
                    <span>{{ $item->cantidad }} × {{ $item->producto->nombre }}</span>
                    <strong>S/ {{ number_format($item->importe, 2) }}</strong>
                </div>
            @endforeach
            @if ($ahorro > 0)
                <div class="item-r" style="color:var(--ok);font-size:.88rem"><span>🏷️ Ahorras con ofertas</span><strong>S/ {{ number_format($ahorro, 2) }}</strong></div>
            @endif
            @if ($cupon)
                <div class="item-r" style="color:var(--ok);font-weight:700"><span>🎟️ Cupón {{ $cupon->codigo }}</span><strong>− S/ {{ number_format($descuento, 2) }}</strong></div>
            @endif
            <div class="item-r" id="linea-envio" @style(['display:none' => $entrega !== 'delivery'])><span>🛵 Delivery <small id="txt-distrito" style="color:var(--gris)"></small></span><strong>S/ <span id="monto-envio">0.00</span></strong></div>
            <div class="total-r" data-base="{{ $total - $descuento }}" id="total-pagar"><span>Total</span><span>S/ <span class="js-total">{{ number_format($total - $descuento, 2) }}</span></span></div>
            <p style="font-size:.8rem;color:var(--gris)">Incluye IGV. Boleta con DNI o CE, factura con RUC.</p>
            <label style="display:flex;gap:.55rem;align-items:flex-start;font-weight:500;font-size:.82rem;margin:.4rem 0 .9rem">
                <input type="checkbox" name="acepta_terminos" value="1" required data-msg="Debes aceptar los términos para continuar." style="width:auto;margin-top:.15rem;accent-color:var(--vino)" @checked(old('acepta_terminos'))>
                <span>Acepto los <a href="{{ route('tienda.pagina', 'terminos') }}" target="_blank">términos y condiciones</a> y la <a href="{{ route('tienda.pagina', 'privacidad') }}" target="_blank">política de privacidad</a>.</span>
            </label>
            @error('acepta_terminos') <div class="error-txt" style="margin:-.5rem 0 .8rem">{{ $message }}</div> @enderror
            <button class="btn btn-vino" style="width:100%" id="btn-confirmar">Confirmar pedido</button>
            <a class="btn btn-borde" style="width:100%;margin-top:.6rem" href="{{ route('tienda.carrito') }}">← Volver al carrito</a>
        </aside>
    </form>
</div>
@endsection

@section('scripts')
<script>
    // Muestra/oculta campos según el documento y el método de pago
    document.querySelectorAll('input[name=tipo_documento]').forEach(r => r.addEventListener('change', e => {
        const ruc = e.target.value === 'RUC';
        document.getElementById('lbl-nombres').textContent = ruc ? 'Razón social' : 'Nombres';
        document.getElementById('campo-apellidos').style.display = ruc ? 'none' : '';
    }));
    document.querySelectorAll('input[name=metodo]').forEach(r => r.addEventListener('change', e => {
        const recojo = e.target.value === 'recojo';
        document.getElementById('caja-movil').style.display = recojo ? 'none' : '';
        document.getElementById('caja-recojo').style.display = recojo ? '' : 'none';
    }));
    // Entrega: recojo o delivery (el costo del distrito se suma al total)
    const actualizarEntrega = () => {
        const delivery = document.querySelector('input[name=tipo_entrega]:checked')?.value === 'delivery';
        const zona = document.getElementById('zona_envio_id');
        const opcion = zona.options[zona.selectedIndex];
        const envio = delivery && opcion && opcion.dataset.costo ? parseFloat(opcion.dataset.costo) : 0;
        const base = parseFloat(document.getElementById('total-pagar').dataset.base) || 0;

        document.getElementById('caja-delivery').style.display = delivery ? '' : 'none';
        document.getElementById('linea-envio').style.display = delivery ? '' : 'none';
        document.getElementById('monto-envio').textContent = envio.toFixed(2);
        document.getElementById('txt-distrito').textContent = delivery && zona.value ? '(' + opcion.text.split('—')[0].trim() + ')' : '';
        document.querySelectorAll('.js-total').forEach(el => el.textContent = (base + envio).toFixed(2));
        document.getElementById('opc-recojo').innerHTML = delivery ? 'Contra entrega<small>Pagas al recibir</small>' : 'En tienda<small>Pagas al recoger</small>';
        document.getElementById('txt-recojo').textContent = delivery
            ? 'Pagas en efectivo, Yape o Plin al recibir tu pedido.'
            : 'Separamos tu pedido y pagas al recogerlo en tienda (efectivo, tarjeta, Yape o Plin).';
    };
    document.querySelectorAll('input[name=tipo_entrega]').forEach(r => r.addEventListener('change', actualizarEntrega));
    document.getElementById('zona_envio_id').addEventListener('change', actualizarEntrega);
    actualizarEntrega();

    // Evita doble clic
    document.getElementById('form-pago').addEventListener('submit', () => {
        const b = document.getElementById('btn-confirmar');
        b.disabled = true; b.textContent = 'Procesando…';
    });
</script>
@endsection
