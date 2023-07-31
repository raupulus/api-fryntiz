<div id="{{$id}}" class="r-warning-container">
    <div class="r-warning-wrapper">
        <div class="r-warning-title">
            {!! preg_replace('/\\n|\\r/', '<br>', $title) !!}
            {{--
            <span class="r-warning-btn-close"><svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg></span>
            --}}
        </div>

        <div class="r-warning-body">
            {!! preg_replace('/\\n|\\r/', '<br>', $message) !!}
        </div>
    </div>
</div>
