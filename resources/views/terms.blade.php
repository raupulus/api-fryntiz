@extends('layouts.app')

@section('title', 'Términos de Servicio')

@section('content')
    <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0 bg-surface">
        <div class="w-full sm:max-w-2xl mt-6 p-6 bg-surface-container overflow-hidden sm:rounded-lg prose dark:prose-invert">
            {!! $terms !!}
        </div>
    </div>
@endsection
