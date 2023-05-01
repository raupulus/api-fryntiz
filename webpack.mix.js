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
    .js('resources/js/vue.js', 'public/js')
    .js('resources/js/alpine.js', 'public/js')
    .js('resources/js/footer.js', 'public/js')
    .js('resources/js/global_vars.js', 'public/js')
    .js('resources/js/scripts.js', 'public/js')
    .js('resources/js/header.js', 'public/js')
    .js('resources/js/functions.js', 'public/js')
    .js('resources/js/jquery.js', 'public/js')
    .combine(['resources/js/dashboard.js', 'resources/js/functions.js', 'resources/js/global_vars.js'], 'public/dashboard/js/dashboard.js')
    .js('resources/js/dashboard/editors.js', 'public/dashboard/js/editors.js')

    .sass('resources/sass/styles.scss', 'public/css/')
    .sass('resources/sass/bootstrap.scss', 'public/css/')
    .sass('resources/sass/dashboard.scss', 'public/dashboard/css/dashboard.css')
    .sass('resources/sass/primevue.scss', 'public/css/primevue.css')
    .postCss('resources/css/assets/tailwind.css', 'public/css', [
        require('postcss-import'),
        //require('postcss-nested'),
        //require('tailwindcss/nesting'),
        require('tailwindcss'),
    ])
    .copy('node_modules/flowbite/dist/flowbite.js', 'public/vendor/flowbite/flowbite.js')
    .copy('node_modules/inputmask/dist/inputmask.min.js', 'public/js/inputmask.js')
    .copy('node_modules/quill/dist/quill.min.js', 'public/js/quill.js')
    .copy('node_modules/quill/dist/quill.core.css', 'public/css/quill.core.css')
    .copy('node_modules/quill/dist/quill.snow.css', 'public/css/quill.snow.css')
    .copy('node_modules/quill/dist/quill.bubble.css', 'public/css/quill.bubble.css')
    .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}
