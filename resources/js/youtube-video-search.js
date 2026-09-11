// Buscador de vídeos de YouTube para el campo `YoutubeVideoField` del panel.
//
// Vivía en `public/js/youtube_video_search.js`, que es un directorio ignorado
// por git (`.gitignore`: `/public/js`, la salida que dejaba Laravel Mix en la
// v1). El fichero nunca llegó al servidor: la petición caía en el enrutador,
// Laravel devolvía su página de error en HTML y el navegador la rechazaba por
// `X-Content-Type-Options: nosniff`. El «MIME mismatch» era un 404 disfrazado.
//
// En `main` esto lo compilaba el bundler (`resources/js/dashboard/`), así que
// vuelve a su sitio. La clase se sigue exponiendo en `window` porque la vista
// del campo la busca ahí (`typeof YoutubeVideoSearch === 'undefined'`).

import '../css/youtube-video-search-tailwind.css';

if (typeof window.YoutubeVideoSearch === 'undefined') {
window.YoutubeVideoSearch = class YoutubeVideoSearch {
    url = 'https://www.googleapis.com/youtube/v3/search';

    totalResults = 0;
    resultsPerPage = 0;
    prevPageToken = null;
    nextPageToken = null;
    videos = [];

    timeoutSearch = null;

    search = null;

    // Caché de tokens de página ya visitados: pageTokens[k] guarda el token
    // para llegar a la página k+1 (la API de YouTube sólo da "siguiente" y
    // "anterior", no salto directo a página N; esto permite volver a una
    // página ya vista sin repetir toda la cadena de peticiones).
    pageTokens = [];
    currentPage = 1;
    maxKnownPage = 1;

    /**
     * Constructor para preparar el buscador.
     *
     * @param apiKey Clave api de youtube.
     * @param channelId Id del canal sobre el que buscar.
     * @param boxTarget Elemento donde se pondrá el modal, o selector CSS
     *   para buscarlo en todo el documento. Se admite el elemento directo
     *   para no depender de un id global: si el campo llegara a existir dos
     *   veces en la página, un querySelector por id siempre devuelve el
     *   primero, mezclando instancias.
     * @param callback Función que se llamará una vez cambiado el vídeo.
     * @param btnTarget Elemento o selector CSS para el botón que abre el modal.
     */
    constructor(apiKey, channelId, boxTarget, callback, btnTarget = null) {
        this.apiKey = apiKey;
        this.channelId = channelId;
        this.callback = callback;

        const box = boxTarget instanceof Element ? boxTarget : document.querySelector(boxTarget);
        const btn = btnTarget instanceof Element ? btnTarget : document.querySelector(btnTarget);

        this.box = box;

        // Prepara el DOM completo del buscador.
        this.domModalGenerate();

        this.inputSearch = this.box.querySelector('.input-modal-youtube-video-search');
        const prevButton = this.box.querySelectorAll('[data-modal_youtube_prev]');
        const nextButton = this.box.querySelectorAll('[data-modal_youtube_next]');

        // Preparo botón para abrir modal
        if (btn) {
            btn.addEventListener('click', () => {
                box.classList.remove('modal-youtube-video-search-hidden');
            });
        }

        // Evento para cerrar modal al pulsar botones
        box.querySelectorAll('.btn-close-modal-youtube-video-search').forEach(ele => ele.addEventListener('click', () => this.closeModal()));


        // Preparo evento al buscar
        this.inputSearch.addEventListener('keyup', e => this.searchInputChangeHandler(e));

        // En `main` este input nunca vivía dentro de un <form>. Aquí sí: todo
        // el recurso de Filament es un único <form wire:submit="save">, así
        // que sin esto el Enter dispara el envío implícito del formulario
        // (guarda y navega) en vez de quedarse buscando. El valor ya se
        // procesa por el keyup normal, aquí solo se evita el submit.
        //
        // `preventDefault()` sólo frena la acción nativa del navegador; el
        // propio Filament escucha Enter a nivel de formulario para saltar al
        // siguiente campo (evita envíos accidentales) y ese listener sí
        // reacciona al evento burbujeado. Hace falta `stopPropagation()`
        // para que no le llegue.
        this.inputSearch.addEventListener('keydown', e => {
            const key = e.keyCode || e.charCode;
            if (key === 13) {
                e.preventDefault();
                e.stopPropagation();
            }
        });


        // Preparo eventos para botones de avanzar/retroceder en el listado
        prevButton.forEach(ele => ele.addEventListener('click', e => this.goToPrevPage(e)));
        nextButton.forEach(ele => ele.addEventListener('click', e => this.goToNextPage(e)));
    }


    /**
     * Establece un nuevo id de canal para realizar las consultas.
     *
     * @param channelId
     */
    set setChannelId(channelId) {
        this.channelId = channelId;
    }

    /**
     * Obtiene el id del canal actual.
     *
     * @returns {*}
     */
    get getChannelId() {
        return this.channelId;
    }


    /**
     * Genera el contenido para el DOM del modal.
     */
    domModalGenerate() {
        const box = document.createElement('div');
        box.classList.add('box-modal-youtube-video-search');

        const container = document.createElement('div');
        container.classList.add('container-modal-youtube-video-search');

        const boxHeader = this.domModalHeaderGenerate();

        const boxBody = document.createElement('div');
        boxBody.classList.add('body-modal-youtube-video-search');
        boxBody.textContent = 'Introduce un patrón de búsqueda'

        const boxFooter = this.domModalFooterGenerate();

        container.append(boxHeader);
        container.append(boxBody);
        container.append(boxFooter);

        box.append(container);

        this.box.append(box);
    }

    /**
     * Genera el contenido para el DOM solo del Header.
     *
     * @returns {HTMLDivElement}
     */
    domModalHeaderGenerate() {

        const boxHeader = document.createElement('div');
        boxHeader.classList.add('header-modal-youtube-video-search');

        // Insignia con el nombre del canal/plataforma sobre el que se busca.
        // La rellena `setChannelBadge()` en cuanto se conoce la plataforma
        // seleccionada; hasta entonces queda oculta.
        const channelBadge = document.createElement('a');
        channelBadge.classList.add('badge-channel-modal-youtube-video-search');
        channelBadge.target = '_blank';
        channelBadge.rel = 'noopener noreferrer';
        channelBadge.style.display = 'none';
        this.channelBadge = channelBadge;

        const boxClose = document.createElement('span');
        boxClose.classList.add('box-close-modal-youtube-video-search');

        boxClose.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="btn-close-modal-youtube-video-search"\n' +
            'fill="#5d5d5d"\n' +
            'viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>';

        const boxTitle = document.createElement('div');
        const title = document.createElement('span');
        title.classList.add('title-modal-youtube-video-search');
        title.textContent = 'Busca un vídeo en tu canal';

        const searchRow = document.createElement('div');
        searchRow.classList.add('row-search-modal-youtube-video-search');

        const clearBtn = document.createElement('span');
        clearBtn.classList.add('btn-clear-modal-youtube-video-search');
        clearBtn.title = 'Limpiar búsqueda';
        clearBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0H284.2c12.1 0 23.2 6.8 28.6 17.7L320 32h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 96 0 81.7 0 64S14.3 32 32 32h96l7.2-14.3zM32 128H416L394.8 467c-1.6 25.3-22.6 45-47.9 45H101.1c-25.3 0-46.3-19.7-47.9-45L32 128z"/></svg>';
        clearBtn.addEventListener('click', () => this.clearSearch());

        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('input-modal-youtube-video-search');

        searchRow.append(clearBtn);
        searchRow.append(input);

        boxTitle.append(title);
        boxTitle.append(searchRow);

        boxHeader.append(channelBadge);
        boxHeader.append(boxClose);
        boxHeader.append(boxTitle);

        return boxHeader;
    }

    /**
     * Muestra/actualiza la insignia con el canal o plataforma sobre el que
     * se está buscando, enlazando al canal real de YouTube en una pestaña
     * nueva. Se oculta si no hay canal resuelto todavía.
     *
     * @param name Nombre a mostrar (plataforma o canal). Puede ser null.
     * @param channelId Id del canal de YouTube sobre el que se busca.
     */
    setChannelBadge(name, channelId) {
        if (!this.channelBadge) {
            return;
        }

        if (!channelId) {
            this.channelBadge.style.display = 'none';

            return;
        }

        this.channelBadge.textContent = name || 'Ver canal';
        this.channelBadge.href = 'https://www.youtube.com/channel/' + channelId;
        this.channelBadge.style.display = '';
    }

    /**
     * Genera la parte del DOM para el footer del modal.
     *
     * @returns {HTMLDivElement}
     */
    domModalFooterGenerate() {
        const boxFooter = document.createElement('div');
        boxFooter.classList.add('footer-modal-youtube-video-search');

        // Números de página ya visitados/alcanzables, para saltar directo
        // en vez de ir pulsando "Siguiente" una a una. La API de YouTube no
        // da salto a página N, así que sólo se listan las que ya tienen un
        // token conocido (ver `pageTokens`).
        const pageNumbers = document.createElement('div');
        pageNumbers.classList.add('modal-youtube-video-search-text-center', 'pages-modal-youtube-video-search');
        this.pageNumbersRow = pageNumbers;
        boxFooter.append(pageNumbers);

        const div =  document.createElement('div');
        div.classList.add('modal-youtube-video-search-text-center');

        const prev = document.createElement('span');
        prev.classList.add('btn-modal-youtube-video-search', 'btn-modal-youtube-video-search-primary', 'btn-modal-youtube-video-search-disable');
        prev.setAttribute('data-modal_youtube_prev', '');
        prev.textContent = 'Página Anterior';

        const next = document.createElement('span');
        next.classList.add('btn-modal-youtube-video-search', 'btn-modal-youtube-video-search-primary', 'btn-modal-youtube-video-search-disable');
        next.setAttribute('data-modal_youtube_next', '');
        next.textContent = 'Página Siguiente';

        div.append(prev);
        div.append(next);

        boxFooter.append(div);

        return boxFooter;
    }

    /**
     * Pinta los números de página ya alcanzables (con token conocido).
     */
    renderPageNumbers() {
        if (!this.pageNumbersRow) {
            return;
        }

        this.pageNumbersRow.innerHTML = '';

        if (this.maxKnownPage <= 1) {
            return;
        }

        for (let page = 1; page <= this.maxKnownPage; page++) {
            const btn = document.createElement('span');
            btn.classList.add('btn-page-modal-youtube-video-search');

            if (page === this.currentPage) {
                btn.classList.add('btn-page-modal-youtube-video-search-active');
            }

            btn.textContent = String(page);
            btn.addEventListener('click', () => this.goToPageNumber(page));

            this.pageNumbersRow.append(btn);
        }
    }

    /**
     * Maneja el evento cuando cambia el contenido del input del buscador.
     */
    searchInputChangeHandler(e) {
        const key = e.keyCode || e.charCode;

        // Descarta teclas no necesarias (ctrl, alt, meta...)
        const keysDiscard = [16,17,18,27,37,38,39,40,44,224];

        if (keysDiscard.includes(key)) {
            //console.log('Tecla ' + key + ' Descartada');

            return;
        }

        const search = this.inputSearch.value.trim().replace(/ +/g,' ');

        if (search === this.search) {
            //console.log('La cadena es igual a la actual');

            return
        }

        if (this.timeoutSearch) {
            clearTimeout(this.timeoutSearch);
        }

        if (search.length >= 3) {
            this.timeoutSearch = setTimeout(() => this.queryYoutubeApi(), 400);
        } else {
            // Menos de 3 carácteres (incluido vacío): no hay búsqueda válida,
            // así que no se deja la lista de resultados de la búsqueda
            // anterior a la vista.
            this.search = null;
            this.resetResults();
        }
    }

    /**
     * Lleva a la siguiente página de resultados, reutilizando el token ya
     * conocido en vez de depender sólo del último guardado.
     */
    async goToNextPage() {
        return this.goToPageNumber(this.currentPage + 1);
    }

    /**
     * Lleva a la página anterior de resultados.
     */
    async goToPrevPage() {
        return this.goToPageNumber(this.currentPage - 1);
    }

    /**
     * Salta directamente a una página ya alcanzada (con token conocido).
     * La API de YouTube no admite saltar a una página no visitada todavía.
     *
     * @param page Número de página (1-indexado) al que saltar.
     */
    async goToPageNumber(page) {
        if (page < 1 || page === this.currentPage) {
            return;
        }

        const token = page === 1 ? null : this.pageTokens[page - 1];

        if (page > 1 && !token) {
            // Página aún no descubierta: no hay token para llegar a ella.
            return;
        }

        return this.queryYoutubeApi(token, page);
    }

    /**
     * Realiza la petición a la api para obtener resultados.
     *
     * @param pageToken Token de la página de resultados a revisar.
     *
     * @returns {Promise<void>}
     */
    async queryYoutubeApi(pageToken = null, pageNumber = 1) {
        const search = this.inputSearch.value.trim().replace(/ +/g,' ');

        this.search = search;

        if (pageNumber === 1) {
            // Búsqueda nueva: los tokens de páginas de la búsqueda anterior
            // ya no valen para ésta.
            this.pageTokens = [];
            this.maxKnownPage = 1;
        }

        //console.log('Realiza petición a la api de google con el valor: ', search);

        const params = {
            q: search,
            part: 'id,snippet', // snippet por defecto
            channelId: this.channelId,
            type: 'video',
            key: this.apiKey,
            maxResults: 10,
            order: 'relevance', // viewCount, rating, title, relevance, date
            safeSearch: 'none',
        }

        if (pageToken) {
            params.pageToken = pageToken
        }

        fetch(this.url + '?' + new URLSearchParams(params), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    this.showError(data.error.message || 'Error desconocido al consultar la API de YouTube.');

                    return;
                }

                const results = {
                    totalResults: data.pageInfo.totalResults,
                    resultsPerPage: data.pageInfo.resultsPerPage,
                    nextPageToken: data.nextPageToken,
                    prevPageToken: data.prevPageToken,
                    videos: []
                }

                this.totalResults = results.totalResults;
                this.resultsPerPage = results.resultsPerPage;
                this.nextPageToken = results.nextPageToken;
                this.prevPageToken = results.prevPageToken;

                this.currentPage = pageNumber;
                this.maxKnownPage = Math.max(this.maxKnownPage, pageNumber);

                if (results.nextPageToken) {
                    // Token para llegar a la página siguiente, cacheado para
                    // poder volver a ella sin repetir toda la cadena.
                    this.pageTokens[pageNumber] = results.nextPageToken;
                    this.maxKnownPage = Math.max(this.maxKnownPage, pageNumber + 1);
                }

                this.cleanBody();

                data.items.forEach(ele => {
                    const video = {
                        id: ele.id.videoId,
                        title: ele.snippet.title,
                        description: ele.snippet.description,
                        publishedAt: ele.snippet.publishedAt,
                        thumbnails: {
                            small: {
                                width: ele.snippet.thumbnails.default.width,
                                height: ele.snippet.thumbnails.default.height,
                                url: ele.snippet.thumbnails.default.url,
                            },
                            medium: {
                                width: ele.snippet.thumbnails.medium.width,
                                height: ele.snippet.thumbnails.medium.height,
                                url: ele.snippet.thumbnails.medium.url,
                            },
                            high: {
                                width: ele.snippet.thumbnails.high.width,
                                height: ele.snippet.thumbnails.high.height,
                                url: ele.snippet.thumbnails.high.url,
                            },
                        }
                    };

                    results.videos.push(video);

                    this.appendVideo(video);
                });

                this.videos = results.videos

                if (!this.videos.length) {
                    const body = this.box.querySelector('.body-modal-youtube-video-search');
                    body.textContent = 'No hay resultados para esta búsqueda';
                }

                //console.log('Results:', results);
                //console.log('Videos:', this.videos);

                const prevButton = this.box.querySelectorAll('[data-modal_youtube_prev]');
                const nextButton = this.box.querySelectorAll('[data-modal_youtube_next]');

                if (this.prevPageToken) {
                    prevButton.forEach(ele => ele.classList.remove('btn-modal-youtube-video-search-disable'));
                } else {
                    prevButton.forEach(ele => ele.classList.add('btn-modal-youtube-video-search-disable'));
                }

                if (this.nextPageToken) {
                    nextButton.forEach(ele => ele.classList.remove('btn-modal-youtube-video-search-disable'));
                } else {
                    nextButton.forEach(ele => ele.classList.add('btn-modal-youtube-video-search-disable'));
                }

                this.renderPageNumbers();
            })
            .catch(() => this.showError('No se ha podido contactar con la API de YouTube.'))
        ;
    }

    /**
     * Muestra un error en el cuerpo del modal en lugar de dejarlo congelado
     * en «Introduce un patrón de búsqueda» sin ninguna pista de qué ha fallado.
     *
     * @param message Mensaje de error a mostrar.
     */
    showError(message) {
        console.error('YoutubeVideoSearch:', message);

        this.cleanBody();

        const body = this.box.querySelector('.body-modal-youtube-video-search');
        body.textContent = 'Error al buscar vídeos: ' + message;
    }

    /**
     * Añade un vídeo al listado de visualización.
     *
     * @param video Objeto con el vídeo para añadir.
     */
    appendVideo(video) {
        const box = document.createElement('div');
        box.classList.add('modal-youtube-video-search-card-container');

        const img = document.createElement('img');
        img.src = video.thumbnails.small.url;
        img.alt = video.title;

        box.append(img);

        const boxInfo = document.createElement('div');
        const title = document.createElement('span');
        title.classList.add('modal-youtube-video-search-card-title')
        title.textContent = video.title;
        const description = document.createElement('span');
        description.textContent = video.description;

        boxInfo.append(title);
        boxInfo.append(description);

        box.append(boxInfo);

        const boxActions = document.createElement('div');
        const btnUse = document.createElement('span');
        btnUse.classList.add('btn-modal-youtube-video-search', 'btn-modal-youtube-video-search-primary');
        btnUse.textContent = 'Usar';
        btnUse.addEventListener('click', (e) => this.callback(e, video));

        box.append(btnUse);

        const body = this.box.querySelector('.body-modal-youtube-video-search');
        body.append(box);
    }

    /**
     * Elimina el contenido de la lista de vídeos de youtube.
     */
    cleanBody() {
        const body = this.box.querySelector('.body-modal-youtube-video-search');

        while (body.firstChild) {
            body.removeChild(body.lastChild);
        }
    }

    /**
     * Vacía el input de búsqueda y limpia el resultado que hubiera, en vez
     * de dejar la lista de la búsqueda anterior a la vista.
     */
    clearSearch() {
        this.inputSearch.value = '';
        this.search = null;

        if (this.timeoutSearch) {
            clearTimeout(this.timeoutSearch);
        }

        this.resetResults();
        this.inputSearch.focus();
    }

    /**
     * Vuelve el modal a su estado inicial: sin vídeos, sin paginación y con
     * el texto de partida en el cuerpo.
     */
    resetResults() {
        this.videos = [];
        this.totalResults = 0;
        this.resultsPerPage = 0;
        this.nextPageToken = null;
        this.prevPageToken = null;
        this.pageTokens = [];
        this.currentPage = 1;
        this.maxKnownPage = 1;

        this.cleanBody();

        const body = this.box.querySelector('.body-modal-youtube-video-search');
        body.textContent = 'Introduce un patrón de búsqueda';

        const prevButton = this.box.querySelectorAll('[data-modal_youtube_prev]');
        const nextButton = this.box.querySelectorAll('[data-modal_youtube_next]');
        prevButton.forEach(ele => ele.classList.add('btn-modal-youtube-video-search-disable'));
        nextButton.forEach(ele => ele.classList.add('btn-modal-youtube-video-search-disable'));

        this.renderPageNumbers();
    }

    /**
     * Oculta el modal de búsqueda.
     */
    closeModal() {
        this.box.classList.add('modal-youtube-video-search-hidden');
    }

    /**
     * Devuelve el conjunto de resultados de la última búsqueda.
     * Es un array de objetos con los datos de los vídeos.
     *
     * @returns {*[]}
     */
    get getVideos() {
        return this.videos;
    }

}
}
