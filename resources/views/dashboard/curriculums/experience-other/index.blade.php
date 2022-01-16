@extends('dashboard.curriculums.layouts._add-edit-section')

@section('form_inputs')

    <div class="form-group">
        <label for="image">
            Imagen
        </label>

        <div class="input-group">
            <img
                src="{{
                    $model ?
                    $model->urlThumbnail('small') :
                    \App\Models\File::urlDefaultImage('small') }}"
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

                    Añadir archivo
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>
            Título
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-heading"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('title',
                           $model ?
                           $model->title : '') }}"
                   name="title"
                   class="form-control"
                   placeholder="Título">
        </div>
    </div>

    <div class="form-group">
        <label>
            Posición
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-people-arrows"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('position',
                           $model ?
                           $model->position : '') }}"
                   name="position"
                   class="form-control"
                   placeholder="Encargado">
        </div>
    </div>

    <div class="form-group">
        <label>
            Empresa
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-shopping-bag"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('company',
                           $model ?
                           $model->company : '') }}"
                   name="company"
                   class="form-control"
                   placeholder="Nombre de la empresa">
        </div>
    </div>

    <div class="form-group">
        <label>
            Descripción
        </label>

        <div class="input-group mb-3">
            <textarea name="description"
                      class="form-control"
                      rows="5"
                      placeholder="Descripción del servicio">{{old('description',
                   $model ?
                   $model->description : '') }}</textarea>
        </div>
    </div>

    <div class="form-group">
        <div class="input-group mb-3">
            <label class="d-block" style="width: 100%;">
                Fecha de inicio
            </label>

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="far fa-calendar-alt"></i>
                </span>
            </div>

            <input type="text"
                   class="form-control"
                   data-inputmask-alias="datetime"
                   data-inputmask-inputformat="dd/mm/yyyy"
                   data-mask=""
                   name="start_at"
                   value="{{old('start_at',
                           $model ?
                           $model->start_at : '') }}"
                   inputmode="numeric">
        </div>
    </div>

    <div class="form-group">
        <div class="input-group">
            <label class="d-block" style="width: 100%;">
                Fecha de fin
            </label>

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="far fa-calendar-alt"></i>
                </span>
            </div>

            <input type="text"
                   class="form-control"
                   data-inputmask-alias="datetime"
                   data-inputmask-inputformat="dd/mm/yyyy"
                   data-mask=""
                   name="end_at"
                   value="{{old('end_at',
                           $model ?
                           $model->end_at : '') }}"
                   inputmode="numeric">
        </div>
    </div>

    <div class="form-group">
        <label>
            Anotaciones
        </label>

        <div class="input-group mb-3">
            <textarea name="note"
                      class="form-control"
                      rows="5"
                      placeholder="Notas">{{old('note',
                   $model ?
                   $model->note : '') }}</textarea>
        </div>
    </div>

    <div class="form-group">
        @if ($model && $model->id)
            <button type="submit"
                    class="btn btn-primary">
                <i class="fas fa-save"></i>
                Guardar
            </button>
        @else
            <button type="submit"
                    class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Añadir
            </button>
        @endif
    </div>
@stop

@section('plugins.InputMask', true)

@section('js')
    @parent

    <script>
        $(document).ready(function(){
            Inputmask().mask(document.querySelectorAll("[data-mask]"));
        });
    </script>
@stop
