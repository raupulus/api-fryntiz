<p id="{{$id}}"
   class="r-paragraph {{count($tunes) && isset($tunes['textVariant']) ? 'r-paragraph-' . $tunes['textVariant'] : ''}}">

    @if($tunes && count($tunes) && isset($tunes['textVariant']))
        @if($tunes['textVariant'] == 'citation')
            <cite>
                {!! $text !!}
            </cite>
        @elseif($tunes['textVariant'] == 'call-out')
            <span class="r-call-out">
                <span class="r-call-out-left"></span>
                <span class="r-call-out-right">{!! $text !!}</span>
            </span>
        @elseif($tunes['textVariant'] == 'details') {{-- Texto pequño --}}
            <details>
                <summary>Detalles</summary>
                {!! $text !!}
            </details>
        @else
            {!! $text !!}
        @endif
    @else
        {!! $text !!}
    @endif
</p>


