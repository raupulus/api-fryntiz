<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Preview - {{$content->title}}</title>
</head>
<body>
<div id="app">
    <div class="container">
        <h1 class="content-title">{{$content->title}}</h1>


        @foreach($pages as $page)
            <div class="page">
                <div class="header">
                    <h2>Página {{$page->order}} - {{$page->title}}</h2>
                </div>

                <div class="page-html-paste">
                    {!! $page->content !!}
                </div>
            </div>
        @endforeach

    </div>
</div>


<style>
    body {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
    }

    #app {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
        background-color: #4b545c;
        width: 100%;
        height: 100%;
        min-height: 100vh;
    }

    .content-title {
        position: fixed;
        width: 100vW;
        top: 0;
        left: 0;
        text-align: center;
        margin: 0;
        padding: 0.5rem;
        color: #4b545c;
        background-color: #fafafa;
    }

    /** Página actual **/
    .container {
        display: flex;
        flex-direction: column;
        margin: 0 auto;
        padding: 2rem 0.5rem;
        max-width: 1400px;
        box-sizing: border-box;
    }

    .page {
        margin: 2rem auto;
        padding: 2rem;
        width: 100%;
        box-sizing: border-box;
        background-color: #fafafa;
        border-radius: 0.3rem;
        box-shadow: 3px 3px 3px black;
    }

    /** Bloque donde pego el html **/
    .page-html-paste {

    }

    h2, h3, h4, h5, h6 {
        color: #4b545c;
    }

    h2 {
        text-align: center;
        font-weight: bold;
        font-size: 1.9rem;
    }


    /************** Bloques con el contenido html por cada elemento **************/

    /** Párrafos **/

    .r-paragraph {
        font-size: 1rem;
    }

    .r-paragraph > a {
        text-decoration: none;
        color: rgba(63, 131, 248, 0.9);
        font-size: 1.1rem;
        font-weight: bold;
        font-style: italic;
    }

    .r-paragraph > b, .r-paragraph > strong {
        color: rgba(0, 0, 0, 0.8);
    }

    .r-paragraph > mark {

    }

    .inline-code {
        background-color: #2e3440;
        color: rgba(160, 228, 155, 1);
        padding: 0.3rem 0.6rem;
        border-radius: 3px;
        font-size: 1.1rem;
    }


    /** Cita **/

    .r-paragraph .r-paragraph-citation {

    }

    .r-paragraph .r-paragraph-citation cite {

    }

    /** Callout **/
    .r-paragraph.r-paragraph-call-out {
        margin: auto;
        width: 100%;
        max-width: 80%;
        box-sizing: border-box;
        color: #fafafa;
    }

    .r-paragraph.r-paragraph-call-out .r-call-out {
        display: flex;
        width: 100%;
        align-content: center;
        align-items: center;
        box-sizing: border-box;
    }

    .r-call-out-left {
        display: inline-block;
        width: 0;
        height: 0;
        border-right: 60px solid #2d3748;
        border-top: 25px solid transparent;
        border-bottom: 25px solid transparent;
        box-sizing: border-box;
        translate: 8px;
        border-radius: 10px;
    }

    .r-call-out-right {
        flex: 1;
        display: inline-block;
        padding: 1rem;
        font-size: 1.4rem;
        color: #d3d3d3;
        background: #2d3748;
        box-sizing: border-box;
        border-radius: 10px;
    }

    /** Headers **/
    .r-header {
        text-align: center;
    }

    h3.r-header {

    }

    h4.r-header {

    }

    h5.r-header {

    }

    h6.r-header {

    }

    /** Raw HTML **/
    .r-raw-html {

    }

    /** Codeblock **/
    .r-codeblock-container {
        margin: 1rem 0;
        box-sizing: border-box;
        font-family: "Courier New", monospace, Consolas, Courier;
    }

    .r-codeblock-header {
        display: flex;
        width: 100%;
        padding: 0.2rem 0.4rem;
        align-items: center;
        font-size: 1.4rem;
        color: #fafafa;
        background-color: #4b545c;
        border-top: 3px solid #000;
        border-left: 3px solid #000;
        border-right: 3px solid #000;
        border-bottom: 5px groove #000;
        border-radius: 10px 10px 0 0;
        box-sizing: border-box;
    }

    .r-codeblock-header-left {
        flex: 1;
    }

    .r-codeblock-header-center {
        flex: 2;
    }

    .r-codeblock-header-right {
        flex: 1;
        text-align: right;
    }

    .r-codeblock-header-right svg {
        cursor: pointer;
        margin-right: 1rem;
        fill: #fafafa;
        translate: 0 4px;
    }

    .r-codeblock-content {
        display: flex;
        padding: 0 3rem 0 0;
        background-color: #2e3440;
        color: rgba(160, 228, 155, 1);
        font-size: 1.1rem;
        width: 100%;
        border-right: 3px solid #000;
        box-sizing: border-box;
        border-left: 3px solid #000;
    }

    .r-codeblock-numbers {
        display: inline-block;
        padding: 0 0.5rem;
        width: 60px;
        color: #fafafa;
        background-color: #4b545c;
        text-align: right;
        box-sizing: border-box;
    }

    .r-codeblock-container .r-codeblock {
        display: inline-block;
        margin-left: 0.5rem;
        box-sizing: border-box;
    }

    .r-codeblock-footer {
        display: block;
        width: 100%;
        text-align: right;
        padding: 0.2rem 0.4rem;
        color: #4b545c;
        background-color: #eaeaea;
        border-left: 3px solid #000;
        border-right: 3px solid #000;
        border-bottom: 3px solid #000;
        border-radius: 0 0 5px 5px;
        box-sizing: border-box;
    }

    /** Warning **/
    .r-warning-container {
        background-color: #f8d7da;
        color: #721c24;
        padding: 2rem 1rem;
        border: 1px solid #f5c6cb;
        border-radius: .25rem;
        width: 80%;
        margin: auto;
    }

    .r-warning-wrapper {
        text-align: center;
    }

    .r-warning-title {
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .r-warning-body {
        font-size: 1rem;
        font-weight: 400;
    }

    .r-warning-btn-close {
        position: absolute;
        top: 3px;
        right: 3px;
        padding: 3px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 3px;
    }


    /** Adjuntos **/

    .r-attaches-container {

    }

    .r-attaches-img {

    }

    .r-attaches-info {

    }

    .r-attaches-info .r-attaches-info-originalname {

    }

    .r-attaches-download {

    }

    .r-attaches-download .r-attaches-download-link {

    }


    /** Cita Blockquote **/

    .r-blockquote {
        width: 100%;
        max-width: 400px;
        margin: auto;
        box-sizing: border-box;
        font-size: 1.4rem;
        line-height: 1.4;
        font-style: italic;
        word-break: break-all;
    }

    .r-blockquote {
        margin-top: 1.2rem;
    }

    .r-blockquote-icon {

    }

    .r-blockquote-icon > svg {
        width: 60px;
    }

    .r-blockquote.r-blockquote-alignment-left .r-blockquote-title {
        text-align: left;
    }

    .r-blockquote.r-blockquote-alignment-center .r-blockquote-title {
        text-align: center;
    }

    .r-blockquote.r-blockquote-alignment-right .r-blockquote-title {
        text-align: right;
    }

    .r-blockquote-title {

    }

    .r-blockquote-caption {
        text-align: right;
        font-size: 1.2rem;
        font-weight: bold;
    }

    /** Listas **/

    .r-list-container {
        margin: 1rem 0;
        padding: 0;
        width: 100%;
        box-sizing: border-box;
    }

    .r-list-box {
        margin: auto;
        max-width: 500px;
        box-sizing: border-box;
    }

    .r-list-item {
        display: flex;
        margin: 0.3rem 0;
        padding: 0.4rem;
        align-items: center;
    }

    .r-list-item-icon {
        padding: 0.4rem;
        margin-right: 0.3rem;
        font-size: 2rem;
        font-weight: bold;
        background: #0071a7;
        border-radius: 0 8px 0 8px;
        color: #eee;
    }

    .r-list-item-content {
        flex: 1;
        padding: 0.4rem;
        min-height: 50px;
        background-color: #2d3748;
        border: 1px solid #1a202c;
        border-radius: 5px;
        color: #d3d3d3;
        font-size: 1.1rem;
        word-break: break-all;
    }

    /** Checkbox **/
    .r-checkbox-container {
        margin: 1rem 0;
        padding: 0;
        width: 100%;
        box-sizing: border-box;
    }

    .r-checkbox-box {
        margin: auto;
        max-width: 500px;
        box-sizing: border-box;
    }

    .r-checkbox-item {
        display: flex;
        margin: 0.3rem 0;
        padding: 0.6rem;
        align-items: center;
        background-color: #eee;
    }

    .r-checkbox-item-icon {
        margin-right: 0.3rem;
        font-size: 2rem;
        font-weight: bold;
    }

    .r-checkbox-item-content {
        flex: 1;
        font-size: 1.1rem;
        word-break: break-all;
    }

    /** Imágenes **/

    .r-image-container {

    }

    .r-image-container .r-image-container-with-border {

    }

    .r-image-container .r-image-container-stretched {

    }

    .r-image-container .r-image-container-withBackground {

    }

    .r-image-box {

    }

    .r-image-figure {

    }

    .r-image-img {

    }

    .r-image-caption {

    }

    /** Delimitador **/
    .r-delimiter-container {

    }

    .r-delimiter {

    }

    .r-delimiter-content {

    }

    /** Alerts **/

    .r-alert-container {
        position: relative;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .r-alert-align-left {
        text-align: left;
    }

    .r-alert-align-right {
        text-align: right;
    }

    .r-alert-align-center {
        text-align: center;
    }

    .r-alert-type-primary {
        background-color: #ebf8ff;
        border: 1px solid #4299e1;
        color: #2b6cb0;
    }

    .r-alert-type-secondary {
        background-color: #f7fafc;
        border: 1px solid #cbd5e0;
        color: #222731;
    }

    .r-alert-type-info {
        background-color: #e6fdff;
        border: 1px solid #4cd4ce;
        color: #00727c;
    }

    .r-alert-type-success {
        background-color: #f0fff4;
        border: 1px solid #68d391;
        color: #2f855a;
    }

    .r-alert-type-warning {
        background-color: #fffaf0;
        border: 1px solid #ed8936;
        color: #c05621;
    }

    .r-alert-type-danger {
        background-color: #fff5f5;
        border: 1px solid #fc8181;
        color: #c53030;
    }

    .r-alert-type-light {
        background-color: #fff;
        border: 1px solid #edf2f7;
        color: #1a202c;
    }

    .r-alert-type-dark {
        background-color: #2d3748;
        border: 1px solid #1a202c;
        color: #d3d3d3;
    }

    /** Web Preview **/
    .r-web-preview-container {

    }

    .r-web-preview-box {

    }

    .r-web-preview-link {

    }

    .r-web-preview-image {

    }

    .r-web-preview-title {

    }

    .r-web-preview-description {

    }

    .r-web-preview-anchor {

    }

    /** Reproductor de contenido embebido **/

    .r-embed-container {

    }

    .r-embed-box {

    }

    .r-embed-title {

    }

    .r-embed-box-iframe {

    }

    .r-embed-iframe {

    }

    /** Tablas **/
    .r-table-container {

    }

    .r-table {

    }

</style>

</body>
</html>
