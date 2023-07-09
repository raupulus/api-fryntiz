{{--
Debe recibirse "$page" como instancia del modelo "ContentPage"
--}}


<div class="row">
    <div class="col-12">

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="title">Título</label>
                    <input type="text" class="page-form-input-title form-control"
                           name="title"
                           value="{{$page->title}}"
                           placeholder="Título de la página">
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" class="page-form-input-slug form-control"
                           name="slug"
                            value="{{$page->slug}}"
                           placeholder="Slug de la página">
                </div>
            </div>



            {{-- Selector Cropper de imágenes --}}
            <div class="col-12">
                <div style="width: 100%; max-width: 400px; margin: auto;">
                    <v-image-cropper
                        url="{{$page->urlStoreImage}}"
                        default-image="{{$page->urlImage}}"
                        name="image-main"
                        csrf="{{csrf_token()}}"
                        :aspect-ratios-restriction="[16,9]"
                    ></v-image-cropper>
                </div>
            </div>


            {{--
            <div class="col-12">
                <div class="form-group">
                    <label for="image">Imagen</label>
                    <input type="file"
                           class="form-control"
                           name="image"
                           alt="Imagen para la página">


                </div>
            </div>
            --}}

        </div>

    </div>
</div>
