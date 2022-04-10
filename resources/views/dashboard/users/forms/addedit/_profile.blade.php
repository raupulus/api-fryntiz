<div class="row">
    {{-- Nombre --}}
    <div class="col-md-6">
        <label for="name">Nombre</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="name">
                    <i class="fa fa-user"></i>
                </span>
            </div>

            <input type="text" class="form-control"
                   aria-describedby="name"
                   name="name"
                   value="{{UserHelper::oldForm('name', $user)}}"
                   placeholder="Tu nombre" />
        </div>
    </div>

    {{-- Teléfono --}}
    <div class="col-md-6">
        <label for="phone">Teléfono</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="phone">
                    <i class="fa fa-mobile-phone"></i>
                </span>
            </div>

            <input type="text" class="form-control"
                   aria-describedby="phone"
                   name="phone"
                   value="{{UserHelper::oldForm('phone', $user_data)}}"
                   placeholder="Teléfono" />
        </div>
    </div>
</div>

<div class="row mt-5">
    {{-- Descripción --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="description">Descripción</label>
            <textarea class="form-control"
                      name="description"
                      rows="4"
                      placeholder="Descríbete aquí"
            >{{UserHelper::oldForm('description', $user_data)}}</textarea>
        </div>
    </div>
</div>

<div class="row mt-5">
    {{-- Biografía --}}
    <div class="col-md-12">
        <div class="form-group">
            <label for="biography">Biografía</label>
            <textarea class="form-control"
                      name="bio"
                      rows="6"
                      placeholder="Cuenta aquí tu trayectoría"
            >{{UserHelper::oldForm('bio', $user_data)}}</textarea>
        </div>
    </div>
</div>
