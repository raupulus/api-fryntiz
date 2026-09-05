# Módulo: Registro de Vuelos (AirFlight)

Módulo IoT para detectar y registrar aviones mediante receptor ADS-B, almacenando datos de vuelo, telemetría y rutas históricas.

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

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| GET | `/api/v2/airflight/aircrafts` | No | — | Aviones recientes |
| GET | `/api/v2/airflight/history` | No | — | Historial extendido (100) |
| GET | `/api/v2/airflight/receiver` | No | — | Información del receptor ADS-B |
| GET | `/api/v2/airflight/db/{bkey}` | No | — | Consulta a la base de datos de aeronaves por clave |
| POST | `/api/v2/airflight/register` | Sí (`ability:airflight:write`) | api-store | Registrar un avión |
| POST | `/api/v2/airflight/register/batch` | Sí (`ability:airflight:write`) | api-store-batch | Registrar lote (max 500) |

> **Nota (2026-08-31):** tabla desactualizada. Las rutas reales en
> `routes/airflight/v2.php` son `GET /api/v2/airflight/aircrafts`,
> `GET /api/v2/airflight/receiver`, `POST /api/v2/airflight/aircrafts` y
> `POST /api/v2/airflight/aircrafts/batch` (`/history` y `/db/{bkey}` ya no
> existen, ver `AirFlightController`). Contrato exacto en
> [`docs/info/api/v2/airflight.md`](api/v2/airflight.md), que sí está
> actualizado. Los dos `POST` admiten desde ahora una clave opcional
> `hardware_device_info` con el estado del receptor (batería, temperatura,
> uptime...), solo aplicable si la petición trae también `hardware_device_id`
> (aquí es opcional, no todos los receptores lo mandan).

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/airflight` | Mapa interactivo de aviones detectados + tabla |

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

> Creado: 2026-05-25 · Última revisión: 2026-09-05
