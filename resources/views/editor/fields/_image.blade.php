<div id="{{$id}}"
     class="r-image-container {{$withBorder ? 'r-image-container-with-border' : ''}} {{$stretched ? 'r-image-container-stretched' : ''}} {{$withBackground ? 'r-image-container-withBackground' : ''}}">
    <div class="r-image-box">
        <figure class="r-image-figure">
            <img src="{{$file['url']}}"
                 class="r-image-img"
                 data-thumbnail="{{$file['url_thumbnail']}}"
                 data-url_full="{{$file['url_large']}}"
                 alt="{{$caption}}"
                 title="{{$caption}}">

            <figcaption class="r-image-caption">
                {{$caption}}
            </figcaption>
        </figure>
    </div>
</div>
