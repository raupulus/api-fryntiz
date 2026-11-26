@props([
    'image' => 'images/heroes/about.webp',
    'minHeight' => 'min-h-[38svh]',
    'maxWidth' => 'max-w-7xl',
    'position' => 'object-center',
])

<section {{ $attributes->merge(['class' => "hero-banner hero-gradient {$minHeight} flex items-center pt-24 pb-10"]) }}>
    <img src="{{ asset($image) }}"
         alt=""
         aria-hidden="true"
         fetchpriority="high"
         decoding="async"
         class="absolute inset-0 w-full h-full object-cover {{ $position }} pointer-events-none select-none"/>
    <div class="hero-banner-scrim pointer-events-none" aria-hidden="true"></div>
    <div class="hero-banner-content {{ $maxWidth }} mx-auto px-4 sm:px-6 text-on-hero w-full">
        {{ $slot }}
    </div>
</section>
