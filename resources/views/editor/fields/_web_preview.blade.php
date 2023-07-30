<div id="{{$id}}" class="r-web-preview-container">
    <div class="r-web-preview-box">
        <a class="r-web-preview-link"
           target="_blank"
           rel="nofollow noindex noreferrer"
           href="{{$link}}">

            @if($image)
                <div class="r-web-preview-image" style="background-image: url('{{$image}}');"></div>
            @endif

            <div class="r-web-preview-title">
                {!! $title !!}
            </div>

            @if($description)
                <div class="r-web-preview-description">
                    {!! $description !!}
                </div>
            @endif

            <span class="r-web-preview-anchor">{{preg_replace('/https*:\/\//', '', $link)}}</span>
        </a>
    </div>
</div>
