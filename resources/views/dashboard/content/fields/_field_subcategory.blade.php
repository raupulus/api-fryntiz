<div class="custom-control custom-checkbox mr-2 mb-2 d-inline-block">

    @if($content && $content->id)
        @php($checked = in_array($subcategory->id, $content->subcategories->pluck('id')->toArray()))
        @php($main_id = \App\Models\Content\ContentCategory::where('content_id', $content->id)->where('is_main', true)->first()?->platformCategory?->category_id)
    @else
        @php($checked = false)
        @php($main_id = null)
    @endif

    <input class="custom-control-input custom-control-input-success"
           type="checkbox"
           id="subcategory-{{$subcategory->id}}"
           value="{{$subcategory->id}}"
           name="subcategories[]"
        {{$checked ? 'checked' : ''}}>

    <label for="subcategory-{{$subcategory->id}}"
           class="custom-control-label">

        {{-- Selector para la subcategoría principal --}}
        <input type="radio" name="subcategory_main" value="{{$subcategory->id}}"
               {{$subcategory->id == $main_id ? 'checked' : ''}}
               onclick="document.getElementById('subcategory-{{$subcategory->id}}').checked = true; document.getElementById('subcategory-{{$subcategory->id}}').setAttribute('checked', 'checked')">


        {{$subcategory->name}}
    </label>
</div>
