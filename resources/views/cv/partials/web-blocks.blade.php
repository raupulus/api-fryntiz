{{-- Párrafos y viñetas de una descripción (ver CurriculumDocument::blocks()). --}}
@foreach($blocks as $block)
    @if($block['type'] === 'list')
        <ul class="space-y-1">
            @foreach($block['items'] as $item)
                <li class="pl-3 -indent-3">– {{ $item }}</li>
            @endforeach
        </ul>
    @else
        <p>{{ $block['text'] }}</p>
    @endif
@endforeach
