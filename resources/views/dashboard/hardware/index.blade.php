@extends('adminlte::page')

@section('title', 'Hardware')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Hardware
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Listado de Dispositivos
                <a href="{{ route('dashboard.hardware.device.create') }}"
                   class="btn  btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nuevo
                </a>
            </h2>
        </div>

        <div class="col-12">


            {{--
            <v-table-component title="titulo de la tabla"
                               csrf="{{csrf_token()}}"
                               url="{{route('language.ajax.get.languages')}}" />
                               --}}


            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dispositivos</h3>
                </div>

                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                        <tr>
                            <th style="width: 1%">
                                #
                            </th>
                            <th style="width: 14%;" class="text-center">
                                Imagen
                            </th>

                            <th style="width: 30%;">
                                Nombre
                            </th>

                            <th style="width: 20%;" class="text-center">
                                Model
                            </th>
                            <th style="width: 35%;">
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($devices as $device)
                            <tr>
                                <td>
                                    #
                                </td>
                                <td class="text-center">
                                    <ul class="list-inline">
                                        <li class="list-inline-item">
                                            <img alt="Avatar"
                                                 class="table-avatar"
                                                 src="{{$device->urlThumbnail('small')}}">
                                        </li>
                                    </ul>
                                </td>

                                <td>
                                    {{$device->name}}

                                    @if ($device->buy_at)
                                        <br>
                                        <small>
                                            Compra:
                                            {{$device->buy_at->format('d/m/Y')}}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success">
                                        {{$device->model}}
                                    </span>
                                </td>

                                <td class="project-actions text-right">
                                    {{--
                                    <a class="btn btn-primary btn-sm" href="#">
                                        <i class="fas fa-folder">
                                        </i>
                                        Ver
                                    </a>
                                    --}}

                                    <a class="btn btn-info btn-sm"
                                       href="{{route('dashboard.hardware.device.edit', $device->id)}}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Editar
                                    </a>

                                    &nbsp;

                                    <form
                                        action="{{route('dashboard.hardware.device.destroy', $device->id)}}"
                                        method="POST"
                                        style="display: inline-block;">

                                        @csrf

                                        <input type="hidden" name="id"
                                               value="{{$device->id}}">

                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                            Eliminar
                                        </button>

                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>


        </div>
    </div>

@stop
