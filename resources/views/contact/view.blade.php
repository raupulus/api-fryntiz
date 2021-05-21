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

            <form method="post" action="{{route('contact-send')}}" class="was-validated">

                @csrf

                <h3>Envíanos un mensaje</h3>

                <div class="row">
                    <div class="col-md-12">

                        {{-- Nombre --}}
                        <div class="form-group">
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   placeholder="Nombre *"
                                   required
                                   minlength="4"
                                   maxlength="220"
                                   value="{{ old('name') }}" />
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="Email *"
                                   required
                                   value="{{ old('email') }}"
                                   aria-describedby="emailHelp" />
                        </div>

                        {{-- Teléfono --}}
                        <div class="form-group">
                            <input type="number"
                                   name="phone"
                                   class="form-control"
                                   placeholder="Teléfono"
                                   value="{{ old('phone') }}" />
                        </div>
                    </div>

                    {{-- Mensaje --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <textarea name="message"
                                      class="form-control"
                                      placeholder="Mensaje *"
                                      required
                                      minlength="50"
                                      style="width: 100%; height: 150px;">{{ old('message') }}</textarea>
                        </div>
                    </div>

                    {{-- Consentimiento contactar--}}
                    <div class="col-md-12">
                        <div class="form-group form-check">
                            <div class="form-check-inline">
                                <input type="checkbox"
                                       name="contactme"
                                       class="form-check-input" />

                                <label class="form-check-label" for="contactme">
                                    Doy el consentimiento para ser contactado
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Consentimiento privacidad--}}
                    <div class="col-md-12">
                        <div class="form-group form-check">
                            <div class="form-check-inline">
                                <input type="checkbox"
                                       name="privacity"
                                       class="form-check-input"
                                       required />

                                <label class="form-check-label" for="privacity">
                                    He leído y acepto las
                                    <a href="{{route('privacity')}}"
                                       title="Declaración de Privacidad">
                                        <strong>
                                            políticas de privacidad
                                        </strong>
                                    </a>
                                    *
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Captcha --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <input type="hidden"
                                   name="g_recaptcha"
                                   id="recaptcha" />
                        </div>
                    </div>

                    <div class="col-md-12 mt-1 mb-5 text-center">
                        <small>
                            <em>
                                <strong>
                                    <span style="color: #f00">
                                        *
                                    </span>
                                    Campos requeridos
                                </strong>
                            </em>
                        </small>
                    </div>

                    <div class="col-md-12 text-center">
                        <div class="form-group">
                            <input type="submit"
                                   name="btnSubmit"
                                   class="btnContact"
                                   value="Enviar" />
                        </div>
                    </div>
                </div>
            </form>
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

@section('js')
    <script src="https://www.google.com/recaptcha/api.js?render={{config('constant.google_recaptcha_key')}}"></script>


    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ config('constant.google_recaptcha_key') }}', {
                action: 'contact'
            }).then(function(token) {
                if (token) {
                    document.getElementById('recaptcha').value = token;
                }
            });
        });
    </script>
@endsection
