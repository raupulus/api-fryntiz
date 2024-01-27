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
                {{$user->id ? 'Editar' : 'Crear'}} Usuario

                <a href="{{ route('dashboard.users.store') }}"
                   class="btn btn-primary float-right">
                    <i class="fas fa-save"></i>
                    Guardar
                </a>

                <a href="{{ route('dashboard.users.index') }}"
                   class="btn btn-success float-right ml-2 mr-2">
                    <i class="fas fa-arrow-left"></i>
                    Volver al listado
                </a>
            </h2>
        </div>

        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Datos Principales</h3>
                </div>

                <form>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email"
                                   required
                                   value="{{old('email', $user->email)}}"
                                   class="form-control" id="email" placeholder="Introduce un email">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password"
                                   class="form-control" id="password" placeholder="Password">
                        </div>

                        <div class="form-group">
                            <label for="name">Nombre</label>
                            <input type="text"
                                   required
                                   value="{{old('name', $user->name)}}"
                                   class="form-control" id="name" placeholder="Nombre del usuario">
                        </div>

                        <div class="form-group">
                            <label for="surname">Apellidos</label>
                            <input type="text"
                                   value="{{old('surname', $user->surname)}}"
                                   class="form-control" id="surname" placeholder="Apellidos del usuario">
                        </div>

                        <div class="form-group">
                            <label for="nick">Nick</label>
                            <input type="text"
                                   required
                                   value="{{old('nick', $user->nick)}}"
                                   class="form-control" id="nick" placeholder="">
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>


        </div>
    </div>

@stop
