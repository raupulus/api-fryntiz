class YoutubeVideoSearch {
    url = 'https://www.googleapis.com/youtube/v3/search';

    totalResults = 0;
    resultsPerPage = 0;
    prevPageToken = null;
    nextPageToken = null;
    videos = [];

    timeoutSearch = null;

    constructor(apiKey, channelId, boxSelector, btnSelector = null) {
        this.apiKey = apiKey;
        this.channelId = channelId;

        const box = document.querySelector(boxSelector);
        const btn = document.querySelector(btnSelector);

        this.box = box;
        this.inputSearch = this.box.querySelector('.input-modal-youtube-video-search');

        // Preparo botón para abrir modal
        if (btn) {
            btn.addEventListener('click', () => {
                box.classList.remove('modal-youtube-video-search-hidden');
            });
        }

        // Evento para cerrar modal al pulsar botones
        box.querySelectorAll('.btn-close-modal-youtube-video-search').forEach(ele => ele.addEventListener('click', () => this.closeModal()));


        // Preparo evento al buscar
        this.inputSearch.addEventListener('keyup', () => this.searchInputChangeHandler());
    }

    searchInputChangeHandler() {

        console.log('se ha pulsado una tecla!!!');

        // TODO: Descartar teclas meta, ctrl, alt.... ya que también salta este evento.


        if (this.timeoutSearch) {
            clearTimeout(this.timeoutSearch);
        }

        this.timeoutSearch = setTimeout(() => this.queryYoutubeApi(), 1000);
    }

    async queryYoutubeApi(pageToken = null) {
        const search = this.inputSearch.value;

        console.log('Realiza petición a la api de google con el valor: ', search);

        const params = {
            q: search,
            part: 'snippet',
            channelId: this.channelId,
            type: 'video',
            key: this.apiKey,
            maxResults: 5,
            order: 'title',
            safeSearch: 'none'
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
                console.log('DATA:', data);

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

                data.items.forEach(ele => {
                    results.videos.push({
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
                    });
                });

                this.videos = results.videos

                console.log('Results:', results);
                console.log('Videos:', this.videos);


                // TODO: Poner todos estos resultados en el DOM
            })


        ;
    }

    closeModal() {
        this.box.classList.add('modal-youtube-video-search-hidden');
    }





    get test() {
        return this.totalResults;
    }

    search(query) {
        console.log('Buscando:', query);



    }

    goToNextPage() {

    }

    goToPrevPage() {

    }

}
