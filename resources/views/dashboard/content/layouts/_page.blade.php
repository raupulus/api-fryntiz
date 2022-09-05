<div class="row">
    <div class="col-12">
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="title">Título</label>
                    <input type="text" class="form-control"
                           id="title" name="title"
                           placeholder="Título de la página">
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" class="form-control"
                           id="slug" name="slug"
                           placeholder="Slug de la página">
                </div>
            </div>

            <div class="col-12">
                <div class="form-group">
                    <label for="image">Imagen</label>
                    <input type="file" class="form-control"
                           id="image" name="image"
                           alt="Imagen para la página">
                </div>
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-12 text-center">
        <h3>
            <div class="btn-group" role="group"
                 aria-label="Basic example"
                 style="">
                <button type="button"
                        class="btn btn-secondary disabled btn-success"
                        style="opacity: 0.4; text-align: center;">
                    Quilljs
                </button>

                <button type="button"
                        class="btn btn-secondary btn-info">
                    Summernote
                </button>

                <button type="button"
                        class="btn btn-secondary btn-info">
                    GrapesJS
                </button>
            </div>
            <br></h3>
    </div>
</div>

@include('dashboard.content.layouts._editor_quill')
@include('dashboard.content.layouts._editor_summernote')
@include('dashboard.content.layouts._editor_grapesjs')
