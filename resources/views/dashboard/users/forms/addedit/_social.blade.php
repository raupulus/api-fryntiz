<div id="user_social_content_box">
    {{-- Widget con el selector de redes sociales --}}
    @widget('UserSocialSelectorWidget', [
        'socialNetworks' => $socialNetworks,
    ])

    {{-- Añado la vista del widget una vez por cada red social del usuario --}}
    @if($user_social)
        @foreach($user_social as $idx => $social)
            @widget('UserSocialSelectorWidget', [
                'socialNetworks' => $socialNetworks,
                'social_nick' => $social->personal->nick,
                'social_id' => $social->personal->social_network_id,
                'social_url' => $social->personal->url,
            ])
        @endforeach
    @endif

        {{-- Añadir otra red social --}}
    <div class="row">
        <div class="col-12">
            <span id="red_social_add" class="btn btn-warning btn-hover">
                Agregar Otra
            </span>
        </div>
    </div>
</div>
