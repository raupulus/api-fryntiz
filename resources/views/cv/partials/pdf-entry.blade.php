{{--
    Una fila de cualquier sección en el PDF, con la forma del CV de 2024:
    título y fecha en la primera línea, entidad (en azul) y ubicación debajo,
    y la descripción en viñetas.
--}}
@php
    $compact = $compact ?? false;
    $display = fn (?string $url): string => rtrim((string) preg_replace('#^https?://(www\.)?#', '', (string) $url), '/');
    $links = collect([$row['url'], $row['repository'], $row['info_url']])->filter()->unique()->values();
@endphp
<div class="entry{{ $row['keep_together'] ? ' keep' : '' }}">
    {{-- Dos tablas y no una: así la ubicación no hereda el ancho de la columna de fechas. --}}
    <table class="entry-head">
        <tr>
            <td class="entry-title{{ $compact ? ' is-compact' : '' }}">{{ $row['title'] }}</td>
            <td class="entry-date">{{ $row['period'] }}</td>
        </tr>
    </table>
    @if ($row['subtitle'] || $row['note'])
        <table class="entry-head entry-sub">
            <tr>
                <td class="entry-subtitle">{{ $row['subtitle'] }}</td>
                <td class="entry-note">{{ $row['note'] }}</td>
            </tr>
        </table>
    @endif

    @if ($row['position'])
        <div class="entry-position">{{ $row['position'] }}</div>
    @endif

    @if ($row['blocks'])
        <div class="entry-body">
            @include('cv.partials.pdf-blocks', ['blocks' => $row['blocks']])
        </div>
    @endif

    @if ($row['meta'])
        <div class="meta">
            @if ($row['credential_url'])
                <a href="{{ $row['credential_url'] }}">{{ implode(' · ', $row['meta']) }}</a>
            @else
                {{ implode(' · ', $row['meta']) }}
            @endif
        </div>
    @endif

    @if ($links->isNotEmpty())
        <div class="links">
            @foreach ($links as $link)
                <a href="{{ $link }}">{{ $display($link) }}</a>@if (! $loop->last) &nbsp;·&nbsp; @endif
            @endforeach
        </div>
    @endif
</div>
