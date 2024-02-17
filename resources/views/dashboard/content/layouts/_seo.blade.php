<form action="{{route('dashboard.content.seo.store', $model->id)}}"
      enctype="multipart/form-data"
      method="POST">

    @method('PUT')

    @csrf

    <input type="hidden"
           name="content_id"
           value="{{$model->id}}">

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
        </div>

        <div class="col-12 col-md-6">

            {{-- Imagen --}}
            <div class="card card-dark">
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
                                        default-image="{{ $model->seo?->urlImage }}"
                                        name="image"
                                        :aspect-ratios-restriction="[16, 9]"
                                    ></v-image-cropper>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="image_alt">Image Alt</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-dark">
                                                <i class="fa fa-tag"></i>
                                            </span>
                                        </div>

                                        <input name="image_alt"
                                               id="image_alt"
                                               placeholder="Título descriptivo sobre la imagen"
                                               value="{{$model->seo?->image_alt ?? $model->title}}"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                 <div class="form-group">
                                    <label for="keywords">Keywords</label>

                                     <div class="input-group">
                                         <div class="input-group-prepend">
                                            <span class="input-group-text bg-dark">
                                                <i class="fa fa-list"></i>
                                            </span>
                                         </div>

                                         <input name="keywords"
                                                id="keywords"
                                                placeholder="keyword1, keyword2, keyword3"
                                                value="{{$model->seo?->keywords ?? $model->tags->pluck('name')->implode(', ')}}"
                                                class="form-control">
                                     </div>
                                </div>

                                <div class="form-group">
                                    <label for="distribution">Scope (Recomendado Global)</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-dark">
                                                <i class="fa fa-stop"></i>
                                            </span>
                                        </div>

                                        <select name="distribution"
                                                id="distribution"
                                                class="form-control">
                                            <option
                                                value="global" {{$model->seo?->distribution === 'global' ? 'selected' : ''}}>
                                                Global
                                            </option>
                                            <option
                                                value="local" {{$model->seo?->distribution === 'local' ? 'selected' : ''}}>
                                                Local
                                            </option>
                                            <option
                                                value="ui" {{$model->seo?->distribution === 'ui' ? 'selected' : ''}}>
                                                UI (Interno, puede ser bloqueado desde exterior)
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="description">Descripción</label>
                                    <textarea name="description"
                                              id="description"
                                              placeholder="Información sobre el contenido"
                                              class="form-control">{{$model->seo?->description ?? $model->excerpt}}</textarea>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


        {{-- Buscadores --}}
        <div class="col-12 col-md-6">

            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        Buscadores (Crowling)
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="col-12">

                                <div class="form-group">
                                    <label for="revisit_after">Revisitar web (Crawlers)</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-secondary">
                                                <i class="fa fa-search"></i>
                                            </span>
                                        </div>

                                        <input name="revisit_after"
                                               id="revisit_after"
                                               placeholder="7 days"
                                               value="{{$model->seo?->revisit_after ?? '7 days'}}"
                                               class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="robots">Robots (Indexado)</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-secondary">
                                                <i class="fa fa-robot"></i>
                                            </span>
                                        </div>

                                        <select name="robots"
                                                id="robots"
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

            <div class="card card-indigo">
                <div class="card-header">
                    <h3 class="card-title">
                        Redes Sociales
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">

                            <div class="col-12">

                                <div class="form-group">
                                    <label for="og_title">Título</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-indigo">
                                                <i class="fa fa-heading"></i>
                                            </span>
                                        </div>

                                        <input type="text" id="og_title"
                                               name="og_title"
                                               placeholder="{{$model->title}}"
                                               value="{{$model->seo?->og_title ?? $model->title}}"
                                               class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    {{-- TODO: Según el tipo, puede tener otras metaetiquetas definidas aquí: https://ogp.me/#types  REVISAR SI ES INTERESANTE --}}
                                    <label for="og_type">Tipo</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-indigo">
                                                <i class="fa fa-pager"></i>
                                            </span>
                                        </div>

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
        </div>


        {{-- Twitter --}}
        <div class="col-12 col-md-6">

            <div class="card card-info">
                <div class="card-header" style="background-color: #00acee;">
                    <h3 class="card-title">
                        Twitter
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="twitter_creator">Nick del creador del contenido</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" style="background-color: #00acee; color: #fff;">@</span>
                                        </div>

                                        <input type="text"
                                               name="twitter_creator"
                                               id="twitter_creator"
                                               placeholder="raupulus"
                                               value="{{$model->seo?->twitter_creator ?? $model->author?->twitter?->nick ?? 'raupulus'}}"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label for="twitter_card">Card Type</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" style="background-color: #00acee; color: #fff;">
                                                <i class="fa fa-id-card"></i>
                                            </span>
                                        </div>

                                        <select name="twitter_card" id="twitter_card" class="form-control">
                                            <option
                                                value="summary" {{($model->seo?->twitter_card === 'summary') ? 'select' : ''}}>
                                                Summary
                                            </option>
                                            <option
                                                value="summary_large_image" {{$model->seo?->twitter_card === 'summary_large_image' ? 'select' : ''}}>
                                                Summary Large Image
                                            </option>
                                            <option value="app" {{$model->seo?->twitter_card === 'app' ? 'select' : ''}}>
                                                App
                                            </option>
                                            <option
                                                value="player" {{$model->seo?->twitter_card === 'player' ? 'select' : ''}}>
                                                Player
                                            </option>
                                        </select>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
