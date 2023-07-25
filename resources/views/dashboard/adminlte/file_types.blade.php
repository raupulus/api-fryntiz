@extends('adminlte::page')

@section('title', 'Tipos de Archivos')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>

        Tipos de Archivos
    </h1>
@stop

@section('content')

    <div class="row" id="app">

        <div class="col-12">

            @foreach($types as $type)
                <div class="row">
                    <div class="col-12">
                        <h2>
                            {{\Str::ucfirst($type)}}
                        </h2>
                    </div>


                    @foreach($fileTypes->where('type', $type) as $fileType)

                            <div class="col-md-3 col-sm-6 col-12">
                                <div class="info-box">
                                        <span class="">
                                            <v-image-cropper
                                                style="width: 68px; height: 68px;"
                                                url="{{$fileType->urlIconUpdate}}"
                                                default-image="{{$fileType->urlImage}}"
                                                csrf="{{csrf_token()}}"
                                                :aspect-ratios-restriction="[1,1]"
                                            ></v-image-cropper>
                                        </span>

                                    <div class="info-box-content">
                                        <span class="info-box-text">{{$fileType->mime}}</span>
                                        <span class="info-box-number">ID: {{$fileType->id}}</span>
                                    </div>

                                </div>

                            </div>
                    @endforeach
                </div>
            @endforeach

        </div>
    </div>

@stop

@section('css')
    <style>
        /*
        .attachment-icon {
            width: 40px;
            height: 40px;
            box-sizing: border-box;
        }

        .attachment-data {
            display: inline-block;
            width: calc(100% - 128px);
            height: 128px;
            box-sizing: border-box;
        }

         */
    </style>
@endsection
