<div class="col-12 col-md-3 user-profil-part pull-left">
    {{-- User Card --}}
    <div class="row ">
        @if(RoleHelper::canUserEdit($user->id))
            <v-cropper-image style="margin: -90px auto 0 auto;"
                    width="500"
                    preview_width="150"
                    api_id="{{ $user->id }}"
                    name="{{ $user->name }}"
                    api_url="{{ route('panel.ajax.user.avatar.upload') }}"
                    csrf_token="{{csrf_token()}}"
                    image_path="{{$user->urlAvatar}}"></v-cropper-image>
        @else
            <div class="col-12 user-image text-center">
                <img class="rounded-circle"
                     src="{{ $user->urlAvatar }}"
                     title="Imagen de {{ $user->name }}"
                     alt="Imagen de {{ $user->name }}" />
            </div>
        @endif

        <div class="col-12 user-detail-section1 text-center">
            <button class="btn btn-warning btn-block follow">
                <i class="fas fa-file-download"></i>
                Descargar Curriculum
            </button>

            <button class="btn btn-danger btn-block">
                <i class="fas fa-user-shield"></i>
                Reportar Usuario
            </button>

            <button class="btn btn-dark btn-block">
                <i class="fas fa-envelope-open"></i>
                Enviar Mensaje
            </button>

            <a href="{{route('panel.users.add', ['user_id' => $user->id])}}"
               class="btn btn-primary btn-block">
                <i class="fas fa-user-edit"></i>
                Editar Perfil
            </a>

            <a href="{{route('panel.users.add')}}"
               class="btn btn-danger btn-block">
                <i class="fas fa-trash"></i>
                Eliminar Cuenta
            </a>
        </div>

        {{-- Seguidores --}}
        <div class="row user-detail-row">
            <div class="col-12 user-detail-section2 pull-left">
                <div class="border"></div>
                <p>FOLLOWER</p>
                <span>320</span>
            </div>
        </div>
    </div>
</div>
