# 🧱 Fundamentos de Open-Meteo


> [!IMPORTANT]
> **Lectura obligatoria antes de cualquier otro archivo.**

Complementos obligatorios: [`ERRATAS.md`](ERRATAS.md) y [`LIMITACIONES.md`](LIMITACIONES.md).

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/*.yml` (9 specs OpenAPI 3.1.0 del repositorio
> oficial), `src/web-texto/forecast.txt`, `src/web-texto/features.txt`, `src/web-texto/terms.txt`,
> `src/web-texto/licence.txt`, `src/web-texto/model-updates.txt` + **verificación en vivo del
> 2026-08-31 y 2026-09-01** (190 peticiones reales espaciadas 3–5 s, sin credencial, contra los 16
> endpoints, en tres rondas: verificación inicial, cierre de pendientes y comprobación de cobertura
> y formatos).

---

## Datos básicos

| | |
|---|---|
| **Proveedor** | Open-Meteo.com (Patrick Zippenfenig, Suiza) |
| **Base URL** | Un **subdominio distinto por API** — ver tabla más abajo 🟢 |
| **Prefijo de rutas** | `/v1/` (excepto la metadata API: `/data/…`) 🟢 |
| **Métodos** | `GET` y **`POST`** 🟢 — el `POST` funciona pero **no está documentado** ([`D10`](ERRATAS.md#d10--la-api-acepta-post-y-eso-no-está-documentado-en-ninguna-parte-)) |
| **Autenticación** | **Ninguna** en el nivel gratuito · `apikey` en query para clientes de pago 🟢 |
| **Codificación** | `UTF-8` real y declarado (`charset=utf-8`) 🟢 |
| **Formato de respuesta** | `application/json` 🟢 · también `csv`, `xlsx`, `flatbuffers` vía `&format=` |
| **Especificación** | OpenAPI 3.1.0, **solo para 9 de los 16 endpoints** ⚠️ |
| **Versionado** | Semántico; los cambios de ruptura llegan como nueva versión mayor 🔵 |

> [!CAUTION]
> **Un `200 OK` puede traer un cuerpo que ni siquiera es JSON.** Si se pide un modelo que no cubre
> la coordenada, la respuesta es `{"latitude":nan,…}` con `nan` sin comillas: `json_decode` devuelve
> `null` y `JSON.parse` lanza excepción. Verificado el 2026-08-31 —
> [`A7`](ERRATAS.md#a7--un-modelo-fuera-de-su-dominio-devuelve-json-inválido-con-200-ok-).

> [!WARNING]
> **No hay una única base URL.** Cada API vive en su propio subdominio y usar el equivocado no da
> un 404 evidente: puede devolver `200 OK` con valores `null`. Verificado el 2026-08-31 con
> `seasonal-api.open-meteo.com/v1/forecast` → ver [`ERRATAS.md`](ERRATAS.md#a3--un-host-equivocado-devuelve-200-con-la-serie-entera-a-null-).

---

## Mapa de endpoints 🟢

Los 16 endpoints, todos verificados con una petición real el **2026-08-31**:

| # | Host | Ruta | Módulo |
|---|---|---|---|
| 1 | `api.open-meteo.com` | `/v1/forecast` | [`01`](01-prevision-meteorologica.md) |
| 2 | `archive-api.open-meteo.com` | `/v1/archive` | [`02`](02-historico-reanalisis.md) |
| 3 | `historical-forecast-api.open-meteo.com` | `/v1/forecast` | [`03`](03-archivo-de-predicciones.md) |
| 4 | `previous-runs-api.open-meteo.com` | `/v1/forecast` | [`03`](03-archivo-de-predicciones.md) |
| 5 | `single-runs-api.open-meteo.com` | `/v1/forecast` | [`03`](03-archivo-de-predicciones.md) |
| 6 | `ensemble-api.open-meteo.com` | `/v1/ensemble` | [`04`](04-ensemble.md) |
| 7 | `seasonal-api.open-meteo.com` | `/v1/seasonal` | [`05`](05-estacional.md) |
| 8 | `climate-api.open-meteo.com` | `/v1/climate` | [`06`](06-clima-cmip6.md) |
| 9 | `marine-api.open-meteo.com` | `/v1/marine` | [`07`](07-marina.md) |
| 10 | `air-quality-api.open-meteo.com` | `/v1/air-quality` | [`08`](08-calidad-del-aire.md) |
| 11 | `flood-api.open-meteo.com` | `/v1/flood` | [`09`](09-inundaciones.md) |
| 12 | `satellite-api.open-meteo.com` | `/v1/archive` | [`10`](10-radiacion-satelite.md) |
| 13 | `geocoding-api.open-meteo.com` | `/v1/search` | [`11`](11-geocodificacion-y-elevacion.md) |
| 14 | `geocoding-api.open-meteo.com` | `/v1/get` | [`11`](11-geocodificacion-y-elevacion.md) |
| 15 | `api.open-meteo.com` | `/v1/elevation` | [`11`](11-geocodificacion-y-elevacion.md) |
| 16 | `api.open-meteo.com` | `/data/{modelo}/static/meta.json` | [`12`](12-modelos-y-actualizaciones.md) |

**Suma: 16 endpoints = los 16 documentados en los módulos.**

Para clientes de pago el host lleva el prefijo `customer-` (`customer-api.open-meteo.com`,
`customer-archive-api.open-meteo.com`…) 🟢 y el resto de la sintaxis es idéntica 🔵.

---

## Autenticación

### Nivel gratuito: sin credencial 🟢

No hay clave, ni registro, ni cabecera. Cualquier `GET` anónimo funciona. Verificado el 2026-08-31:
todas las peticiones de esta documentación se hicieron sin credencial.

### Nivel de pago: `apikey` en la query 🟢

```
https://customer-api.open-meteo.com/v1/forecast?…&apikey=$OPEN_METEO_API_KEY
```

Dos reglas verificadas el 2026-08-31:

| Situación | Resultado real |
|---|---|
| `apikey` en el **dominio libre** (`api.open-meteo.com`) | **`303 See Other`** con `Location:` al dominio `customer-` (y una **doble barra**: `customer-api.open-meteo.com//v1/forecast`). Cuerpo vacío |
| Dominio `customer-` **sin** `apikey` | `401` + `{"error":true,"reason":"API key required. Please add &apikey= to the URL."}` |

> [!CAUTION]
> El `303` importa: un cliente HTTP que **no siga redirecciones** recibirá un cuerpo vacío con un
> código que no es de error para muchas librerías. Detalle en [`ERRATAS.md`](ERRATAS.md#a4--apikey-en-el-dominio-libre-provoca-un-303-con-location-malformado-).

La clave nunca se escribe aquí: iría en `.env` como `OPEN_METEO_API_KEY`. **Api Raupulus no tiene
todavía ninguna suscripción contratada** — ver la advertencia de uso comercial en
[`LIMITACIONES.md`](LIMITACIONES.md#uso-comercial--el-límite-que-no-es-técnico).

### Caducidad

No hay caducidad que gestionar en el nivel gratuito (no hay credencial). Para las claves de pago la
documentación oficial no menciona caducidad 🔴.

---

## Peticiones POST — la vía para consultas masivas 🟢

Aunque toda la documentación oficial hable solo de `GET`, la API acepta `POST` en las mismas rutas.
Verificado el 2026-09-01:

```bash
# formulario: idéntico al GET
curl -X POST "https://api.open-meteo.com/v1/forecast" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data "latitude=40.4168&longitude=-3.7038&daily=temperature_2m_max&timezone=Europe/Madrid"

# JSON: los parámetros multivalor van como arrays
curl -X POST "https://api.open-meteo.com/v1/forecast" \
  -H "Content-Type: application/json" \
  -d '{"latitude":[40.4168],"longitude":[-3.7038],"daily":["temperature_2m_max"],"timezone":["Europe/Madrid"]}'
```

| | |
|---|---|
| Cuerpo máximo | 128 kB 🔵 (según el código del servidor) |
| Coordenadas por petición | **500 comprobadas** 🟢: array de 500 elementos, 153 KB, 0,38 s |
| Cuota | Sigue siendo **una sola llamada** 🟡 |
| Riesgo | Indocumentado: puede desaparecer sin aviso. Conviene poder volver a `GET` |

> [!WARNING]
> En JSON, `{"timezone":"Europe/Madrid"}` devuelve `400` — «Value was not of type 'Array<Any>' at
> path 'timezone'». Hay que escribirlo como array: `{"timezone":["Europe/Madrid"]}`.

---

## Flujo de una petición

**Un solo salto.** No hay paso intermedio de descarga como en AEMET: se pide y se responde con los
datos en el mismo cuerpo 🟢.

```
GET https://api.open-meteo.com/v1/forecast?latitude=…&longitude=…&hourly=…
   └─► 200 application/json  { …datos… }
```

La única excepción a "todo va en la respuesta" es el redirect `303` cuando se pasa `apikey` al
dominio libre.

---

## Codificación 🟢

`UTF-8` real, coincidente con el `charset=utf-8` declarado. Verificado el 2026-08-31 en respuestas
que incluyen caracteres no ASCII: `"°C"`, `"μg/m³"`, `"m³/s"`, `"W/m²"`, `"España"`, `"Andalucía"`.

No hay que hacer ninguna conversión de codificación: `json_decode()` funciona directamente sobre el
cuerpo. Es la diferencia principal con AEMET.

**Cuidado con las unidades como texto**: los valores de `*_units` (`"°C"`, `"μg/m³"`) son cadenas
UTF-8 con caracteres multibyte. Si se guardan en base de datos, la columna debe ser `utf8mb4`.

---

## Validación de respuestas

**El código HTTP no basta.** Comportamiento real medido el 2026-08-31 sobre `/v1/forecast`:

| Situación | HTTP 🟢 | Cuerpo 🟢 |
|---|---|---|
| Éxito | `200` | JSON con metadatos + bloques `hourly`/`daily`/`current` |
| **Sin `latitude` ni `longitude`** | **`200`** | **VACÍO (0 bytes)** ⚠️ |
| Solo `latitude`, sin `longitude` | `400` | `{"reason":"Parameter 'latitude' and 'longitude' must have the same number of elements","error":true}` |
| Coordenada fuera de rango | `400` | `{"reason":"Latitude must be in range of -90 to 90°. Given: 522.52.","error":true}` |
| Variable inexistente | `400` | `{"error":true,"reason":"Data corrupted at path ''. Cannot initialize SurfacePressureAndHeightVariable<…> from invalid String value tempeture_2m."}` |
| **Sin ninguna variable pedida** | **`200`** | **Solo metadatos, sin bloque de datos** ⚠️ |
| **Modelo válido fuera de su cobertura** | **`200`** | **`{"latitude":nan,…}` — JSON INVÁLIDO** ⚠️ |
| Modelo inexistente | `400` | `{"error":true,"reason":"Cannot initialize MultiDomains from invalid String value …"}` |
| Ruta inexistente | `404` | `{"reason":"Not Found","error":true}` |
| Sin credencial (dominio libre) | `200` | Normal: no se necesita credencial |
| Dominio `customer-` sin `apikey` | `401` | `{"error":true,"reason":"API key required…"}` |
| Límite superado | 🔴 | 🔴 No sondeado a propósito — ver [`LIMITACIONES.md`](LIMITACIONES.md) |

El objeto de error tiene siempre esta forma 🟢 (el **orden de las claves varía** entre respuestas —
no se puede parsear por posición):

```json
{"error": true, "reason": "texto explicativo"}
```

### Orden de comprobaciones recomendado 🟡

1. **¿El cuerpo está vacío?** → error de parámetros que la API no señaló (caso `200` + 0 bytes).
2. **¿El cuerpo parsea como JSON?** Si falla, lo más probable es que traiga `nan` sin comillas por
   haber pedido un modelo que no cubre esa coordenada — no es una caída del servicio
   ([`A7`](ERRATAS.md#a7--un-modelo-fuera-de-su-dominio-devuelve-json-inválido-con-200-ok-)).
3. ¿Hay `error: true` en el cuerpo? → usar `reason` para el log.
4. ¿Existe el bloque de datos que pediste (`hourly`, `daily`, `current`)? → si no, la petición se
   quedó sin variables.
5. ¿Los valores son `null`? Un `200` con la serie entera a `null` es un caso real (host o rango de
   fechas equivocados) — ver [`ERRATAS.md`](ERRATAS.md#a3--un-host-equivocado-devuelve-200-con-la-serie-entera-a-null-).
6. Para multi-localización: ¿la raíz es un objeto o un array? Cambia según el número de coordenadas.

---

## Parámetros comunes a casi todas las APIs

Estos parámetros se repiten con el mismo nombre y semántica en la mayoría de endpoints 🔵/🟢. Las
diferencias por endpoint (valores por defecto y máximos) están en cada módulo.

| Parámetro | Formato | Notas |
|---|---|---|
| `latitude`, `longitude` | Decimal WGS84, coma como separador de lista | **Obligatorios.** Deben tener el mismo número de elementos |
| `hourly`, `daily`, `current`, `minutely_15` | Lista separada por comas | También admite repetir el parámetro (`&hourly=a&hourly=b`) 🔵 |
| `timezone` | Nombre IANA (`Europe/Madrid`) o `auto` | Por defecto `GMT`. Con `auto` se resuelve por coordenadas 🟢 |
| `timeformat` | `iso8601` (defecto) \| `unixtime` | Con `unixtime` los valores son el **instante UTC real**; con `iso8601` son hora local **sin indicar la zona** 🟢 — ver aviso abajo |
| `temperature_unit` | `celsius` (defecto) \| `fahrenheit` | |
| `wind_speed_unit` | `kmh` (defecto) \| `ms` \| `mph` \| `kn` | |
| `precipitation_unit` | `mm` (defecto) \| `inch` | |
| `past_days` | Entero 0–92 | |
| `forecast_days` | Entero | **El máximo cambia en cada API** |
| `start_date`, `end_date` | `yyyy-mm-dd` | |
| `start_hour`, `end_hour` | `yyyy-mm-ddThh:mm` | No aparece en las specs OpenAPI ⚠️ |
| `cell_selection` | `land` \| `sea` \| `nearest` | El valor por defecto **cambia según la API** |
| `models` | Lista separada por comas | Por defecto `best_match` donde aplica |
| `elevation` | Decimal, o `nan` | `nan` desactiva el ajuste estadístico por altitud |
| `format` | `json` (defecto) \| `csv` \| `xlsx` \| `flatbuffers` | Los cuatro verificados 🟢. **No aparece en ninguna spec OpenAPI** ⚠️ |
| `apikey` | Cadena | Solo en dominios `customer-` |

> [!CAUTION]
> **Las marcas de tiempo ISO no llevan zona horaria.** Con `timezone=Europe/Madrid` la API devuelve
> `"2026-09-01T00:00"`: es hora local de Madrid, pero la cadena no lo dice —sin `Z`, sin `+02:00`—.
> Un `Carbon::parse()` con `APP_TIMEZONE=UTC` desplaza toda la serie dos horas sin avisar.
> O se parsea indicando la zona de `timezone` explícitamente, o se usa `timeformat=unixtime`, que
> devuelve el instante UTC y no admite interpretación. Verificado el 2026-09-01:
> [`A10`](ERRATAS.md#a10--las-marcas-de-tiempo-iso-no-llevan-zona-horaria-aunque-sean-hora-local-).

### Precauciones al construir la URL

- **Longitudes negativas para España**: `-3.7038` (Madrid). Un signo perdido lleva la consulta a
  otro continente sin ningún error.
- **`timezone` hay que codificarlo**: `Europe%2FMadrid`. Sin codificar la barra, el comportamiento
  no está verificado 🔴.
- **`daily` requiere `timezone`** según la documentación oficial de `/v1/forecast` y `/v1/marine`
  🔵. En la verificación del 2026-08-31 **no se probó ese caso en `/v1/forecast`**; sí se observó
  que `/v1/flood` y `/v1/climate` aceptan `daily` sin `timezone` y responden `200` en `GMT` 🟢 —
  son APIs distintas, así que no dice nada de `/v1/forecast`. Pasar siempre `timezone` explícito.
- **Los códigos numéricos son números, no cadenas**: `weather_code` llega como entero (`0`).
- **La serie empieza a las 00:00 del día en curso en la zona pedida**, no en la hora actual. Con
  `timezone=Europe/Madrid` eso son las 22:00 UTC del día anterior 🟢.
- **`nan` es una cadena literal** en `elevation`, `tilt` y `azimuth`, no un `null` de JSON.

---

## Estructura de la respuesta 🟢

Verificada el 2026-08-31 en `/v1/forecast`, `/v1/archive`, `/v1/air-quality`, `/v1/marine`,
`/v1/flood`, `/v1/climate`, `/v1/ensemble`, `/v1/seasonal` y los tres archivos de predicciones: la
forma es la misma en todos ellos.

```json
{
  "latitude": 40.4375,
  "longitude": -3.6875,
  "generationtime_ms": 0.0307,
  "utc_offset_seconds": 0,
  "timezone": "GMT",
  "timezone_abbreviation": "GMT",
  "elevation": 666.0,
  "hourly_units": { "time": "iso8601", "temperature_2m": "°C" },
  "hourly":       { "time": ["2026-08-31T00:00", …], "temperature_2m": [17.3, …] }
}
```

| Campo | Qué es |
|---|---|
| `latitude` / `longitude` | **El centro de la celda de rejilla usada**, no la coordenada pedida. Puede estar a varios km 🟢 |
| `elevation` | Altitud del modelo digital de 90 m para esa celda |
| `generationtime_ms` | Tiempo de generación en el servidor. Solo métrica |
| `utc_offset_seconds` | Desplazamiento aplicado por `timezone` |
| `hourly_units` / `daily_units` / `current_units` | Unidad de cada variable, como texto |
| `hourly` / `daily` / `minutely_15` | **Arrays paralelos**: `time[i]` corresponde a `temperature_2m[i]` |
| `current` | Objeto plano (no arrays) con `time` e `interval` (segundos de agregación) |

> [!IMPORTANT]
> Los bloques de datos son **arrays paralelos indexados por posición**, no una lista de objetos por
> instante. Nunca se debe asumir que la longitud de dos variables coincide sin comprobarlo, ni que
> `time[0]` es la hora actual: por defecto la serie empieza a las 00:00 del día de hoy.

### Los valores pueden ser `null` dentro de un `200` 🟢

Verificado el 2026-08-31: `/v1/climate` con `start_date=2050-01-01` y modelo `EC_Earth3P_HR`
devolvió `"temperature_2m_max": [null, null]` con `200 OK`. La misma petición con fechas de 2030
devolvió valores reales. Un `null` no es un error de la API: es "no hay dato ahí".

### Multi-localización: la raíz cambia de forma ⚠️🟢

Con **una** coordenada la raíz es un **objeto**. Con **varias** es un **array** de objetos. Además,
verificado con tres coordenadas el 2026-08-31:

```
elemento 0 → claves sin  location_id
elemento 1 → location_id = 1
elemento 2 → location_id = 2
```

**`location_id` no existe en el primer elemento.** Detalle en [`ERRATAS.md`](ERRATAS.md#a2--location_id-no-existe-en-el-primer-elemento-de-una-respuesta-multi-localización-).

---

## Compresión 🟢

Con `Accept-Encoding: gzip, deflate, br` el servidor respondió `Content-Encoding: deflate`
(2026-08-31, previsión de 16 días: 1.771 bytes comprimidos). Sin la cabecera responde sin comprimir
y con `Transfer-Encoding: chunked`. Merece la pena pedir compresión en series largas.

---

## Un ejemplo completo, de principio a fin 🟢

Ejecutado el **2026-08-31** contra la API real, sin credencial:

```bash
curl -s "https://api.open-meteo.com/v1/forecast?latitude=40.4168&longitude=-3.7038&daily=temperature_2m_max&timezone=Europe%2FMadrid&forecast_days=1"
```

Respuesta literal (`200`, `application/json; charset=utf-8`, 325 bytes):

```json
{
  "latitude": 40.4375,
  "longitude": -3.6875,
  "generationtime_ms": 0.0270,
  "utc_offset_seconds": 7200,
  "timezone": "Europe/Madrid",
  "timezone_abbreviation": "GMT+2",
  "elevation": 666.0,
  "daily_units": { "time": "iso8601", "temperature_2m_max": "°C" },
  "daily": { "time": ["2026-08-31"], "temperature_2m_max": [32.7] }
}
```

Obsérvese que la coordenada devuelta (`40.4375, -3.6875`) **no es la pedida**
(`40.4168, -3.7038`): es el centro de la celda de rejilla.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Comportamiento y cabeceras de un `429` (**no se sondeará a propósito**: solo se registrará si ocurre en uso normal) | Alta |
| 2 | Comportamiento con `timezone` sin codificar la barra | Baja |
| 3 | Si los dominios `customer-` aceptan exactamente los mismos parámetros | Baja (sin suscripción no se puede probar) |
| 4 | Si `Accept-Encoding: gzip` a secas devuelve `gzip` o también `deflate` | Baja |

> Creado: 2026-09-01 · Última revisión: 2026-09-01
