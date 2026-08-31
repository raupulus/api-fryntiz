@extends('layouts.app')

@section('title', 'Error 401 · No has iniciado sesión')

@section('content')
    @include('errors._page', [
        'code' => '401',
        'title' => 'No has iniciado sesión',
        'message' => 'Esta página pide que te identifiques antes de entrar.',
    ])
@endsection
