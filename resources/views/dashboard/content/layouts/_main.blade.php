<div class="row">

    <div class="col-12">
        <h2 style="display: inline-block;">
            {{(isset($model) && $model && $model->id) ? 'Editar ' . $model::getModelTitles()['singular'] : 'Creando ' . $model::getModelTitles()['singular']}}
        </h2>

        <div class="float-right">
            <button type="submit"
                    class="btn btn-success float-right">
                <i class="fas fa-save"></i>
                Guardar
            </button>
        </div>
    </div>

    {{-- Columna Izquierda --}}
    <div class="col-12 col-md-6">

        {{-- Datos Principales --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Datos Principales
                </h3>
            </div>

            <div class="card-body">

                {{-- Title --}}
                <div class="form-group">
                    <label for="title">
                        Título
                    </label>

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-heading"></i>
                            </span>
                        </div>

                        <input id="title"
                               type="text"
                               class="form-control"
                               name="title"
                               data-slug_provider="title"
                               minlength="5"
                               maxlength="255"
                               required
                               value="{{ old('title', $model->title) }}"
                               placeholder="Título">
                    </div>
                </div>

                {{-- Slug TODO:check en tiempo real, poner título si está vacío al perder focus del campo título--}}

                {{-- Slug TODO:comprobar si está bien formado en tiempo real--}}
                <div class="form-group">
                    <label for="slug">
                        Slug
                    </label>

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-link"></i>
                            </span>
                        </div>

                        <input id="slug"
                               type="text"
                               class="form-control"
                               name="slug"
                               data-sluggable="title"
                               required
                               minlength="3"
                               maxlength="255"
                               value="{{ old('slug', $model->slug) }}"
                               placeholder="slug-para-el-contenido">
                    </div>
                </div>

                {{-- Autor --}}
                <div class="form-group">
                    <label for="author_id">
                        Autor
                    </label>

                    <select id="author_id" name="author_id"
                            class="custom-select rounded-0">
                        @if (in_array(auth()->user()->role_id, [1, 2]))
                            @foreach($users as $user)
                                @php($checked = (int)old('author_id')===  $user->id)
                                @php($checked = $checked ?? !$model->author_id
                                 && ($user->id === auth()->id()))
                                @php($checked = $checked ?? $model->author_id  === $user->id)

                                <option value="{{ $user->id }}"
                                        {{$checked ? 'selected' : ''}}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        @else
                            <option value="{{ auth()->user()->id }}" selected>
                                {{ auth()->user()->name }}
                            </option>
                        @endif
                    </select>
                </div>

                {{-- Plataforma --}}
                <div class="form-group">
                    <label for="platform_id">
                        Plataforma
                    </label>

                    <select id="platform_id" name="platform_id"
                            class="custom-select rounded-0">
                        @foreach($platforms as $platform)
                            @php($checked = (int)old('platform_id', $model->platform_id) ===
                            $platform->id)

                            <option value="{{ $platform->id }}"
                                    {{$checked ? 'selected' : ''}}>
                                {{ $platform->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de Contenido --}}
                <div class="form-group">
                    <label for="type_id">
                        Tipo de Contenido
                    </label>

                    <select id="type_id" name="type_id"
                            class="custom-select rounded-0">
                        @foreach($contentTypes as $contentType)
                            @php($checked = (int)old('type_id') === $contentType->id)
                            @php($checked = $checked ?? $model->type_id === $contentType->id)

                            <option value="{{ $contentType->id }}"
                                    {{$checked ? 'selected' : ''}}>
                                {{ $contentType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Propiedades --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Propiedades
                </h3>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <div class="custom-control custom-switch custom-switch-off custom-switch-on-success">
                        <input type="checkbox" class="custom-control-input"
                               {{$model->id && $model->is_copyright_valid ? 'checked' : ''}}
                               disabled
                               id="is_copyright_valid">
                        <label class="custom-control-label"
                               for="is_copyright_valid">
                            Copyright Validado
                        </label>
                    </div>

                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                        <input type="checkbox" class="custom-control-input"
                               {{old('is_active', $model->is_active) ?
                               'checked' : ''}}
                               name="is_active"
                               id="is_active">
                        <label class="custom-control-label" for="is_active">
                            Contenido Activo
                        </label>
                    </div>

                    <div class="custom-control custom-switch  custom-switch-off custom-switch-on-primary">
                        <input type="checkbox" class="custom-control-input"
                               {{old('is_featured', $model->is_featured) ? 'checked' : ''}}
                               id="is_featured"
                               name="is_featured">
                        <label class="custom-control-label" for="is_featured">
                            Destacar
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Opciones --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Opciones
                </h3>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input"
                               type="checkbox"
                               id="is_comment_enabled"
                               name="is_comment_enabled"
                                {{old('is_comment_enabled', $model->is_comment_enabled) ? 'checked' : ''}}>
                        <label for="is_comment_enabled"
                               class="custom-control-label">
                            Permitir Comentarios
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-danger"
                               type="checkbox"
                               id="is_comment_anonymous"
                               name="is_comment_anonymous"
                                {{ old('is_comment_anonymous', $model->is_comment_anonymous) ? 'checked' : '' }}>
                        <label for="is_comment_anonymous"
                               class="custom-control-label">
                            Permitir Comentarios Anónimos
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-danger"
                               type="checkbox"
                               id="is_visible_on_archive"
                               name="is_visible_on_archive"
                                {{ old('is_visible_on_archive', $model->is_visible_on_archive) ? 'checked' : '' }}>
                        <label for="is_visible_on_archive"
                               class="custom-control-label">
                            Contenido Archivado (obsoleto)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Publicación --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Publicación
                </h3>
            </div>

            <div class="card-body">
                <div class="callout callout-success">
                    <p>
                        <strong>
                            Procesado del contenido, autoría,
                            actualizaciones de nube, verificaciones, etc
                        </strong>
                    </p>

                    <p>
                        @if($model->processed_at)
                            <span class="badge badge-success">
                                Procesado el {{ $model->processed_at->format('d/m/Y H:i:s') }}
                            </span>
                        @else
                            <span class="badge badge-danger">
                                No Procesado
                            </span>
                        @endif
                    </p>
                </div>

                <div class="callout callout-success">
                    <p>
                        <strong>
                            Fecha de publicación
                        </strong>
                    </p>

                    <p>
                        @if($model->published_at)
                            <span class="badge badge-success">
                                Publicado el {{ $model->published_at->format('d/m/Y H:i:s') }}
                            </span>
                        @else
                            <span class="badge badge-danger">
                                No Publicado
                            </span>
                        @endif
                    </p>
                </div>

                <div class="callout callout-info">
                    <p>
                        <strong>
                            Fecha de creación
                        </strong>
                    </p>

                    <p>
                        @if($model->created_at)
                            <span class="badge badge-success">
                                Creado el {{ $model->created_at->format('d/m/Y H:i:s') }}
                            </span>
                        @else
                            <span class="badge badge-danger">
                                No Guardado
                            </span>
                        @endif
                    </p>
                </div>

                <div class="callout callout-success">
                    <p>
                        <strong>
                            Fecha de la última actualización
                        </strong>
                    </p>

                    <p>
                        @if($model->updated_at)
                            <span class="badge badge-success">
                                Actualizado el {{ $model->updated_at->format('d/m/Y H:i:s') }}
                            </span>
                        @else
                            <span class="badge badge-danger">
                                No Guardado
                            </span>
                        @endif
                    </p>
                </div>


                {{-- Solo se muestra programar cuando aún no está publicado --}}
                @if(!$model->published_at)
                    <div class="form-group">
                        <label>
                            Programar Publicación:
                        </label>

                        <div class="input-group date"
                             id="programated_at"
                             data-target-input="nearest">
                            <input type="text"
                                   value="{{old('programated_at', $model->programated_at)}}"
                                   name="programated_at"
                                   class="form-control datetimepicker-input"
                                   data-target="#programated_at">
                            <div class="input-group-append"
                                 data-target="#programated_at"
                                 data-toggle="datetimepicker">

                                <div class="input-group-text">
                                    <i class="fa fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Columna Derecha --}}
    <div class="col-12 col-md-6">

        {{-- Imagen --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Imagen Adjunta
                </h3>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <div class="input-group">
                        {{-- Selector Cropper de imágenes --}}
                        <div class="col-12">
                            <div style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                <v-image-cropper
                                    default-image="{{ $model->urlImage }}"
                                    name="image"
                                    :aspect-ratios-restriction="[16,9]"
                                ></v-image-cropper>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Detalles --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Detalles
                </h3>
            </div>

            <div class="card-body">
                {{-- Excerpt --}}
                <div class="form-group">
                    <label for="excerpt">
                        Resumen
                    </label>

                    <textarea id="excerpt"
                              type="text"
                              class="form-control"
                              name="excerpt"
                              rows="3"
                              style="height: 104px; resize: none;"
                              placeholder="Descripción de la entrada">{{ old('excerpt', $model->excerpt) }}</textarea>
                </div>

                {{-- Contribuidores --}}
                <div class="form-group">
                    <label for="contributors">
                        Contribuidores
                        <br/>
                        <small>
                            Podrán editar las páginas del contenido
                        </small>
                    </label>

                    <select id="contributors"
                            name="contributors[]"
                            class="duallistbox"
                            multiple="multiple">

                        @foreach($users as $user)
                            @php($checked = $contributorsIds && in_array($user->id, $contributorsIds))
                            @php($checked = $checked || (old('contributors') && in_array($user->id, old('contributors'))))

                            @if ($user->id != auth()->id())
                                <option {{$checked ? 'selected' : ''}}
                                        value="{{$user->id}}">
                                    {{$user->name}}
                                </option>
                            @endif
                        @endforeach

                    </select>
                </div>

                {{-- Contenido relacionado --}}
                <div class="form-group">
                    <label for="contentRelated">
                        Contenido relacionado

                        <br>

                        <small>
                            Contenido vinculado a la entrada, se mostrará como
                            sugerencia al final de la entrada.
                        </small>
                    </label>

                    <input class="form-control mb-2"
                           placeholder="Buscar contenido..."
                           value=""
                           id="searchContentRelated"/>

                    <select id="contentRelated"
                            name="contents_related[]"
                            multiple="multiple"
                            class="form-control">

                        @foreach($contentRelatedAll as $contentRelated)
                            <option value="{{$contentRelated->id}}" selected>
                                {{$contentRelated->title}}
                            </option>
                        @endforeach


                    </select>

                </div>


                {{-- Etiquetas --}}
                <div class="form-group">
                    <label for="tags">
                        Etiquetas
                    </label>

                    <br>

                    <small>
                        Adjetivos que identifiquen este contenido, se
                        sacarán las sugerencias para leer más contenido
                    </small>

                    <br/>

                    <div class="select2-purple">
                        <select id="tags" name="tags[]"
                                class="select2 select2-hidden-accessible"
                                multiple="multiple"
                                data-placeholder="Selecciona las etiquetas"
                                data-dropdown-css-class="select2-purple"
                                style="width: 100%;"
                                tabindex="-1"
                                aria-hidden="true">

                            @foreach($tags as $tag)
                                @php($checked = $modelTagsIds && in_array($tag->id, $modelTagsIds))
                                @php($checked = $checked || (old('tags') && in_array($tag->id, old('tags'))))

                                <option {{$checked ? 'selected' : ''}}
                                        value="{{$tag->id}}">
                                    {{$tag->name}}
                                </option>

                            @endforeach
                        </select>

                        <div class="mt-3 text-center">
                            <span class="btn btn-sm btn-primary"
                                  data-toggle="modal"
                                  data-target=".modal-create-tag"  >
                                <i class="fa fa-plus"></i>
                                Crear etiqueta
                            </span>

                            <div id="modal-create-tag"
                                 class="modal fade modal-create-tag"
                                 tabindex="-1" role="dialog"
                                 aria-labelledby="modal-create-tag-label"
                                 aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="tag-create">
                                                        Añadir etiquetas
                                                    </label>

                                                    <p>
                                                        <small>
                                                            Las etiquetas se
                                                            separan por comas
                                                            para añadir varias
                                                        </small>
                                                    </p>

                                                    <input id="create-tag-input"
                                                           class="form-control m-auto w-75"/>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <p class="msg-success">
                                                    Error/Success
                                                </p>
                                            </div>

                                            {{-- Añado badges con las
                                            etiquetas que estoy creando --}}
                                            <div id="box-tags-created"
                                                 class="col-12">
                                            </div>

                                            <div class="col-12">
                                                <span id="create-tag-button"
                                                      class="btn btn-sm
                                                btn-success m-2">
                                                        Añadir
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


                {{-- Categorías --}}
                <div class="form-group">
                    <label for="categories">
                        Categorías

                        <br>

                        <small>
                            Agrupa tipo de contenido relacionado
                            indirectamente, sobre el mismo tema pero no
                            exactamente igual.
                        </small>
                    </label>

                    <div class="select2-info">

                        <select id="categories" name="categories[]"
                                class="select2 select2-hidden-accessible"
                                multiple=""
                                data-placeholder="Selecciona las categorías"
                                data-dropdown-css-class="select2-info"
                                style="width: 100%;"
                                tabindex="-1"
                                aria-hidden="true">


                            @foreach($categories as $category)
                                @php($checked = $modelCategoriesIds && in_array($category->id, $modelCategoriesIds))
                                @php($checked = $checked || (old('categories') && in_array($category->id, old('categories'))))

                                <option {{$checked ? 'selected' : ''}}
                                        value="{{$category->id}}">
                                    {{$category->name}}
                                </option>

                            @endforeach

                        </select>

                        <div class="mt-3 text-center">
                            <span class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i>
                                Crear etiqueta
                            </span>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        {{-- Visibilidad --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    Visibilidad
                </h3>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_home"
                               name="is_visible_on_home"
                                {{ old('is_visible_on_home', $model->is_visible_on_home) ? 'checked' : '' }}>
                        <label for="is_visible_on_home"
                               class="custom-control-label">
                            Visible en la página principal
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_menu"
                               name="is_visible_on_menu"
                                {{ old('is_visible_on_menu', $model->is_visible_on_menu) ? 'checked' : '' }}>
                        <label for="is_visible_on_menu"
                               class="custom-control-label">
                            Visible en el menú
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_footer"
                               name="is_visible_on_footer"
                                {{ old('is_visible_on_footer', $model->is_visible_on_footer) ? 'checked' : '' }}>
                        <label for="is_visible_on_footer"
                               class="custom-control-label">
                            Visible en el Footer
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_sidebar"
                               name="is_visible_on_sidebar"
                                {{ old('is_visible_on_sidebar', $model->is_visible_on_sidebar) ? 'checked' : '' }}>
                        <label for="is_visible_on_sidebar"
                               class="custom-control-label">
                            Visible en el Sidebar
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_search"
                               name="is_visible_on_search"
                                {{ old('is_visible_on_search', $model->is_visible_on_search) ? 'checked' : '' }}>
                        <label for="is_visible_on_search"
                               class="custom-control-label">
                            Visible en las búsquedas
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_rss"
                               name="is_visible_on_rss"
                                {{ old('is_visible_on_rss', $model->is_visible_on_rss) ? 'checked' : '' }}>
                        <label for="is_visible_on_rss"
                               class="custom-control-label">
                            Visible en las redes sociales
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_sitemap"
                               name="is_visible_on_sitemap"
                                {{ old('is_visible_on_sitemap', $model->is_visible_on_sitemap) ? 'checked' : '' }}>
                        <label for="is_visible_on_sitemap"
                               class="custom-control-label">
                            Visible para el Sitemap General
                        </label>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input custom-control-input-success"
                               type="checkbox"
                               id="is_visible_on_sitemap_news"
                               name="is_visible_on_sitemap_news"
                                {{ old('is_visible_on_sitemap_news', $model->is_visible_on_sitemap_news) ? 'checked' : '' }}>
                        <label for="is_visible_on_sitemap_news"
                               class="custom-control-label">
                            Visible para el Sitemap de Noticias
                        </label>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
