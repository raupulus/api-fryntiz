@extends('adminlte::page')

@section('title', 'Hardware')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Usuarios
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Listado de Usuarios
                <a href="{{ route('dashboard.users.create') }}"
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
                    <h3 class="card-title">Usuarios</h3>
                </div>

                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                        <tr>
                            <th style="width: 1%">
                                ID
                            </th>
                            <th style="width: 14%;" class="text-center">
                                Imagen
                            </th>

                            <th style="width: 30%;">
                                Nombre
                            </th>

                            <th style="width: 20%;" class="text-center">
                                Email
                            </th>

                            <th style="width: 35%;">
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($users as $user)
                            <tr>
                                <td>
                                    {{$user->id}}
                                </td>
                                <td class="text-center">
                                    <ul class="list-inline">
                                        <li class="list-inline-item">
                                            <img alt="Avatar"
                                                 class="table-avatar"
                                                 src="{{$user->urlThumbnail('small')}}">
                                        </li>
                                    </ul>
                                </td>

                                <td>
                                    {{$user->name}}
                                </td>

                                <td>
                                    {{$user->email}}
                                </td>

                                <td class="project-actions text-right">

                                    <a class="btn btn-success btn-sm mr-2"
                                       href="{{route('dashboard.users.show', $user->id)}}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Ver
                                    </a>

                                    <a class="btn btn-info btn-sm"
                                       href="{{route('dashboard.users.edit', $user->id)}}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Editar
                                    </a>

                                    &nbsp;

                                    {{--
                                    Prevenir eliminar al pulsar botón directamente, pedir confirmación
                                    <form
                                        action="{{route('dashboard.users.destroy', $user->id)}}"
                                        method="POST"
                                        style="display: inline-block;">

                                        @csrf

                                        <input type="hidden" name="id"
                                               value="{{$user->id}}">

                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                            Eliminar
                                        </button>

                                    </form>
                                    --}}
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
