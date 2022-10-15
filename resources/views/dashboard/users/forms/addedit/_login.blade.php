<div class="row">
    {{-- Correo --}}
    <div class="col-md-6">
        <label for="email">Correo</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="email">
                    <i class="fa fa-mail-bulk"></i>
                </span>
            </div>

            <input type="email" class="form-control"
                   placeholder="Correo"
                   aria-describedby="email"
                   name="email"
                   value="{{UserHelper::oldForm('email', $user)}}"
                   pattern="^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$"
                   required />
        </div>
    </div>

    {{-- Nick --}}
    <div class="col-md-6">
        <label for="nick">Nick</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="nick">
                    <i class="fa fa-code"></i>
                </span>
            </div>

            <input type="nick" class="form-control"
                   placeholder="Nick"
                   aria-describedby="nick"
                   name="nick"
                   value="{{UserHelper::oldForm('nick', $user)}}"
                   required />
        </div>
    </div>
</div>

<div class="row mt-5">
    {{-- Contraseña --}}
    <div class="col-md-6">
        <label for="password">Password</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="password">
                    <i class="fa fa-user-secret"></i>
                </span>
            </div>

            <input type="password" class="form-control"
                   aria-describedby="password"
                   name="password"
                   value=""
                   autocomplete="off"/>
        </div>
    </div>

    {{-- Contraseña --}}
    <div class="col-md-6">
        <label for="password_repeat">Password Repeat</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="password_repeat">
                    <i class="fa fa-repeat"></i>
                </span>
            </div>

            <input type="password" class="form-control"
                   aria-describedby="password_confirmation"
                   name="password_confirmation"
                   value=""
                   autocomplete="off"/>
        </div>
    </div>
</div>

<div class="row mt-5">
    {{-- Rol --}}
    @if ($roles)
        <div class="col-md-6">
            <div class="form-group">
                <label for="role_id">Role</label>

                <select title="Selecciona el role"
                        class="form-control selectpicker"
                        name="role_id">
                    @foreach($roles as $role)
                        <option data-icon="{{ $role->icon }}"
                                data-subtext="{{ $role->display_name }}"
                                style="color: {{ $role->color }}"
                                value="{{ $role->id }}"
                                {{FormHelper::selected(
                                    $user ? $user->role_id : null,
                                    $role->id)}}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
</div>
