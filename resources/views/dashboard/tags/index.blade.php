@extends('adminlte::page')

@section('title', 'Etiquetas')

@section('content_header')
    <h1>
        <i class="fas fa-file-pdf"></i>
        Etiquetas
    </h1>
@stop

@section('content')

    <div class="row" id="app">
        <div class="col-12">
            <h2>
                Listado de Etiquetas
                <a href="{{ route('dashboard.tag.create') }}"
                   class="btn btn-primary float-right">
                    <i class="fas fa-plus"></i>
                    Nueva
                </a>
            </h2>
        </div>

        <div class="col-12">

            <v-table-component title="Listado de Categorías"
                               :editable="true"
                               :show-id="false"
                               csrf="{{csrf_token()}}"
                               :actions='{!!\App\Models\Tag::getTableActionsInfo()->toJson(JSON_UNESCAPED_UNICODE)!!}'
                               url="{{route('dashboard.tag.ajax.table.get')}}" />


            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Etiquetas</h3>

                </div>

                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                        <tr>
                            <th style="width: 1%">
                                #
                            </th>
                            <th style="width: 20%">
                                Nombre
                            </th>
                            <th style="width: 30%">
                                Slug
                            </th>
                            <th style="width: 8%" class="text-center">
                                Descripción
                            </th>
                            <th style="width: 20%">
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($tags as $tag)
                            <tr>
                                <td>
                                    #
                                </td>
                                <td>
                                    <a>
                                        {{$tag->name}}
                                    </a>
                                    <br>
                                    <small>
                                        Actualizado
                                        {{$tag->created_at->format('d/m/Y H:i')}}
                                    </small>
                                </td>
                                <td>
                                    {{$tag->slug}}
                                </td>

                                <td class="project-state">
                                    {{$tag->description}}
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
                                       href="{{route('dashboard.tag.edit', $tag->id)}}">
                                        <i class="fas fa-pencil-alt">
                                        </i>
                                        Editar
                                    </a>

                                    &nbsp;

                                    <form action="{{route('dashboard.tag.destroy', $tag->id)}}"
                                          method="POST"
                                          style="display: inline-block;">

                                        @csrf

                                        <input type="hidden" name="id"
                                               value="{{$tag->id}}">

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
