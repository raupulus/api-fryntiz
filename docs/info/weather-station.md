# Módulo: Estación Meteorológica (WeatherStation)

Módulo IoT para recopilar datos meteorológicos de sensores locales y datos oficiales de AEMET (Agencia Estatal de Meteorología). Incluye 12 tipos de sensores, integración con API de AEMET y resúmenes históricos.

> 📘 Para detalles de la integración técnica con AEMET (endpoints, rate-limit, retry/backoff, caché), ver [apis/aemet.md](apis/aemet.md).

## Estaciones, tipo de hardware y ubicación

Una **estación meteorológica es un `HardwareDevice` cuyo tipo de hardware es
"Estación Meteorológica"** (`HardwareType::WEATHER_STATION`, id 6 en producción).
El tipo es lo único que define que un dispositivo sea estación.

Independientemente de eso, **todo** hardware tiene una ubicación física:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `location_type` | enum `HardwareLocationTypeEnum` (`indoor`/`outdoor`) | Ubicación física de cualquier hardware. **Por defecto `indoor`**. |
| `zone` | string(100) nullable | Zona concreta, EJ: `Azotea`, `Salón`, `Jardín`. |

- Enum: `app/Enums/HardwareLocationTypeEnum.php` (`Indoor`→"Interior",
  `Outdoor`→"Exterior", con `label()` y `options()`).
- `HardwareType::WEATHER_STATION` = `'Estación Meteorológica'`. Los tipos por
  defecto se siembran con `HardwareTypesSeeder` (ids fijos de producción,
  idempotente por id, resincroniza la secuencia en PostgreSQL).
- Modelo `HardwareDevice`: scope `weatherStations()` (filtra por el tipo de
  hardware), helper `isWeatherStation()`, atributos `location_label` y
  `display_name` (nombre amistoso + zona).
- **Estación principal por defecto**: `config/weather_station.php` →
  `main_station_id` (env `WEATHER_STATION_MAIN_ID`). Resolución en
  `WeatherStationService::resolveMainStationId()`: config → primera estación de
  exterior → cualquier estación.
- **Panel Admin** (`HardwareDeviceResource`): sección "Ubicación" con
  `location_type` (por defecto interior) y `zone` para **cualquier** dispositivo;
  para marcarlo estación se elige el tipo "Estación Meteorológica".

El **frontend** agrupa las estaciones por interior/exterior y, dentro, por zona.
El **widget** resumen apunta por defecto a la estación principal y admite
cambiarla vía `data-station` (prop `station`), mostrando ubicación dinámica.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/WeatherStation/BaseWeatherStation.php` | — | Modelo base abstracto para todos los sensores |
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
| `app/Http/Controllers/Api/WeatherStation/V2/StationController.php` | API V2 | Estación por id (`/station/{id?}`) y por zona (`/zone/{zone}`), datos formateados |
| `app/Http/Controllers/Api/WeatherStation/V2/GenericController.php` | API V2 | Store genérico multi-sensor |
| `app/Http/Controllers/Api/WeatherStation/V2/TemperatureController.php` | API V2 | CRUD temperatura |
| `app/Http/Controllers/Api/WeatherStation/V2/HumidityController.php` | API V2 | CRUD humedad |
| `app/Http/Controllers/Api/WeatherStation/V2/PressureController.php` | API V2 | CRUD presión |
| `app/Http/Controllers/WeatherStation/WeatherStationController.php` | Web | Frontend público |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/WeatherStation/WeatherStationService.php` | Lógica de negocio: store, consultas, resúmenes |
| `app/Services/WeatherStation/AEMETService.php` | Integración con API de AEMET |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/WeatherStation/WeatherStationResource.php` | Resource JSON de estación (datos formateados + selección de sensores) |
| `app/Http/Resources/V2/WeatherStation/TemperatureResource.php` | Resource JSON temperatura |
| `app/Http/Resources/V2/WeatherStation/HumidityResource.php` | Resource JSON humedad |
| `app/Http/Resources/V2/WeatherStation/PressureResource.php` | Resource JSON presión |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/WeatherStation/V2/ShowStationRequest.php` | Validación de `/station/{id?}` (parámetro `sensors`) |
| `app/Http/Requests/Api/WeatherStation/V2/ShowZoneRequest.php` | Validación de `/zone/{zone}` (`sensors`, `location_type`) |
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

