<div id="{{$id}}"
     class="r-image-container {{$withBorder ? 'r-image-container-with-border' : ''}} {{$stretched ? 'r-image-container-stretched' : ''}} {{$withBackground ? 'r-image-container-withBackground' : ''}}">
    <div class="r-image-box">
        <figure class="r-image-figure">
            <img src="{{$file['url_thumbnail']}}"
                 class="r-image-img"
                 data-url_medium="{{$file['url']}}"
                 data-url_full="{{$file['url_large']}}"
                 alt="{{$caption}}"
                 title="{{$caption}}">

            @if($caption)
                <figcaption class="r-image-caption">
                    {{$caption}}
                </figcaption>
            @endif
        </figure>
    </div>
</div>
