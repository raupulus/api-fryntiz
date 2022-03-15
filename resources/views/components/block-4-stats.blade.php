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
        'porcent' => '',
     ]
--}}

{{-- Card --}}
<div class="grid grid-cols-2 gap-4 p-2 md:grid-cols-4 xl:grid-cols-4">
    @foreach($stats as $stat)
        <div class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
            <div>
                <h6 class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light">
                    {{ $stat['title'] }}
                </h6>

                <span class="text-md font-semibold">
                    {{ $stat['value'] }}{{ $stat['unit'] }}
                </span>

                <span class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                    +0.0%
                </span>
            </div>

            <div>
                <span>
                    <img src="{{ $stat['image'] }}"
                         alt="{{ $stat['title'] }}"
                         class="w-14 h-14">
                </span>
            </div>
        </div>
    @endforeach
</div>
