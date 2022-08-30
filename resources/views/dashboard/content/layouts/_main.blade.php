<div class="row">


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
                               value="{{ old('title', $model->title) }}"
                               placeholder="Título">
                    </div>
                </div>

                {{-- Slug --}}
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
                               value="{{ old('slug', $model->slug) }}"
                               placeholder="slug-para-el-contenido">
                    </div>
                </div>

                {{-- Autor --}}
                <div class="form-group">
                    <label for="author">
                        Autor
                    </label>

                    <select id="author" name="author"
                            class="custom-select rounded-0">
                        @if (auth()->user()->role_id === 2)
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                        {{!$model->author_id && ($user->id === auth()->id()) ? 'selected' : ''}}
                                        {{$model->author_id === $user->id ? 'selected' : ''}}>
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
                    <label for="platform">
                        Plataforma
                    </label>

                    <select id="platform" name="platform"
                            class="custom-select rounded-0">
                        @foreach($platforms as $platform)
                            <option value="{{ $platform->id }}"
                                    {{$model->platform_id === $platform->id ? 'selected' : ''}}>
                                {{ $platform->title }}
                            </option>
                        @endforeach
                    </select>
                </div>


                {{-- Tipo de Contenido --}}
                <div class="form-group">
                    <label for="platform">
                        Tipo de Contenido
                    </label>

                    <select id="platform" name="platform"
                            class="custom-select rounded-0">
                        @foreach($contentTypes as $contentType)
                            <option value="{{ $contentType->id }}"
                                    {{$model->type_id === $contentType->id ? 'selected' : ''}}>
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
                               disabled
                               id="is_copyright_valid">
                        <label class="custom-control-label"
                               for="is_copyright_valid">
                            Copyright Validado
                        </label>
                    </div>

                    <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                        <input type="checkbox" class="custom-control-input"
                               id="is_visible">
                        <label class="custom-control-label" for="is_visible">
                            Contenido Visible
                        </label>
                    </div>

                    <div class="custom-control custom-switch  custom-switch-off custom-switch-on-primary">
                        <input type="checkbox" class="custom-control-input"
                               id="is_featured">
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
                               value="{{ $model->is_comment_enabled ? 'checked' : '' }}">
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
                               value="{{ $model->is_comment_anonymous ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_archive ? 'checked' : '' }}">
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


                <div class="form-group">
                    programated_at	timestamp(0) NULL	Fecha en la que está programada la programación del contenido, deberá ser previamente visible. Si es null, no está programada y estará visible en cualquier momento
                </div>
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
                    <label for="image">
                        Imagen
                    </label>

                    <div class="input-group">
                        <img
                                src="{{ $model->urlThumbnail('small') }}"
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

                                @if ($model->image)
                                    {{$model->image->original_name}}
                                @else
                                    Añadir archivo
                                @endif
                            </label>
                        </div>
                    </div>
                </div>

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
                              placeholder="Descipción de la entrada">{{ old('excerpt', $model->excerpt) }}</textarea>
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
                              placeholder="Descipción de la entrada">{{ old('excerpt', $model->excerpt) }}</textarea>
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
                               value="{{ $model->is_visible_on_home ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_home ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_footer ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_sidebar ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_search ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_rss ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_sitemap ? 'checked' : '' }}">
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
                               value="{{ $model->is_visible_on_sitemap_news ? 'checked' : '' }}">
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
