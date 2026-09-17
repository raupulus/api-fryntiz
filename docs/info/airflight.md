# Módulo: Registro de Vuelos (AirFlight)

Módulo IoT para detectar y registrar aviones mediante receptor ADS-B, almacenando datos de vuelo, telemetría y rutas históricas.

## Unidades de `airflight_routes` (contrato definitivo, 2026-09-09)

Tabla cerrada con el propietario del capturador (`dump1090-to-db`, fuera de
este repo) sobre lo que de verdad sube a `POST /airflight/aircrafts` /
`POST /airflight/aircrafts/batch`. **Es la única fuente de verdad de este
proyecto** — no un valor de un dato histórico, no un comentario de código
sin contrastar, no lo que "suene lógico" para ADS-B. Si algo del resto de
este documento (o del código) la contradice, manda esta tabla y hay que
corregir lo otro.

| Campo | Tipo | Unidad | Rango | Ejemplo |
|---|---|---|---|---|
| `icao` | string | — | 6 hex | `"4ca61f"` |
| `registration` | string\|null | — | matrícula, opcional | `"EC-NBA"` |
| `aircraft_type` | string\|null | — | tipo ICAO de aeronave, opcional | `"A320"` |
| `category` | string\|null | — | categoría de emisor ADS-B, opcional | `"A3"` |
| `wtc` | string\|null | — | Wake Turbulence Category OACI, opcional | `"M"` |
| `aircraft_desc` | string\|null | — | descripción OACI fuselaje/propulsión, opcional | `"L2J"` |
| `flight` | string\|null | — | callsign sin espacios | `"RYR11CL"` |
| `squawk` | string\|null | — | 4 dígitos octales | `"7105"` |
| `lat` | float\|null | grados decimales WGS84 (°) | -90 a 90 | `36.623623` |
| `lon` | float\|null | grados decimales WGS84 (°) | -180 a 180 | `-5.885049` |
| `altitude` | float\|null | **metros (m)** | 0 a 60000 | `11277.0` |
| `speed` | float\|null | **metros por segundo (m/s)** | 0 a 1000 | `232.2` |
| `track` | int\|null | grados sexagesimales (°) | 0 a 360 | `214` |
| `vert_rate` | float\|null | **metros por segundo (m/s)** | -100 a 100 | `-21.1` |
| `messages` | int\|null | conteo de tramas | ≥ 0 | `86` |
| `rssi` | float\|null | dBFS | -100 a 0 | `-24.7` |
| `nic` | int\|null | — | 0 a 11 | `8` |
| `rc` | float\|null | **metros (m)** | ≥ 0 | `185.2` |
| `emergency` | string\|null | cadena de estado | `none`, `general`, `lifeguard`... | `"general"` |
| `seen` / `seen_pos` | — | — | no se persisten (siempre `null` en el sondeo real) | `null` |

### Contrato cerrado: todo lo que sube la Raspberry se valida y se guarda (2026-09-15)

Comprobado campo por campo contra el payload real que construye
`upload_data_to_api.php::getDbData()` en `dump1090-to-db` (commits `94175ca`
"incluye category en el payload" y `328b06b` "resolución local y subida de
matrícula/tipo"), no de memoria. `wtc`/`aircraft_desc`/`nic`/`rc` (2026-09-15,
segunda ronda) siguen el mismo patrón:

| Campo que manda la Raspberry | ¿Validado en `StoreAirFlightRequest`/`StoreBatchAirFlightRequest`? | ¿Dónde se guarda? |
|---|---|---|
| `icao` | Sí | `airflight_airplanes.icao` |
| `registration` | Sí | `airflight_airplanes.registration` |
| `aircraft_type` | Sí | `airflight_airplanes.aircraft_type` |
| `category` | Sí | `airflight_airplanes.category` |
| `wtc` | Sí | `airflight_airplanes.wtc` |
| `aircraft_desc` | Sí | `airflight_airplanes.aircraft_desc` |
| `flight`, `squawk`, `lat`, `lon`, `altitude`, `vert_rate`, `track`, `speed`, `messages`, `rssi`, `nic`, `rc`, `emergency` | Sí | `airflight_routes` (una fila por sondeo, vía `routeFieldsOnly()`) |
| `seen`, `seen_pos` | Sí (validados, pero no se persisten a propósito — ver tabla de arriba) | — |

`country`/`flag`/`route_last_at` no están en esta lista porque la Raspberry
**no los manda**: se calculan en `AirFlightService::addAircraft()` a partir
del ICAO y del propio historial de rutas, no de nada que llegue en el
sondeo (ver más abajo). No queda ningún campo del payload real sin validar
ni sin destino.

El receptor (Raspberry Pi + `dump1090-to-db`) decodifica Mode S con
`dump1090`, que —como cualquier decodificador ADS-B— trabaja internamente en
**pies**, **nudos** y **pies/minuto** (el estándar real de la especificación
ADS-B). Pero esa conversión a metros/m·s **ya la hace el capturador antes de
subir**, no esta API: por eso `altitude`/`speed`/`vert_rate` llegan aquí en
SI, aunque su origen sea ft/kt/ft·min. Esta API nunca ve el dato en pies o
nudos; sólo el capturador lo ve, y sólo un instante, antes de convertirlo.

