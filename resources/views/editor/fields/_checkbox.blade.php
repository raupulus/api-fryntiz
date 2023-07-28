<div id="{{$id}}" class="r-checkbox-container">
    @foreach($items as $item)
        <input type="checkbox"
               class="r-checkbox"
               {{$item['checked'] ? 'checked' : ''}} > {{$item['text']}}
    @endforeach
</div>
