@extends('layouts.app')

@section('title', 'Error 500 · Algo se ha roto por nuestra parte')

@section('content')
    @include('errors._page', [
        'code' => '500',
        'title' => 'Algo se ha roto por nuestra parte',
        'message' => 'No es cosa tuya. Ya ha quedado registrado; si se repite, avísame.',
    ])
@endsection
