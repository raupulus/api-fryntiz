@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', '')
@section('description', '')
@section('keywords', '')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', '')
@section('rs-sitename', '')
@section('rs-description', '')
@section('rs-image', '')
@section('rs-url', '')
@section('rs-image-alt', '')

@section('twitter-site', '')
@section('twitter-creator', '')

{{-- Marca el elemento del menú que se encuentra activo --}}
@section('active-contact', 'active')

@section('content')
    @include('layouts.breadcrumbs')

    <div class="row">
        <div class="col-md-8 mb-5 contact-form">
            <div class="contact-image">
                <img src="{{asset('images/icons/rocket-187x187.png')}}"
                     alt="Rocket icon contact"
                     title="Rocket icon contact" />
            </div>

            <div class="row">
                <div class="col-12">
                    <h2>Se ha enviado el siguiente mensaje:</h2>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    {!! $email !!}
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-5">
            @include('layouts.sidebar')
        </div>
    </div>
@endsection

@section('css')
    <style>
        body {
            background: -webkit-linear-gradient(left, #0072ff, #00c6ff);
        }
    </style>
@endsection
