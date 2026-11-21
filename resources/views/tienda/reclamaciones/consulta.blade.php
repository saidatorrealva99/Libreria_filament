@extends('tienda.layout')

@section('titulo', 'Consultar mi reclamo')

@section('contenido')
<div class="wrap" style="max-width: 560px">
    <h1 class="titulo-pag">Consultar mi reclamo</h1>
    <p class="sub">Escribe el número de tu hoja y tu documento para ver el estado y la respuesta.</p>

    <form class="panel" method="POST" action="{{ route('reclamaciones.buscar') }}">
        @csrf
        <div class="campo">
            <label for="codigo">N° de hoja</label>
            <input id="codigo" name="codigo" value="{{ old('codigo') }}" placeholder="000001-{{ date('Y') }}" required data-v="hoja" maxlength="11">
        </div>
        <div class="campo">
            <label for="numero_documento">N° de documento</label>
            <input id="numero_documento" name="numero_documento" value="{{ old('numero_documento') }}" required>
        </div>
        <button class="btn btn-vino" style="width:100%">Consultar</button>
        <p style="text-align:center;margin:1rem 0 0;font-size:.88rem">
            <a href="{{ route('reclamaciones.formulario') }}">Registrar un nuevo reclamo o queja</a>
        </p>
    </form>
</div>
@endsection
