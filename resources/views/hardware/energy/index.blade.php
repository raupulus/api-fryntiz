@extends('layouts.app')

{{-- Descripción sobre esta página --}}
@section('title', 'Energía (Generadores y Consumo)')
@section('description', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')
@section('keywords', 'energía, generador, generadores, consumo, panel solar, paneles solares, Raúl Caro Pastorino, fryntiz')

{{-- Etiquetas para Redes sociales --}}
@section('rs-title', 'Energía (Generadores y Consumo)')
@section('rs-sitename', 'Api Fryntiz')
@section('rs-description', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')
@section('rs-image', asset('images/keycounter/social-thumbnail.jpg'))
@section('rs-url', route('hardware.energy.index'))
@section('rs-image-alt', 'Estadísticas y datos para la energía producida y la consumida por mis sistemas y equipos.')

@section('meta-twitter-title', 'Energía (Generadores y Consumo)')

@section('content')



    <div class="leading-normal tracking-normal"
         style="font-family: 'Source Sans Pro', sans-serif;">


        <section class="text-gray-600 body-font">
            <div class="container px-5 py-10 mx-auto flex flex-col">
                <div class="lg:w-4/6 mx-auto">
                    <div class="rounded-lg h-64 overflow-hidden">
                        <img alt="content"
                             class="object-cover object-center h-full w-full"
                             src="https://dummyimage.com/1200x500">
                    </div>

                    <div class="container px-1 py-5 mx-auto flex flex-wrap">
                        <h2 class="sm:text-5xl text-4xl text-gray-900
                        font-medium title-font mb-2 w-full text-center">
                            Energía (Generadores y Consumo)
                        </h2>
                    </div>
                </div>
            </div>
        </section>

        {{-- Resumen --}}
        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <!-- State cards -->
                <div
                    class="grid grid-cols-1 gap-8 p-4 lg:grid-cols-2 xl:grid-cols-4">
                    <!-- Value card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6 class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light">
                                Generado
                            </h6>

                            <span class="text-xl font-semibold">
                                0kwh
                            </span>

                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg
                          class="w-12 h-12 text-gray-300 dark:text-primary-dark"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                      >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                      </svg>
                    </span>
                        </div>
                    </div>

                    <!-- Users card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6
                                class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light"
                            >
                                Consumido
                            </h6>
                            <span class="text-xl font-semibold">50,021</span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +2.6%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg
                          class="w-12 h-12 text-gray-300 dark:text-primary-dark"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                      >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                      </svg>
                    </span>
                        </div>
                    </div>

                    <!-- Orders card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6
                                class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light"
                            >
                                Actual
                            </h6>
                            <span class="text-xl font-semibold">
                                0kwh
                            </span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +3.1%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg
                          class="w-12 h-12 text-gray-300 dark:text-primary-dark"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                      >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                        />
                      </svg>
                    </span>
                        </div>
                    </div>

                    <!-- Tickets card -->
                    <div
                        class="flex items-center justify-between p-4 bg-white rounded-md dark:bg-darker">
                        <div>
                            <h6
                                class="text-xs font-medium leading-none tracking-wider text-gray-500 uppercase dark:text-primary-light"
                            >
                                Hoy
                            </h6>
                            <span class="text-xl font-semibold">
                                0kwh
                            </span>
                            <span
                                class="inline-block px-2 py-px ml-2 text-xs text-green-500 bg-green-100 rounded-md">
                      +0.0%
                    </span>
                        </div>
                        <div>
                    <span>
                      <svg
                          class="w-12 h-12 text-gray-300 dark:text-primary-dark"
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                      >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                        />
                      </svg>
                    </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>




        {{-- Listado de dispositivos --}}
        <section class="text-gray-600 body-font">
            <div class="container px-5 py-24 mx-auto">
                <div class="flex flex-col text-center w-full mb-20">
                    <h1 class="text-2xl font-medium title-font mb-4 text-gray-900 tracking-widest">
                        Dispositivos
                    </h1>

                    <p class="lg:w-2/3 mx-auto leading-relaxed text-base">
                        Descripción de la sección
                    </p>
                </div>

                <div class="flex flex-wrap -m-4">
                    <div class="p-4 lg:w-1/2">
                        <div class="h-full flex sm:flex-row flex-col items-center sm:justify-start justify-center text-center sm:text-left">

                            <img alt="team"
                                 class="flex-shrink-0 rounded-lg w-48 h-48 object-cover object-center sm:mb-0 mb-4"
                                 src="https://dummyimage.com/200x200">

                            <div class="flex-grow sm:pl-8">
                                <h2 class="title-font font-medium text-lg text-gray-900">
                                    Dispositivo 1
                                </h2>
                                <h3 class="text-gray-500 mb-3">
                                    UI Developer
                                </h3>

                                <p class="mb-4">
                                    DIY tote bag drinking vinegar
                                    cronut adaptogen squid fanny pack
                                    vaporware.
                                </p>

                                <span class="inline-flex">

            </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 lg:w-1/2">
                        <div
                            class="h-full flex sm:flex-row flex-col items-center sm:justify-start justify-center text-center sm:text-left">
                            <img alt="team"
                                 class="flex-shrink-0 rounded-lg w-48 h-48 object-cover object-center sm:mb-0 mb-4"
                                 src="https://dummyimage.com/201x201">
                            <div class="flex-grow sm:pl-8">
                                <h2 class="title-font font-medium text-lg text-gray-900">
                                    Alper Kamu</h2>
                                <h3 class="text-gray-500 mb-3">Designer</h3>
                                <p class="mb-4">DIY tote bag drinking vinegar
                                    cronut adaptogen squid fanny pack
                                    vaporware.</p>
                                <span class="inline-flex">
              <a class="text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
                </svg>
              </a>
              <a class="ml-2 text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                </svg>
              </a>
              <a class="ml-2 text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path>
                </svg>
              </a>
            </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 lg:w-1/2">
                        <div
                            class="h-full flex sm:flex-row flex-col items-center sm:justify-start justify-center text-center sm:text-left">
                            <img alt="team"
                                 class="flex-shrink-0 rounded-lg w-48 h-48 object-cover object-center sm:mb-0 mb-4"
                                 src="https://dummyimage.com/204x204">
                            <div class="flex-grow sm:pl-8">
                                <h2 class="title-font font-medium text-lg text-gray-900">
                                    Atticus Finch</h2>
                                <h3 class="text-gray-500 mb-3">UI Developer</h3>
                                <p class="mb-4">DIY tote bag drinking vinegar
                                    cronut adaptogen squid fanny pack
                                    vaporware.</p>
                                <span class="inline-flex">
              <a class="text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
                </svg>
              </a>
              <a class="ml-2 text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                </svg>
              </a>
              <a class="ml-2 text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path>
                </svg>
              </a>
            </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 lg:w-1/2">
                        <div
                            class="h-full flex sm:flex-row flex-col items-center sm:justify-start justify-center text-center sm:text-left">
                            <img alt="team"
                                 class="flex-shrink-0 rounded-lg w-48 h-48 object-cover object-center sm:mb-0 mb-4"
                                 src="https://dummyimage.com/206x206">
                            <div class="flex-grow sm:pl-8">
                                <h2 class="title-font font-medium text-lg text-gray-900">
                                    Henry Letham</h2>
                                <h3 class="text-gray-500 mb-3">Designer</h3>
                                <p class="mb-4">DIY tote bag drinking vinegar
                                    cronut adaptogen squid fanny pack
                                    vaporware.</p>
                                <span class="inline-flex">
              <a class="text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
                </svg>
              </a>
              <a class="ml-2 text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
                </svg>
              </a>
              <a class="ml-2 text-gray-500">
                <svg fill="none" stroke="currentColor" stroke-linecap="round"
                     stroke-linejoin="round" stroke-width="2" class="w-5 h-5"
                     viewBox="0 0 24 24">
                  <path
                      d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path>
                </svg>
              </a>
            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <section class="bg-white border-b">
            <div class="container max-w-5xl mx-auto m-4">
                <h1 class="w-full my-2 text-5xl font-bold leading-tight text-center text-gray-800">
                    Energía (Generadores y Consumo)
                </h1>

                <div class="w-full py-3 px-6 text-center">
                    <p class="text-gray-600 mb-1">
                        Estadísticas
                    </p>
                </div>

                <div class="w-full mb-4 text-center">
                    block 1
                </div>

                <div class="w-full mb-4 text-center">
                    block 1
                </div>


            </div>
        </section>
    </div>







    TEST

    https://tailwind-elements.com/docs/standard/components/charts/
    https://tailwindcomponents.com/
    https://github.com/aniftyco/awesome-tailwindcss

    @include('hardware.energy.layouts._generators')
    @include('hardware.energy.layouts._loads')
@endsection
