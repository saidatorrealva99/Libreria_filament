@extends('tienda.layout')

@php
    $cfg = config('libreria');
    $paginas = [
        'ayuda'       => 'Preguntas frecuentes',
        'envios'      => 'Recojo y entregas',
        'cambios'     => 'Cambios y devoluciones',
        'terminos'    => 'Términos y condiciones',
        'privacidad'  => 'Política de privacidad',
    ];
@endphp

@section('titulo', $paginas[$pagina])

@section('estilos')
    .legal { display: grid; grid-template-columns: 240px 1fr; gap: 2rem; align-items: start; margin-top: 2rem; }
    .legal nav { position: sticky; top: 90px; background: #fff; border: 1px solid var(--borde); border-radius: 1rem; padding: .6rem; }
    .legal nav a { display: block; padding: .6rem .8rem; border-radius: .6rem; text-decoration: none; font-weight: 600; font-size: .9rem; color: #44403c; }
    .legal nav a.on { background: var(--vino); color: #fff; }
    .legal article { background: #fff; border: 1px solid var(--borde); border-radius: 1.2rem; padding: 1.75rem 2rem; line-height: 1.65; }
    .legal article h1 { font-family: 'Fraunces', serif; margin: 0 0 .25rem; font-size: 1.9rem; }
    .legal article h2 { font-size: 1.05rem; margin: 1.5rem 0 .4rem; color: var(--vino); }
    .legal details { border-bottom: 1px solid var(--borde); padding: .85rem 0; }
    .legal summary { cursor: pointer; font-weight: 700; }
    .legal details p { margin: .5rem 0 0; color: #44403c; }
    .act { color: var(--gris); font-size: .85rem; margin: 0 0 1rem; }
    @media (max-width: 760px) { .legal { grid-template-columns: 1fr; } .legal nav { position: static; } .legal article { padding: 1.25rem; } }
@endsection

@section('contenido')
<div class="wrap legal">
    <nav aria-label="Información">
        @foreach ($paginas as $clave => $titulo)
            <a href="{{ route('tienda.pagina', $clave) }}" class="{{ $clave === $pagina ? 'on' : '' }}">{{ $titulo }}</a>
        @endforeach
        <a href="{{ route('reclamaciones.formulario') }}">📕 Libro de Reclamaciones</a>
    </nav>

    <article>
        <h1>{{ $paginas[$pagina] }}</h1>
        <p class="act">{{ $cfg['razon_social'] }} · RUC {{ $cfg['ruc'] }}</p>

        @switch($pagina)
            @case('ayuda')
                @foreach ([
                    ['¿Cómo compro en la tienda online?', 'Agrega los productos al carrito, ve a "Finalizar compra", completa tus datos y elige cómo pagar. Recibirás un código de pedido (por ejemplo WEB-AB12CD) para hacer el seguimiento.'],
                    ['¿Qué medios de pago aceptan?', 'Yape y Plin (escribes el número de operación y lo verificamos) o pago al recoger en tienda con efectivo o tarjeta.'],
                    ['¿Hacen delivery?', 'Sí. Al finalizar la compra eliges "Delivery" y tu distrito; el costo de envío se suma al total. También puedes recoger gratis en tienda.'],
                    ['¿Cuánto tarda en estar listo mi pedido?', 'Los pedidos pagados con Yape o Plin se verifican y preparan el mismo día hábil, dentro del horario de atención.'],
                    ['¿Puedo pedir boleta o factura?', 'Sí. Si compras con RUC se emite factura; con DNI o carné de extranjería, boleta. Puedes descargar tu comprobante desde "Mi pedido".'],
                    ['¿Venden listas escolares completas?', 'Sí. Escríbenos por WhatsApp con tu lista y te ayudamos a armarla con los productos disponibles.'],
                    ['¿Cómo presento un reclamo?', 'En nuestro Libro de Reclamaciones virtual. Te respondemos en un plazo máximo de 15 días hábiles.'],
                ] as [$p, $r])
                    <details @if ($loop->first) open @endif><summary>{{ $p }}</summary><p>{{ $r }}</p></details>
                @endforeach
                @break

            @case('envios')
                <h2>Recojo en tienda</h2>
                <p>Dirección: {{ $cfg['direccion'] }}.<br>Horario: {{ $cfg['horario'] }}.</p>
                <h2>Delivery por distrito</h2>
                @php $zonas = \App\Models\ZonaEnvio::activas()->get(); @endphp
                @if ($zonas->isEmpty())
                    <p>Por ahora solo atendemos recojo en tienda.</p>
                @else
                    <table style="width:100%;border-collapse:collapse;font-size:.92rem;margin:.4rem 0 1rem">
                        <tr style="text-align:left;color:var(--gris)"><th style="padding:.35rem 0">Distrito</th><th>Costo</th><th>Tiempo</th></tr>
                        @foreach ($zonas as $z)
                            <tr style="border-top:1px solid var(--borde)"><td style="padding:.4rem 0">{{ $z->distrito }}</td><td>S/ {{ number_format((float) $z->costo, 2) }}</td><td>{{ $z->tiempo }}</td></tr>
                        @endforeach
                    </table>
                    <p>Puedes pagar con Yape o Plin al hacer el pedido, o en efectivo al recibirlo.</p>
                @endif
                <h2>¿Qué necesito para recoger?</h2>
                <p>Tu código de pedido y el documento de identidad con el que compraste. Si recoge otra persona, debe mostrar el código de pedido y una copia de tu documento.</p>
                <h2>Plazo para recoger</h2>
                <p>Guardamos tu pedido por 7 días calendario desde que está listo. Pasado ese plazo, los pedidos no pagados se anulan y los pagados se mantienen hasta que te comuniquemos con nosotros.</p>
                @break

            @case('cambios')
                <h2>Cambios</h2>
                <p>Puedes cambiar un producto dentro de los 7 días calendario de tu compra, presentando la boleta o factura, siempre que esté sin uso, en su empaque original y en buen estado.</p>
                <h2>Productos con falla</h2>
                <p>Si el producto tiene un defecto de fábrica (hojas en blanco, páginas faltantes, lapicero que no escribe, etc.), lo cambiamos por otro igual o, si no hay stock, te devolvemos el dinero por el mismo medio de pago.</p>
                <h2>No aplica cambio</h2>
                <p>Productos usados, dañados por mal uso, o artículos personalizados.</p>
                <h2>¿No estás conforme?</h2>
                <p>Puedes registrar tu caso en el <a href="{{ route('reclamaciones.formulario') }}">Libro de Reclamaciones</a>.</p>
                @break

            @case('terminos')
                <h2>1. Precios</h2>
                <p>Los precios están en soles e incluyen IGV. Pueden cambiar sin previo aviso, pero se respeta el precio del momento en que confirmaste tu pedido.</p>
                <h2>2. Stock</h2>
                <p>Los productos se reservan al confirmar el pedido. Si un producto se agota antes, te avisamos y te ofrecemos un reemplazo o la devolución.</p>
                <h2>3. Pagos con Yape o Plin</h2>
                <p>El pedido se confirma cuando verificamos el número de operación. Si la operación no existe o el monto no coincide, el pedido puede ser rechazado.</p>
                <h2>4. Comprobantes</h2>
                <p>Emitimos boleta o factura según el documento registrado en la compra.</p>
                <h2>5. Reclamos</h2>
                <p>Contamos con Libro de Reclamaciones virtual conforme a la normativa vigente.</p>
                @break

            @case('privacidad')
                <h2>¿Qué datos recogemos?</h2>
                <p>Nombre, documento de identidad, teléfono, correo y los datos de tus pedidos o reclamos.</p>
                <h2>¿Para qué los usamos?</h2>
                <p>Para procesar tus compras, emitir comprobantes, avisarte del estado de tu pedido y atender tus reclamos. No vendemos ni compartimos tus datos con terceros con fines comerciales.</p>
                <h2>Tus derechos</h2>
                <p>Conforme a la Ley N.° 29733, Ley de Protección de Datos Personales, puedes pedir el acceso, rectificación o cancelación de tus datos escribiendo a {{ $cfg['email'] }}.</p>
                @break
        @endswitch
    </article>
</div>
@endsection