Confirmado con un caso real (2026-09-09): un `vert_rate` que en bruto de
dump1090 era **-1267.906126181 ft/min**, tras la conversión del capturador,
sube como **≈ -6.44 m/s** (`-1267.906126181 × 0.3048 / 60`) — un descenso
normal, dentro del rango -100 a 100 de la tabla de arriba.

### Matrícula y tipo de aeronave: los resuelve el receptor, no esta API (2026-09-14)

`registration`/`aircraft_type` son **opcionales** y llegan ya resueltos: el
receptor (`dump1090-fa`+`skyaware`) trae instalada de fábrica una base local
de matrículas (`/usr/share/skyaware/html/db/`, snapshot fijo de VRS
`BasicAircraftLookup`, ya no se actualiza en origen), y el capturador
(`dump1090-to-db`, fuera de este repo) la consulta por ICAO antes de subir. Si
no encuentra el ICAO en esa base, manda `null` — esta API nunca busca nada,
solo guarda lo que llegue. Decisión completa y alternativas descartadas (API
externa, servir los 250.000 ficheros del dataset) en
[`docs/future/archived/airflight-registro-de-matriculas.md`](../future/archived/airflight-registro-de-matriculas.md)
(archivada: la funcionalidad ya está resuelta y en producción).

`AirFlightService::addAircraft()` los guarda tanto en alta como en avión ya
existente (a diferencia de `user_id`/`hardware_device_id`, que solo se fijan
al crear): son un dato fijo del aparato, no de "quién lo vio primero", y
pueden llegar en cualquier sondeo. Igual que con la posición
(`routeFieldsOnly()`), un sondeo sin estos campos nunca borra un valor ya
guardado — solo se escribe si llega con valor.

**Alcance de este cambio**: solo lo que se suba a partir de ahora. Los
aviones ya guardados se quedan con `registration`/`aircraft_type` a `null`
hasta que el receptor vuelva a reportarlos — no ha corrido ningún backfill.

### `category`, `route_last_at`, `country`, `flag`: se dejan de depender de comandos manuales (2026-09-15)

Auditoría real (dump de producción, 2026-09-15) encontró que la mayoría de
aviones recientes tenían `country`, `category`, `route_last_at`, `flag`,
`registration` y `aircraft_type` a `null` a la vez. Causas distintas, todas
arregladas:

- **`category`** (categoría de emisor ADS-B, ej. `"A3"`, decodificada del
  propio Mode S — no depende de ninguna base externa) sí la manda el
  capturador, pero `StoreAirFlightRequest`/`StoreBatchAirFlightRequest` no la
  declaraban en `rules()`: `->validated()` la descartaba en silencio antes de
  llegar a `AirFlightService::addAircraft()`. **Arreglado**: ya se valida y se
  guarda, mismo patrón que `registration`/`aircraft_type` (solo si llega con
  valor, nunca borra uno ya guardado). El dato de lo que pasó **antes** de
  este cambio no se puede recuperar: se descartaba antes de guardarse en
  ningún sitio.
- **`route_last_at`** ("el momento del último registro con ruta válida") no lo
  mantenía ningún código — el único método que lo leía,
  `AirFlightAirPlane::getRecentsAircrafts()`, no tiene ningún caller en toda
  la app. **Arreglado**: `addAircraft()` lo actualiza ahora en cada sondeo con
  posición real (`lat`/`lon` no nulos), igual que `latestPosition` frente a
  `latestRoute`. A diferencia de `category`, esto sí se pudo recuperar para
  lo ya existente: `airflight_routes` ya tenía el historial completo, así que
  un `UPDATE` de una vez (`MAX(seen_at)` con posición real, por avión) rellenó
  route_last_at en 5175 aviones sin tocar la Raspberry para nada (aplicado en
  producción el 2026-09-15).
- **`country`/`flag`** dependían solo de `php artisan airflight:fix`
  (`app/Console/Commands/AirflightFixCommand.php`), que calcula ambos a
  partir del rango ICAO (`AirFlightAirPlane::searchHex()`) — **no** de nada
  que mande el receptor — y no estaba programado en ningún sitio
  (`routes/console.php`): el último avión con `country` en el dump auditado
  era del 2026-09-02. **Arreglado**: `addAircraft()` los calcula ahora en
  vivo con el mismo `searchHex()`, solo si están a `null`, sin esperar a
  ningún comando. El comando sigue existiendo para el backfill puntual de lo
  ya existente (o por si hiciera falta corregir algo a mano), pero ya no hace
  falta programarlo: nada nuevo va a depender de él.

### `airflight:fix` dejaba aviones resolubles sin corregir (2026-09-16)

Auditoría del dump de producción del 2026-09-16 (ya con los tres fixes de
arriba desplegados) encontró aviones con `country`/`flag` a `null` cuyo ICAO
sí caía dentro de un rango de `AirFlightAirPlane::FLAGS` (por ejemplo
`40653d`, matrícula `G-EZGN`, Reino Unido). No podía ser el cálculo en vivo
—esos aviones llevaban días sin verse, ninguna subida nueva iba a disparar
`addAircraft()`—, así que el sospechoso era el propio comando de backfill.

Confirmado ejecutándolo contra una copia local: de 204 aviones sin país,
solo corrigió 21 y dejó 183, de los cuales 23 sí eran resolubles con
`searchHex()`. Dos bugs en
[`AirflightFixCommand::fixAirplaneFlagsAndCountries()`](../../app/Console/Commands/AirflightFixCommand.php):

