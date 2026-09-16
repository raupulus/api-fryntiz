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

Todos bajo `app/Models/WeatherStation/AEMET/` — no hay un modelo base `AEMET`
suelto, cada producto tiene el suyo.

| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `AEMETPrediction.php` | `meteorology_aemet_predictions` | Predicción horaria del municipio |
| `AEMETDailyPrediction.php` | `meteorology_aemet_daily_predictions` | Predicción diaria del municipio (resumen, hasta 7 días) |
| `AEMETAdverseEvents.php` | `meteorology_aemet_adverse_events` | Avisos de fenómenos adversos (CAP) |
| `AEMETCoast.php` | `meteorology_aemet_prediction_coasts` | Predicción costera |
| `AEMETHighSea.php` | `meteorology_aemet_high_seas` | Predicción de alta mar |
| `AEMETContamination.php` | `meteorology_aemet_contamination` | Contaminación de fondo (EMEP) |
| `AEMETOzone.php` | `meteorology_aemet_ozone` | Perfil vertical de ozono (sondeo), no ozono de superficie — ver [aemet.md](apis/aemet.md#cadencia-de-cada-producto) |
| `AEMETOzoneTotal.php` | `meteorology_aemet_ozone_total` | Ozono total diario en superficie, por estación |
| `AEMETSunRadiation.php` | `meteorology_aemet_sun_radiation` | Radiación solar |
| `AEMETPredictionBeach.php` | `meteorology_aemet_prediction_beachs` | Predicción de playas |
| `AEMETUvi.php` | `meteorology_aemet_uvi` | Índice UV máximo previsto (ciudad configurada) |
| `AEMETStationObservation.php` | `meteorology_aemet_station_observations` | Observación real (no predicción) de Chipiona/Rota/Almonte |

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

Un comando por producto (todos bajo `app/Console/Commands/AEMET/`) — ver
[commands.md](commands.md) y
[apis/aemet.md](apis/aemet.md#cadencia-de-cada-producto) para la cadencia
completa de los 12.

| Comando | Producto |
|---------|----------|
| `aemet:adverse-events` | Avisos de fenómenos adversos (CAP) |
| `aemet:contamination` | Contaminación atmosférica |
| `aemet:hourly-prediction` | Predicción horaria del municipio |
| `aemet:daily-prediction` | Predicción diaria del municipio (resumen, hasta 7 días) |
| `aemet:beaches` | Predicción de playas |
| `aemet:coast` | Predicción de costa |
| `aemet:high-sea` | Alta mar |
| `aemet:sun-radiation` | Radiación solar |
| `aemet:ozone-profile` | Perfil vertical de ozono (sondeo) |
| `aemet:ozone-total` | Ozono total en superficie |
| `aemet:uvi` | Índice UV máximo previsto |
| `aemet:station-observations` | Observación real de Chipiona/Rota/Almonte |
| `aemet:check-api-key` | Vigila la caducidad de la clave |

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
- **Wind:** `speed`, `average`, `min`, `max` — en **m/s** (unidad nativa del sensor); las vistas y la API los convierten a km/h con `Wind::msToKmh()`, la tabla `meteorology_winter` los guarda tal cual llegan
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
`/energy/readings`.

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
- **La calidad de aire (`air_quality`, `tvoc`, `eco2`) hace fallback a interior**
  cuando la zona filtrada por exterior no tiene dato de ese sensor: se sirve el
  último de interior de la misma zona. A diferencia de la presión, aquí SÍ manda
  el exterior si lo hay (aunque el de interior sea más reciente); solo sustituye
  cuando no hay ningún dato de fuera. Se monitorizan más fácil desde dentro, pero
  el aire de dentro no es "el mismo" que el de fuera.
- Los **rayos** se cuentan en toda la zona, no en un dispositivo. Además de la
  ventana configurable (`count_in_window`), siempre se devuelven dos fijos:
  `count_last_hour` y `count_last_10_minutes`.
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
| `/weatherstation` | Dashboard público con widget Vue 3 del clima, fila de datos ambientales (luna/marea/ozono/aviso AEMET), banner de última alerta GDACS y tarjetas de sensores con iconos |
| `/weatherstation/sensor/{type}` | Página individual de un sensor con tabla paginada Blade y botón volver |
| `/weatherstation/gdacs` | Listado paginado (50/página) de todas las alertas GDACS guardadas, ordenadas por `from_date` desc. Ver [gdacs.md](apis/gdacs.md) |
| `/weatherstation/widget` | **JSON** del widget: la estación principal, ya resuelta y cacheada 60 s |
| `/weatherstation/widget/zone/{zone}/{locationType?}` | Lo mismo, agregado por zona. Es lo que consume el widget |
| `/weatherstation/widget/{station}` | Lo mismo, fijado a una estación por id |

### Fila de datos ambientales (2026-09-16)

Debajo del widget del tiempo, `WeatherStationController::index()` calcula y
pasa a la vista (server-rendered, sin Vue):

| Tarjeta | Fuente | Cómo se obtiene |
|---|---|---|
| Luna | Cálculo propio | `App\Support\WeatherStation\MoonPhase::forDate()`, fórmula del mes sinódico (29.53058868 días), sin API externa |
| Sol (orto/ocaso) | AEMET | `WeatherStationController::todaySunTimes()`, sobre `AEMETPrediction::sunrise/sunset` (predicción horaria, `orto`/`ocaso` de AEMET, repetidos en cada hora del día) de hoy; si aún no ha llegado, cae a la fila más reciente. Se muestra dentro de la tarjeta "Sol y Luna", junto a la fase lunar |
| Marea | Open-Meteo Marine | Próximo extremo de `open_meteo_marine_tides` (ver más abajo y [open-meteo-marine.md](apis/open-meteo-marine.md)) |
| Estado de la mar | AEMET | `App\Support\WeatherStation\SeaStateExtractor` sobre el texto libre de `AEMETCoast` (subzona de Chipiona), mostrado dentro de la tarjeta de Marea |
| Ozono | AEMET | Último registro de `AEMETOzoneTotal` (`meteorology_aemet_ozone_total`) |
| Aviso AEMET | AEMET | El aviso vigente de mayor gravedad en la provincia de Cádiz (`AEMETAdverseEvents::current()->inZone('6111')`), o "sin avisos" |

Justo encima de "Datos de los sensores" hay un banner con la última alerta
GDACS vigente (`GdacsEvent::active()->orderByDesc('last_modified_at')`), o un
enlace a "ver anteriores" si no hay ninguna activa. Enlaza a `/weatherstation/gdacs`.

**Atribución obligatoria** (ver [aemet.md](apis/aemet.md) y
[open-meteo-marine.md](apis/open-meteo-marine.md)): la fila lleva un pie con
`config('aemet.attribution.short')` y el enlace a Open-Meteo.com (CC BY 4.0).
El banner y la página de GDACS llevan `config('gdacs.attribution')`.

### Marea: por qué Open-Meteo Marine y no AEMET

AEMET no publica altura de marea: sus dos productos marítimos
(`/prediccion/maritima/costera/*` y `/altamar/*`) son boletines de texto
(viento, estado de la mar, visibilidad), no series numéricas — verificado
contra la API real el 2026-09-16 (ver `docs/apis/aemet/07-maritima.md`). La
fuente oficial española sería Puertos del Estado, sin API pública sencilla de
integrar; se usa Open-Meteo Marine (`sea_level_height_msl` horario, gratis,
sin registro, CC BY 4.0). Detalle completo en
[open-meteo-marine.md](apis/open-meteo-marine.md).

Los tres devuelven `{success, data}` y **no piden token**: son datos de una
página propia, no una integración. Por eso viven aquí y no en la API — se sirve
lo justo que se pinta, ya filtrado y cacheado, mientras que la API ofrece
filtros, orden, paginación e histórico a cambio de un token.

Los tres widget llevan además `same-origin` (`App\Http\Middleware\
EnsureRequestIsSameOrigin`, 2026-09-15) y `throttle:public-widget`. Sin token
no hay nada que compruebe quién llama, así que se corta a quien pide sin un
`Origin`/`Referer` del propio host — el caso de copiar la URL del panel de red
del navegador y reutilizarla desde fuera —, y se limita a 40 peticiones/min por
IP (`RATE_LIMIT_PUBLIC_WIDGET`) contra el scraping sostenido. No es infalible
(`Origin`/`Referer` se falsean con curl), pero para el "copiar y pegar" real
basta. Mismo criterio en `airflight` (`/airflight/aircrafts`, `/receiver`,
`/detected`).

### Tipos de sensor soportados en ruta web

`temperature`, `humidity`, `pressure`, `light`, `uva`, `uvb`, `wind`, `wind-direction`, `rain`, `eco2`, `tvoc`, `air-quality`, `lightning`

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
- **Secciones:** General, Viento, TVOC/Calidad del Aire, UV/Radiación Solar, Rayos.
  La de Rayos es siempre visible (no depende de que haya habido alguno) y muestra
  a la vez `count_last_hour` y `count_last_10_minutes`.
- **Viento:** además de la media/mín/máx, si la estación reporta dirección
  (`wind.direction`/`direction_grades`) se pinta un icono de flecha rotado por
  grados con la etiqueta cardinal (`N`, `NW`…) abajo a la derecha del bloque; si
  no hay dirección subida, no se muestra nada.
- **Ubicación dinámica:** muestra `data.name` + `data.location_label` en lugar de un literal fijo.
- **Contrato:** consume `GET /station/{id?}` (envelope `{success, message, data}`). `data` incluye `name`, `location_label`, `instant` y los bloques de sensores (`wind.average/min/max/direction/direction_grades`, `light.uv_index`, `air_quality.quality/eco2/tvoc`, `lightning.count_in_window/count_last_hour/count_last_10_minutes`, `temperature`, `humidity`, `pressure`) ya formateados como números.

### Iconos Material Symbols por sensor

Cada tarjeta de sensor usa un icono representativo definido en `SENSOR_MAP` del controlador.

### Luz, UVA y UVB: qué campo va en cada tarjeta (2026-09-09)

`lumens`, `lux`, `index` (índice UV), `uva` y `uvb` son columnas del mismo
modelo `Light`, pero se reparten en tres tarjetas distintas del dashboard
(`SENSOR_MAP` en `WeatherStationController`):

| Tarjeta | `primary` | `secondary` |
|---------|-----------|--------------|
| Luz | `lumens` (lm) | `lux` |
| UVA | `uva` | `index` (índice UV) |
| UVB | `uvb` | `index` (índice UV) |

`index` es una única lectura del sensor (el índice UV general), no un valor
distinto para UVA y UVB, así que aparece como secundario en ambas tarjetas.
Antes se mostraba por error como secundario de la tarjeta de Luz —donde
parecía "el índice de la luz"— y las tarjetas de UVA/UVB no mostraban nada
debajo del valor.

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
> Retirado de las trece tablas el 2026-09-06. Comprobado antes sobre los
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
| Modelos (18 sensores + 12 AEMET) | ✅ |
| API V2 (27 rutas) | ✅ |
| Tests (22 métodos) | ✅ |
| Comandos AEMET (12) | ✅ existen |
| **Scheduler de AEMET** | ✅ Arreglado: un comando por producto, con la cadencia que declara AEMET. `SchedulerTest` impide que vuelva a programar comandos que no existen |
| Frontend público | ✅ |
| **Panel Filament** | 🟠 Sin Resource de sensores. Sí hay panel de AEMET (`/admin/aemet`) con una tarjeta por producto y su botón de resincronizar |
| Broadcasting en vivo | 🟠 Implementado y **apagado por defecto**. Un evento por subida (`ReadingsReceived`) al canal público `weather-station.{id}`. Falta `composer require laravel/reverb` y levantar el demonio. Ver [websockets.md](websockets.md) |

---

> Creado: 2026-05-25 · Última revisión: 2026-09-14
