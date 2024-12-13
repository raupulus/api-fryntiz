<div class="custom-control custom-checkbox mr-2 mb-2 d-inline-block">

    @if($content && $content->id)
        @php($checked = in_array($subcategory->id, $content->subcategories->pluck('id')->toArray()))
    @else
        @php($checked = false)
    @endif



    <input class="custom-control-input custom-control-input-success"
           type="checkbox"
           id="subcategory-{{$subcategory->id}}"
           value="{{$subcategory->id}}"
           name="subcategories[]"
           {{$checked ? 'checked' : ''}}>
    <label for="subcategory-{{$subcategory->id}}"
           class="custom-control-label">
        {{$subcategory->name}}
    </label>
</div>
