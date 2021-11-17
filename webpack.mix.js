const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .vue()
    .js('resources/js/alpine.js', 'public/js')
    .js('resources/js/footer.js', 'public/js')
    .js('resources/js/global_vars.js', 'public/js')
    .js('resources/js/scripts.js', 'public/js')
    .js('resources/js/header.js', 'public/js')
    .js('resources/js/functions.js', 'public/js')
    .js('resources/js/jquery.js', 'public/js')
    .js('resources/js/dashboard.js', 'public/dashboard/js')
    .sass('resources/sass/styles.scss', 'public/css/')
    .sass('resources/sass/bootstrap.scss', 'public/css/')
    .sass('resources/sass/dashboard.scss', 'public/dashboard/css/')
    .postCss('resources/css/assets/tailwind.css', 'public/css', [
        require('postcss-import'),
        require('tailwindcss'),
    ])
    .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}
