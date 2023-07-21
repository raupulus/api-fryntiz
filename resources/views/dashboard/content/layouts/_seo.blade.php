<form action="#"
      enctype="multipart/form-data"
      method="POST">

    @method('PUT')

    @csrf

    <div class="row">

        <div class="col-12">
            <h2 style="display: inline-block;">
                SEO
            </h2>

            <div class="float-right">
                <button type="submit"
                        class="btn btn-success float-right">
                    <i class="fas fa-save"></i>
                    Guardar
                </button>
            </div>

            <div>
                <p>
                    Al actualizar el contenido:
                </p>

                <p>
                    meta_author
                </p>
            </div>
        </div>

        <div class="col-12 col-md-6">

            {{-- Imagen --}}
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        General
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            {{-- Selector Cropper de imágenes --}}
                            <div class="col-12">
                                <div
                                    style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                    <v-image-cropper
                                        default-image="{{ $model->urlImage }}"
                                        name="image"
                                        :aspect-ratios-restriction="[16,9]"
                                    ></v-image-cropper>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="meta_distribution">Scope (Recomendado Global)</label>
                                    <select name="meta_distribution"
                                            id="meta_distribution"
                                            class="form-control">
                                        <option
                                            value="global" {{$model->seo?->meta_description === 'global' ? 'selected' : ''}}>
                                            Global
                                        </option>
                                        <option
                                            value="local" {{$model->seo?->meta_description === 'local' ? 'selected' : ''}}>
                                            Local
                                        </option>
                                        <option
                                            value="ui" {{$model->seo?->meta_description === 'global' ? 'selected' : ''}}>
                                            UI (Interno, puede ser bloqueado desde exterior)
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="meta_keywords">Keywords</label>
                                    <input name="meta_keywords"
                                           id="meta_keywords"
                                           value="{{$model->seo?->meta_keywords ?? $model->tags->pluck('name')->implode(', ')}}"
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="meta_description">Descripción</label>
                                    <textarea name="meta_description"
                                              id="meta_description"
                                              class="form-control">{{$model->seo?->meta_description ?? $model->excerpt}}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="meta_copyright">Copyright</label>
                                    <input name="meta_copyright"
                                           id="meta_copyright"
                                           value="{{$model->seo?->meta_copyright ?? $model->author->fullName}}"
                                           class="form-control">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


        {{-- Buscadores --}}
        <div class="col-12 col-md-6">

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        Buscadores (Crowling)
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            {{-- Selector Cropper de imágenes --}}
                            <div class="col-12">
                                <div
                                    style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                    <v-image-cropper
                                        default-image="{{ $model->urlImage }}"
                                        name="search_image"
                                        :aspect-ratios-restriction="[16,9]"
                                    ></v-image-cropper>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="search_title">Título</label>

                                    <input type="text"
                                           id="search_title"
                                           name="search_title"
                                           value="{{$model->seo?->search_title ?? $model->title}}"
                                           class="form-control">

                                </div>

                                <div class="form-group">
                                    <label for="search_description">Descripción de buscadores</label>
                                    <textarea name="search_description"
                                              id="search_description"
                                              class="form-control">{{$model->seo?->search_description ?? $model->excerpt}}</textarea>
                                </div>

                                <div class="form-group">
                                    <label for="meta_robots">Robots (TODO -> al crear contenido, marcar "noindex,
                                        nofollow")</label>

                                    <select name="meta_robots"
                                            id="meta_robots"
                                            class="form-control">
                                        <option
                                            value="index, follow" {{$model->seo?->meta_robots === 'index, follow' ? 'selected' : ''}}>
                                            Index, Follow
                                        </option>
                                        <option
                                            value="noindex, follow" {{$model->seo?->meta_robots === 'noindex, follow' ? 'selected' : ''}}>
                                            No Index, Follow
                                        </option>
                                        <option
                                            value="index, nofollow" {{$model->seo?->meta_robots === 'index, nofollow' ? 'selected' : ''}}>
                                            Index, No Follow
                                        </option>
                                        <option
                                            value="noindex, nofollow" {{$model->seo?->meta_robots === 'noindex, nofollow' ? 'selected' : ''}}>
                                            No Index, No Follow
                                        </option>
                                        <option value="none" {{$model->seo?->meta_robots === 'none' ? 'selected' : ''}}>
                                            None
                                        </option>
                                    </select>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Redes Sociales --}}
        <div class="col-12 col-md-6">

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        Redes Sociales
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            {{-- Selector Cropper de imágenes --}}
                            <div class="col-12">
                                <div
                                    style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                    <v-image-cropper
                                        default-image="{{ $model->urlImage }}"
                                        name="og_image"
                                        :aspect-ratios-restriction="[16,9]"
                                    ></v-image-cropper>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="og_image_alt">Imagen ALT</label>

                                    <input type="text" name="og_image_alt"
                                           id="og_image_alt"
                                           value="{{$model->seo?->search_title ?? $model->title}}"
                                           class="form-control">

                                </div>


                                <div class="form-group">
                                    <label for="og_title">Título</label>

                                    <input type="text" id="og_title"
                                           name="og_title"
                                           value="{{$model->seo?->search_title ?? $model->title}}"
                                           class="form-control">

                                </div>


                                <div class="form-group">
                                    <label for="og_description">Descripción de Redes Sociales</label>
                                    <textarea name="og_description"
                                              id="og_description"
                                              class="form-control">{{$model->seo?->og_description ?? $model->excerpt}}</textarea>
                                </div>


                                <div class="form-group">
                                    {{-- TODO: Según el tipo, puede tener otras metaetiquetas definidas aquí: https://ogp.me/#types  REVISAR SI ES INTERESANTE --}}
                                    <label for="og_type">Tipo</label>

                                    <select name="og_type" id="og_type" class="form-control">
                                        <option value="article">Article</option>
                                        <option value="website">Website</option>
                                        <option value="profile">Profile</option>
                                        <option value="video">Video</option>
                                        <option value="music">Music</option>
                                        <option value="book">Book</option>
                                    </select>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Twitter --}}
        <div class="col-12 col-md-6">

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        Twitter
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            {{-- Selector Cropper de imágenes --}}
                            <div class="col-12">
                                <div
                                    style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                    <v-image-cropper
                                        default-image="{{ $model->urlImage }}"
                                        name="search_image"
                                        :aspect-ratios-restriction="[16,9]"
                                    ></v-image-cropper>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="twitter_site">Site user (@raupulus)</label>

                                    <input type="text" name="twitter_site" id="twitter_site"
                                           value="{{$model->seo?->twitter_site ?? '@raupulus'}}"
                                           class="form-control">

                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="twitter_creator">Título</label>

                                    <input type="text"
                                           name="twitter_creator"
                                           id="twitter_creator"
                                           value="{{$model->seo?->twitter_creator ?? {{$model->author?->}}}}"
                                           class="form-control">

                                </div>
                            </div>

                            <p>
                                twitter_card
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Facebook --}}
        <div class="col-12 col-md-6">

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        Facebook
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            {{-- Selector Cropper de imágenes --}}
                            <div class="col-12">
                                <div
                                    style="height: 100%; max-height: 220px; margin: auto; overflow: hidden; box-sizing: border-box;">
                                    <v-image-cropper
                                        default-image="{{ $model->urlImage }}"
                                        name="????"
                                        :aspect-ratios-restriction="[16,9]"
                                    ></v-image-cropper>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="????">Título</label>

                                    <input type="text" name="???" class="form-control">

                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</form>
