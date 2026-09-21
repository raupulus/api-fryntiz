{{--
    Una fila de cualquier sección en la vista web, con la misma forma que en
    el PDF (cv/partials/pdf-entry): título y fecha, entidad y ubicación debajo,
    y la descripción en párrafos y viñetas.
--}}
@php
    $compact = $compact ?? false;
    $display = fn (?string $url): string => rtrim((string) preg_replace('#^https?://(www\.)?#', '', (string) $url), '/');
    $links = collect([$row['url'], $row['repository'], $row['info_url']])->filter()->unique()->values();
@endphp
<div class="pl-0 sm:pl-2">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
        <h3 class="{{ $compact ? 'text-base' : 'text-lg' }} text-on-surface leading-snug">{{ $row['title'] }}</h3>
        @if($row['period'])
            <span class="text-xs text-on-surface-variant whitespace-nowrap">{{ $row['period'] }}</span>
        @endif
    </div>

    @if($row['subtitle'] || $row['note'])
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5 mt-0.5">
            <span class="text-sm text-cv-accent">{{ $row['subtitle'] }}</span>
            @if($row['note'])
                <span class="text-xs text-on-surface-variant whitespace-nowrap">{{ $row['note'] }}</span>
            @endif
        </div>
    @endif

    @if($row['position'])
        <p class="text-xs italic text-on-surface-variant mt-0.5">{{ $row['position'] }}</p>
    @endif

    @if($row['blocks'])
        <div class="mt-2 space-y-1 text-sm text-on-surface">
            @include('cv.partials.web-blocks', ['blocks' => $row['blocks']])
        </div>
    @endif

    @if($row['meta'])
        <p class="text-xs text-on-surface-variant mt-1">
            @if($row['credential_url'])
                <a href="{{ $row['credential_url'] }}" target="_blank" rel="noopener" class="text-cv-accent hover:underline">{{ implode(' · ', $row['meta']) }}</a>
            @else
                {{ implode(' · ', $row['meta']) }}
            @endif
        </p>
    @endif

    @if($links->isNotEmpty())
        <p class="text-xs mt-1.5 flex flex-wrap gap-x-3 gap-y-1">
            @foreach($links as $link)
                <a href="{{ $link }}" target="_blank" rel="noopener" class="text-cv-accent hover:underline break-all">{{ $display($link) }}</a>
            @endforeach
        </p>
    @endif
</div>
