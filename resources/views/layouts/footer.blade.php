<footer class="footer-2 bg-gray-800 pt-6 md:pt-12">
    <div class="container px-4 mx-auto">

        <div class="md:flex md:flex-wrap md:-mx-4 py-6 md:pb-12">

            <div class="footer-info lg:w-1/3 md:px-4">
                <h4 class="text-white text-2xl mb-4">
                    Hecho con
                    <svg width="30"
                         height="30"
                         viewBox="0 0 100 100"
                         class="inline">

                        <path fill="tomato" d="M92.71,7.27L92.71,7.27c-9.71-9.69-25.46-9.69-35.18,0L50,14.79l-7.54-7.52C32.75-2.42,17-2.42,7.29,7.27v0 c-9.71,9.69-9.71,25.41,0,35.1L50,85l42.71-42.63C102.43,32.68,102.43,16.96,92.71,7.27z"></path>

                        <animateTransform
                            attributeName="transform"
                            type="scale"
                            values="1; 1.5; 1.25; 1.5; 1.5; 1;"
                            dur="1s"
                            repeatCount="indefinite">
                        </animateTransform>

                    </svg>
                     y
                </h4>

                <p class="text-gray-400">
                    Laravel
                </p>

                <p class="text-gray-400">
                    Tailwind
                </p>

                <p class="text-gray-400">
                    VueJs
                </p>
            </div>

            <div class="md:w-2/3 lg:w-1/3 md:px-4 xl:pl-16 mt-12 lg:mt-0">
                <div class="sm:flex">
                    <div class="sm:flex-1">
                        <h6 class="text-base font-medium text-white uppercase mb-2">
                            Apis
                        </h6>

                        <div>
                            @foreach(MenuHelper::getApiRoutesIndex() as $idx => $ele)
                                <a href="{{$ele['route']}}"
                                   class="text-gray-400 py-1 block hover:underline {{$ele['selected'] ? 'underline' : ''}}">
                                    {{$ele['name']}}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="sm:flex-1 mt-4 sm:mt-0">
                        <h6 class="text-base font-medium text-white uppercase mb-2">
                            Documentación
                        </h6>

                        <div>
                            @forelse(MenuHelper::getApiRoutesDocs() as $idx => $ele)
                                <a href="{{$ele['route']}}"
                                   class="text-gray-400 py-1 block hover:underline {{$ele['selected'] ? 'underline' : ''}}">
                                    {{$ele['name']}}
                                </a>
                            @empty
                                <p class="text-gray-400">
                                    WIP
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:w-1/3 md:px-4 md:text-center mt-12 lg:mt-0">
                <h5 class="text-lg text-white font-medium mb-4">
                    Explora mi sitio Personal
                </h5>
                <a href="https://fryntiz.es"
                   target="_blank"
                   class="bg-indigo-600 text-white hover:bg-indigo-700 rounded py-2 px-6 md:px-12 transition-colors duration-300">
                    Explorar
                </a>
            </div>

        </div>

    </div>

    <div class="border-t border-solid border-gray-900 mt-4 py-4">
        <div class="container px-4 mx-auto">

            <div class="md:flex md:-mx-4 md:items-center">
                <div class="md:flex-1 md:px-4 text-center md:text-left">
                    <p class="text-white">
                        &copy; <strong>Raúl Caro Pastorino</strong>
                    </p>
                </div>

                {{--
                <div class="md:flex-1 md:px-4 text-center md:text-right">
                    <a href="#" class="py-2 px-4 text-white inline-block hover:underline">Terms of Service</a>
                    <a href="#" class="py-2 px-4 text-white inline-block hover:underline">Privacy Policy</a>
                </div>
                --}}
            </div>
        </div>
    </div>
</footer>



