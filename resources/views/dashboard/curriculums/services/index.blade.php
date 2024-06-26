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
            URL
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
                   placeholder="https://raupulus.dev">
        </div>
    </div>


    <div class="form-group">
        <label>
            Nombre
        </label>

        <div class="input-group mb-3">

            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-heading"></i>
                </span>
            </div>

            <input type="text"
                   value="{{old('name',
                           $model ?
                           $model->name : '') }}"
                   name="name"
                   class="form-control"
                   placeholder="Mumble">
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
