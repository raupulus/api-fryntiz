<div id="{{$id}}"
     class="r-attaches-container"
     data-content_id="{{$file['content_id']}}"
     data-content_file_id="{{$file['content_file_id']}}"
     data-file_id="{{$file['file_id']}}">

    <div class="r-attaches-box">
        @if ($file['url_thumbnail'])
            <div class="r-attaches-img">
                <img src="{{$file['file_type_image']}}" alt="{{$title}}">
            </div>
        @else ($file['file_type_image'])
            <div class="r-attaches-img">
                <img src="{{$file['file_type_image']}}" alt="{{$title}}">
            </div>
        @endif

        <div class="r-attaches-info">
            <div>{{$title}} <span class="r-attaches-info-originalname"> ({{$file['name']}})</span></div>

            @if ($size)
                <div class="r-attaches-info-size">
                    {{$size}}
                </div>
            @endif
        </div>

        <div class="r-attaches-download" data-url_download="{{$file['url']}}">
            <a href="{{$file['url']}}" download target="_self" class="r-attaches-download-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                          d="M7 10L11.8586 14.8586C11.9367 14.9367 12.0633 14.9367 12.1414 14.8586L17 10"></path>
                </svg>
            </a>
        </div>
    </div>
</div>
