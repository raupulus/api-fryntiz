{{--
    Muestra un bloque de 4 estadísticas con un título, una imagen y un valor
    en unidades. Por ejemplo para mostrar precios, energía.

    Se recibe un array en el que cada elemento será un array con la siguiente
    información:

     [
        'title' => '',
        'image' => '',
        'value' => '',
        'unit' => '',
     ]
--}}

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-2 md:grid-cols-4">
    @foreach($stats as $stat)
        <div class="flex items-center justify-between gap-3 p-4 bg-surface-container-lowest border border-outline-variant/20 rounded-xl shadow-sm">
            <div class="min-w-0">
                <p class="text-xs font-semibold leading-tight tracking-wider text-on-surface-variant uppercase">
                    {{ $stat['title'] }}
                </p>

                <p class="text-base font-bold text-on-surface mt-1">
                    {{ $stat['value'] }}{{ $stat['unit'] }}
                </p>
            </div>

            @if(! empty($stat['image']))
                <img src="{{ $stat['image'] }}"
                     alt="{{ $stat['title'] }}"
                     width="56"
                     height="56"
                     loading="lazy"
                     decoding="async"
                     class="w-14 h-14 shrink-0 object-contain">
            @endif
        </div>
    @endforeach
</div>
