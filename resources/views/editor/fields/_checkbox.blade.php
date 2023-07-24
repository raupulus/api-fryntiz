<div id="{{$id}}">
    @foreach($items as $item)
        <input type="checkbox" {{$item['checked'] ? 'checked' : ''}} > {{$item['text']}}
    @endforeach
</div>
