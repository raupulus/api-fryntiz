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
    .js('resources/js/dashboard/content_pages.js', 'public/dashboard/js/content_pages.js')


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
    .copy('node_modules/@editorjs/editorjs/dist/editor.js', 'public/vendor/editorjs/editor.js')
    .copy('node_modules/@editorjs/header/dist/bundle.js', 'public/vendor/editorjs/header.js')
    .copy('node_modules/@editorjs/link/dist/bundle.js', 'public/vendor/editorjs/link.js')
    .copy('node_modules/@editorjs/raw/dist/bundle.js', 'public/vendor/editorjs/raw.js')
    .copy('node_modules/@editorjs/simple-image/dist/bundle.js', 'public/vendor/editorjs/simple-image.js')
    .copy('node_modules/@editorjs/image/dist/bundle.js', 'public/vendor/editorjs/image.js')
    .copy('node_modules/@editorjs/checklist/dist/bundle.js', 'public/vendor/editorjs/checklist.js')
    .copy('node_modules/@editorjs/embed/dist/bundle.js', 'public/vendor/editorjs/embed.js')
    .copy('node_modules/@editorjs/delimiter/dist/bundle.js', 'public/vendor/editorjs/delimiter.js')
    .copy('node_modules/@editorjs/list/dist/bundle.js', 'public/vendor/editorjs/list.js')
    .copy('node_modules/@editorjs/quote/dist/bundle.js', 'public/vendor/editorjs/quote.js')
    .copy('node_modules/@editorjs/text-variant-tune/dist/text-variant-tune.js', 'public/vendor/editorjs/text-variant-tune.js')
    .copy('node_modules/@editorjs/attaches/dist/bundle.js', 'public/vendor/editorjs/attaches.js')
    .copy('node_modules/@editorjs/inline-code/dist/bundle.js', 'public/vendor/editorjs/inline-code.js')
    .copy('node_modules/@editorjs/code/dist/bundle.js', 'public/vendor/editorjs/code.js')
    .copy('node_modules/editorjs-codemirror/dist/editorjs-codemirror.umd.js', 'public/vendor/editorjs/codemirror.js')
    .copy('node_modules/@bomdi/codebox/dist/index.min.js', 'public/vendor/editorjs/codebox.js')
    .copy('node_modules/@calumk/editorjs-codeflask/dist/editorjs-codeflask.bundle.js', 'public/vendor/editorjs/codeflask.js')
    .copy('node_modules/@editorjs/paragraph/dist/bundle.js', 'public/vendor/editorjs/paragraph.js')
    .copy('node_modules/@editorjs/warning/dist/bundle.js', 'public/vendor/editorjs/warning.js')
    .copy('node_modules/@editorjs/marker/dist/bundle.js', 'public/vendor/editorjs/marker.js')
    .copy('node_modules/@editorjs/personality/dist/bundle.js', 'public/vendor/editorjs/personality.js')
    .copy('node_modules/editorjs-html/build/edjsHTML.browser.js', 'public/vendor/editorjs/editorjs-html.js')
    .copy('node_modules/inputmask/dist/inputmask.min.js', 'public/js/inputmask.js')
    .copy('node_modules/quill/dist/quill.min.js', 'public/js/quill.js')
    .copy('node_modules/quill/dist/quill.core.css', 'public/css/quill.core.css')
    .copy('node_modules/quill/dist/quill.snow.css', 'public/css/quill.snow.css')
    .copy('node_modules/quill/dist/quill.bubble.css', 'public/css/quill.bubble.css')
    .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}
