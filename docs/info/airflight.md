# Módulo: Registro de Vuelos (AirFlight)

Módulo IoT para detectar y registrar aviones mediante receptor ADS-B, almacenando datos de vuelo, telemetría y rutas históricas.

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
| `app/Services/AirFlight/AirFlightService.php` | Lógica: addAircraft, addAircraftBatch, getAircraftHistory |

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

## Campos del modelo AirFlightAirPlane

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `icao` | string(10) | Código ICAO del avión (identificador único transponder) |
| `category` | string | Categoría del avión |
| `seen_last_at` | timestamp | Última vez detectado |
| `seen_first_at` | timestamp | Primera vez detectado |

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

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `airplane_id` | int | FK → `airflight_airplanes.id` |
| `hardware_device_id` | int | FK → `hardware_devices.id` — receptor |
| `user_id` | int | FK → `users.id` — propietario del receptor |
| `squawk` | string(10) | Código squawk (transponder) |
| `flight` | string(20) | Número de vuelo |
| `lat` | decimal | Latitud (-90 a 90) |
| `lon` | decimal | Longitud (-180 a 180) |
| `altitude` | decimal | Altitud (≥0) |
| `vert_rate` | decimal | Velocidad vertical |
| `track` | decimal | Rumbo (0-360°) |
| `speed` | decimal | Velocidad (≥0) |
| `seen_at` | timestamp | Momento de detección |
| `messages` | int | Número de mensajes recibidos (≥0) |
| `rssi` | decimal | Intensidad de señal |

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

`/airflight/detected` alimenta la tabla "Aviones detectados (última hora)" de
la propia vista: la primera tanda la pinta el servidor en el HTML (sin
petición extra al cargar) y, sólo cuando la página termina de cargar del todo
y se está viendo la página 1 de la paginación, el frontend la sondea cada
minuto para refrescarla sin recargar. Si se está navegando otra página de la
paginación, el sondeo no arranca.

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

---

> Creado: 2026-05-25 · Última revisión: 2026-09-06
