
{{-- FIX arreglar con componente de tailwind --}}


@if (isset($message))
    <div class="row mt-2 mb-2">
        <div class="col-12">
            {{-- Si es un array, por cada tipo de mensaje recorro sus elementos--}}
            @if (is_array($message))
                @foreach($message as $idx => $type)
                    @foreach($type as $text)
                        <div class="alert alert-{!! $idx !!} alert-dismissable">
                            <button type="button"
                                    class="close"
                                    aria-hidden="true">
                                &times;
                            </button>

                            <p>{!! $text !!}</p>
                        </div>
                    @endforeach
                @endforeach
            @else
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-dismissable alert-{{isset($alert_type) && $alert-type ? $alert : 'warning'}}">
                            <button type="button"
                                    class="close"
                                    aria-hidden="true">
                                &times;
                            </button>

                            <p>{!! $message !!}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="row mt-2 mb-2">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button"
                        class="close"
                        data-dismiss="alert">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
