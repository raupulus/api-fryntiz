
<script>
    /**
     * Parámetros de la aplicación.
     */
    const site =  {
        url: "{{config('app.url')}}",
        route: "{{request()->url()}}",
        path: "{{request()->path()}}",
        full_url: "{{request()->fullUrl() }}",
        name: "{{config('app.name')}}",
        description: "{{config('app.description')}}",
        author: "{{config('app.author')}}",
        author_url: "{{config('app.author_url')}}",
        locale: "{{config('app.locale')}}",
        timezone: "{{config('app.timezone')}}",
    };

    /**
     * Alias.
     */
    var log = console.log;
</script>
