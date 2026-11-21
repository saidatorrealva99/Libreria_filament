@extends('tienda.layout')

@section('titulo', 'Libro de Reclamaciones')

@section('estilos')
    .lr-cab { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; margin: 2rem 0 1.25rem; }
    .lr-cab .titulo-pag { margin: 0; }
    .lr-meta { margin-left: auto; text-align: right; font-size: .85rem; color: var(--gris); }
    .lr-meta b { display: block; font-family: 'Fraunces', serif; font-size: 1.2rem; color: var(--vino); }
    .lr-prov { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; font-size: .88rem; background: #fff; border: 1px solid var(--borde); border-radius: 1rem; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
    .lr-prov span { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: var(--gris); font-weight: 700; }
    .lr-sec { margin-bottom: 1.25rem; }
    .lr-sec h2 { display: flex; align-items: center; gap: .6rem; font-family: 'Fraunces', serif; font-size: 1.15rem; margin: 0 0 1rem; }
    .lr-sec h2 .n { width: 1.8rem; height: 1.8rem; border-radius: 999px; display: grid; place-items: center; background: var(--vino); color: #fff; font: 700 .85rem 'DM Sans', sans-serif; }
    .g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
    .g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 1rem; }
    textarea { width: 100%; padding: .75rem .9rem; border-radius: .75rem; border: 1.5px solid #e2dbd0; background: #fff; font: 500 .95rem 'DM Sans', sans-serif; color: var(--tinta); min-height: 110px; resize: vertical; }
    textarea:focus { outline: none; border-color: var(--vino); box-shadow: 0 0 0 4px rgba(159,29,58,.12); }
    .opciones { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .opcion { display: flex; gap: .7rem; align-items: flex-start; padding: .9rem 1rem; border: 1.5px solid #e2dbd0; border-radius: .9rem; background: #fff; cursor: pointer; margin: 0; font-weight: 500; }
    .opcion input { width: auto; margin-top: .2rem; accent-color: var(--vino); }
    .opcion b { display: block; font-size: .95rem; color: var(--tinta); }
    .opcion small { color: var(--gris); font-weight: 500; }
    .opcion:has(input:checked) { border-color: var(--vino); background: #fdf2f4; }
    .check { display: flex; gap: .6rem; align-items: flex-start; font-weight: 500; font-size: .88rem; }
    .check input { width: auto; margin-top: .2rem; accent-color: var(--vino); }
    .nota { font-size: .8rem; color: var(--gris); line-height: 1.55; }
    .lr-enviar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
    @media (max-width: 760px) { .g2, .g3, .opciones, .lr-prov { grid-template-columns: 1fr; } .lr-meta { margin-left: 0; text-align: left; } }
@endsection

@section('contenido')
<div class="wrap" style="max-width: 900px">
    <div class="lr-cab">
        @include('tienda.reclamaciones._icono', ['tam' => 64])
        <div>
            <h1 class="titulo-pag">Libro de Reclamaciones</h1>
            <p class="sub" style="margin:0">Conforme al Código de Protección y Defensa del Consumidor.</p>
        </div>
        <div class="lr-meta">
            Hoja N°
            <b>{{ $siguiente }}</b>
            {{ now()->format('d/m/Y') }}
        </div>
    </div>

    <div class="lr-prov">
        <div><span>Proveedor</span>{{ config('libreria.razon_social') }}</div>
        <div><span>RUC</span>{{ config('libreria.ruc') }}</div>
        <div><span>Dirección</span>{{ config('libreria.direccion') }}</div>
    </div>

    @if ($errors->any())
        <div class="aviso mal">⚠ Revisa los campos marcados en rojo.</div>
    @endif

    <form method="POST" action="{{ route('reclamaciones.guardar') }}" x-data>
        @csrf

        {{-- 1. Consumidor --}}
        <section class="panel lr-sec">
            <h2><span class="n">1</span> Identificación del consumidor</h2>
            <div class="g3">
                <div class="campo">
                    <label for="tipo_documento">Tipo de documento</label>
                    <select id="tipo_documento" name="tipo_documento" required>
                        @foreach (\App\Models\Reclamacion::DOCUMENTOS as $k => $v)
                            <option value="{{ $k }}" @selected(old('tipo_documento', 'DNI') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo">
                    <label for="numero_documento">N° de documento</label>
                    <input id="numero_documento" name="numero_documento" value="{{ old('numero_documento') }}" required maxlength="15" autocomplete="off" data-v="doc" data-tipo="tipo_documento">
                    @error('numero_documento') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
                <div class="campo">
                    <label for="telefono">Teléfono / celular</label>
                    <input id="telefono" name="telefono" value="{{ old('telefono') }}" required maxlength="9" inputmode="numeric" data-v="celular">
                    @error('telefono') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="g2">
                <div class="campo">
                    <label for="nombres">Nombres (o razón social)</label>
                    <input id="nombres" name="nombres" value="{{ old('nombres') }}" required maxlength="100" data-v="nombre" data-tipo="tipo_documento">
                    @error('nombres') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input id="apellidos" name="apellidos" value="{{ old('apellidos') }}" maxlength="100" data-v="nombre">
                    @error('apellidos') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
                <div class="campo">
                    <label for="email">Correo electrónico (aquí te enviamos la respuesta)</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="120" data-v="email">
                    @error('email') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
                <div class="campo">
                    <label for="direccion">Domicilio</label>
                    <input id="direccion" name="direccion" value="{{ old('direccion') }}" required maxlength="200">
                    @error('direccion') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
            </div>
            <label class="check">
                <input type="checkbox" name="menor_edad" value="1" id="menor_edad" @checked(old('menor_edad'))
                       onchange="document.getElementById('caja-apoderado').style.display = this.checked ? 'block' : 'none'">
                Soy menor de edad
            </label>
            <div class="campo" id="caja-apoderado" style="margin-top:.8rem; display: {{ old('menor_edad') ? 'block' : 'none' }}">
                <label for="apoderado">Nombre del padre, madre o tutor</label>
                <input id="apoderado" name="apoderado" value="{{ old('apoderado') }}" maxlength="150" required data-v="nombre" data-msg="Escribe el nombre de tu padre, madre o tutor.">
                @error('apoderado') <div class="error-txt">{{ $message }}</div> @enderror
            </div>
        </section>

        {{-- 2. Bien contratado --}}
        <section class="panel lr-sec">
            <h2><span class="n">2</span> Identificación del bien contratado</h2>
            <div class="opciones" style="margin-bottom:1rem">
                <label class="opcion"><input type="radio" name="bien_tipo" value="producto" @checked(old('bien_tipo', 'producto') === 'producto')><span><b>Producto</b><small>Libro, útil, artículo comprado</small></span></label>
                <label class="opcion"><input type="radio" name="bien_tipo" value="servicio" @checked(old('bien_tipo') === 'servicio')><span><b>Servicio</b><small>Atención, entrega, pedido web</small></span></label>
            </div>
            <div class="g3">
                <div class="campo" style="grid-column: span 2">
                    <label for="bien_descripcion">Descripción</label>
                    <input id="bien_descripcion" name="bien_descripcion" value="{{ old('bien_descripcion') }}" required maxlength="200" placeholder="Ej.: Cuaderno A4 cuadriculado x 5 unidades">
                    @error('bien_descripcion') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
                <div class="campo">
                    <label for="monto">Monto reclamado (S/)</label>
                    <input id="monto" name="monto" inputmode="decimal" value="{{ old('monto') }}" data-v="monto" placeholder="0.00">
                    @error('monto') <div class="error-txt">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="campo" style="max-width: 320px">
                <label for="pedido_referencia">N° de boleta o pedido web (opcional)</label>
                <input id="pedido_referencia" name="pedido_referencia" value="{{ old('pedido_referencia') }}" maxlength="30" placeholder="B001-00000123 / WEB-AB12CD">
            </div>
        </section>

        {{-- 3. Detalle --}}
        <section class="panel lr-sec">
            <h2><span class="n">3</span> Detalle de la reclamación</h2>
            <div class="opciones" style="margin-bottom:1rem">
                @foreach (\App\Models\Reclamacion::TIPOS as $k => $v)
                    <label class="opcion">
                        <input type="radio" name="tipo" value="{{ $k }}" @checked(old('tipo', 'reclamo') === $k)>
                        <span><b>{{ $v }}</b><small>{{ \App\Models\Reclamacion::TIPOS_AYUDA[$k] }}</small></span>
                    </label>
                @endforeach
            </div>
            <div class="campo">
                <label for="detalle">Detalle (¿qué pasó?)</label>
                <textarea id="detalle" name="detalle" required maxlength="2000" data-v="texto" data-min="15">{{ old('detalle') }}</textarea>
                @error('detalle') <div class="error-txt">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="pedido">Pedido (¿qué solución esperas?)</label>
                <textarea id="pedido" name="pedido" required maxlength="1000" data-v="texto" data-min="5" style="min-height:80px">{{ old('pedido') }}</textarea>
                @error('pedido') <div class="error-txt">{{ $message }}</div> @enderror
            </div>
        </section>

        <section class="panel lr-sec">
            <p class="nota">
                • La formulación del reclamo no impide acudir a otras vías de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.<br>
                • El proveedor debe responder en un plazo no mayor a <b>15 días hábiles</b>.<br>
                • Recibirás una copia de esta hoja en tu correo y podrás consultar su estado en
                <a href="{{ route('reclamaciones.consulta') }}">Consultar mi reclamo</a>.
            </p>
            <label class="check">
                <input type="checkbox" name="acepta_terminos" value="1" @checked(old('acepta_terminos')) required data-msg="Debes aceptar para registrar tu hoja.">
                Declaro que los datos son verdaderos y acepto la <a href="{{ route('tienda.pagina', 'privacidad') }}" target="_blank">política de privacidad</a>.
            </label>
            @error('acepta_terminos') <div class="error-txt">{{ $message }}</div> @enderror

            <div class="lr-enviar" style="margin-top:1.25rem">
                <a href="{{ route('reclamaciones.consulta') }}">¿Ya registraste uno? Consultar estado →</a>
                <button class="btn btn-vino">Enviar hoja de reclamación</button>
            </div>
        </section>
    </form>
</div>
@endsection
