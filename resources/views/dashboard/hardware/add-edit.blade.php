@extends('adminlte::page')

@section('title', 'Añadir Nuevo Dispositivo')

@section('content_header')
    <h1>
        <i class="fas fa-globe"></i>
        Dispositivo
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
                action="{{$device && $device->id ? route('dashboard.hardware.device.update', $device->id) : route('dashboard.hardware.device.store')}}"
                enctype="multipart/form-data"
                method="POST">

                @csrf

                <input type="hidden" name="cv_id" value="{{$device->id}}">

                <div class="row">
                    <div class="col-12">
                        <h2 style="display: inline-block;">
                            {{(isset($device) && $device && $device->id) ? 'Editar ' .
                            $device->title : 'Creando nuevo Dispositivo'}}


                        </h2>

                        <div class="float-right">
                            <button type="submit"
                                    class="btn btn-success float-right">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>
                        </div>
                    </div>

                    {{-- Imagen --}}
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Imagen Adjunta
                                </h3>
                            </div>

                            <div class="card-body">
                                <div class="form-group">
                                    <label for="image">
                                        Imagen
                                    </label>

                                    <div class="input-group">
                                        <img
                                            src="{{ $device->urlThumbnail('small') }}"
                                            alt="Curriculum Image"
                                            id="cv-image-preview"
                                            style="width: 80px; margin-right: 10px;"/>

                                        <div class="custom-file">

                                            <input type="file"
                                                   name="image"
                                                   id="cv-image-input"
                                                   accept="image/*"
                                                   class="form-control-file">

                                            <label class="custom-file-label"
                                                   id="cv-image-label"
                                                   for="cv-image-input">

                                                @if ($device->image)
                                                    {{$device->image->original_name}}
                                                @else
                                                    Añadir archivo
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos principales
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 690px;">
                                <div class="form-group">
                                    <label for="name">
                                        Nombre
                                    </label>

                                    <input id="name"
                                           type="text"
                                           class="form-control"
                                           name="name"
                                           value="{{ old('name', $device->name) }}"
                                           placeholder="Nombre del dispositivo">
                                </div>

                                <div class="form-group">
                                    <label for="name_friendly">
                                        Nombre Amigable

                                        <br/>

                                        <small class="text-muted">
                                            Nombre corto y descriptivo
                                        </small>
                                    </label>

                                    <input id="name_friendly"
                                           type="text"
                                           class="form-control"
                                           name="name_friendly"
                                           value="{{ old('name_friendly', $device->name_friendly) }}"
                                           placeholder="Nombre amigable del dispositivo">
                                </div>

                                <div class="form-group">
                                    <label for="ref">
                                        Referencia
                                    </label>

                                    <input id="ref"
                                           type="text"
                                           class="form-control"
                                           name="ref"
                                           value="{{ old('ref', $device->ref) }}"
                                           placeholder="Referencia del dispositivo">
                                </div>

                                <div class="form-group">
                                    <label for="brand">
                                         Marca
                                    </label>

                                    <input id="brand"
                                           type="text"
                                           class="form-control"
                                           name="brand"
                                           value="{{ old('brand', $device->brand) }}"
                                           placeholder="Marca del dispositivo">
                                </div>

                                <div class="form-group">
                                    <label for="model">
                                        Modelo
                                    </label>

                                    <input id="model"
                                           type="text"
                                           class="form-control"
                                           name="model"
                                           value="{{ old('model', $device->model) }}"
                                           placeholder="Modelo del dispositivo">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="software_version">
                                                Versión del software
                                            </label>

                                            <input id="software_version"
                                                   type="text"
                                                   class="form-control"
                                                   name="software_version"
                                                   value="{{ old('software_version', $device->software_version) }}"
                                                   placeholder="Versión del software en el dispositivo">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hardware_version">
                                                Versión del hardware
                                            </label>

                                            <input id="hardware_version"
                                                   type="text"
                                                   class="form-control"
                                                   name="hardware_version"
                                                   value="{{ old('hardware_version', $device->hardware_version) }}"
                                                   placeholder="Versión del hardware en el dispositivo">
                                        </div>
                                    </div>
                                </div>




                                <div class="form-group">
                                    <label for="serial_number">
                                        Número de serie
                                    </label>

                                    <input id="serial_number"
                                           type="text"
                                           class="form-control"
                                           name="serial_number"
                                           value="{{ old('serial_number', $device->serial_number) }}"
                                           placeholder="Número de serie del dispositivo">
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Datos Secundarios
                                </h3>
                            </div>

                            <div class="card-body" style="min-height: 690px;">

                                <div class="form-group">
                                    <label for="hardware_type_id">
                                        Tipo de Hardware
                                    </label>
                                    <select id="hardware_type_id"
                                            name="hardware_type_id"
                                            class="form-control">

                                        <option value="">
                                            Seleccione un tipo de hardware
                                        </option>

                                        @foreach (\App\Models\Hardware\HardwareType::all() as $hardwareType)
                                            <option
                                                value="{{$hardwareType->id}}">
                                                {{$hardwareType->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="battery_type">
                                        Tipo de Batería
                                    </label>
                                    <select id="battery_type"
                                            name="battery_type"
                                            class="form-control">

                                        <option value="">
                                            Seleccione el tipo de batería
                                        </option>

                                        <option value="">
                                            No tiene batería
                                        </option>

                                        <option value="GEL">
                                            GEL
                                        </option>

                                        <option value="NI-MH">
                                            NI-MH
                                        </option>

                                        <option value="LI-ION">
                                            LI-ION
                                        </option>

                                        <option value="LIPO">
                                            LIPO
                                        </option>

                                        <option value="AGM">
                                            AGM
                                        </option>

                                        <option value="VRLA">
                                            VRLA (Plomo ácido sellada)
                                        </option>

                                        <option value="NI-FE">
                                            NI-FE
                                        </option>

                                        <option value="NI-CD">
                                            NI-CD
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="battery_nominal_capacity">
                                        Capacidad Nominal de la Batería
                                    </label>

                                    <input id="battery_nominal_capacity"
                                           type="number"
                                           class="form-control"
                                           name="battery_nominal_capacity"
                                           value="{{ old('battery_nominal_capacity', $device->battery_nominal_capacity) }}"
                                           placeholder="50">
                                </div>

                                <div class="form-group">
                                    <label for="url_company">
                                        Url al fabricante
                                    </label>

                                    <input id="url_company"
                                           type="text"
                                           class="form-control"
                                           name="url_company"
                                           value="{{ old('url_company', $device->url_company) }}"
                                           placeholder="Enlace a la web del fabricante">
                                </div>

                                <div class="form-group">
                                    <label for="url_company">
                                        Url al fabricante
                                    </label>

                                    <input id="url_company"
                                           type="text"
                                           class="form-control"
                                           name="url_company"
                                           value="{{ old('url_company', $device->url_company) }}"
                                           placeholder="Enlace a la web del fabricante">
                                </div>

                                <div class="form-group">
                                    <label>Fecha de compra</label>
                                    <div class="input-group date"
                                         id="buy_at_calendar"
                                         data-target-input="nearest">
                                        <input type="text"
                                               id="buy_at"
                                               class="form-control datetimepicker-input"
                                               data-target="#buy_at_calendar">
                                        <div class="input-group-append"
                                             data-target="#buy_at_calendar"
                                             data-toggle="datetimepicker">
                                            <div class="input-group-text">
                                                <i class="fa fa-calendar"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- TODO → Implementar sistema para asociar con productos referidos --}}
                                <div class="form-group">
                                    <label for="referred_thing_id">
                                        Asociar producto referido (TODO)
                                    </label>

                                    <input id="referred_thing_id"
                                           type="text"
                                           disabled
                                           class="form-control"
                                           name="referred_thing_id"
                                           value="{{ old('referred_thing_id', $device->referred_thing_id) }}"
                                           placeholder="Referido asociado">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div class="col-12">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Descripción
                                </h3>
                            </div>

                            <div class="card-body">
                                <div class="form-group">
                                    <label for="description">
                                        Resumen del dispositivo
                                    </label>
                                    <textarea id="description"
                                              class="form-control"
                                              rows="5"
                                              name="description"
                                              placeholder="Añade tu Resumen...">{{ old('description', $device->description) }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </form>
        </div>


        @if (isset($device) && $device && $device->id)
            <div class="col-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            General
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="btn-group">
                            <a href="{{route('dashboard.hardware.device.repository.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Repositorios
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.service.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Servicios
                            </a>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            Formación
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="btn-group">
                            <a href="{{route('dashboard.hardware.device.academic_complementary.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Complementaria
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.academic_complementary_online.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Online
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.academic_training.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Académica
                            </a>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            Experiencia Laboral
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="btn-group">
                            <a href="{{route('dashboard.hardware.device.experience_accredited.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Acreditada
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.experience_additional.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Adicional
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.experience_no_accredited.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                No Acreditada
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.experience_other.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Otros
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.experience_selfemployed.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Autoempleado
                            </a>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            Personal
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="btn-group">
                            <a href="{{route('dashboard.hardware.device.collaboration.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Colaboraciones
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.hobby.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Aficciones
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.job.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Trabajos
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.project.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Proyectos
                            </a>

                            &nbsp;

                            <a href="{{route('dashboard.hardware.device.skill.index', $device->id)}}"
                               class="btn btn-info">
                                <i class="fas fa-folder-open"></i>
                                Habilidades
                            </a>

                        </div>
                    </div>
                </div>
            </div>

        @endif
    </div>
@stop

@section('js')
    <script src="{{ mix('dashboard/js/dashboard.js') }}"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />>

    <script>
        window.document.addEventListener('click', () => {
            /********** Cambiar Imagen al subirla **********/
            const avatarInput = document.getElementById('cv-image-input');
            const imageView = document.getElementById('cv-image-preview');
            const imageLabel = document.getElementById('cv-image-label');

            if(avatarInput) {
                avatarInput.onchange = () => {
                    const reader = new FileReader();

                    reader.onload = () => {
                        imageView.src = reader.result;
                    }

                    if(avatarInput.files && avatarInput.files[0]) {
                        reader.readAsDataURL(avatarInput.files[0]);

                        if(imageLabel) {
                            imageLabel.textContent = avatarInput.files[0].name;
                        }
                    }
                };
            }

        });



        // Selector para fecha de compra
        //$('#buy_at').datepicker()

        $(function() {


            $('#buy_at_calendar').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                minYear: 2000,
                autoApply: true,
            }, function(start, end, label) {
                let input = document.getElementById('buy_at');
                input.value = start.format('YYYY-MM-DD');
            });
        });
    </script>
@stop
