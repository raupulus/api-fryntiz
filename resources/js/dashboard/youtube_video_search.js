class YoutubeVideoSearch {
    url = 'https://www.googleapis.com/youtube/v3/search';

    totalResults = 0;
    resultsPerPage = 0;
    prevPageToken = null;
    nextPageToken = null;
    videos = [];

    timeoutSearch = null;

    search = null;

    /**
     * Constructor para preparar el buscador.
     *
     * @param apiKey Clave api de youtube.
     * @param channelId Id del canal sobre el que buscar.
     * @param boxSelector Selector CSS para la caja dónde se pondrá el modal.
     * @param callback Función que se llamará una vez cambiado el vídeo.
     * @param btnSelector Selector CSS para el botón dónde se pondrá el modal.
     */
    constructor(apiKey, channelId, boxSelector, callback, btnSelector = null) {
        this.apiKey = apiKey;
        this.channelId = channelId;
        this.callback = callback;

        const box = document.querySelector(boxSelector);
        const btn = document.querySelector(btnSelector);

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

        this.domModalFooterGenerate()
    }

    /**
     * Genera el contenido para el DOM solo del Header.
     *
     * @returns {HTMLDivElement}
     */
    domModalHeaderGenerate() {

        const boxHeader = document.createElement('div');
        boxHeader.classList.add('header-modal-youtube-video-search');

        const boxClose = document.createElement('span');
        boxClose.classList.add('box-close-modal-youtube-video-search');

        boxClose.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="btn-close-modal-youtube-video-search"\n' +
            'fill="#5d5d5d"\n' +
            'viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"/></svg>';

        const boxTitle = document.createElement('div');
        const title = document.createElement('span');
        title.classList.add('title-modal-youtube-video-search');
        title.textContent = 'Busca un vídeo en tu canal';

        const input = document.createElement('input');
        input.type = 'text';
        input.classList.add('input-modal-youtube-video-search');

        boxTitle.append(title);
        boxTitle.append(input);

        boxHeader.append(boxClose);
        boxHeader.append(boxTitle);

        return boxHeader;
    }

    /**
     * Genera la parte del DOM para el footer del modal.
     *
     * @returns {HTMLDivElement}
     */
    domModalFooterGenerate() {
        const boxFooter = document.createElement('div');
        boxFooter.classList.add('footer-modal-youtube-video-search');


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
            //console.log('Introduce mínimo 3 carácteres');
        }
    }

    /**
     * Lleva a la siguiente página de resultados.
     */
    async goToNextPage(e) {
        const nextPage = this.nextPageToken

        if (nextPage) {
            return this.queryYoutubeApi(nextPage);
        }
    }

    /**
     * Lleva a la página anterior de resultados.
     */
    async goToPrevPage(e) {
        const prevPage = this.prevPageToken

        if (prevPage) {
            return this.queryYoutubeApi(prevPage);
        }
    }

    /**
     * Realiza la petición a la api para obtener resultados.
     *
     * @param pageToken Token de la página de resultados a revisar.
     *
     * @returns {Promise<void>}
     */
    async queryYoutubeApi(pageToken = null) {
        const search = this.inputSearch.value.trim().replace(/ +/g,' ');

        this.search = search;

        //console.log('Realiza petición a la api de google con el valor: ', search);

        const params = {
            q: search,
            part: 'id,snippet', // snippet por defecto
            channelId: this.channelId,
            type: 'video',
            key: this.apiKey,
            maxResults: 5,
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
            })

        ;
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
