<{{ ($style === 'ordered') ? 'ol' : 'ul'}}
    id="{{$id}}" class="r-list">
    @foreach($items as $item)
        <li class="r-list-item">
            {!! preg_replace('/\\n|\\r|\<br\>/', '', $item) !!}
        </li>
    @endforeach
</{{ ($style === 'ordered') ? 'ol' : 'ul'}} >
