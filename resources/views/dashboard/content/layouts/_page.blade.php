{{--
Debe recibirse "$page" como instancia del modelo "ContentPage"
--}}

<div class="row ml-1">

    <div class="col-12">
        <div style="display: flex;flex-direction: row;flex-wrap: wrap;width: 100%;">
            {{-- Selector Cropper de imágenes --}}
            <div style="height: 100px; box-sizing: border-box;" class="mt-2">
                <v-image-cropper
                    url="{{$page->urlStoreImage}}"
                    default-image="{{$page->urlImage}}"
                    csrf="{{csrf_token()}}"
                    :aspect-ratios-restriction="[16,9]"
                ></v-image-cropper>
            </div>

            <div style="flex: 1; padding-left: 1rem; padding-right: 1rem;">
                <div class="form-group mt-2">

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-heading"></i>
                            </span>
                        </div>

                        <input type="text"
                               class="form-control"
                               name="title"
                               data-page_slug_provider="title"
                               value="{{$page->title}}"
                               minlength="5"
                               maxlength="255"
                               required
                               placeholder="Título">
                    </div>

                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fa fa-link"></i>
                            </span>
                        </div>

                        <input type="text"
                               class="page-slug form-control"
                               name="slug"
                               data-page_id="{{$page->id}}"
                               data-page_sluggable="title"
                               required
                               value="{{$page->slug}}"
                               minlength="3"
                               maxlength="255"
                               placeholder="slug-para-la-pagina">
                    </div>
                </div>

                {{--
                <div class="form-group">
                    <label for="title">Título</label>

                    <input type="text"
                           class="page-form-input-title form-control"
                           name="title"
                           value="{{$page->title}}"
                           placeholder="Título de la página">
                </div>

                <div class="form-group">
                    <label for="slug">Slug</label>

                    <input type="text"
                           class="page-form-input-slug form-control"
                           name="slug"
                           value="{{$page->slug}}"
                           placeholder="Slug de la página">
                </div>
                --}}

            </div>
        </div>
    </div>
</div>
