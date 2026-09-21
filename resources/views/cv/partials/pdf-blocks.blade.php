{{-- Párrafos y viñetas de una descripción (ver CurriculumDocument::blocks()). --}}
@foreach ($blocks as $block)
    @if ($block['type'] === 'list')
        @foreach ($block['items'] as $item)
            <p class="bullet">-&nbsp;{{ $item }}</p>
        @endforeach
    @else
        <p>{{ $block['text'] }}</p>
    @endif
@endforeach
