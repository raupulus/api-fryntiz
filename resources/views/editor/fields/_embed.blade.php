<div id="{{$id}}" class="r-embed-container">
    <div class="r-embed-box">
        @if($caption)
            <div class="r-embed-title">
                {!! $caption !!}
            </div>
        @endif

        <div class="r-embed-box-iframe">
            <iframe class="r-embed-iframe"
                    data-width="{{$width}}"
                    data-height="{{$height}}"
                    src="{{$embed}}" frameborder="0"
                    allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
    </div>
</div>
