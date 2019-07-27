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

mix.js('resources/js/scripts.js', 'public/js')
  .js('resources/js/panel/scripts.js', 'public/admin-panel/js')
  .js('resources/js/panel/functions.js', 'public/admin-panel/js')
  .js('resources/js/panel/login/scripts.js', 'public/admin-panel/login/js')
  .js('resources/js/panel/login/functions.js', 'public/admin-panel/login/js')
  .js('resources/js/header.js', 'public/js')
  .js('resources/js/footer.js', 'public/js')
  .js('resources/js/functions.js', 'public/js')
  .js('resources/js/bootstrap.js', 'public/assets/js')
  .js('resources/js/jquery.js', 'public/assets/js')
  .js('resources/js/popper.js', 'public/assets/js')
  //.js('node_modules/bootstrap-sass/assets/javascripts/bootstrap.js', 'public/assets/js')
  .sass('resources/sass/styles.scss', 'public/css/')
  .sass('resources/sass/panel/styles.scss', 'public/admin-panel/css')
  .sass('resources/sass/panel/login/styles.scss', 'public/admin-panel/login/css')
  .sass('resources/sass/assets/bootstrap.scss', 'public/assets/css')
  .sass('resources/sass/assets/fontawesome.scss', 'public/assets/css')
  .autoload({
    jQuery: 'jquery',
    $: 'jquery',
    Popper: 'popper.js',
    jquery: ['$', 'window.jQuery', 'jquery']
  });

