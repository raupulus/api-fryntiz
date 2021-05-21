<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div>
                <h3>Sobre el sitio y el autor</h3>
            </div>

            <div class="text-center">
                <img src="{{asset('images/logos/180x180.png')}}"
                     alt="{{config('app.name')}}"
                     title="{{config('app.name')}}" />
            </div>

            <div>
                <table cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td colspan="2" class="text-center">
                                <a href="{{config('app.url')}}"
                                   title="{{config('app.name')}}">
                                    {{config('app.name')}}
                                </a>
                            </td>
                        </tr>

                        <tr>
                            <td>Autor del sitio</td>
                            <td>
                                <a href="{{config('app.author_url')}}"
                                   title="{{config('app.author')}}">
                                    {{config('app.author')}}
                                </a>
                            </td>
                        </tr>

                        <tr>
                            <td>Sobre el sitio web</td>
                            <td>
                                <a href="{{route('about')}}"
                                   title="{{'About ' . config('app.name')}}">
                                    {{route('about')}}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
