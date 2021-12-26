@extends('adminlte::page')

@section('title', 'Curriculums')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Curriculums
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Listado de Curriculums
                <a href="{{ route('dashboard.cv.create') }}"
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
                            <th style="width: 1%">
                                #
                            </th>
                            <th style="width: 20%">
                                Project Name
                            </th>
                            <th style="width: 30%">
                                Imagen
                            </th>
                            <th style="width: 8%" class="text-center">
                                Activo
                            </th>
                            <th style="width: 20%">
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($curriculums as $curriculum)
                            <tr>
                                <td>
                                    #
                                </td>
                                <td>
                                    <a>
                                        {{$curriculum->title}}
                                    </a>
                                    <br>
                                    <small>
                                        Actualizado
                                        {{$curriculum->created_at->format('d/m/Y H:i')}}
                                    </small>
                                </td>
                                <td>
                                    @if ($curriculum->image)
                                        <ul class="list-inline">
                                            <li class="list-inline-item">
                                                <img alt="Avatar"
                                                     class="table-avatar"
                                                     src="{{$curriculum->image->url}}">
                                            </li>
                                        </ul>
                                    @endif
                                </td>

                                <td class="project-state">
                                    @if ($curriculum->is_active)
                                        <span class="badge badge-success">
                                            Activo
                                        </span>

                                    @else
                                        <span class="badge badge-danger">
                                            Inactivo
                                        </span>

                                    @endif
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
                                       href="{{route('dashboard.cv.edit', $curriculum->id)}}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Editar
                                    </a>

                                    &nbsp;

                                    <form action="{{route('dashboard.cv.destroy')}}"
                                          method="POST"
                                          style="display: inline-block;">

                                        @csrf

                                        <input type="hidden" name="id"
                                               value="{{$curriculum->id}}">

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
