<p id="{{$id}}">
    @if($tunes && count($tunes) && isset($tunes['textVariant']))
        @if($tunes['textVariant'] == 'citation')
            <cite>
                {{$text}}
            </cite>
        @elseif($tunes['textVariant'] == 'call-out')

            <span class="call-out">
                {{$text}}
            </span>

        @elseif($tunes['textVariant'] == 'details') {{-- Texto pequño --}}
            <details>
                <summary>{{$text}}</summary>
            </details>
        @else
            {{$text}}
        @endif
    @else
        {{$text}}
    @endif
</p>


