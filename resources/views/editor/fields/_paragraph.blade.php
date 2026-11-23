{{--
    Sin variante, `textVariant` llega como null (datos de la v1) o como ""
    (Editor.js 2.31): en los dos casos no hay clase de variante.
--}}
@if($tunes && count($tunes) && ($tunes['textVariant'] ?? null) === 'details')
    <div id="{{$id}}" class="r-paragraph r-paragraph-details">
        <details>
            <summary>Detalles</summary>
            {!! $text !!}
        </details>
    </div>
@else
    <p id="{{$id}}"
       class="r-paragraph {{filled($tunes['textVariant'] ?? null) ? 'r-paragraph-' . $tunes['textVariant'] : ''}}">

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
            @else
                {!! $text !!}
            @endif
        @else
            {!! $text !!}
        @endif
    </p>
@endif


