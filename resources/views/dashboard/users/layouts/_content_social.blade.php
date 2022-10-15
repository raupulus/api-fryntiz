<div role="tabpanel"
     class="tab-pane fade"
     id="social">

    <div class="row mt-4 mb-4 text-center font-weight-bold">
        <div class="col-md-3">
            Red Social
        </div>

        <div class="col-md-3">
            User
        </div>

        <div class="col-md-6">
            Link
        </div>
    </div>

    @foreach($user->social as $social)
        <div class="row text-center">
            <div class="col-md-3 text-left">
                <i class="{{ $social->icon }} font-weight-bold"
                   style="color: {{ $social->color }}" ></i>
                <label>
                    {{ $social->name }}
                </label>
            </div>

            <div class="col-md-3">
                <a href="{{ $social->personal->url }}"
                   title="{{ $social->name }} - {{ $social->personal->nick }}">
                    {{ $social->personal->nick }}
                </a>
            </div>

            <div class="col-md-6">
                <a href="{{ $social->personal->url }}"
                   title="{{ $social->name }} - {{ $social->personal->nick }}">
                    {{ $social->personal->url }}
                </a>
            </div>
        </div>
    @endforeach
</div>
