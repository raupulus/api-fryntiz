# ⚠️ Erratas y trampas de Open-Meteo


> [!CAUTION]
> **Lectura obligatoria antes de implementar o modificar cualquier endpoint.**
> Agrupa todo lo que la documentación oficial dice mal, dice a medias o contradice el
> comportamiento real. Las del bloque A **rompen la integración sin lanzar ningún error**.

- **Fecha de la última verificación en vivo:** `2026-09-01` (ronda inicial: `2026-08-31`)
- **Fuente verificada:** 190 peticiones reales espaciadas 3–5 s, **sin credencial**, contra los 16
  endpoints públicos (33 en la ronda inicial, 50 en la de cierre de pendientes altos —28 de ellas
  para la matriz de cobertura por modelo— y 107 en la de cierre de pendientes medios, esta
  última apoyada además en la lectura del código del servidor en GitHub). Registro completo del proceso en el módulo correspondiente de cada endpoint.
- **Documentación auditada:** `src/especificacion/*.yml` (9 specs OpenAPI 3.1.0) y
  `src/web-texto/*.txt` (18 páginas oficiales transcritas).

Leyenda: 🟢 verificado · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

---

## 🔴 Bloque A — Erratas que rompen la integración sin avisar

### A1 · Faltar `latitude`/`longitude` devuelve `200` con el cuerpo VACÍO 🟢

Las specs marcan ambos parámetros como `required: true`. La realidad:

```bash
curl -i "https://api.open-meteo.com/v1/forecast?hourly=temperature_2m"
```

```
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8
(cuerpo de 0 bytes)
```

**Por qué rompe en silencio:** `json_decode('')` devuelve `null` sin excepción, y un
`$response->successful()` de Laravel da `true`. El resultado es un `null` que se propaga hasta donde
se use.

