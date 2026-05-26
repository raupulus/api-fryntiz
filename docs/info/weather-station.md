# Módulo: Estación Meteorológica (WeatherStation)

Módulo IoT para recopilar datos meteorológicos de sensores locales y datos oficiales de AEMET (Agencia Estatal de Meteorología). Incluye 12 tipos de sensores, integración con API de AEMET y resúmenes históricos.

> 📘 Para detalles de la integración técnica con AEMET (endpoints, rate-limit, retry/backoff, caché), ver [apis/aemet.md](apis/aemet.md).

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/WeatherStation/BaseWheaterStation.php` | — | Modelo base abstracto para todos los sensores |
| `app/Models/WeatherStation/Temperature.php` | `meteorology_temperature` | Datos de temperatura |
| `app/Models/WeatherStation/Humidity.php` | `meteorology_humidity` | Datos de humedad |
| `app/Models/WeatherStation/Pressure.php` | `meteorology_pressure` | Datos de presión atmosférica |
| `app/Models/WeatherStation/Light.php` | `meteorology_light` | Datos de luminosidad |
| `app/Models/WeatherStation/Wind.php` | `meteorology_winter` | Datos de velocidad del viento |
| `app/Models/WeatherStation/WindDirection.php` | `meteorology_wind_direction` | Dirección del viento |
| `app/Models/WeatherStation/Rain.php` | `meteorology_rain` | Datos de precipitación |
| `app/Models/WeatherStation/Eco2.php` | `meteorology_eco2` | CO2 equivalente |
| `app/Models/WeatherStation/Tvoc.php` | `meteorology_tvoc` | Compuestos orgánicos volátiles totales |
| `app/Models/WeatherStation/AirQuality.php` | `meteorology_air_quality` | Calidad del aire |
| `app/Models/WeatherStation/Lightning.php` | `meteorology_lightning` | Detección de rayos |

### Modelos AEMET
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/WeatherStation/AEMET.php` | Variable | Modelo base para datos AEMET |
| `app/Models/WeatherStation/AEMETPrediction.php` | — | Predicciones meteorológicas |
| `app/Models/WeatherStation/AEMETAdverseEvents.php` | — | Eventos meteorológicos adversos |
| `app/Models/WeatherStation/AEMETCoast.php` | — | Predicción costera |
| `app/Models/WeatherStation/AEMETHighSea.php` | — | Predicción de alta mar |
| `app/Models/WeatherStation/AEMETContamination.php` | — | Datos de contaminación |
| `app/Models/WeatherStation/AEMETOzone.php` | — | Datos de ozono |
| `app/Models/WeatherStation/AEMETSunRadiation.php` | — | Radiación solar |
| `app/Models/WeatherStation/AEMETPredictionBeach.php` | — | Predicción de playas |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/WeatherStation/V2/GeneralController.php` | API V2 | Resumen meteorológico |
| `app/Http/Controllers/Api/WeatherStation/V2/GenericController.php` | API V2 | Store genérico multi-sensor |
| `app/Http/Controllers/Api/WeatherStation/V2/TemperatureController.php` | API V2 | CRUD temperatura |
| `app/Http/Controllers/Api/WeatherStation/V2/HumidityController.php` | API V2 | CRUD humedad |
| `app/Http/Controllers/Api/WeatherStation/V2/PressureController.php` | API V2 | CRUD presión |
| `app/Http/Controllers/WeatherStation/WeatherStationController.php` | Web | Frontend público |
| `app/Http/Controllers/Api/WeatherStation/*.php` | API V1 | Controladores V1 legacy (12 archivos) |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/WeatherStation/WeatherStationService.php` | Lógica de negocio: store, consultas, resúmenes |
| `app/Services/WeatherStation/AEMETService.php` | Integración con API de AEMET |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/WeatherStation/TemperatureResource.php` | Resource JSON temperatura |
| `app/Http/Resources/V2/WeatherStation/HumidityResource.php` | Resource JSON humedad |
| `app/Http/Resources/V2/WeatherStation/PressureResource.php` | Resource JSON presión |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/WeatherStation/V2/StoreSensorRequest.php` | Validación store sensor individual |
| `app/Http/Requests/Api/WeatherStation/V2/StoreGenericRequest.php` | Validación store genérico multi-sensor |

### Comandos Artisan (AEMET)
| Archivo | Comando | Frecuencia |
|---------|---------|------------|
| `app/Console/Commands/AEMET/AEMETDailyCommand.php` | `aemet:daily` | Diario |
| `app/Console/Commands/AEMET/AEMETDaily8Command.php` | `aemet:daily8` | Diario 8:00 |
| `app/Console/Commands/AEMET/AEMETDaily12Command.php` | `aemet:daily12` | Diario 12:00 |
| `app/Console/Commands/AEMET/AEMETDaily20Command.php` | `aemet:daily20` | Diario 20:00 |
| `app/Console/Commands/AEMET/AEMETEvery10mCommand.php` | `aemet:every10m` | Cada 10 min |
| `app/Console/Commands/AEMET/AEMETEvery30mCommand.php` | `aemet:every30m` | Cada 30 min |
| `app/Console/Commands/AEMET/AEMETEvery4hCommand.php` | `aemet:every4h` | Cada 4 horas |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/WeatherStationPolicy.php` | Política de autorización |
| `config/aemet.php` | Configuración de API AEMET (apikey, códigos municipio) |

## Campos del modelo base (BaseWheaterStation)

Todos los sensores heredan estos campos:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK autoincremental |
| `hardware_device_id` | int | FK → `hardware_devices.id` |
| `value` | decimal | Valor del sensor |
| `created_at` | timestamp | Fecha de lectura |

### Campos adicionales por sensor

- **Light:** `llesistance` (resistencia lumínica)
- **Wind:** `speed`, `min`, `max`
- **WindDirection:** `grades`, `direction`, `resistance`
- **Rain:** `intensity`
- **AirQuality:** `value`
- **Lightning:** `distance`, `noise_floor`, `energy`

## Relaciones

- `BaseWheaterStation` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)

## Métodos clave (BaseWheaterStation)

| Método | Descripción |
|--------|-------------|
| `averageLast(int $hours)` | Media de las últimas N horas con caché (600s) |
| `prepareApiResponse()` | Formatea datos para respuesta API |
| `getAllAttributes()` | Devuelve todos los atributos del modelo |

## Rutas API V2

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/weatherstation/resume` | No | Resumen meteorológico |
| GET | `/api/v2/weatherstation/temperature` | No | Listado de temperaturas |
| GET | `/api/v2/weatherstation/humidity` | No | Listado de humedad |
| GET | `/api/v2/weatherstation/pressure` | No | Listado de presión |
| POST | `/api/v2/weatherstation/generic/store` | Sí | Store multi-sensor |
| POST | `/api/v2/weatherstation/temperature/store` | Sí | Store temperatura |
| POST | `/api/v2/weatherstation/humidity/store` | Sí | Store humedad |
| POST | `/api/v2/weatherstation/pressure/store` | Sí | Store presión |

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/weatherstation` | Dashboard público con widget Vue 3 del clima y tarjetas de sensores con iconos |
| `/weatherstation/sensor/{type}` | Página individual de un sensor con tabla paginada Blade y botón volver |

### Tipos de sensor soportados en ruta web

`temperature`, `humidity`, `pressure`, `light`, `wind`, `wind-direction`, `rain`, `eco2`, `tvoc`, `air-quality`, `lightning`

### Widget Vue 3 (`ChipionaWeatherComponent`)

- **Archivo:** `resources/js/vue/Components/ChipionaWeatherComponent.vue`
- **Montaje:** `resources/js/vue.js` (carga con `@vite('resources/js/vue.js')`)
- **Props:** `apiBaseUrl` (URL base), `apiPath` (ruta API, default `api/weatherstation/v1/resume`)
- **Actualización:** Cada 65 segundos vía `fetch()` desde API V1
- **Secciones:** General, Viento, TVOC/Calidad del Aire, UV/Radiación Solar

### Iconos Material Symbols por sensor

Cada tarjeta de sensor usa un icono representativo definido en `SENSOR_MAP` del controlador.

### Comando de debug

```bash
php artisan debug:seed-weatherstation --count=20
```
