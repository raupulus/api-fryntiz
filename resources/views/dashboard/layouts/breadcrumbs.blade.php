@if (isset($breadcrumbs))
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb cyan lighten-2">
            <li class="breadcrumb-item">
                <i class="fa fa-home mr-1 white-text" aria-hidden="true"></i>
                <a href="{{route('panel.index')}}">Inicio</a>
            </li>

            @php($n_breadcrumbs = count($breadcrumbs) - 1)

            @foreach($breadcrumbs as $idx => $bread)
                @php($icon = '')

                @if (array_key_exists('icon', $bread))
                    @php($icon = '<i class="' . $bread['icon'] . '"></i>')
                @endif

                @if($n_breadcrumbs === $idx)
                    <li class="breadcrumb-item active">
                        {!! $icon !!}
                        {{$bread['title']}}
                    </li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{$bread['url']}}">
                            {!! $icon !!}
                            {{$bread['title']}}
                        </a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
