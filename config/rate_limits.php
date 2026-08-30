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
     * Lecturas autenticadas de la API. Por token también.
     */
    'api_per_minute' => (int) env('RATE_LIMIT_API', 60),

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
