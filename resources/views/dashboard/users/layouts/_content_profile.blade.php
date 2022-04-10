<div role="tabpanel"
     class="tab-pane fade show active"
     id="profile">

    @if(RoleHelper::isAdmin())
        <div class="row">
            <div class="col-md-2">
                <label>
                    ID
                </label>
            </div>

            <div class="col-md-6">
                <p>
                    {{ $user->id }}
                </p>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-md-2">
            <label>
                Teléfono
            </label>
        </div>

        <div class="col-md-6">
            <p>
                {{ $user->data->phone }}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2">
            <label>
                Descripción
            </label>
        </div>

        <div class="col-md-6">
            <p>
                {{ $user->data->description }}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2">
            <label>
                Biografía
            </label>
        </div>

        <div class="col-md-6">
            <p>
                {{ $user->data->bio }}
            </p>
        </div>
    </div>

</div>
