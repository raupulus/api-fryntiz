@php
if (isset($user_id) && $user_id) {
    $routeAction = route('panel.users.edit', ['user_id' => $user_id]);
} else {
    $routeAction = route('panel.users.edit');
}
@endphp

<form id="form-add-user"
      action="{{$routeAction}}"
      method="post">
    @csrf

    {{-- Pestañas con los pasos --}}
    <ul id="user-form-create-tabs" class="nav nav-pills mb-3" role="tablist">
        <li class="nav-item">
            <a id="user-form-create-tab-profile"
               class="nav-link active"
               data-toggle="pill"
               href="#user-form-create-profile"
               role="tab"
               aria-controls="user-form-create-profile"
               aria-selected="true">
                Perfil
            </a>
        </li>

        <li class="nav-item">
            <a id="user-form-create-tab-details"
               class="nav-link"
               data-toggle="pill"
               href="#user-form-create-details"
               role="tab"
               aria-controls="user-form-create-details"
               aria-selected="false">
                Detalles
            </a>
        </li>

        <li class="nav-item">
            <a id="user-form-create-tab-social"
               class="nav-link"
               data-toggle="pill"
               href="#user-form-create-social"
               role="tab"
               aria-controls="user-form-create-social"
               aria-selected="false">
                Redes Sociales
            </a>
        </li>

        <li class="nav-item">
            <a id="user-form-create-tab-login"
               class="nav-link"
               data-toggle="pill"
               href="#user-form-create-login"
               role="tab"
               aria-controls="user-form-create-login"
               aria-selected="false">
                Login
            </a>
        </li>
    </ul>

    {{-- Contenido accesible desde las pestañas --}}
    <div class="tab-content" id="pills-tabContent">
        <div id="user-form-create-profile"
             class="tab-pane fade show active"
             role="tabpanel"
             aria-labelledby="user-form-create-tab-profile">
            @include('panel.users.forms.addedit._profile')
        </div>

        <div id="user-form-create-details"
             class="tab-pane fade"
             role="tabpanel"
             aria-labelledby="user-form-create-tab-details">
            @include('panel.users.forms.addedit._details')
        </div>

        <div id="user-form-create-social"
             class="tab-pane fade"
             role="tabpanel"
             aria-labelledby="user-form-create-tab-social">
            @include('panel.users.forms.addedit._social')
        </div>

        <div id="user-form-create-login"
             class="tab-pane fade"
             role="tabpanel"
             aria-labelledby="user-form-create-tab-login">
            @include('panel.users.forms.addedit._login')
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div id="box-progress-bar" class="row mt-4 mb-4">
        <div class="col-12">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" aria-valuenow="20" aria-valuemin="0"
                     aria-valuemax="100" style="width: 20%;"></div>
            </div>
        </div>
    </div>

    {{-- Botones de Acción bajo el formulario --}}
    <div class="row text-center">
        <div class="col-12">
            <span id="user-add-step-left" class="btn btn-info ml-4 mr-4 btn-hover">
                <i class="fa fa-arrow-left"></i>
            </span>

            <span id="user-add-step-right" class="btn btn-info ml-4 mr-4 btn-hover">
                <i class="fa fa-arrow-right"></i>
            </span>
        </div>
    </div>
</form>
