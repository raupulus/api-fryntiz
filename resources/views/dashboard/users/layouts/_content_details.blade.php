<div role="tabpanel"
     class="tab-pane fade"
     id="details">

    <div class="row">
        <div class="col-md-6">
            <label>Profesión</label>
        </div>
        <div class="col-md-6">
            <p>
                {{ $user->details->profession }}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <label>Web</label>
        </div>
        <div class="col-md-6">
            <a href="{{ $user->details->web }}"
               title="Web de {{ $user->name }}">
                {{ $user->details->web }}
            </a>
        </div>
    </div>
</div>
