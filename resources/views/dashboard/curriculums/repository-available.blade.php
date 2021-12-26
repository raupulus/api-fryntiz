@extends('adminlte::page')

@section('title', 'Curriculums - Tipos de Repositorios Disponibles')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Tipos de Repositorios Disponibles
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Listado de Tipos
                <a href="{{ route('dashboard.cv.repository_available_type.create') }}"
                   class="btn  btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nuevo
                </a>
            </h2>
        </div>

        <div class="col-12">


            {{--
            <v-table-component title="titulo de la tabla"
                               url="{{route('language.ajax.get.languages')}}" />
                               --}}


            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Curriculums</h3>

                </div>

                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                        <tr>
                            <th style="width: 2%">
                                #
                            </th>

                            <th style="width: 30%">
                                Título del repositorio
                            </th>

                            <th style="width: 18%" class="text-center">
                                slug
                            </th>

                            <th style="width: 30%"  class="text-center">
                                Imagen
                            </th>

                            <th style="width: 20%">
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($repositoryTypes as $repositoryType)
                            <tr>
                                <td>
                                    #
                                </td>
                                <td>
                                    <a>
                                        {{$repositoryType->title}}
                                    </a>
                                    <br>
                                    <small>
                                        Actualizado
                                        {{$repositoryType->updated_at->format('d/m/Y H:i')}}
                                    </small>
                                </td>

                                <td class="text-center">
                                    <span class="badge badge-info">
                                        {{$repositoryType->slug}}
                                    </span>
                                </td>

                                <td class="text-center">
                                    @if ($repositoryType->image)
                                        <ul class="list-inline">
                                            <li class="list-inline-item">
                                                <img alt="Avatar"
                                                     class="table-avatar"
                                                     src="{{$repositoryType->image->url}}">
                                            </li>
                                        </ul>
                                    @endif
                                </td>


                                <td class="project-actions text-right">

                                    <a class="btn btn-info btn-sm"
                                       href="{{route('dashboard.cv.repository_available_type.edit',  $repositoryType->id)}}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Editar
                                    </a>

                                    &nbsp;

                                    <form action="{{route('dashboard.cv.repository_available_type.destroy')}}"
                                          method="POST"
                                          style="display: inline-block;">

                                        @csrf

                                        <input type="hidden" name="id"
                                               value="{{$repositoryType->id}}">

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

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
@stop
