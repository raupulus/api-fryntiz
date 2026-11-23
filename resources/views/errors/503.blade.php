@extends('layouts.app')

@section('title', 'Error 503 · Estamos en mantenimiento')
@section('noindex', '1')

@section('content')
    @include('errors._page', [
        'code' => '503',
        'title' => 'Estamos en mantenimiento',
        'message' => 'El sitio vuelve en unos minutos. Gracias por la paciencia.',
    ])
@endsection
