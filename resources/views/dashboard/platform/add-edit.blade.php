@extends('adminlte::page')

@section('title', 'Añadir ' . $model::getModelTitles()['singular'])

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        {{\Illuminate\Support\Str::ucfirst($model::getModelTitles()['singular'])}}
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-12">
            <form
                    action="{{$model && $model->id ? route($model::getCrudRoutes()['update'], $model->id) : route($model::getCrudRoutes()['store'])}}"
                    enctype="multipart/form-data"
                    method="POST">

                @csrf

                <input type="hidden" name="id" value="{{$model->id}}">

                <div class="row">
                    <div class="col-12">
                        <h2 style="display: inline-block;">
                            {{(isset($model) && $model && $model->id) ? 'Editar ' . $model::getModelTitles()['singular'] : 'Creando ' . $model::getModelTitles()['singular']}}
                        </h2>

                        <div class="float-right">
                            <button type="submit"
                                    class="btn btn-success float-right">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos principales
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="title">
                                        Nombre
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="title"
                                           name="title"
                                           value="{{ old('title', $model->title) }}"
                                           placeholder="Título de la plataforma">
                                </div>

                                <div class="form-group">
                                    <label for="slug">
                                        Slug
                                    </label>

                                    <input type="text"
                                           id="slug"
                                           class="form-control"
                                           name="slug"
                                           value="{{ old('slug', $model->slug) }}"
                                           placeholder="Slug-de-la-plataforma">
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Dominio y Url
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="domain">
                                        Dominio
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           name="domain"
                                           value="{{ old('domain', $model->domain) }}"
                                           placeholder="Dominio principal de la plataforma">
                                </div>

                                <div class="form-group">
                                    <label for="url_about">
                                        Url About
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="url_about"
                                           name="url_about"
                                           value="{{ old('url_about', $model->url_about) }}"
                                           placeholder="Url para informar sobre la plataforma">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Datos --}}
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos
                                </h3>
                            </div>

                            <div class="card-body">

                                {{-- Selector Cropper de imágenes --}}
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="col-12">
                                            <div
                                                style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                                <v-image-cropper
                                                    default-image="{{ $model->urlImage }}"
                                                    name="image"
                                                    :aspect-ratios-restriction="[1,1]"
                                                ></v-image-cropper>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="description">
                                        Descripción
                                    </label>
                                    <textarea class="form-control"
                                              rows="5"
                                              id="description"
                                              name="description"
                                              placeholder="Descripción de la plataforma...">{{ old('description', $model->description) }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>



                    {{-- Youtube --}}
                    <div class="col-md-6">
                        <div class="card card-red">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Youtube Channel ID
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="youtube_channel_id">
                                        Youtube User Account
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="youtube_channel_id"
                                           name="youtube_channel_id"
                                           value="{{ old('youtube_channel_id', $model->youtube_channel_id) }}"
                                           placeholder="UFazjf-D3n_yPSdRfCneOpz">
                                </div>

                                <div class="form-group">
                                    <label for="youtube_presentation_video_id">
                                        Youtube URL Vídeo Principal
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="youtube_presentation_video_id"
                                           name="youtube_presentation_video_id"
                                           value="{{ old('youtube_presentation_video_id', $model->youtube_presentation_video_id) }}"
                                           placeholder="https://youtube.com/.....">
                                </div>
                            </div>
                        </div>

                        {{-- Instagram --}}
                        <div class="card card-fuchsia">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Instagram
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="instagram">
                                        Instagram User Account
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="instagram"
                                           name="instagram"
                                           value="{{ old('instagram', $model->instagram) }}"
                                           placeholder="@user">
                                </div>
                            </div>
                        </div>
                    </div>



                    {{-- Twitter --}}
                    <div class="col-md-6">
                        <div class="card card-blue">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Twitter
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="twitter">
                                        Twitter User Account
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="twitter"
                                           name="twitter"
                                           value="{{ old('twitter', $model->twitter) }}"
                                           placeholder="@user">
                                </div>

                                <div class="form-group">
                                    <label for="twitter_token">
                                        Twitter Token
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="twitter_token"
                                           name="twitter_token"
                                           value="{{ old('twitter_token', $model->twitter_token) }}"
                                           placeholder="sdfg98dsf98g9s8dfg8sd9fg8dsfg89">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Mastodon --}}
                    <div class="col-md-6">
                        <div class="card card-purple">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Mastodon
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="mastodon">
                                        Mastodon User Account
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="mastodon"
                                           name="mastodon"
                                           value="{{ old('mastodon', $model->mastodon) }}"
                                           placeholder="@user">
                                </div>

                                <div class="form-group">
                                    <label for="mastodon_token">
                                        Mastodon Token
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="mastodon_token"
                                           name="mastodon_token"
                                           value="{{ old('mastodon_token', $model->mastodon_token) }}"
                                           placeholder="D0SDF789SDFS79DF79SDF79SSD89FSD">
                                </div>
                            </div>
                        </div>
                    </div>






                    {{-- Twitch --}}
                    <div class="col-md-6">
                        <div class="card card-purple">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Twitch
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="twitch">
                                        Twitch User Account
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="twitch"
                                           name="twitch"
                                           value="{{ old('twitch', $model->twitch) }}"
                                           placeholder="@user">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TikTok --}}
                    <div class="col-md-6">
                        <div class="card card-dark">
                            <div class="card-header">
                                <h3 class="card-title">
                                    TikTok
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 160px;">
                                <div class="form-group">
                                    <label for="tikTok">
                                        TikTok User Account
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           id="tikTok"
                                           name="tikTok"
                                           value="{{ old('tikTok', $model->tikTok) }}"
                                           placeholder="@user">
                                </div>
                            </div>
                        </div>
                    </div>









                </div>

            </form>
        </div>
    </div>
@stop
