<?php

declare(strict_types=1);

/**
 * Configuración de AEMET OpenData.
 *
 * Los valores de aquí NO son estimaciones: salen de `docs/apis/aemet/LIMITACIONES.md`,
 * medidos con peticiones reales. Antes de cambiar alguno, léelo.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Credenciales
    |--------------------------------------------------------------------------
    |
    | La clave es un JWT que CADUCA a los ~100 días. Va siempre en la cabecera
    | `api_key`, nunca en la query string (acabaría en los logs del servidor,
    | del proxy y en el Referer).
    |
    | `expires_at` existe para poder avisar ANTES de que caduque: una integración
    | que no lo contemple se cae sola a los tres meses de desplegarla.
    | Se renueva en https://opendata.aemet.es/centrodedescargas/altaUsuario
    |
    */
    'api_key' => env('AEMET_API_KEY', ''),
    'expires_at' => env('AEMET_API_KEY_EXPIRES_AT'),
    'warn_days_before_expiry' => 15,

    /*
    | La URL base NO lleva `/api`… salvo que aquí sí, porque los endpoints de
    | este servicio se escriben sin él. Si algún día se cambia una cosa hay que
    | cambiar la otra, o sale `/api/api/...`.
    */
    'base_url' => env('AEMET_BASE_URL', 'https://opendata.aemet.es/opendata/api'),

    'timeout_seconds' => 30,

    /*
    |--------------------------------------------------------------------------
    | Códigos por defecto
    |--------------------------------------------------------------------------
    |
    | ⚠️ Sin verificar con petición real. Un endpoint que funciona con un valor
    | no dice nada de los demás valores: hay que probar cada uno.
    |
    */
    'default_municipality' => env('AEMET_DEFAULT_MUNICIPIO', '11015'),  // Chipiona
    'default_beach' => env('AEMET_DEFAULT_PLAYA', '1101501'),
    'default_coast' => env('AEMET_DEFAULT_COSTA', '11'),
    'default_area' => env('AEMET_DEFAULT_AREA', '61'),

    /*
    |--------------------------------------------------------------------------
    | Cuota
    |--------------------------------------------------------------------------
    |
    | El límite real de AEMET no es un número de peticiones por minuto: es un
    | cubo de ~40 POR PLANTILLA DE ENDPOINT (15 en los productos pesados), que
    | además va ligado a la IP, no sólo a la clave. Generar otra API Key no
    | desbloquea un endpoint agotado, y dos entornos en el mismo servidor
    | comparten cuota.
    |
    | La cabecera `Remaining-request-endpoint` dice cuánto queda. No garantiza
    | que la siguiente petición funcione —hay un segundo límite más corto sin
    | documentar— así que se combina con espaciado y tolerancia al 429.
    |
    | El 429 NO trae `Retry-After` y la recuperación tarda MÁS DE UNA HORA.
    | Por eso el backoff es de 30 s y sólo 2 reintentos: insistir no recupera
    | nada y quema el cubo.
    |
    */
    'rate_limit' => [
        'retry_attempts' => 2,
        'retry_base_delay_ms' => 30000,
        'seconds_between_requests' => 5,
    ],

    'quota_window_seconds' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Frescura
    |--------------------------------------------------------------------------
    |
    | Hay endpoints que devuelven contenido de años atrás con un 200 impecable.
    | Por encima de estos días, se registra un aviso en el log.
    |
    */
    'max_age_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | Atribución obligatoria
    |--------------------------------------------------------------------------
    |
    | No es cortesía: la Ley 18/2015 somete al reutilizador a su régimen
    | sancionador, y la nota legal de AEMET exige citarla como fuente y mostrar
    | la fecha de última actualización del dato. Los textos son los literales de
    | `docs/apis/aemet/12-uso-legal-y-atribucion.md`, transcritos de la nota
    | legal oficial: no se reescriben.
    |
    | Mostrar la predicción de AEMET en una web es un «servicio de valor
    | añadido», y para esos la nota legal exige la mención EXPLÍCITA.
    |
    */
    'attribution' => [
        'short' => 'Fuente: AEMET',
        'long' => 'Información elaborada utilizando, entre otras, la obtenida de la Agencia Estatal de Meteorología',
        'copyright' => '© AEMET. Autorizado el uso de la información y su reproducción citando a AEMET como autora de la misma.',
        'legal_notice' => 'https://www.aemet.es/es/nota_legal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Avisos de fenómenos adversos (CAP)
    |--------------------------------------------------------------------------
    |
    | Todo lo de aquí sale de la verificación en vivo del 2026-08-26 recogida en
    | `docs/apis/aemet/04-avisos-y-riesgos.md` (56 XML de una comunidad y 252 del
    | paquete nacional). No es la especificación: es lo que llegó de verdad.
    |
    | El filtro es por CÓDIGO DE ZONA, no por nombre. El nombre viaja en
    | `areaDesc` y cambia; el código es estable y es el mismo `zona_comarcal` que
    | devuelve el maestro de municipios. Se acepta el código exacto o un prefijo:
    | `6111` son todas las zonas de Cádiz.
    |
    | Estructura del código: CCAA(2) + provincia INE(2) + comarca(2), y sufijo
    | `C` para la variante costera de la misma zona.
    |
    */
    'warnings' => [
        'area' => env('AEMET_AVISOS_AREA', '61'),  // 61 = Andalucía

        'zones' => [
            '611102',   // Campiña gaditana
            '611103',   // Litoral gaditano
            '611103C',  // Costa - Litoral gaditano
            '611104',   // Estrecho
            '611104C',  // Costa - Estrecho
            '612103',   // Litoral de Huelva
            '612103C',  // Costa - Litoral de Huelva
        ],

        /*
        | `Minor` es el nivel verde, y el Plan Meteoalerta v8 SUPRIMIÓ el nivel
        | verde: no es un aviso, es la ausencia de aviso. En el paquete nacional
        | eran 177 de 252. Además no traen `description`, `instruction`,
        | `parametro` ni `probabilidad`, así que el código que los espera falla
        | justo en los que hay que tirar.
        */
        'discard_severity' => ['Minor'],

        /*
        | `Test` son mensajes de prueba. Sólo `Actual` es operativo.
        */
        'valid_statuses' => ['Actual'],

        /*
        | Idioma del bloque `<info>` que se guarda. Cada XML trae DOS bloques,
        | `es-ES` y `en-GB`, con el MISMO aviso: recorrerlos todos lo duplica.
        */
        'language' => 'es-ES',
    ],

    /*
    |--------------------------------------------------------------------------
    | TTL de caché
    |--------------------------------------------------------------------------
    |
    | Salen del campo `periodicidad` de los metadatos de cada producto. Refrescar
    | más a menudo NO trae datos nuevos: sólo gasta cuota. Los valores anteriores
    | (10 minutos para la predicción diaria) eran 18 veces más agresivos de lo
    | que AEMET genera, sobre un cubo de 40.
    |
    */
    'cache_ttl' => [
        // "Cuatro veces al día"
        'daily_prediction' => 3 * 3600,
        // Horas preferentes de emisión; es lo más crítico, pero no cada 5 min
        'adverse_events' => 20 * 60,
        // "Dos veces al día (12:00 y 20:00) h.o.p"
        'coast' => 6 * 3600,
        // "Dos veces al día (08:00 y 20:00) UTC"
        'high_sea' => 6 * 3600,
        // "Dos veces al día"
        'prediction_beach' => 6 * 3600,
        // "Cada 1h"
        'contamination' => 3600,
        // "Cada 24 h" — y viene en UTF-8 y CSV, no en JSON ISO-8859-15
        'ozone' => 12 * 3600,
        // "Cada 24h" — hora solar verdadera, que no coincide con UTC ni con la local
        'sun_radiation' => 12 * 3600,
    ],
];
