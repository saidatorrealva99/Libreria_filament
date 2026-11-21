@extends('tienda.layout')

@section('titulo', 'Seguir mi pedido')

@section('contenido')
<div class="wrap" style="max-width:560px">
    <h1 class="titulo-pag">Seguir mi pedido</h1>
    <p class="sub">Escribe el código que te dimos al comprar (por ejemplo WEB-7K2P9Q) y tu documento.</p>

    <form class="panel" method="POST" action="{{ route('tienda.buscar') }}">
        @csrf
        <div class="campo">
            <label for="codigo">Código de pedido</label>
            <input id="codigo" name="codigo" value="{{ old('codigo') }}" placeholder="WEB-XXXXXX" style="text-transform:uppercase" required data-v="pedido" maxlength="10" autocomplete="off">
            @error('codigo') <div class="error-txt">{{ $message }}</div> @enderror
        </div>
        <div class="campo">
            <label for="documento">DNI, RUC o carné de extranjería</label>
            <input id="documento" name="documento" value="{{ old('documento') }}" inputmode="numeric" required maxlength="12" data-v="documento" data-msg="Escribe el documento con el que compraste.">
            @error('documento') <div class="error-txt">{{ $message }}</div> @enderror
        </div>
        <button class="btn btn-vino" style="width:100%">Buscar pedido</button>
    </form>
</div>
@endsection