- El bucle paginaba con `$position < $airflightsCount`, un total calculado
  **una sola vez** al principio, y volvía a lanzar la misma query
  `whereNull('country')->orWhereNull('flag')->limit(100)` sin `ORDER BY` ni
  exclusión de lo ya intentado. Los ICAO que `searchHex()` nunca va a
  resolver (rangos reservados/basura, ej. `000403`) no salen del `WHERE
  NULL` al fallar, así que se quedan ocupando la misma página en cada vuelta
  y le roban recorrido a filas que sí eran corregibles, sin avisar de que se
  quedaron fuera. Arreglado excluyendo por `id` lo ya intentado y saliendo
  del bucle cuando una página vuelve vacía, en vez de fiarse de un contador
  fijo.
- `searchHex()` convertía el ICAO con `base_convert('0x'.$icao, 16, 10)`,
  deprecated en PHP 8.4 (el prefijo `'0x'` no es un dígito hexadecimal
  válido para `base_convert`). Cambiado a `hexdec($icao)`, mismo resultado
  sin el aviso.

Test de regresión:
[`AirflightFixCommandTest`](../../tests/Feature/Console/AirflightFixCommandTest.php)
(más de una página de ICAO irresolubles por delante de uno resoluble).
Pendiente: volver a ejecutar `php artisan airflight:fix` en producción tras
desplegar esto, para los ~23 aviones que se quedaron atrás la vez anterior.

### Corrección sobre una confusión propia (2026-09-08 → 09)

Un commit de esta misma fecha llegó a la conclusión contraria —que
`altitude`/`speed` se ingerían en pies/nudos— apoyándose en el máximo
histórico de la tabla (~39000 y ~1347). Ese razonamiento estaba mal: esos
máximos eran precisamente **datos corruptos**, el mismo tipo de lectura que
la auditoría AD-T01 ya documentaba como fallo de un receptor de pruebas (ver
el comentario en `StoreAirFlightRequest.php`) — la validación (`max:60000`
en metros, `max:1000` en m/s) existe justo para descartar ese ruido, no para
insinuar que la unidad real fuera otra. El comentario de la migración
(`airflight_routes.altitude`/`.speed`, "metros"/"metros por segundos")
**siempre estuvo bien**; no se toca.

**Lección, para no repetirla:** un máximo o un valor puntual en datos
históricos no es un dato fiable de la unidad si la validación ya existe
precisamente para atrapar decodificaciones corruptas — un dato corrupto
puede, por azar, "parecer" plausible en la unidad equivocada. Ante la duda
sobre una unidad: **primero la tabla de este apartado**; si algo no está en
ella, se pregunta a quien manda los datos (el propietario del capturador),
nunca se infiere de una tabla que puede tener ruido.

## Regla: `airflight_routes` guarda TODO, lo que mira al mapa filtra por posición

Decisión consciente (2026-09-08): se sigue guardando en `airflight_routes`
**cualquier** mensaje recibido, tenga o no `lat`/`lon`. Mode S manda
identificación, altitud y posición en mensajes distintos — un mensaje sin
posición no es basura, es telemetría real (squawk, altitud, contador de
mensajes) entre una posición GPS y la siguiente, y hoy alimenta "Aviones
(total)/Mensajes" del mapa y las columnas squawk/altitud/velocidad de
"Aviones detectados". No se ha tocado la ingesta (`AirFlightService::addAircraft()`)
ni el capturador (`dump1090-to-db`, fuera de este repo).

**Lo que sí es una regla dura: nada que se dibuje en un mapa (marcador o
traza) puede venir de una fila sin `lat`/`lon`.** Si añades un endpoint o
vista nueva que pinte una posición, sigue el mismo patrón que ya usan
`getActiveAircrafts()`, `latestPosition()` y `trail()`:

1. Para "¿este avión tiene una posición vigente?" — filtra por la existencia
   de una ruta con `lat`/`lon` no nulos dentro de la ventana de tiempo que
   toque (`whereHas('routes', fn ($q) => $q->whereNotNull('lat')->whereNotNull('lon')->where('seen_at', '>=', ...))`).
2. Para "¿cuál es su posición actual?" — usa `AirFlightAirPlane::latestPosition()`,
   **no** `latestRoute()`. `latestRoute()` es el último mensaje sea cual sea
   su contenido; si ese mensaje no trae posición, sus `lat`/`lon` son `null`
   aunque el avión tenga una posición real de hace un minuto. Éste fue
   exactamente el bug: el mapa pintaba aviones "parados" en `(0, 0)` porque
   `AirFlightResource` leía `lat`/`lon` de `latestRoute`.
3. **Trampa de Eloquent, no la repitas:** encadenar `whereNotNull('lat')` /
   `whereNotNull('lon')` ANTES de `latestOfMany()` no sirve, aunque parezca
   lo lógico y sea la primera versión que a cualquiera —humano o IA— se le
   ocurre escribir. `ofMany()` elige "el más reciente" con una subconsulta de
   agregación (`MAX(seen_at)`) que **no incluye** los `where` encadenados
   después: esos sólo se aplican a la fila final ya elegida. Si el mensaje
   más reciente de verdad no tiene posición, la subconsulta se queda con su
   `seen_at`, esa fila no pasa el `whereNotNull` de fuera, y la relación
   entera devuelve `null` — no cae a la posición anterior, que es lo que se
   necesita. Probado y confirmado con SQL real: da `NULL` incluso con una
   posición válida cinco minutos antes. La forma correcta es meter el filtro
   **dentro** del closure de `ofMany()`, que sí forma parte de esa
   subconsulta:
   ```php
   public function latestPosition(): HasOne
   {
       return $this->hasOne(AirFlightRoute::class, 'airplane_id')
           ->ofMany(['seen_at' => 'max'], function ($query) {
               $query->whereNotNull('lat')->whereNotNull('lon');
           });
   }
   ```
