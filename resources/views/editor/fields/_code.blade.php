<div id="{{$id}}" class="r-codeblock-container" data-lan>
    <div class="r-codeblock-header">
        <div class="r-codeblock-header-left">
            &lt;Code&gt;
        </div>

        <div class="r-codeblock-header-center">

        </div>

        <div class="r-codeblock-header-right">
            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M448 384H256c-35.3 0-64-28.7-64-64V64c0-35.3 28.7-64 64-64H396.1c12.7 0 24.9 5.1 33.9 14.1l67.9 67.9c9 9 14.1 21.2 14.1 33.9V320c0 35.3-28.7 64-64 64zM64 128h96v48H64c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H256c8.8 0 16-7.2 16-16V416h48v32c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V192c0-35.3 28.7-64 64-64z"/></svg>
        </div>
    </div>

    {{-- Contenido --}}
    <div class="r-codeblock-content">
        <div class="r-codeblock-numbers">

            @foreach(range(1, $nLines + 1) as $n)
                {{$n}}

                @if ($n < ($nLines + 1))
                    <br>
                @endif
            @endforeach
        </div>

        <code class="r-codeblock"
              data-language="{{isset($data['language']) ? $data['language'] : 'text'}}">
            {!! $code !!}
        </code>
    </div>

    {{-- Footer --}}
    <div class="r-codeblock-footer">
        {{isset($data['language']) ? $data['language'] : 'text'}}
    </div>
</div>

{{--
<p id="{{$id}}" class="r-codeblock-container">
    <code class="r-codeblock"
        data-language="{{isset($data['language']) ? $data['language'] : 'text'}}">
        {!! preg_replace('/\\n|\\r/', '<br>', $data['code']) !!}
    </code>
</p>
--}}