## Campos del modelo base (BaseWeatherStation)

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

- `BaseWeatherStation` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)

## Métodos clave (BaseWeatherStation)

| Método | Descripción |
|--------|-------------|
| `averageLast(int $hours)` | Media de las últimas N horas con caché (600s) |
| `prepareApiResponse()` | Formatea datos para respuesta API |
| `getAllAttributes()` | Devuelve todos los atributos del modelo |

## Rutas API V2

**Todas exigen token desde el 2026-09-06**, lecturas incluidas.

| Método | Ruta | Auth |
|--------|------|------|
| GET | `/api/v2/weather-stations` | `ability:weatherstation:read` |
| GET | `/api/v2/weather-stations/{station}` | `ability:weatherstation:read` |
| GET | `/api/v2/weather-stations/zone/{zone}/{locationType?}` | `ability:weatherstation:read` |
| GET | `/api/v2/weather-stations/{station}/{sensor}` | `ability:weatherstation:read` |
| POST | `/api/v2/weather-stations/{station}/readings` | `ability:weatherstation:write` |
| POST | `/api/v2/weather-stations/{station}/{sensor}` | `ability:weatherstation:write` |

Las escrituras usan `throttle:api-store` y un token IoT ligado a su estación
(`device:{id}`). Admiten la clave opcional `hardware_device_info` con el estado
del propio dispositivo (batería, temperatura, uptime, RAM…), igual que
`/hardware/energy-readings` y `/hardware/solar-readings`.

> **Las lecturas eran públicas hasta el 2026-09-06.** Lo eran porque el widget
> del clima de esta misma web las llamaba desde el navegador, y eso dejaba la
> ability `weatherstation:read` sin nada que proteger. El widget se sirve ahora
> desde el bloque **web** (ver más abajo) y la API pide token. Un cliente que
> leyera sin token necesita emitir uno con `weatherstation:read`.

> Contrato exacto —cuerpos, respuestas y errores— en
> [`docs/info/api/v2/weather-station.md`](api/v2/weather-station.md).

### Endpoints de estación (datos formateados)

`StationController` + `WeatherStationResource` (`app/Http/Resources/V2/WeatherStation/`).
Los valores llegan **listos para usar y como números** (nunca cadenas ni
unidades): viento en **km/h**, temperatura/magnitudes redondeadas a **2 decimales**,
`eco2`/`tvoc`/`energy` enteros. La unidad es documentación, no se envía.

- **Selección de sensores** (`?sensors=`): lista separada por comas. Sensores
  válidos: `temperature`, `humidity`, `pressure`, `wind`, `light`, `air_quality`,
  `rain`, `lightning`. Sin el parámetro se devuelven todos. Validado por
  `ShowStationRequest`/`ShowZoneRequest` (422 si un sensor no existe).
- **`GET /station/{id?}`**: si no se pasa `id`, resuelve la estación principal
  (config `weather_station.main_station_id` → primera de exterior → cualquiera).
  404 si el id no es una estación.
- **`GET /zone/{zone}`**: siempre devuelve una **colección** (aunque haya una o
  ninguna). `?location_type=indoor|outdoor` acota dentro de la zona. Coincidencia
  de zona insensible a mayúsculas.

#### `GET /api/v2/weather-stations/zone/{zone}/{locationType?}` — lectura AGREGADA de zona

**Nuevo el 2026-09-06.** No confundir con el `/zone/{zone}` de arriba, que
devuelve la lista de estaciones: éste devuelve **una sola lectura**, la de la
zona entendida como conjunto.

Para cada sensor toma el registro **más reciente de cualquiera de sus
estaciones**, en vez de atarse a un aparato concreto. Es lo que consume el widget
de portada.

El porqué: el widget iba fijado a una estación, y en cuanto ésa dejaba de subir
seguía enseñando su último valor —la humedad al 49 % durante días— mientras la
estación de al lado, en la misma azotea, subía el 20 % real. El dato bueno
estaba en la base y no se miraba.