**Qué hacer:** comprobar que el cuerpo no está vacío **antes** de decodificar, en todos los
endpoints. Es la primera comprobación de la lista de
[`00-fundamentos.md`](00-fundamentos.md#orden-de-comprobaciones-recomendado-).

Curiosamente, pasar **solo** `latitude` sí da un `400` correcto. El fallo silencioso ocurre cuando
faltan **los dos**.

### A2 · `location_id` no existe en el primer elemento de una respuesta multi-localización 🟢

La documentación oficial dice que en CSV y XLSX «se añade una columna `location_id`» y no menciona
el caso JSON. Verificado con tres coordenadas el 2026-08-31:

| Elemento | ¿Tiene `location_id`? | Valor |
|---|---|---|
| 0 | **No** | — |
| 1 | Sí | `1` |
| 2 | Sí | `2` |

**Por qué rompe en silencio:** indexar por `$item['location_id']` deja fuera la primera localización
o lanza un aviso de clave inexistente, según el lenguaje. Un `array_column($data, null,
'location_id')` pierde silenciosamente el primer elemento.

**Qué hacer:** usar el **índice posicional del array**, que sí coincide con el orden de las
coordenadas pedidas, o tratar la ausencia de `location_id` como `0`.

### A3 · Un host equivocado devuelve `200` con la serie entera a `null` 🟢

El formulario de la página oficial de la API estacional lleva
`action="https://seasonal-api.open-meteo.com/v1/forecast"`, mientras que los ejemplos de URL de esa
misma página usan `/v1/seasonal`. Comprobado el 2026-08-31, la ruta del formulario responde:

```json
{"latitude":40.4375,…,"daily":{"time":["2026-08-31","2026-09-01"],"temperature_2m_max":[null,null]}}
```

`200 OK`, estructura correcta, **valores nulos**. La ruta correcta (`/v1/seasonal`) devuelve los 51
miembros del ensemble en la misma petición.

**Por qué rompe en silencio:** no hay `error`, ni `404`, ni cuerpo vacío. Solo datos que no existen.

**Qué hacer:** tratar «serie completa a `null`» como un fallo de configuración, no como ausencia de
datos meteorológicos. Y usar siempre la tabla de hosts de
[`00-fundamentos.md`](00-fundamentos.md#mapa-de-endpoints-).

### A4 · `apikey` en el dominio libre provoca un `303` con `Location` malformado 🟢

```bash
curl -i "https://api.open-meteo.com/v1/forecast?latitude=40.4168&longitude=-3.7038&apikey=xxx"
```

```
HTTP/1.1 303 See Other
location: https://customer-api.open-meteo.com//v1/forecast?…&apikey=xxx
```

Nótese la **doble barra** `com//v1/`. La redirección funciona en curl, pero:

- Un cliente HTTP con las redirecciones desactivadas recibe un cuerpo vacío con código `303`, que
  no es `4xx` ni `5xx` y puede pasar por «éxito» en comprobaciones laxas.
- La `apikey` viaja en la URL de la redirección, y por tanto **acaba en los logs** de cualquier
  proxy intermedio.

**Qué hacer:** enviar la `apikey` directamente al host `customer-…` y no depender del redirect.

### A5 · Los valores `null` conviven con `200 OK` 🟢

Verificado el 2026-08-31 en `/v1/climate`: la petición con `start_date=2050-01-01` y el modelo
`EC_Earth3P_HR` devolvió `[null, null]`; la misma petición para 2030 devolvió `[32.0, 33.5]`. Es
decir: fuera del rango real de un modelo, la API no avisa, rellena con nulos.

**Qué hacer:** validar los valores, no solo la forma. Un gráfico o una media aritmética sobre nulos
falla o miente.

### A6 · Sin variables pedidas, `200` con solo metadatos 🟢

```bash
curl "https://api.open-meteo.com/v1/forecast?latitude=40.4168&longitude=-3.7038"
```

Devuelve `200` y 170 bytes: coordenadas, altitud, zona horaria… y **ningún bloque `hourly` ni
`daily`**. Acceder a `$data['hourly']['time']` revienta.

### A7 · Un modelo fuera de su dominio devuelve **JSON inválido** con `200 OK` 🟢

El hallazgo más grave de toda la verificación. Verificado el 2026-08-31 con tres peticiones:

```bash
curl "https://api.open-meteo.com/v1/forecast?latitude=40.4168&longitude=-3.7038&hourly=temperature_2m&models=metno_nordic"
```

```
HTTP/1.1 200 OK
Content-Type: application/json; charset=utf-8

{"latitude":nan,"longitude":nan,"generationtime_ms":0.0021,"utc_offset_seconds":0,
 "timezone":"GMT","timezone_abbreviation":"GMT"}
```

**`nan` sin comillas no es JSON válido.** Ni `json_decode` de PHP, ni `JSON.parse`, ni el módulo
`json` de Python pueden leerlo: el primero devuelve `null` silenciosamente, los otros lanzan
excepción. Y llega con **código 200**.

**No todos los modelos fuera de cobertura se comportan igual.** Verificado con 28 combinaciones de
modelo y coordenada el 2026-08-31:

| Caso | Resultado |
|---|---|
| Modelo inexistente (`modelo_que_no_existe`) | `400`: «Cannot initialize MultiDomains from invalid String value» |
| Modelo cuya rejilla **no llega** a la coordenada (`icon_d2` o `icon_eu` en Canarias) | `400`: **`{"error":true,"reason":"No data is available for this location"}`** — correcto y legible |
| Modelo cuya rejilla **sí llega pero sin datos** (`metno_nordic`, `knmi_harmonie_arome_europe`, `dmi_harmonie_arome_europe`, `italia_meteo_arpae_icon_2i`, `meteofrance_arome_france` en Cádiz) | **`200` con `nan`** |
| Mismo modelo dentro de su dominio | `200` correcto |

Es decir: el `400` legible es lo habitual, y el `nan` aparece en el caso intermedio —rejilla que
abarca la coordenada pero sin valores— que es **imposible de anticipar desde fuera**.

**Por qué importa en España:** `meteofrance_arome_france` funciona en Madrid y en Baleares y
devuelve `nan` en Cádiz y en Canarias. Probar en la capital y dar el modelo por bueno para todo el
país es exactamente la forma de que esto llegue a producción. Matriz completa en
[`01-prevision-meteorologica.md`](01-prevision-meteorologica.md#cobertura-real-en-españa-).

**Qué hacer:**

1. No fijar modelos regionales sin comprobar su cobertura; con `best_match` no ocurre.
2. Tratar el fallo de parseo como un error de la petición, no como una caída de la API.
3. Si se permite elegir modelo desde configuración, validar antes contra la coordenada.

### A8 · Una búsqueda sin resultados no devuelve `results` 🟢

```bash
curl "https://geocoding-api.open-meteo.com/v1/search?name=Zzzxqvnoexistelugar&count=5"
```

```
HTTP/1.1 200 OK
{"generationtime_ms":0.52297115}
```

**No hay clave `results`**, ni siquiera como array vacío. Un `foreach ($data['results'] …)` lanza un
aviso y recorre `null`. Hay que leerlo siempre con un valor por defecto (`$data['results'] ?? []`).

### A9 · La API de radiación por satélite mezcla observación y relleno, salvo que se fije el modelo 🟢

Consultada el **2026-08-31 a las 06:35 UTC** para ese mismo día, devolvió la curva completa hasta
las 23:00, con 868 W/m² a las 13:00 —siete horas en el futuro— y ningún indicador de que esos
valores no fueran observados.

**Causa aislada el 2026-09-01** repitiendo la petición y cambiando un solo parámetro cada vez:

| Petición (mismo día, 09:45 UTC) | Última hora con dato |
|---|---|
| Sin `models` | **23:00** — la curva completa, futuro incluido |
| Sin `models`, con `timezone=UTC` | **23:00** — la zona horaria no influye |
| **`models=satellite_radiation_seamless`** | **09:00** — solo lo realmente observado |

Es decir: **el modelo por defecto no es el «seamless» de satélite**. Por defecto la API completa el
resto del día con valores que no proceden del satélite; tampoco son los de la Forecast API (852
frente a 871 a las 12:00 del 2026-08-31), así que su origen sigue sin identificarse 🔴.

**Qué hacer:** para radiación **observada**, pasar siempre `models=satellite_radiation_seamless` y
comprobar dónde se corta la serie. Sin ese parámetro se están mezclando medidas con relleno.

### A10 · Las marcas de tiempo ISO **no llevan zona horaria**, aunque sean hora local 🟢

Con `timezone=Europe/Madrid`, la respuesta trae:

```json
"utc_offset_seconds": 7200,
"timezone": "Europe/Madrid",
"hourly": {"time": ["2026-09-01T00:00", "2026-09-01T01:00", …]}
```

`"2026-09-01T00:00"` **es hora local de Madrid**, pero la cadena no lo dice: no hay `Z`, ni `+02:00`,
ni offset alguno. Cualquier parser que aplique su zona por defecto —`Carbon::parse()` en una
aplicación con `APP_TIMEZONE=UTC`, `new Date()` en JavaScript— la interpretará mal y desplazará
toda la serie dos horas.

Verificado el 2026-09-01 comparando las dos formas de la **misma** petición:

| `timeformat` | Primer instante | Qué es en realidad |
|---|---|---|
| `iso8601` | `"2026-09-01T00:00"` | Hora **local** de Madrid, sin indicarlo |
| `unixtime` | `1788213600` | `2026-08-31T22:00 UTC` — el mismo instante, sin ambigüedad |

**Qué hacer:** o se parsea la cadena ISO indicando explícitamente la zona de `timezone`, o se usa
`timeformat=unixtime`, que devuelve el instante UTC real y no admite interpretación.

Y ojo con el aviso oficial 🔵 —«para valores diarios con timestamps unix, aplique
`utc_offset_seconds` de nuevo para obtener la fecha correcta»—: **no significa que el timestamp esté
mal**. El timestamp es correcto como instante; sumar el offset sirve solo para saber a qué **fecha
local** pertenece ese día (`1788213600 + 7200` → `2026-09-01T00:00`, que es el día que la API
considera).

---

## 🟠 Bloque B — La documentación miente sobre el formato

### B1 · Las specs OpenAPI no declaran `servers` a nivel de documento 🟢

Los 9 ficheros `openapi/*.yml` declaran los servidores **dentro de cada path item**, no en la raíz.
Muchos generadores de clientes ignoran esa ubicación y producen un cliente sin host base. Los
valores están ahí y son correctos (por ejemplo `https://flood-api.open-meteo.com` +
`https://customer-flood-api.open-meteo.com`), pero hay que ir a buscarlos.

### B2 · La spec no describe `*_units`, y la API siempre los devuelve 🟢

El `schema` de la respuesta `200` de `forecast.yml` enumera `latitude`, `longitude`, `elevation`,
`generationtime_ms`, `utc_offset_seconds`, `timezone`, `timezone_abbreviation` y `hourly`, pero
**no** `hourly_units`, `daily_units` ni `current_units`. La API los devuelve siempre (verificado en
todos los endpoints de datos el 2026-08-31), y la documentación web sí los describe.

Consecuencia: un cliente generado desde la spec con validación estricta descarta esos campos.

### B3 · El orden de las claves del objeto de error no es estable 🟢

Ambas formas se observaron el mismo día en el mismo endpoint:

```json
{"error":true,"reason":"…"}
{"reason":"…","error":true}
```

No parsear por posición; leer por clave.

### B4 · `Content-Type` sin `charset` en `/v1/elevation` 🟢

`/v1/elevation` respondió `application/json` a secas, mientras que el resto de endpoints responden
`application/json; charset=utf-8`. El cuerpo es igualmente UTF-8 (en ese caso, ASCII puro). Solo
importa si el cliente decide la codificación a partir de la cabecera.

### B5 · Los formatos alternativos no aparecen en ninguna spec, y todos funcionan 🟢

Probados uno a uno (2026-08-31 y 2026-09-01):

| `format=` | HTTP | `Content-Type` | Tamaño |
|---|---|---|---|
| `csv` | `200` | `text/csv; charset=utf-8` + `content-disposition: attachment` | 173 B |
| `xlsx` | `200` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` | 2.588 B |
| `flatbuffers` | `200` | `application/octet-stream` | 208 B |
| `protobuf` (solo geocodificación) | `200` | `application/x-protobuf` | 319 B, frente a 912 B del JSON |

Ninguno está declarado en las specs OpenAPI. Detalles que importan:

- **El CSV trae dos bloques separados por una línea en blanco**: primero los metadatos de la
  localización, después la tabla de datos con la unidad en la cabecera de columna
  (`temperature_2m_max (°C)`). No es un CSV de una sola tabla: un `fgetcsv` ingenuo lo lee mal.
- **`flatbuffers` es el más compacto** y el indicado para series largas, pero necesita el esquema
  para descodificarse; con una variable y un día ya ocupa un cuarto que el JSON.
- El `.proto` de la geocodificación está en el repositorio de GitHub de Open-Meteo 🔵.

---

## 🟡 Bloque C — Erratas de contenido en la documentación

### C1 · Contradicciones entre la spec OpenAPI y la web sobre `forecast_days`

| API | Spec OpenAPI | Página web | Realidad medida |
|---|---|---|---|
| Flood | `default=92`, `maximum=366` | «Integer (0-210) … Up to 210 days» | **Acepta 366** 🟢 — la spec acierta |
| Ensemble | `maximum=36` | «Integer (0-35) … Up to 35 days» | 🔴 sin probar |
| Seasonal | `default=183`, `maximum=217` | «hasta 9 meses» en la descripción general | 🔴 sin probar |

En Flood queda además un detalle que ninguna fuente menciona: **acepta 366 días pero solo devuelve
dato hasta el día ~185** (2026-09-01: 366 valores, 181 de ellos `null`, el primer nulo en el índice
185). Aceptar un parámetro no es lo mismo que tener datos para él.

### C2 · Parámetros marcados como obligatorios en la web y opcionales en la spec

| API | Parámetro | Web | Spec | Realidad medida el 2026-09-01 🟢 |
|---|---|---|---|---|
| Climate | `models` | Obligatorio | Opcional | **Opcional**: responde con una serie sin sufijo de modelo, y la respuesta no dice cuál ha usado |
| Climate | `daily` | Obligatorio | Opcional | **Opcional**: `200` con solo metadatos (el caso [`A6`](#a6--sin-variables-pedidas-200-con-solo-metadatos-)) |
| Ensemble | `models` | Obligatorio | Opcional | **Opcional**: devuelve 31 series, lo que apunta a GEFS 0,25° por defecto 🟡 |

**Gana la spec: los tres son opcionales.** Lo malo es lo que pasa al omitirlos: en Climate y Ensemble
la respuesta no identifica el modelo empleado, así que se obtienen datos sin saber de dónde salen.

### C3 · La columna «Default» de `cell_selection` contradice a su propia descripción 🔵

En las páginas de Air Quality, Flood y Marine la tabla dice `Default: nearest` (o `sea`, en Marine)
y **la descripción de la celda de al lado** dice «The default `land` finds a suitable grid-cell on
land…». Es un texto copiado sin adaptar, y la spec OpenAPI no declara ningún `default`.

**Resuelto para Marine** 🟢 (2026-09-01, costa de Cádiz): omitir el parámetro da exactamente el mismo
resultado que `cell_selection=sea` (celda `36.5417,-6.2917`), y `land` devuelve otra distinta
(`36.5417,-6.1250`, con valores de oleaje también distintos). **El valor por defecto de Marine es
`sea`**, como dice la columna y no como dice la descripción. En Air Quality y Flood sigue sin
comprobarse 🔴.

### C4 · Rango final de la Climate API: tres fuentes, tres respuestas

- Spec: «Maximum is `2050-12-31`».
- Web: «Data is available from 1950-01-01 until `2050-01-01`».
- Realidad medida el 2026-08-31, **consultando los siete modelos a la vez** con el rango
  `2049-12-28` – `2050-12-31` 🟢:

| Modelo | Último día con dato |
|---|---|
| `CMCC_CM2_VHR4` | `2050-12-31` |
| `FGOALS_f3_H` | `2050-12-31` |
| `HiRAM_SIT_HR` | `2050-12-31` |
| `MRI_AGCM3_2_S` | `2050-12-31` |
| **`EC_Earth3P_HR`** | **`2049-12-31`** — un año menos que el resto |
| `MPI_ESM1_2_XR` | `2050-12-31` |
| `NICAM16_8S` | `2050-12-31` |

Conclusión: **la spec acierta y la web se equivoca**, salvo para `EC_Earth3P_HR`, que se queda un año
corto sin que ninguna fuente lo mencione. Ese es el modelo que en la primera prueba devolvió
`[null, null]` para 2050.

### C5 · La `termsOfService` de las 9 specs apunta a un ancla que ya no existe 🟢

Todas declaran `termsOfService: https://open-meteo.com/en/features#terms`. La página `/en/features`
descargada el 2026-08-31 **no tiene ninguna sección `#terms`**: las condiciones están en
`/en/terms`.


### C6 · Las specs OpenAPI cubren 9 de los 16 endpoints ⚠️

No existe spec para: Historical Forecast, Previous Runs, Single Runs, Satellite Radiation, Geocoding
(`/v1/search` y `/v1/get`) ni la metadata API. Para esos siete, la única fuente es la web, con la
fiabilidad que eso implica.

### C7 · Los `enum` de las specs están incompletos: **no son un catálogo cerrado** 🟢

La spec de `/v1/forecast` enumera 22 variables `daily` y 50 valores de `models`. Verificado el
2026-08-31 que la API acepta valores que **no están en esos `enum`**:

| Valor probado | ¿En la spec? | Resultado real |
|---|---|---|
| `daily=cloud_cover_mean` | No | `200` con `"cloud_cover_mean":[0]`, unidad `%` |
| `daily=growing_degree_days_base_0_limit_50` | No | `200` con `[26.33]`, unidad `GGDc` |
| `models=chmi_aladin_seamless` | No | `200` con datos (Praga) |
| `models=dwd_icon_d2` | No (la spec dice `icon_d2`) | `200` con datos (Múnich) — **hay alias** |

La página web sí documenta esas variables diarias y esos modelos. Conclusión: **la spec va por
detrás de la API**, y validar la entrada del usuario contra sus `enum` rechazaría valores válidos.

Como contrapartida, un nombre de variable mal escrito sí da `400` con un mensaje explícito, así que
la API es su propio validador.

### C8 · `temporal_resolution=native` no hace nada en la API de satélite 🟢

La página de la Satellite Radiation API dice literalmente que los datos están disponibles en pasos
de 10, 15 o 30 minutos y que, para acceder a la resolución subyacente, hay que poner la resolución
temporal a `native`.

Verificado el 2026-08-31 con `temporal_resolution=native` y un día completo:

| Coordenada | Satélite esperado | Pasos devueltos |
|---|---|---|
| Madrid | SARAH3 / MTG | **24** (horarios) |
| Tokio | Himawari-9 (10 min nativos) | **24** (horarios) |

En las dos zonas devuelve exactamente lo mismo que sin el parámetro. O el nombre del parámetro es
otro, o no está implementado en este endpoint. Con los datos disponibles **no hay forma de obtener
la resolución subhoraria que la documentación promete** 🔴.

### C9 · La web describe CERRA como un producto en tiempo real, y termina en 2021 🟢

La tabla «Data Sources» de la página del histórico presenta CERRA así: «9 km · 6-Hourly ·
**2024 to present** · Daily with 2 days delay». Es decir, lo describe como un reanálisis regional
vivo y con dos días de desfase.

Comprobado el 2026-09-01 con `models=cerra` sobre Madrid:

| Fecha pedida | Resultado |
|---|---|
| `1985-06-01` | `19.1` |
| `2015-06-01` | `30.6` |
| `2020-06-01` | `29.5` |
| `2021-06-01` | `22.7` |
| `2022-06-01` | **`null`** |
| `2023-06-01` | **`null`** |
| `2026-06-01` | **`null`** |

**CERRA cubre aproximadamente 1985–2021 y no se actualiza.** Coincide con lo que publica Copernicus
para ese reanálisis y contradice de lleno a la web de Open-Meteo. Elegir `models=cerra` para
cualquier fecha posterior a 2021 devuelve `200` con nulos, sin ningún aviso.

---

## 🔵 Bloque D — Cosas que la API hace y la documentación no cuenta


### D1 · Cabecera `X-Encoding-Time` en la API de geocodificación 🟢

`geocoding-api.open-meteo.com` devuelve `X-Encoding-Time: 0.00987… ms`. No está documentada. Es
informativa; no depender de ella.

### D2 · La metadata API existe y no cuenta para la cuota 🟢🔵

`https://api.open-meteo.com/data/{modelo}/static/meta.json` devuelve un JSON con los tiempos de
ejecución del modelo. La web afirma que «las llamadas a la metadata API no cuentan para los límites
diarios ni mensuales» 🔵, pero **no publica la plantilla de URL en el texto** (solo la enlaza desde
una tabla que se genera por JavaScript). La ruta se dedujo y se verificó con `dwd_icon` el
2026-08-31. Detalle en [`12-modelos-y-actualizaciones.md`](12-modelos-y-actualizaciones.md).

### D3 · `/v1/get` responde en inglés salvo que se le pase `language` 🟢

`/v1/search?name=Sevilla&language=es` devuelve `"country":"España"`, `"admin1":"Andalucía"`. La
misma localidad por `/v1/get?id=2510911` **sin** `language` devuelve `"Seville"`, `"Spain"`,
`"Andalusia"`.

Verificado el 2026-08-31 que **`/v1/get` sí acepta `language`**: con `&language=es` devuelve
`"Sevilla"`, `"España"`, `"Andalucía"`. El parámetro no está documentado para este endpoint —la
página solo lo describe en `/v1/search`— pero funciona igual.

### D4 · `current` incluye un campo `interval` 🟢

La respuesta de `current` trae `"interval": 900` (segundos) además de `time`. Indica la ventana de
agregación hacia atrás de las variables acumuladas. Está documentado en la web pero es fácil
pasarlo por alto y tomar `precipitation` de `current` como «acumulado de la hora» cuando son 15
minutos.

### D5 · Compresión `deflate` 🟢

Pidiendo `gzip, deflate, br` el servidor eligió `deflate`. Ninguna documentación menciona qué
codificaciones soporta.

### D6 · Los archivos de predicciones tienen más historia de la que dicen —y con huecos 🟢

Verificado el 2026-09-01 sobre Madrid:

| API | Lo que dice la web | Lo que devuelve |
|---|---|---|
| Historical Forecast | «desde ~2021» | `2017-06-01` → 24/24 valores · `2016-06-01` → ninguno |
| Previous Runs | «desde enero de 2024» | `2023-06-01` → 24/24 valores |

Y en Previous Runs **la serie no es continua**: `2024-01-05` devolvió 0/24, mientras que
`2024-01-20`, `2024-03-01`, `2024-06-01` y `2025-06-01` devolvieron 24/24. Son huecos de días
sueltos que nada señala: llegan como `200` con la serie a `null`.

**Qué hacer:** no dar por buena la fecha de inicio documentada —hay más historia disponible— y, en
cualquier proceso que recorra fechas, contar los días sin dato en vez de suponer continuidad.

### D7 · Cero y nulo significan cosas distintas en la API de inundaciones 🟢

Verificado el 2026-09-01:

| Coordenada | Respuesta |
|---|---|
| Sáhara (`23, 13`) | `[0.0, 0.0]` — hay celda, el caudal es cero |
| Atlántico (`30, -40`) | `[null, null]` — no hay celda |

Ninguna fuente lo documenta. Un `0.0` **no** significa «aquí no hay río», y tratar ambos casos igual
falsea cualquier estadística.

### D8 · Las profundidades de suelo de otro modelo devuelven nulos, no un error 🟢

Cada modelo expone el suelo con su propia nomenclatura: ICON usa `soil_temperature_0cm`,
`6cm`, `18cm`, `54cm`; IFS usa `soil_temperature_0_to_7cm`, `7_to_28cm`… Verificado el 2026-09-01
que la API **acepta ambas y no siempre las traduce**:

| Petición | Resultado |
|---|---|
| `soil_temperature_0_to_7cm` con `models=icon_eu` | `200`, serie **entera a `null`** |
| `soil_temperature_0cm` con `models=ecmwf_ifs025` | `200`, 24/24 con valores |

La conversión funciona en un sentido y no en el otro, sin que nada lo indique.

### D9 · EFI y SOT solo existen en `weekly` 🟢

Las variables de extremos de la API estacional (`temperature_2m_efi`, `temperature_2m_sot10`,
`temperature_2m_sot90`, `precipitation_efi`, `precipitation_sot90`) están en el `enum` de la spec,
pero **pedirlas en `monthly` da `400`**: «Cannot initialize ForecastVariableMonthly from invalid
String value temperature_2m_efi». En `weekly` funcionan. La documentación las describe sin decir a
qué resolución pertenecen.

### D10 · La API acepta **POST**, y eso no está documentado en ninguna parte 🟢

Toda la documentación oficial describe únicamente peticiones `GET` con parámetros en la URL. El
código del servidor registra ambos verbos (`routes.swift`: `self.on(.GET, …)` y
`self.on(.POST, path, body: .collect(maxSize: "128kb"), …)`), y **funciona**. Verificado el
2026-09-01 contra `/v1/forecast`:

| Petición | Resultado |
|---|---|
| `POST` con `Content-Type: application/x-www-form-urlencoded` y los mismos parámetros | `200`, respuesta idéntica al `GET` |
| `POST` con `Content-Type: application/json` | `200`, **pero los parámetros multivalor deben ser arrays**: `{"timezone":"Europe/Madrid"}` da `400` y `{"timezone":["Europe/Madrid"]}` funciona |
| `POST` con JSON y **500 coordenadas** | `200`, array de 500 elementos, 153 KB en 0,38 s |

**Por qué importa:** es la forma de consultar cientos de puntos sin chocar con el límite de longitud
de una URL, y **sigue contando como una sola llamada** para la cuota. El límite del cuerpo es de
128 kB según el código 🔵.

Al no estar documentado, tampoco hay garantía de que se mantenga: conviene envolverlo de forma que
se pueda volver a `GET` sin tocar el resto.

### D11 · El catálogo de identificadores de dominio existe, pero solo en el código 🟢

`Sources/App/Helper/DomainRegistry.swift` enumera **148 dominios** con el identificador exacto que
usa la metadata API. Es la única lista completa que existe: la web no la publica. Guardado en
[`src/repositorio/DomainRegistry.swift`](src/repositorio/DomainRegistry.swift).

Reparto por proveedor: ECMWF 31, NCEP 21, DWD 13, Météo-France 11, CMC 9, GloFAS 8, Copernicus 8,
CAMS 7, UKMO 6, MeteoSwiss 6, JMA 6, EUMETSAT 3, ARPAE 3, y el resto con uno o dos.

Dos avisos:

- **Los nombres del catálogo no son los de `models=`.** `metno_nordic_pp` en el catálogo es
  `metno_nordic` en `models=`; `google_weathernext2_ensemble` no tiene equivalente público.
- **El fichero marca dominios obsoletos** en comentarios (`/// Deprecated 2026-01-18. MeteoFrance
  does not provide this data anymore`) que no aparecen en ninguna otra fuente.

### D12 · `archive-api` sirve la metadata de **cualquier** dominio 🟢

No hace falta acertar con el host. Verificado el 2026-09-01: `archive-api.open-meteo.com` devolvió
`meta.json` de `dwd_icon_d2`, `ncep_gfs013`, `cams_europe`, `eumetsat_sarah3_30min`,
`metno_nordic_pp` y `google_weathernext2_ensemble` — dominios de previsión, calidad del aire y
satélite. `api.open-meteo.com`, en cambio, solo responde por los suyos y devuelve `500` para el
resto.

**Consecuencia práctica:** para vigilar la frescura de cualquier modelo basta con un host,
`archive-api`, y el identificador exacto del catálogo (D11).

---

## 🔴 Bloque E — Erratas pendientes de confirmar

| # | Sospecha | Cómo verificarlo |
|---|---|---|
| 1 | El `cell_selection` por defecto de Air Quality y Flood (en Marine ya está confirmado: `sea`) | Comparar la coordenada devuelta con el parámetro explícito frente a omitido |
| 2 | Si el `429` incluye `Retry-After` o cabeceras `RateLimit-*` | **No se sondeará a propósito.** Registrar las cabeceras si aparece en uso normal |
| 3 | Qué son exactamente los valores futuros de la API de satélite (A9) | Comparar con el modelo de cielo despejado y con la observación del día siguiente |
| 4 | Si los endpoints sin spec aceptan todos los parámetros comunes (comprobado en satélite: `format=csv`, multi-localización y `models` sí) | Petición por endpoint con `timeformat=unixtime` y `format=csv` |
| 5 | Si el `nan` de A7 aparece también en `format=csv` y en las demás APIs | Repetir A7 con `format=csv` y contra marine, air-quality y flood |

**Resueltos** (antes estaban en esta tabla): `/v1/get` acepta `language`
(→ [`D3`](#d3--v1get-responde-en-inglés-salvo-que-se-le-pase-language-)), el rango final de cada
modelo climático (→ [`C4`](#c4--rango-final-de-la-climate-api-tres-fuentes-tres-respuestas)), los
límites de `forecast_days` en Flood (→ [`C1`](#c1--contradicciones-entre-la-spec-openapi-y-la-web-sobre-forecast_days)),
la opcionalidad de `models` y `daily` (→ [`C2`](#c2--parámetros-marcados-como-obligatorios-en-la-web-y-opcionales-en-la-spec))
y el `cell_selection` por defecto de Marine (→ [`C3`](#c3--la-columna-default-de-cell_selection-contradice-a-su-propia-descripción-)).

---

## Cómo añadir una errata

1. **Verifícala con una petición real.** Una sospecha va al bloque E, no a los otros.
2. Colócala en su bloque (A rompe en silencio, B formato, C contenido, D indocumentado, E pendiente).
3. Incluye **la evidencia literal**: URL, código HTTP, cabecera o fragmento de cuerpo.
4. Marca la fiabilidad y **actualiza la fecha** de la cabecera.
5. Si afecta a un endpoint concreto, añade un aviso en su módulo enlazando aquí.

> Creado: 2026-09-01 · Última revisión: 2026-09-01
