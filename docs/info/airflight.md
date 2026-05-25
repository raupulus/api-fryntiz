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
| `app/Http/Controllers/Api/AirFlight/AirFlightController.php` | API V1 | Controlador V1 legacy |
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
| `app/Policies/AirFlightPolicy.php` | Política de autorización |
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
| POST | `/api/v2/airflight/register` | Sí | api-store | Registrar un avión |
| POST | `/api/v2/airflight/register/batch` | Sí | api-store-batch | Registrar lote (max 500) |

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/airflight` | Mapa/listado de aviones detectados |