- `{locationType}` es opcional y sólo admite `indoor` o `outdoor`. Acota el resto de
  sensores; normalmente `outdoor`.
- **La presión es la excepción**: sale de **cualquier** estación de la zona,
  interior incluida, se pase el valor que se pase. Un barómetro mide lo mismo
  dentro que fuera y a la interperie se estropea antes, así que suele vivir en un
  cacharro de interior.
- Los **rayos** se cuentan en toda la zona, no en un dispositivo.
- La estación que sale como referencia (`name`, `location_label`) es la que trae
  el dato más reciente de todas: la que está viva ahora mismo.
- **404** si la zona no tiene estaciones.

La forma de la respuesta es idéntica a la de `GET /{station}`, así que un cliente
que ya consumiera aquélla no necesita cambiar el parseo.

Zona por defecto del widget: `weather_station.main_zone` (variable
`WEATHER_STATION_MAIN_ZONE`), o la primera zona de exterior si no se configura.

Estructura de cada estación: `id`, `name`, `zone`, `location_type`,
`location_label`, `instant`, y un bloque por sensor solicitado
(`wind` → `{average, min, max, direction, direction_grades}`,
`light` → `{lux, uv_index, uva, uvb}`,
`air_quality` → `{quality, eco2, tvoc}`,
`rain` → `{value, intensity}`,
`lightning` → `{last_at, last_six_hours, distance, energy}`).

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/weatherstation` | Dashboard público con widget Vue 3 del clima y tarjetas de sensores con iconos |
| `/weatherstation/sensor/{type}` | Página individual de un sensor con tabla paginada Blade y botón volver |
| `/weatherstation/widget` | **JSON** del widget: la estación principal, ya resuelta y cacheada 60 s |
| `/weatherstation/widget/zone/{zone}/{locationType?}` | Lo mismo, agregado por zona. Es lo que consume el widget |
| `/weatherstation/widget/{station}` | Lo mismo, fijado a una estación por id |

Los tres devuelven `{success, data}` y **no piden token**: son datos de una
página propia, no una integración. Por eso viven aquí y no en la API — se sirve
lo justo que se pinta, ya filtrado y cacheado, mientras que la API ofrece
filtros, orden, paginación e histórico a cambio de un token.

### Tipos de sensor soportados en ruta web

`temperature`, `humidity`, `pressure`, `light`, `wind`, `wind-direction`, `rain`, `eco2`, `tvoc`, `air-quality`, `lightning`

### Widget Vue 3 (`ChipionaWeatherComponent`)

- **Archivo:** `resources/js/vue/Components/ChipionaWeatherComponent.vue`
- **Montaje:** `resources/js/vue.js` (carga con `@vite('resources/js/vue.js')`)
- **Props:** `apiBaseUrl` (URL base), `apiPath` (ruta web del widget, default
  `weatherstation/widget`), **`zone`** (nombre de la zona) y **`locationType`**
  (`indoor`/`outdoor`), y `station` (id) como reserva. En Blade se pasan con
  `data-zone`, `data-location-type` y `data-station`.
- **`zone` tiene prioridad sobre `station`.** Yendo por zona, de cada magnitud se
  coge el dato más reciente entre todas sus estaciones; atado a una, el widget se
  quedaba enseñando su último valor cuando ésa dejaba de subir. Sin zona
  clasificada cae a la estación principal.
- **Actualización:** Cada 65 segundos vía `fetch()` a
  `weatherstation/widget/zone/{zone}[/{locationType}]`, o al de estación si no hay
  zona. **Hasta el 2026-09-06 llamaba a `api/v2/weather-stations`**, lo que
  obligaba a dejar esa ruta de API abierta a cualquiera.
- **Secciones:** General, Viento, TVOC/Calidad del Aire, UV/Radiación Solar
- **Ubicación dinámica:** muestra `data.name` + `data.location_label` en lugar de un literal fijo.
- **Contrato:** consume `GET /station/{id?}` (envelope `{success, message, data}`). `data` incluye `name`, `location_label`, `instant` y los bloques de sensores (`wind.average`, `light.uv_index`, `air_quality.quality/eco2/tvoc`, `lightning.last_six_hours`, `temperature`, `humidity`, `pressure`) ya formateados como números.

### Iconos Material Symbols por sensor

Cada tarjeta de sensor usa un icono representativo definido en `SENSOR_MAP` del controlador.

### Comando de debug

```bash
php artisan debug:seed-weatherstation --count=20
```

El comando rellena todas las tablas de sensores, además de
los resúmenes y datos UV (ver modelos nuevos abajo). Si ya existen estaciones
(dispositivos de tipo "Estación Meteorológica"), las reutiliza; si no, garantiza
el tipo (vía `HardwareTypesSeeder`) y crea **3 estaciones de ejemplo** (2 de
exterior — zonas `Azotea` y `Jardín` — y 1 de interior — `Salón`) y reparte las
lecturas con rangos realistas por perfil (el interior omite viento, lluvia, rayos
y UV alto, y usa temperaturas/humedad más templadas).

## Modelos de resumen y UV (fix_11)

Añadidos para representar tablas que ya existían sin modelo Eloquent
(`app/Models/WeatherStation/`):

| Modelo | Tabla | Campos clave |
|--------|-------|--------------|
| `MeteorologyResumeToday` | `meteorology_resume_today` | resumen agregado del día actual (todos los sensores) |
| `MeteorologyResumeHistorical` | `meteorology_resume_historical` | resumen agregado por día histórico |
| `MeteorologyUvIndex` | `meteorology_uv_index` | `value` (índice UV) |
| `MeteorologyUva` | `meteorology_uva` | `value` (radiación UVA) |
| `MeteorologyUvb` | `meteorology_uvb` | `value` (radiación UVB) |

Todos extienden `BaseModel`, usan `public $timestamps = false` (solo `created_at`)
y tienen relación `hardwareDevice()`. Los dos de resumen conservan `user()`, pero
como `HasOneThrough` a través del dispositivo.

> ### Las tablas de sensores ya no tienen `user_id` (2026-09-06)
>
> El dueño de una lectura es el dueño de la estación que la tomó, y eso está a un
> salto: `hardware_device_id` → `hardware_devices.user_id`. Guardarlo además en
> cada fila era duplicar el mismo dato millones de veces —sólo
> `meteorology_humidity` pasa de los tres millones— y dejaba la puerta abierta a
> que las dos copias dijeran cosas distintas.
>
> Retirado de las trece tablas en
> `2026_09_06_000002_drop_user_id_from_sensor_tables`. Comprobado antes sobre los
> datos de producción: **cero** filas tenían un `user_id` distinto al del
> dispositivo, y ninguna consulta del proyecto filtraba por esa columna.
>
> **Para consultar el dueño de una lectura**, ir por el dispositivo:
>
> ```php
> $lectura->hardwareDevice->user_id;
> // o, con eager loading:
> Temperature::with('hardwareDevice.user')->get();
> ```
>
> La ingesta de V2 ya no lo rellenaba, así que todo lo que entró desde el
> despliegue lo tenía a null: la columna estaba a medio abandonar antes de
> retirarla.

---

## Estado del módulo (2026-08-19)

| Capa | Estado |
|------|--------|
| Modelos (18 sensores + 9 AEMET) | ✅ |
| API V2 (27 rutas) | ✅ |
| Tests (22 métodos) | ✅ |
| Comandos AEMET (7) | ✅ existen |
| **Scheduler de AEMET** | ✅ Arreglado: un comando por producto, con la cadencia que declara AEMET. `SchedulerTest` impide que vuelva a programar comandos que no existen |
| Frontend público | ✅ |
| **Panel Filament** | 🟠 Sin Resource de sensores. Sí hay panel de AEMET (`/admin/aemet`) con una tarjeta por producto y su botón de resincronizar |
| Broadcasting en vivo | 🟠 Implementado y **apagado por defecto**. Un evento por subida (`ReadingsReceived`) al canal público `weather-station.{id}`. Falta `composer require laravel/reverb` y levantar el demonio. Ver [websockets.md](websockets.md) |

---

> Creado: 2026-05-25 · Última revisión: 2026-09-06
