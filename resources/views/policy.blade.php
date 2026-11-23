@extends('layouts.app')

@section('title', 'Política de Privacidad')

@section('content')
    <section class="min-h-[60svh] pt-28 pb-16 px-4 sm:px-6 bg-surface">
        <div class="max-w-3xl mx-auto p-6 sm:p-8 bg-surface-container-lowest rounded-xl shadow-lg prose-basic text-on-surface">
            {!! \App\Helpers\HtmlHelper::safeBasic((string) $policy) !!}
        </div>
    </section>
@endsection
