<div class="row">
    <div class="col-12 col-md-3 col-xl-2 mt-3">

        <div class="nav flex-column nav-pills" id="v-pills-tab"
             role="tablist" aria-orientation="vertical">
            <a class="nav-link active" id="v-pills-home-tab"
               data-toggle="pill" href="#v-pills-home" role="tab"
               aria-controls="v-pills-home"
               aria-selected="true">Página 1</a>
            <a class="nav-link" id="v-pills-profile-tab"
               data-toggle="pill" href="#v-pills-profile" role="tab"
               aria-controls="v-pills-profile"
               aria-selected="false">Página 2</a>
            <a class="nav-link" id="v-pills-messages-tab"
               data-toggle="pill" href="#v-pills-messages"
               role="tab" aria-controls="v-pills-messages"
               aria-selected="false">Página 3</a>
            <a class="nav-link" id="v-pills-settings-tab"
               data-toggle="pill" href="#v-pills-settings"
               role="tab" aria-controls="v-pills-settings"
               aria-selected="false">Página 4</a>

            <a class="btn btn-dark">
                +
            </a>
        </div>
    </div>

    <div class="col-12 col-md-9 col-xl-10 mt-3">

        <div class="tab-content" id="v-pills-tabContent">
            <div class="tab-pane fade show active"
                 id="v-pills-home" role="tabpanel"
                 aria-labelledby="v-pills-home-tab">
                <div class="row">
                    <div class="col-12">
                        <h3>Página 1</h3>
                    </div>

                    <div class="col-12 text-right">
                        <button class="btn btn-success">
                            Guardar Página
                        </button>
                    </div>
                </div>

                @include('dashboard.content.layouts._page')

                <p>
                    111111111111111111
                </p>
            </div>

            <div class="tab-pane fade" id="v-pills-profile"
                 role="tabpanel"
                 aria-labelledby="v-pills-profile-tab">
                <div class="row">
                    <div class="col-12">
                        <h3>Página 2</h3>
                    </div>

                    <div class="col-12 text-right">
                        <button class="btn btn-success">
                            Guardar Página
                        </button>
                    </div>
                </div>

                @include('dashboard.content.layouts._page')

                2222222222222222222
            </div>
            <div class="tab-pane fade" id="v-pills-messages"
                 role="tabpanel"
                 aria-labelledby="v-pills-messages-tab">
                <div class="row">
                    <div class="col-12">
                        <h3>Página 3</h3>
                    </div>

                    <div class="col-12 text-right">
                        <button class="btn btn-success">
                            Guardar Página
                        </button>
                    </div>
                </div>

                @include('dashboard.content.layouts._page')

                3333333333333333333
            </div>
            <div class="tab-pane fade" id="v-pills-settings"
                 role="tabpanel"
                 aria-labelledby="v-pills-settings-tab">
                <div class="row">
                    <div class="col-12">
                        <h3>Página 4</h3>
                    </div>

                    <div class="col-12 text-right">
                        <button class="btn btn-success">
                            Guardar Página
                        </button>
                    </div>
                </div>

                @include('dashboard.content.layouts._page')

                4444444444444444444
            </div>


        </div>
    </div>
</div>


