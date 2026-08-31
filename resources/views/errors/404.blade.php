@extends('layouts.app')

@section('title', 'Error 404 · Esta página no existe')

@section('content')
    @include('errors._page', [
        'code' => '404',
        'title' => 'Esta página no existe',
        'message' => 'Puede que el enlace esté mal escrito o que la página se moviera de sitio.',
    ])
@endsection