4. Para una **traza** (varios puntos, no solo el último) usa `trail()`, que
   ya acota por tiempo, alcance plausible del receptor y `lat`/`lon` no
   nulos — ver más abajo.

### Decisión pendiente: limpieza de filas sin posición

Si `airflight_routes` crece demasiado por las filas sin `lat`/`lon`, se
pueden borrar sin tocar nada más — no las lee nadie salvo por su
`seen_at`/`squawk`/`altitude`/etc., que dejarían de estar disponibles para
esas filas concretas, pero ninguna relación de posición (`latestPosition`,
`trail`, el filtro de `getActiveAircrafts()`) depende de que existan:

```sql
DELETE FROM airflight_routes WHERE lat IS NULL OR lon IS NULL;
```

En producción, trocear el borrado (por lotes de `id`, con `LIMIT`, o por
rango de fecha) para no bloquear la tabla. Es una decisión de volumen, no de
código: no se ha automatizado ni programado — se hace a mano el día que haga
falta.

## Ingesta: fusionar por `messages`, no duplicar por sondeo

`AirFlightService::addAircraft()` (método privado `mergeOrCreateRoute()`)
fusiona en vez de insertar cuando el sondeo trae el mismo contador
`messages` que una ruta ya guardada del mismo avión dentro de la última
hora.

**Por qué**: el SDR sube `messages` cada vez que decodifica un mensaje Mode S
nuevo de ese avión. Si dos sondeos del mismo avión llegan con el mismo
`messages`, no ha llegado ningún mensaje nuevo entre uno y otro: es la misma
detección, que puede traer campos distintos ya decodificados (posición,
identificación y altitud van en mensajes Mode S separados, así que un
sondeo puede completar lo que el anterior no traía). Sin esto, cada campo
que se iba decodificando por separado generaba su propia fila con casi todo
a `null`.

**Cómo fusiona**: busca una ruta del mismo avión con ese `messages` y
`seen_at` dentro de la última hora; si existe, hace `fill($path)` +
`save()`. `$path` (`routeFieldsOnly()`) ya viene sin valores nulos, así que
rellenar con él nunca borra un dato existente con uno vacío — sólo añade o
sobrescribe los campos que sí traen valor nuevo. `seen_at` no se toca en la
fusión: sigue siendo el momento en que se vio esa detección por primera vez,
no el de la última actualización de campos.

Si el sondeo no trae `messages` (algunos receptores podrían no mandarlo), no
hay forma de aplicar esta regla y se crea una fila nueva, como antes.

## "Aviones detectados": el último valor CONOCIDO de cada campo, no la última fila

`AirFlightController::index()` (vista) y `::detected()` (JSON del sondeo)
usan `AirFlightService::getDetectedQuery()` en vez de
`AirFlightAirPlane::with('latestRoute')`.

**Por qué `latestRoute` no vale aquí** (aunque sí valga para el mapa, ver
arriba): Mode S manda cada dato en un mensaje distinto, así que la última
ruta de un avión casi siempre trae uno o dos campos y el resto a `null`. Con
`latestRoute` la tabla salía casi entera con "-": si la última ruta sólo
traía `squawk`, la altitud/velocidad/posición de la ruta anterior —dentro de
la misma última hora— simplemente no se mostraban, aunque existieran.

**Cómo agrega**: `getDetectedQuery()` hace un `JOIN` de `airflight_airplanes`
con `airflight_routes` acotado a la ventana, agrupa por avión, y para cada
columna hace:

```sql
(array_agg(col ORDER BY seen_at DESC) FILTER (WHERE col IS NOT NULL))[1]
```

el idiom de PostgreSQL para "último valor no nulo por grupo": agrega la
columna en orden descendente de fecha, descarta los `null` antes de agregar,
y coge el primer elemento del array resultante — el más reciente de los que
sí tienen dato. Cada campo de la tabla (`flight`, `squawk`, `altitude`,
`speed`, `track`, `lat`, `lon`) puede así venir de una fila distinta dentro
de la misma ventana, que es justo lo que hace falta.

Devuelve un `Illuminate\Database\Query\Builder` (no Eloquent, es un `JOIN` +
`GROUP BY` con columnas agregadas) para que cada llamante decida
`->paginate()` (la vista) o `->limit()->get()` (el JSON del sondeo). La vista
Blade lee `$plane->flight`, `$plane->altitude`, etc. directamente — ya no hay
relación `latestRoute` que cargar, los campos vienen planos en la fila.

