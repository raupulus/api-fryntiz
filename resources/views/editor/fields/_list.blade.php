<ul id="{{$id}}" class="r-list">
    @foreach($items as $item)
        {!! ($style === 'unordered') ? '<li class="r-list-item">' : '<ol class="r-list-item">' !!}
           {{$item}}
        {!! ($style === 'unordered') ? '</li>' : '</ol>' !!}
    @endforeach
</ul>
