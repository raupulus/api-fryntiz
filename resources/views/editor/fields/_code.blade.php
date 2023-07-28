
<p id="{{$id}}" class="r-codeblock-container">
    <code class="r-codeblock"
        data-language="{{isset($data['language']) ? $data['language'] : 'text'}}">
        {!! preg_replace('/\\n|\\r/', '<br>', $data['code']) !!}
    </code>
</p>
