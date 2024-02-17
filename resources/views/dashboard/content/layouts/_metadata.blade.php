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




        {{-- Youtube --}}
        <div class="col-12 col-md-6">
            <div class="card card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        Youtube
                    </h3>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="col-12">

                                <div class="form-group text-center">

                                    <div>
                                        <iframe id="iframe-preview-youtube-video"
                                                width="560" height="315"
                                                style="width: 100%; max-width: 560px; margin: auto;"
                                                src="{{$model->metadata?->youtubeVideoIframeUrl ?? ''}}"
                                                title="YouTube video player"
                                                class="{{$model->metadata?->youtubeVideoIframeUrl ?? 'hidden'}}"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                allowfullscreen></iframe>
                                    </div>



                                    <span id="btn-youtube-video-search"
                                          class="btn btn-danger mt-3">Buscar Vídeo</span>

                                    {{-- Contenedor para el modal de buscar vídeos --}}
                                    <div id="modal-youtube-video-search"
                                         class="modal-youtube-video-search-hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="youtube_video">Url al Vídeo en Youtube</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-red">
                                                <i class="fa fa-play"></i>
                                            </span>
                                        </div>

                                        <input name="youtube_video"
                                               id="youtube_video"
                                               placeholder="https://www.youtube.com/watch?v=o-CQA9URp_I"
                                               value="{{$model->metadata?->youtube_video ?? ''}}"
                                               class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="youtube_video_id">Id del vídeo en Youtube</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-red">
                                                <i class="fa fa-fingerprint"></i>
                                            </span>
                                        </div>

                                        <input name="youtube_video_id"
                                               id="youtube_video_id"
                                               placeholder="o-CQA9URp_I"
                                               value="{{$model->metadata?->youtube_video_id ?? ''}}"
                                               class="form-control">
                                    </div>
                                </div>

                                <hr />

                                <div class="form-group">
                                    <label for="youtube_channel">URL al Canal de Youtube</label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-red">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     style="width: 1rem;"
                                                     fill="#fff"
                                                     viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z"/></svg>
                                            </span>
                                        </div>

                                        <input name="youtube_channel"
                                               id="youtube_channel"
                                               placeholder="https://www.youtube.com/@raupulus"
                                               value="{{$model->metadata?->youtube_channel ?? ''}}"
                                               class="form-control">
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>



        <div class="col-md-6">
            <div class="col-12">

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

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                            <span class="input-group-text bg-teal">
                                                <i class="fa fa-paper-plane"></i>
                                            </span>
                                            </div>
                                            <input name="telegram_channel"
                                                   id="telegram_channel"
                                                   placeholder="https://t.me/raupulus_diffusion"
                                                   value="{{$model->metadata?->telegram_channel ?? ''}}"
                                                   class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="mastodon">Mastodon (@user)</label>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-purple">@</span>
                                            </div>

                                            <input name="mastodon"
                                                   id="mastodon"
                                                   placeholder="username"
                                                   value="{{$model->metadata?->mastodon ?? ''}}"
                                                   class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="twitter">Twitter (@user)</label>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" style="background-color: #00acee; color: #fff;">@</span>
                                            </div>
                                            <input name="twitter"
                                                   id="twitter"
                                                   placeholder="username"
                                                   value="{{$model->metadata?->twitter ?? ''}}"
                                                   class="form-control">
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>





            {{-- Enlaces --}}
            <div class="col-12">

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

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                            <span class="input-group-text bg-black">
                                                <i class="fa fa-globe"></i>
                                            </span>
                                            </div>

                                            <input name="web"
                                                   id="web"
                                                   placeholder="https://raupulus.dev"
                                                   value="{{old('web', $model->metadata?->web)}}"
                                                   class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="gitlab">Gitlab</label>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                            <span class="input-group-text bg-orange">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     fill="#fff"
                                                     style="width: 1rem;"
                                                     viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M503.5 204.6L502.8 202.8L433.1 21C431.7 17.5 429.2 14.4 425.9 12.4C423.5 10.8 420.8 9.9 417.9 9.6C415 9.3 412.2 9.7 409.5 10.7C406.8 11.7 404.4 13.3 402.4 15.5C400.5 17.6 399.1 20.1 398.3 22.9L351.3 166.9H160.8L113.7 22.9C112.9 20.1 111.5 17.6 109.6 15.5C107.6 13.4 105.2 11.7 102.5 10.7C99.9 9.7 97 9.3 94.1 9.6C91.3 9.9 88.5 10.8 86.1 12.4C82.8 14.4 80.3 17.5 78.9 21L9.3 202.8L8.5 204.6C-1.5 230.8-2.7 259.6 5 286.6C12.8 313.5 29.1 337.3 51.5 354.2L51.7 354.4L52.3 354.8L158.3 434.3L210.9 474L242.9 498.2C246.6 500.1 251.2 502.5 255.9 502.5C260.6 502.5 265.2 500.1 268.9 498.2L300.9 474L353.5 434.3L460.2 354.4L460.5 354.1C482.9 337.2 499.2 313.5 506.1 286.6C514.7 259.6 513.5 230.8 503.5 204.6z"/></svg>
                                            </span>
                                            </div>

                                            <input name="gitlab"
                                                   id="gitlab"
                                                   placeholder="https://gitlab.com/raupulus"
                                                   value="{{old('gitlab', $model->metadata?->gitlab)}}"
                                                   class="form-control">
                                        </div>

                                    </div>

                                    <div class="form-group">
                                        <label for="github">Github</label>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                            <span class="input-group-text bg-black">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="#fff"
                                                     style="width: 1rem;"
                                                     viewBox="0 0 496 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3 .3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5 .3-6.2 2.3zm44.2-1.7c-2.9 .7-4.9 2.6-4.6 4.9 .3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3 .7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3 .3 2.9 2.3 3.9 1.6 1 3.6 .7 4.3-.7 .7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3 .7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3 .7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z"/></svg>
                                            </span>
                                            </div>

                                            <input name="github"
                                                   id="github"
                                                   placeholder="https://github.com/raupulus"
                                                   value="{{old('github', $model->metadata?->github)}}"
                                                   class="form-control">
                                        </div>

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
