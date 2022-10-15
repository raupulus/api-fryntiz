<div class="row">
    {{-- Profesión --}}
    <div class="col-md-6">
        <label for="profession">Profesión</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="profession">
                    <i class="fa fa-graduation-cap"></i>
                </span>
            </div>

            <input type="text" class="form-control"
                   aria-describedby="profession"
                   name="profesion"
                   value="{{UserHelper::oldForm('profession', $user_detail)}}"
                   placeholder="Profesión" />
        </div>
    </div>

    {{-- Web --}}
    <div class="col-md-6">
        <label for="web">Web</label>

        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text" id="web">
                    <i class="fa fa-globe-europe"></i>
                </span>
            </div>

            <input type="text" class="form-control"
                   aria-describedby="web"
                   name="web"
                   value="{{UserHelper::oldForm('web', $user_detail)}}"
                   placeholder="{{config('app.url')}}" />
        </div>
    </div>
</div>
