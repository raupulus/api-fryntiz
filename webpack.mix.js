const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/footer.js', 'public/js')
    .js('resources/js/global_vars.js', 'public/js')
    .js('resources/js/scripts.js', 'public/js')
    .js('resources/js/header.js', 'public/js')
    .js('resources/js/functions.js', 'public/js')
    .sass('resources/sass/styles.scss', 'public/css/')
    .postCss('resources/css/assets/tailwind.css', 'public/css', [
        require('postcss-import'),
        require('tailwindcss'),
    ]);

if (mix.inProduction()) {
    mix.version();
}
