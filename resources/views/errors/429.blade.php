@extends('layouts.app')

@section('title', 'Error 429 · Demasiadas peticiones')

@section('content')
    @include('errors._page', [
        'code' => '429',
        'title' => 'Demasiadas peticiones',
        'message' => 'Has pedido esta página muchas veces seguidas. Espera un momento y vuelve a intentarlo.',
    ])
@endsection
