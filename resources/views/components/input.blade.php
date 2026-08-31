@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'icon' => null,
    'helper' => null,
    'error' => null,
])

@php
    $hasError = $error || ($errors->has($name) ?? false);
    $errorMessage = $error ?: ($errors->first($name) ?? null);

    $inputClasses = 'w-full rounded-xl bg-surface-container-lowest text-on-surface border px-4 py-2.5 text-sm transition-colors duration-200 placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 disabled:opacity-50 disabled:bg-surface-container-high ' .
        ($hasError
            ? 'border-error focus:border-error focus:ring-error/20'
            : 'border-outline-variant/60 hover:border-outline focus:border-primary focus:ring-primary/20') .
        ($icon ? ' pl-10' : '');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full flex flex-col gap-1.5']) }}>
    @if($label)
        <label for="{{ $name }}" class="text-sm font-semibold text-on-surface flex items-center justify-between">
            <span>
                {{ $label }}
                @if($required)
                    <span class="text-error ml-0.5">*</span>
                @endif
            </span>
        </label>
    @endif

    <div class="relative flex items-center">
        @if($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-on-surface-variant">
                <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->except('class') }}
            class="{{ $inputClasses }}"
        />
    </div>

    @if($hasError && $errorMessage)
        <p class="text-xs text-error font-medium flex items-center gap-1 mt-0.5">
            <span class="material-symbols-outlined text-sm">error</span>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($helper)
        <p class="text-xs text-on-surface-variant/80 mt-0.5">{{ $helper }}</p>
    @endif
</div>