Con la fusión por `messages` de más arriba, este caso debería ir siendo cada
vez menos frecuente (menos filas nuevas con un único campo cada una), pero
la agregación se queda: los sondeos de dos receptores distintos, o dos
mensajes Mode S que de verdad se decodificaron por separado, van a seguir
repartiendo datos entre varias filas.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/AirFlight/AirFlightAirPlane.php` | `airflight_airplanes` | Aviones únicos detectados |
| `app/Models/AirFlight/AirFlightRoute.php` | `airflight_routes` | Puntos de telemetría de cada avión |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/AirFlight/V2/AirFlightController.php` | API V2 | Listar, registrar, batch |
| `app/Http/Controllers/AirFlight/AirFlightController.php` | Web | Frontend público |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/AirFlight/AirFlightService.php` | Lógica: addAircraft (fusiona por `messages`, ver abajo), addAircraftBatch, getActiveAircrafts, getDetectedQuery (agregación por campo, ver abajo), getAircraftHistory |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/AirFlight/AirFlightResource.php` | Resource JSON vuelo |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/AirFlight/V2/StoreAirFlightRequest.php` | Validación store individual |
| `app/Http/Requests/Api/AirFlight/V2/StoreBatchAirFlightRequest.php` | Validación store batch (max 500) |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/AirFlightPolicy.php` | Aeronaves: catálogo de lectura pública, escritura sólo administrador |
| `app/Policies/AirFlightRoutePolicy.php` | Rutas guardadas: lectura pública, escritura del dueño o administrador |
| `app/Console/Commands/AirflightFixCommand.php` | Comando corrección datos |
| `app/Console/Commands/AirFlightRemoveDuplicateRoutesCommand.php` | `airflight:remove_duplicate_routes` — borra subidas duplicadas en `airflight_routes` (ver detalle más abajo) |
| `app/Filament/Admin/Resources/AirFlight/AirFlightAirPlanes/AirFlightAirPlaneResource.php` | Panel Admin, sólo lectura (ver [Configuración Filament](#configuración-filament)) |
| `app/Filament/Admin/Resources/AirFlight/AirFlightRoutes/AirFlightRouteResource.php` | Panel Admin, sólo lectura (ver [Configuración Filament](#configuración-filament)) |

## Configuración Filament

Panel **Admin**, cluster **AirFlight**. Ambos recursos son **sólo lectura**:
aviones y rutas se suben exclusivamente por la API (ver [Rutas API
V2](#rutas-api-v2)), así que un admin nunca debería poder crearlos ni
editarlos a mano — divergiría de lo que reporta el propio receptor ADS-B.

- **`canCreate()`/`canEdit()` devuelven `false`** en los dos Resources: sin
  botón "Nuevo", sin acción de fila para editar, y la URL directa `.../edit`
  da 403 (`EditRecord::mount()` lo comprueba con `abort_unless`). Las páginas
  `Create*`/`Edit*` se han eliminado, no sólo ocultado.
- La acción de fila es **`ViewAction`**, no `EditAction`: se puede inspeccionar
  un registro, pero no guardar cambios en él.
- **Borrar sigue permitido** (`DeleteBulkAction`): esto es sólo-lectura de
  contenido, no un archivo inmutable. Las Policies (`AirFlightPolicy`,
  `AirFlightRoutePolicy`), compartidas con la autorización de la ingesta por
  API, no se han tocado — el bloqueo de creación/edición vive sólo en el
  Resource de Filament.
- **`AirFlightAirPlaneResource`**: la columna de bandera usa
  `url_flag` (accessor del modelo que resuelve
  `asset('resources/airflight/flags-tiny/'.$flag)`, con `blank.png` de
  fallback), no la columna `flag` cruda — antes apuntaba directamente a
  `flag` y las banderas salían rotas porque `ImageColumn` no sabe resolver un
  nombre de fichero suelto contra el disco correcto sin ese accessor.
- **`AirFlightRouteResource`**: la tabla ordena por defecto `seen_at` **desc**
  (`->defaultSort('seen_at', 'desc')`), para que las rutas vistas más
  recientemente aparezcan arriba al entrar.

## Campos del modelo AirFlightAirPlane

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `icao` | string(10) | Código ICAO del avión (identificador único transponder) |
| `registration` | string\|null | Matrícula, resuelta por el receptor (ver más arriba) |
| `aircraft_type` | string(10)\|null | Tipo ICAO de aeronave, resuelto por el receptor (ver más arriba) |
| `category` | string\|null | Categoría de emisor ADS-B, decodificada del Mode S (ver más arriba) |
| `wtc` | string(1)\|null | Wake Turbulence Category OACI (L/M/H/J), resuelta por el receptor |
| `aircraft_desc` | string(5)\|null | Descripción OACI de fuselaje/propulsión (ej. L2J), resuelta por el receptor |
| `seen_last_at` | timestamp | Última vez detectado (cualquier sondeo) |
| `seen_first_at` | timestamp | Primera vez detectado |
| `route_last_at` | timestamp\|null | Último sondeo con posición real (ver más arriba) |

### `trail()` — traza para la línea de vuelo del mapa

Acotada a la **última hora** y a un radio plausible de ~300 km alrededor del
receptor (`AirFlightAirPlane::RECEIVER_LAT/LON/RANGE_DEGREES`). Sin estos dos
límites, un ICAO visto en dos sobrevuelos de días distintos —o con una sola
lectura mal decodificada— quedaba unido por una línea recta como si fuera un
único vuelo continuo: el origen de la traza no correspondía a ninguna
posición realmente recibida en esa pasada (se veían líneas saliendo de África
central hacia Chipiona). El frontend (`planeObject.js::seedTrail`) sólo
dibuja lo que le llega en `trail`; el filtrado vive en el backend.

## Campos del modelo AirFlightRoute

> Unidades: ver la tabla definitiva en
> ["Unidades de `airflight_routes`"](#unidades-de-airflight_routes-contrato-definitivo-2026-09-09)
> al principio de este documento. Resumen rápido aquí, no la repitas de
> memoria si tienes dudas — consulta la tabla de arriba.

| Campo | Tipo | Unidad | Descripción |
|-------|------|--------|-------------|
| `id` | bigint | — | PK |
| `airplane_id` | int | — | FK → `airflight_airplanes.id` |
| `hardware_device_id` | int | — | FK → `hardware_devices.id` — receptor |
| `user_id` | int | — | FK → `users.id` — propietario del receptor |
| `squawk` | string(10) | — | Código squawk (transponder) |
| `flight` | string(20) | — | Número de vuelo |
| `lat` | decimal | grados (°) | Latitud (-90 a 90) |
| `lon` | decimal | grados (°) | Longitud (-180 a 180) |
| `altitude` | decimal | **metros (m)** | Altitud (0 a 60000) |
| `vert_rate` | decimal | **m/s** | Velocidad vertical (-100 a 100) |
| `track` | decimal | grados (°) | Rumbo (0-360°) |
| `speed` | decimal | **m/s** | Velocidad (0 a 1000) |
| `seen_at` | timestamp | — | Momento de detección |
| `messages` | int | — | Número de mensajes recibidos (≥0) |
| `rssi` | decimal | dBFS | Intensidad de señal (-100 a 0) |
| `nic` | int\|null | — | Navigation Integrity Category (0-11), fiabilidad de `lat`/`lon` de esta fila |
| `rc` | decimal\|null | **metros (m)** | Radius of Containment, de la misma posición que `nic` |

## Relaciones

- `AirFlightAirPlane` → `HasMany` → `AirFlightRoute` (vía `airplane_id`)
- `AirFlightRoute` → `BelongsTo` → `AirFlightAirPlane` (vía `airplane_id`)
- `AirFlightRoute` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)
- `AirFlightRoute` → `BelongsTo` → `User` (vía `user_id`)

## Rutas API V2

**Todas exigen token desde el 2026-09-06**, lecturas incluidas.

| Método | Ruta | Auth | Throttle |
|--------|------|------|----------|
| GET | `/api/v2/airflight/aircrafts` | `ability:airflight:read` | api |
| GET | `/api/v2/airflight/receiver` | `ability:airflight:read` | api |
| POST | `/api/v2/airflight/aircrafts` | `ability:airflight:write` | api-store |
| POST | `/api/v2/airflight/aircrafts/batch` | `ability:airflight:write` | api-store-batch |

`GET /aircrafts` sin parámetros da los vistos en los últimos 10 minutos;
`?minutes=` cambia la ventana y `?from=&to=` da el historial paginado
(absorbió el antiguo `/history`). `/db/{bkey}` ya no existe.

La rama `?from=&to=` (historial) **no** filtra por posición a propósito: es
para integraciones que quieren ver todo lo detectado en un rango de fechas,
posición incluida o no. La regla de "sólo posición real" (ver arriba) es
para lo que se va a **pintar en un mapa** — la rama sin fechas (mapa en
vivo) sí la aplica vía `getActiveAircrafts()`.

Los dos `POST` admiten una clave opcional `hardware_device_info` con el estado
del receptor (batería, temperatura, uptime…), aplicable sólo si la petición trae
también `hardware_device_id` (aquí es opcional: no todos los receptores lo
mandan).

> **Las lecturas eran públicas hasta el 2026-09-06.** Lo eran porque el mapa de
> `/airflight` las llamaba desde el navegador, y eso dejaba la ability
> `airflight:read` sin nada que proteger. El mapa se sirve ahora desde el bloque
> **web** (ver abajo) y la API pide token. Un cliente que leyera sin token
> necesita emitir uno con `airflight:read`.

> Contrato exacto en [`docs/info/api/v2/airflight.md`](api/v2/airflight.md).

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/airflight` | Mapa interactivo de aviones detectados + tabla |
| `/airflight/aircrafts` | **JSON** de los aviones activos para el mapa, cacheado 10 s. `?minutes=` acota la ventana |
| ↳ criterio de "activo" | `AirFlightService::getActiveAircrafts()`: avión con una **ruta** con posición dentro de la ventana — no basta con `seen_last_at` reciente. Detalle y por qué, en la sección [Regla: `airflight_routes` guarda TODO](#regla-airflight_routes-guarda-todo-lo-que-mira-al-mapa-filtra-por-posición) al principio de este documento |
| `/airflight/receiver` | **JSON** con el centro del mapa y el intervalo de refresco |
| `/airflight/detected` | **JSON** con los hasta 20 aviones vistos en la última hora, cacheado 20 s |

Los tres **no piden token**: son los datos de una página propia, no una
integración. El mapa los consumía antes desde `API_URL/v2/airflight/*`, lo que
obligaba a dejar esa parte de la API abierta a cualquiera.

Los tres llevan además `same-origin` (`App\Http\Middleware\
EnsureRequestIsSameOrigin`, 2026-09-15) y `throttle:public-widget`: mismo
criterio y mismos límites que el widget de `weather-station.md`, para el mismo
motivo — sin token, la única señal de quién llama es de dónde dice venir la
petición.

`/airflight/detected` alimenta la tabla "Aviones detectados (última hora)" de
la propia vista: la primera tanda la pinta el servidor en el HTML (sin
petición extra al cargar) y, sólo cuando la página termina de cargar del todo
y se está viendo la página 1 de la paginación, el frontend la sondea cada
minuto para refrescarla sin recargar. Si se está navegando otra página de la
paginación, el sondeo no arranca.

### `seen_last_at`: hora local del navegador, formato español

Tanto en el JSON de `/airflight/detected` como en la vista `/airflight`
(columna "Visto última vez" de la tabla), `seen_last_at` sale como
**ISO-8601 UTC sin ambigüedad** (`AirFlightController::seenLastAtAsUtcIso()`,
`Carbon::parse($valor, 'UTC')->toISOString()` — p. ej.
`2026-09-08T20:15:00.000000Z`), no la cadena tal cual la guarda Postgres
(`"2026-09-08 20:15:00"`, sin zona).

Por qué: `AirFlightService::getDetectedQuery()` no es Eloquent (es un `JOIN` +
`GROUP BY` con `DB::table()`), así que `seen_last_at` no pasa por el casting
de fechas de Eloquent — llega el string crudo de la columna, que en este
proyecto se guarda en `APP_TIMEZONE` (`UTC`, ver `config/app.php`) pero sin
ninguna marca que lo diga. `new Date("2026-09-08 20:15:00")` en JavaScript es
territorio ambiguo: algunos navegadores lo interpretan como UTC, otros como
hora local del propio navegador — con ese formato la hora mostrada podía
variar varias horas según el navegador de quien mirara la página.

El frontend (`resources/views/airflight/index.blade.php`, función global
`window.formatFechaEsLocal`) coge ese ISO-8601 y lo formatea con
`Intl.DateTimeFormat('es-ES', { timeZone: ... })`:

1. Detecta la zona horaria del navegador con
   `Intl.DateTimeFormat().resolvedOptions().timeZone`.
2. Si eso falla (excepción) o el motor de fechas no admite la zona detectada
   al formatear, cae a `Europe/Madrid`.
3. La zona usada se enseña en el banner de encima del mapa ("Horas mostradas
   en tu zona horaria (...)"), para que quede claro qué hora se está viendo.

Se aplica en dos sitios con la misma función, para que no diverjan:
las celdas que ya trae el HTML del servidor (marcadas con
`data-seen-at="<iso>"`, reformateadas nada más cargar el script,
independientemente de en qué página de paginación se esté) y las filas que
reconstruye el sondeo cada minuto (`buildRow()`, sólo en la página 1).

### Columnas de "Aviones detectados": unidades y orden (2026-09-08)

| Columna | Antes | Ahora |
|---|---|---|
| 1 | ICAO | ICAO |
| 2 | Callsign | **Squawk** (movida aquí, justo detrás de ICAO) |
| 3 | Altitud (ft) | **Vuelo** (renombrada de "Callsign") |
| 4 | Velocidad (kt) | **Altitud (m)** |
| 5 | Dirección | **Velocidad (km/h)** |
| 6 | Latitud | Dirección (con flecha, ver abajo) |
| 7 | Longitud | Visto última vez |
| 8 | Squawk | — |
| 9 | Visto última vez | — |

Latitud y longitud se han quitado de esta tabla (no del mapa, que sigue
igual): `AirFlightService::getDetectedQuery()` ya no las selecciona.

**Conversión de unidades** (`AirFlightController::roundOrNull()`/`::toKmh()`,
aplicada en `index()` y `detected()`, redondeada al entero). Unidad real
según la tabla de arriba:

- `altitude`: ya está en metros — sólo se redondea, no se convierte.
- `speed`: m/s → km/h (`× 3.6`), que se lee más cómodo que m/s.

`vert_rate` no se muestra en esta tabla (no hay columna para él); su
validación (`StoreAirFlightRequest`/`StoreBatchAirFlightRequest`,
`between:-100,100`) sí está acotada a m/s, la unidad real confirmada por el
propietario del capturador el 2026-09-09.

**Flecha de dirección**: antes de los grados, un icono de Material Symbols
(`navigation`, una flecha que por defecto apunta hacia arriba) rotado con
`transform: rotate({{ $plane->track }}deg)` — 0° = arriba, 90° = derecha,
180° = abajo, 270° = izquierda, igual que la convención de rumbo compás que
ya usa `track` (0-359°, 0 = norte). Mismo marcado en el HTML del servidor
(`resources/views/airflight/index.blade.php`) y en `direccionCell()` del
sondeo cada minuto, para que no diverjan visualmente.

### Widgets de Actividad y Aviones Más Frecuentes (2026-09-17)

Encima de la tabla de última hora en `/airflight` se ubican dos bloques de 4 widgets con diseño *Obsidian Flux* / *Raupulus Slate*:

1. **Actividad de detección (4 tarjetas temporales):**
   - **Última hora:** aeronaves activas en tiempo real (`seen_last_at >= 1h`, caché 60 s).
   - **Últimas 24 horas:** aeronaves detectadas en el día (`seen_last_at >= 24h`, caché 5 min).
   - **Últimos 7 días:** aeronaves detectadas en la semana (`seen_last_at >= 7d`, caché 15 min).
   - **Total histórico:** total acumulado de aeronaves catalogadas (`AirFlightAirPlane::count()`, caché 1 hora).
   - Servido mediante `AirFlightService::getAirFlightStats()`.

2. **Aviones más frecuentes (4 tarjetas de aeronaves top):**
   - Muestra las 4 aeronaves que han sobrevolado y sido detectadas en un mayor número de jornadas (`DATE(seen_at)`) distintas en el receptor.
   - Cada tarjeta muestra bandera nacional, matrícula (o código ICAO), modelo (`aircraft_type`), país, número de días de presencia, total de posiciones registradas, tiempo relativo desde el último avistamiento y enlace directo a la ficha del aparato en FlightRadar24 (`https://www.flightradar24.com/data/aircraft/...`).
   - Servido mediante `AirFlightService::getTopAircraft(4)`, optimizado con filtro CTE para tablas de gran volumen (>10.000 rutas) y cacheado 24 horas.

### Frontend (Fix 5)

- **Mapa interactivo OpenLayers:** Recuperado de la rama `main`, integrado con layout v2 vía `@push('head')` y `@push('scripts')`.
- **jQuery 3.0:** Cargado solo en `/airflight` desde `public/resources/airflight/jquery/`.
- **OpenLayers 3.17.1:** Cargado desde `public/resources/airflight/ol3/`.
- **Scripts del mapa:** `config.js`, `markers.js`, `dbloader.js`, `registrations.js`, `planeObject.js`, `formatter.js`, `flags.js`, `layers.js`, `script.js`.
- **Assets:** Directorio `public/resources/airflight/` con banderas, bases de datos de aviones, sprites, etc.
- **Controles del mapa:** Reset zoom, recargar datos, ir arriba/abajo del mapa, seguir avión seleccionado.
- **Sidebar de información:** Muestra detalles del vuelo seleccionado (ICAO, callsign, altitud, velocidad, squawk, posición, distancia a Chipiona) con enlaces a FlightAware, FR24, FlightStats y PlaneFinder.

### Comando de debug

Genera aviones con una trayectoria coherente cada uno (rumbo, velocidad y
altitud evolucionan punto a punto, no son coordenadas sueltas), para que el
mapa pueda trazar una línea real. `--routes` es el total de puntos a repartir
entre `--planes` aviones.

```bash
php artisan debug:seed-airflight --planes=10 --routes=100

# Ejecutar varias veces para ir añadiendo aviones nuevos sin borrar los anteriores,
# cada uno con su propia línea de ~25 puntos:
php artisan debug:seed-airflight --planes=1 --routes=25
```

### Comando de limpieza: subidas duplicadas en `airflight_routes`

`AirFlightRemoveDuplicateRoutesCommand` (`airflight:remove_duplicate_routes`)
borra filas que son la **misma subida repetida**: mismo avión
(`airplane_id`), mismo instante detectado (`seen_at`) y mismo contador de
mensajes decodificados (`messages`). No son puntos de ruta distintos —el
receptor mandó (o la API guardó) el mismo sondeo más de una vez—, así que
sobran todas menos una.

Esto es la **limpieza** de lo que ya quedó guardado con ese ruido, no la
prevención: eso ya lo resuelve la fusión por `messages` de
`AirFlightService::addAircraft()` (ver más arriba, "Ingesta: fusionar por
`messages`") para las subidas *a partir* de ese cambio. Este comando es para
lo que se guardó *antes*.

De cada grupo de duplicados se conserva la fila con el `id` más bajo —la
primera que se guardó, no un valor arbitrario—, con `ROW_NUMBER() OVER
(PARTITION BY airplane_id, seen_at, messages ORDER BY id)` y borrando las
`rn > 1`. Mismo patrón que `keycounter:remove_duplicate`, con las mismas dos
salvaguardas:

- **Sin `--force` no borra nada.** Sólo cuenta cuántas filas se borrarían y
  lo deja en el log (`Log::info`) y en pantalla. Es el modo por defecto a
  propósito — para poder revisar el número antes de fiarte del comando.
- **`--date=YYYY-MM-DD`** acota la revisión a ese día por `seen_at`. Sin este
  flag se revisa la tabla **completa**, que en una tabla grande puede tardar
  (en local, con el histórico real, el dry-run sin acotar tardó ~18 s). Sirve
  para dos cosas: repasar un día concreto antes de fiarte del resultado
  general, y trocear un borrado grande en varias ejecuciones —una por
  día— si prefieres no lanzarlo de una vez contra toda la tabla.

```bash
# Revisar cuántos duplicados hay en toda la tabla (no borra nada)
php artisan airflight:remove_duplicate_routes

# Revisar solo un día concreto, para comprobar que el número cuadra
php artisan airflight:remove_duplicate_routes --date=2026-09-08

# Borrar de verdad, acotado a ese día
php artisan airflight:remove_duplicate_routes --date=2026-09-08 --force

# Borrar de verdad en toda la tabla, una vez comprobado que el número tiene sentido
php artisan airflight:remove_duplicate_routes --force
```

No toca las filas sin posición que se guardan a propósito (ver "Regla:
`airflight_routes` guarda TODO" al principio de este documento) salvo que
además compartan avión + instante + contador de mensajes con otra fila —en
cuyo caso sí son un duplicado exacto, tengan o no posición.

---

> Creado: 2026-05-25 · Última revisión: 2026-09-17
