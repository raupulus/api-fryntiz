@extends('layouts.app')

@section('title', 'Error 403 · No tienes acceso')

@section('content')
    @include('errors._page', [
        'code' => '403',
        'title' => 'No tienes acceso',
        'message' => 'Tu cuenta no tiene permiso para ver esta página.',
    ])
@endsection
