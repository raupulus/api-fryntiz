<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Límites de peticiones
|--------------------------------------------------------------------------
|
| Los números de aquí salen de lo que hacen los dispositivos de verdad, no de
| un valor redondo. Medido en producción (2026-08-23) sobre el monitor del
| Renogy Rover: 236 lecturas con un salto medio de 208,8 s (3,5 min) y un
| máximo de 308 s. O sea, un dispositivo de energía sube ~17 veces por hora.
|
| El caso peor no es ése, es la estación meteorológica: hay un endpoint por
| sensor (D33) y una estación con 12 sensores reportando cada minuto gasta
| 12 peticiones por minuto ella sola.
|
| Todos los límites de escritura IoT se cuentan **por token**, que es lo que
| identifica al dispositivo. Contarlos por usuario los convertía en un cupo
| compartido por todo el parque: con cinco estaciones se agotaba el límite y la
| sexta empezaba a comerse 429 (auditoría A8).
|
*/

return [

    /*
     * Escritura IoT por sensor. 60/min deja margen de 5x sobre el caso peor
     * conocido (12 sensores cada minuto) sin dejar la puerta abierta a que un
     * token robado inunde la base de datos.
     */
    'iot_store_per_minute' => (int) env('RATE_LIMIT_IOT_STORE', 60),

    /*
     * Subidas por lotes (AirFlight). Cada petición trae muchos aviones, así que
     * el límite bajo es correcto: 20/min es una cada 3 segundos, más de lo que
     * necesita un receptor dump1090 que agrupa por ciclo de barrido.
     */
    'iot_batch_per_minute' => (int) env('RATE_LIMIT_IOT_BATCH', 20),

    /*
     * Techo global de TODO el grupo `api`, incluido lo que no declara un
     * limitador propio.
     *
     * Desde Laravel 11 el grupo `api` **no trae throttle**: hay que pedirlo con
     * `throttleApi()`. Nadie lo pidió, así que catorce rutas públicas —las
     * plataformas, los contenidos, los currículums, las estaciones y los
     * aviones— y la ruta de cierre estaban sin ningún límite (auditoría
     * AR-S01). Sobre tablas de serie temporal sin índice, eso es un servidor
     * caído a coste cero para quien lo pida.
     *
     * Es un TECHO, no un límite fino: las rutas que declaran `throttle:api`,
     * `throttle:api-store` o `throttle:contact` siguen mandando en lo suyo,
     * porque el middleware de ruta corre después del de grupo y el más
     * estricto es el que corta.
     *
     * 300/min es holgado a propósito. Una web pública encadena varias llamadas
     * por página —la plataforma, sus contenidos, el detalle, las relacionadas—
     * y un visitante navegando rápido puede pasar de 60 sin ser un problema.
     * Lo que corta esto es el barrido automatizado, no la navegación.
     *
     * Se reparte por token cuando hay token y por IP cuando no: contar por IP
     * a los cacharros haría que varias estaciones de la misma casa se
     * repartieran un solo cupo (es el mismo motivo de A8).
     */
    'api_global_per_minute' => (int) env('RATE_LIMIT_API_GLOBAL', 300),

    /*
     * Ruta de cierre de la API (`ANY /api/v2/{any}`).
     *
     * Va aparte y mucho más bajo que el techo global: quien llega aquí está
     * pidiendo rutas que no existen, y eso es exactamente lo que hace el
     * escaneo automático que se lleva la mayor parte del tráfico basura de un
     * VPS. Un cliente legítimo no necesita 30 respuestas 404 por minuto.
     */
    'api_fallback_per_minute' => (int) env('RATE_LIMIT_API_FALLBACK', 30),

    /*
     * Lecturas autenticadas de la API. Por token también.
     */
    'api_per_minute' => (int) env('RATE_LIMIT_API', 60),

    /*
     * Servir imágenes redimensionadas (`/file/resize`). Va aparte y MUY por
     * encima del resto a propósito: no es una llamada de API, es una etiqueta
     * <img> de una página web. Una galería o un artículo ilustrado dispara
     * decenas de peticiones en UNA sola carga, así que con el límite de la API
     * (60/min) el visitante se quedaría sin imágenes a la segunda página.
     *
     * El freno de verdad contra el abuso no es este número, es que el ancho
     * sólo puede ser uno del catálogo y que el resultado se cachea en disco:
     * cada variante se genera una vez y las siguientes se sirven como fichero.
     * Esto es sólo el tope superior para que nadie barra el catálogo entero en
     * bucle.
     */
    'file_resize_per_minute' => (int) env('RATE_LIMIT_FILE_RESIZE', 600),

    /*
     * Login. Por IP, y bajo: es la defensa contra fuerza bruta. Depende de que
     * TRUSTED_PROXIES esté bien puesto; si no, la IP es la del proxy y este
     * límite pasa a ser un cupo global compartido por todos los visitantes.
     */
    'auth_per_minute' => (int) env('RATE_LIMIT_AUTH', 10),

    /*
     * Formulario de contacto. Por IP y por hora.
     */
    'contact_per_hour' => (int) env('RATE_LIMIT_CONTACTO', 5),

];
