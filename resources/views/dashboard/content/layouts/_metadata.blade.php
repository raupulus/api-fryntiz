<form action="{{route('dashboard.content.metadata.store', $model->id)}}"
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
                Metadatos
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

            {{-- Redes Sociales --}}
            <div class="card card-info">
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
                                    <label for="telegram_channel">Canal de Telegram</label>
                                    <input name="telegram_channel"
                                           id="telegram_channel"
                                           value="{{$model->metadata?->telegram_channel ?? ''}}"
                                           class="form-control">
                                </div>


                                {{--
                                <div id="box-youtube-selector">
                                    <button id="youtube-selector" class="btn btn-danger">Buscar vídeo de youtube</button>

                                    <div id="results-youtube-selector">

                                    </div>
                                </div>
                                --}}

                                <div class="form-group">
                                    <label for="youtube_channel">Canal de Youtube</label>
                                    <input name="youtube_channel"
                                           id="youtube_channel"
                                           value="{{$model->metadata?->youtube_channel ?? ''}}"
                                           class="form-control">
                                </div>


                                TODO: Crear selector de vídeos (buscador) en el canal asociado a la plataforma, en
                                caso de no tener la plataforma canal introducido dejar input para escribir.

                                <div class="form-group">
                                    <label for="youtube_video">Vídeo Principal en Youtube</label>
                                    <input name="youtube_video"
                                           id="youtube_video"
                                           value="{{$model->metadata?->youtube_video ?? ''}}"
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="youtube_video_id">Id del vídeo principal</label>
                                    <input name="youtube_video_id"
                                           id="youtube_video_id"
                                           value="{{$model->metadata?->youtube_video_id ?? ''}}"
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="mastodon">Mastodon</label>
                                    <input name="mastodon"
                                           id="mastodon"
                                           value="{{$model->metadata?->mastodon ?? ''}}"
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="twitter">Twitter</label>
                                    <input name="twitter"
                                           id="twitter"
                                           value="{{$model->metadata?->twitter ?? ''}}"
                                           class="form-control">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


        {{-- Enlaces --}}
        <div class="col-12 col-md-6">

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        Enlaces
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="col-12">

                                <div class="form-group">
                                    <label for="web">Web</label>
                                    <input name="web"
                                           id="web"
                                           value="{{$model->metadata?->web ?? ''}}"
                                           class="form-control">
                                </div>

                                TODO: Mirar plantilla de "próximo proyecto" para ver si poner muchos repositorios, adjuntar
                                archivos etc. Puede que aquí sea interesante tener más datos? o crear sección
                                similar a "content_resources" dónde subir documentos, múltiples enlaces, esquemas, estimación....

                                <div class="form-group">
                                    <label for="gitlab">Gitlab</label>
                                    <input name="gitlab"
                                           id="gitlab"
                                           value="{{$model->metadata?->gitlab ?? ''}}"
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="github">Github</label>
                                    <input name="github"
                                           id="github"
                                           value="{{$model->metadata?->github ?? ''}}"
                                           class="form-control">
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</form>
