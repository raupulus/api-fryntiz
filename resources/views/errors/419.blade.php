@extends('layouts.app')

@section('title', 'Error 419 · La sesión ha caducado')

@section('content')
    @include('errors._page', [
        'code' => '419',
        'title' => 'La sesión ha caducado',
        'message' => 'Llevabas un rato con el formulario abierto. Vuelve a cargarlo y envíalo otra vez.',
    ])
@endsection
