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
            Entidad emisora
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-people-arrows"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('credential_id',
                           $model ?
                           $model->credential_id : '') }}"
                   name="credential_id"
                   class="form-control"
                   placeholder="Entidad">
        </div>
    </div>



    <div class="form-group">
        <label>
            URL del sitio web
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-link"></i>
                </span>
            </div>

            <input type="url"
                   value="{{old('url',
                               $model ?
                               $model->url : '') }}"
                   name="url"
                   class="form-control"
                   placeholder="https://fryntiz.es">
        </div>
    </div>

    <div class="form-group">
        <label>
            URL de la credencial
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-link"></i>
                </span>
            </div>

            <input type="url"
                   value="{{old('credential_url',
                               $model ?
                               $model->credential_url : '') }}"
                   name="credential_url"
                   class="form-control"
                   placeholder="https://fryntiz.es/title">
        </div>
    </div>

    <div class="form-group">
        <label>
            Conocimientos adquiridos
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-book-open"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('learned',
                               $model ?
                               $model->learned : '') }}"
                   name="learned"
                   class="form-control"
                   placeholder="Desarrollador">
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
        <label>
            Horas
        </label>

        <div class="input-group mb-3">
            <input type="number"
                   min="0"
                   minlength="0"
                   value="{{old('hours',
                               $model ?
                               $model->hours : '') }}"
                   name="hours"
                   class="form-control"
                   placeholder="240">
        </div>
    </div>

    <div class="form-group">
        <label>
            Instructor de la formación
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('instructor',
                           $model ?
                           $model->instructor : '') }}"
                   name="instructor"
                   class="form-control"
                   placeholder="Pablo Pica Piedras">
        </div>
    </div>

    <div class="form-group">
        <div class="custom-control custom-switch mb-3">
            <input type="checkbox"
                   class="custom-control-input"
                   {{(old('instructor', $model ? $model->expires : false)) ? 'checked' : ''}}
                   id="expires">

            <label class="custom-control-label" for="expires">
                ¿Expira?
            </label>
        </div>
    </div>

    <div class="form-group">
        <div class="input-group mb-3">
            <label class="d-block" style="width: 100%;">
                Fecha de expiración
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
                   name="expires_at"
                   value="{{old('expires_at',
                           $model ?
                           $model->expires_at : '') }}"
                   inputmode="numeric">
        </div>
    </div>


    <div class="form-group">
        <div class="input-group mb-3">
            <label class="d-block" style="width: 100%;">
                Expedido en
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
                   name="expedition_at"
                   value="{{old('expedition_at',
                           $model ?
                           $model->expedition_at : '') }}"
                   inputmode="numeric">
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
