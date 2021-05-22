@extends('mail.layouts._mail_base')

@section('content')
    Este es un mensaje automático por {{config('app.name')}} desde la
    dirección <a href="{{config('app.url')}}"
                 alt="{{config('app.name')}}">{{config('app.url')}}</a>

    <div>
        <p>
            Email: <a href="mailto:{{ $mail->email }}">{{ $mail->email }}</a>
        </p>
        <p>
            Telefono: {{ $mail->phone }}
        </p>
        <p>{{ $mail->body }}</p>
    </div>
@endsection
