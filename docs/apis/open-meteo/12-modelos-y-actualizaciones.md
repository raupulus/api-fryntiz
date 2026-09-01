# 🧭 Catálogo de modelos y metadata API

> **Última actualización:** 2026-09-01

**1 endpoint** (la metadata API) más el catálogo de referencia de los modelos que alimentan al
resto. Es el archivo que hay que consultar para decidir **qué modelo usar** y **cuándo lanzar un
comando programado**.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`LIMITACIONES.md`](LIMITACIONES.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/web-texto/forecast.txt` (tabla «Data Sources»),
> `src/web-texto/model-updates.txt`, `src/web-texto/features.txt` + verificación en vivo del
> 2026-08-31 (petición 29). **La metadata API no tiene especificación OpenAPI ni URL publicada en
> el texto de la web** ⚠️.

---

## Metadata API 🟢

```
GET https://api.open-meteo.com/data/{modelo}/static/meta.json
```

Devuelve cuándo se ejecutó y cuándo estuvo disponible la última pasada de un modelo. Verificado el
2026-08-31 con `dwd_icon`: `200`, `application/json; charset=utf-8`, 595 bytes.

| Campo | Ejemplo (2026-08-31, `dwd_icon`) | Significado 🔵 |
|---|---|---|
| `last_run_initialisation_time` | `1788134400` → 2026-08-31 00:00 UTC | Hora de referencia de la pasada |
| `last_run_modification_time` | `1788147417` → 03:36 UTC | Fin de la descarga y conversión |
| `last_run_availability_time` | `1788147916` → 03:45 UTC | **Momento en que el dato es accesible por la API** |
| `update_interval_seconds` | `21600` | Intervalo típico entre pasadas (6 h) |
| `temporal_resolution_seconds` | `3600` | Paso temporal nativo (1 h) |
| `chunk_time_length` | `253` | Interno del formato de almacenamiento |
| `crs_wkt` | `GEOGCRS["WGS 84"…]` | Sistema de referencia y `BBOX` de cobertura |

> [!IMPORTANT]
> **Sumar 10 minutos a `last_run_availability_time`** antes de dar el dato por propagado: los
> servidores son redundantes y eventualmente consistentes 🔵.
>
> Las llamadas a la metadata API **no cuentan** para los límites diarios ni mensuales 🔵.

**Cómo usarla**: un comando programado consulta primero `meta.json`, compara
`last_run_initialisation_time` con la última pasada que ya tiene guardada y **solo pide datos si ha
cambiado**. Así se evita gastar cuota en refrescos que no traen nada nuevo.

### El catálogo completo: 148 dominios 🟢

La lista de identificadores válidos **no está publicada en la web**. Existe en el código del
servidor, en `Sources/App/Helper/DomainRegistry.swift`, y está guardada en
[`src/repositorio/DomainRegistry.swift`](src/repositorio/DomainRegistry.swift): **148 dominios**.

| Proveedor | Dominios | Proveedor | Dominios |
|---|---|---|---|
| ECMWF | 31 | UKMO | 6 |
| NCEP (NOAA) | 21 | MeteoSwiss | 6 |
| DWD | 13 | JMA | 6 |
| Météo-France | 11 | EUMETSAT | 3 |
| CMC (Canadá) | 9 | ARPAE | 3 |
| GloFAS | 8 | KNMI, KMA, Google, CHMI, BOM | 2 cada uno |
| Copernicus | 8 | NASA, MET Norway, ItaliaMeteo, GeoSphere | 1 cada uno |
| CAMS | 7 | | |

El fichero marca además los dominios **obsoletos** en comentarios que no aparecen en ninguna otra
fuente, por ejemplo: «Deprecated 2026-01-18. MeteoFrance does not provide this data anymore» sobre
`meteofrance_arpege_europe_probabilities`.

> [!WARNING]
> **Los nombres del catálogo no son los de `models=`.** En el catálogo está `metno_nordic_pp`,
> mientras que en `models=` se escribe `metno_nordic`; `google_weathernext2_ensemble` no tiene
> equivalente público. El catálogo sirve para la **metadata API**, no para pedir datos.

### Los identificadores llevan prefijo de proveedor 🟢

**El `{modelo}` de la ruta no es el valor de `models=`.** Verificado el 2026-08-31 probando diez
identificadores:

| En la metadata API | En `models=` de la Forecast API |
|---|---|
| `dwd_icon` | `icon_global` |
| `dwd_icon_eu` | `icon_eu` |
| `dwd_icon_d2` | `icon_d2` |
| `ecmwf_ifs025` | `ecmwf_ifs025` (coincide) |
| `meteofrance_arpege_europe` | `meteofrance_arpege_europe` (coincide) |
| `meteofrance_arome_france_hd` | `meteofrance_arome_france_hd` (coincide) |
| `ukmo_global_deterministic_10km` | `ukmo_global_deterministic_10km` (coincide) |
| `ncep_gfs013` | — (no existe con ese nombre en `models=`) |

La regla observada 🟡: el identificador de la metadata API es el **nombre interno del dominio, con
prefijo del proveedor**; en `models=` unos llevan prefijo y otros no. Cuando no coinciden, es porque
`models=` usa el alias corto.

> [!WARNING]
> **Un identificador inválido devuelve `500 Internal Server Error`, no `404`.** Verificado con
> `icon_eu`, `metno_nordic`, `copernicus_era5` y `cams_europe` contra `api.open-meteo.com`: los
> cuatro dieron `500`. Los tres últimos **sí existen, pero en otro host** (ver abajo). Un `500` aquí
> significa «este modelo no está en este servidor», no «el servidor está caído».

### `archive-api` sirve la metadata de cualquier dominio 🟢

**No hace falta acertar con el host: `archive-api.open-meteo.com` responde por todos.** Verificado
el 2026-09-01 con dominios de previsión, calidad del aire y satélite —`dwd_icon_d2`, `ncep_gfs013`,
`cams_europe`, `eumetsat_sarah3_30min`, `metno_nordic_pp`, `google_weathernext2_ensemble`—: los seis
devolvieron `200` desde `archive-api`. `api.open-meteo.com`, en cambio, solo responde por los suyos
y devuelve `500` para el resto.

Otros hosts también sirven los suyos. Verificado:

| URL | Resultado |
|---|---|
| `archive-api.open-meteo.com/data/copernicus_era5/static/meta.json` | `200` |
| `archive-api.open-meteo.com/data/copernicus_era5_land/static/meta.json` | `200` |
| `archive-api.open-meteo.com/data/copernicus_era5_ensemble/static/meta.json` | `200` |
| `air-quality-api.open-meteo.com/data/cams_europe/static/meta.json` | `200` |
| `flood-api.open-meteo.com/data/glofas_forecast_v4/static/meta.json` | `200` |
| `archive-api.open-meteo.com/data/copernicus_cerra/static/meta.json` | `500` — el identificador de CERRA es otro 🔴 |

Es la forma de saber la frescura real de cada producto sin gastar cuota de datos.

### Tiempos de disponibilidad medidos 🟢

Todos leídos el **2026-08-31 por la mañana (UTC)**. Es una única muestra por modelo: sirve para
dimensionar un cron, no como garantía.

| Modelo | Pasada | Disponible | Retraso | Intervalo |
|---|---|---|---|---|
| `dwd_icon_d2` | 03:00 | 04:24 | **1 h 24 min** | 3 h |
| `dwd_icon_eu` | 03:00 | 05:53 | 2 h 53 min | 3 h |
| `meteofrance_arome_france_hd` | 03:00 | 05:39 | 2 h 39 min | 3 h |
| `meteofrance_arpege_europe` | 00:00 | 03:44 | 3 h 44 min | 6 h |
| `dwd_icon` (global) | 00:00 | 03:45 | 3 h 45 min | 6 h |
| `ncep_gfs013` | 00:00 | 05:43 | 5 h 43 min | 6 h |
| `ukmo_global_deterministic_10km` | 18:00 (día anterior) | 01:07 | 7 h 07 min | 6 h |
| `ecmwf_ifs025` | 18:00 (día anterior) | 01:14 | **7 h 14 min** | 6 h (paso de 3 h) |
| `cams_europe` (calidad del aire) | 2026-08-30 00:00 | 11:32 del mismo día | 11 h 32 min | 24 h |
| `glofas_forecast_v4` (inundaciones) | 2026-08-31 00:00 | 12:49 del mismo día | 12 h 49 min | 24 h |
| `copernicus_era5` (histórico) | 2026-08-25 00:00 | 2026-08-31 00:39 | **6 días** | 24 h |
| `copernicus_era5_land` | 2026-08-26 00:00 | 2026-09-01 00:06 | **6 días** | 24 h |
| `copernicus_era5_ensemble` | 2026-08-26 00:00 | 2026-09-01 00:35 | **6 días** | 24 h |

**Lo que esto significa para un cron** 🟡: los modelos globales de referencia (IFS, GFS) tardan entre
5 y 7 horas en estar disponibles desde su hora de pasada. Programar la descarga «a las 00:15 porque
la pasada es de las 00:00» leería datos de hace seis horas. Lo correcto es consultar `meta.json` y
decidir a partir de `last_run_availability_time` + 10 minutos.

Advertencia oficial 🔵: estos tiempos **no se corresponden directamente** con los de la Forecast API,
porque `best_match` elige un modelo distinto según la coordenada. Y los servidores gratuitos y de
pago se actualizan en momentos ligeramente distintos.

---

## Catálogo de modelos de previsión 🔵

Tabla oficial de `/en/docs` (descargada el 2026-08-31):

| Modelo | Región | Servicio | País | Resolución | Horizonte | Actualización |
|---|---|---|---|---|---|---|
| ICON | 🌍 Global y Europa | DWD | Alemania | 2–11 km | 7,5 días | Cada 3 h |
| GFS & HRRR | 🌍 Global y Norteamérica | NOAA | EE. UU. | 3–25 km | 16 días | Cada hora |
| ARPEGE & AROME | 🌍 Global, Europa y Francia | Météo-France | Francia | 1–25 km | 4 días | Cada hora |
| IFS & AIFS | 🌍 Global | ECMWF | UE | 9–25 km | 15 días | Cada 6 h |
| UKMO | 🌍 Global y Reino Unido | UK Met Office | Reino Unido | 2–10 km | 7 días | Cada hora |
| KMA | 🌍 Global y Corea del Sur | KMA | Corea | 1,5–13 km | 12 días | Cada 6 h |
| MSM & GSM | 🌍 Global y Japón | JMA | Japón | 5–55 km | 11 días | Cada 3 h |
| ICON CH | Europa central | MeteoSwiss | Suiza | 1–2 km | 5 días | Cada 3 h |
| MET Nordic | Nórdicos | MET Norway | Noruega | 1 km | 2,5 días | Cada hora |
| GEM | 🌍 Global y Canadá | CMC | Canadá | 2,5 km | 10 días | Cada 6 h |
| ACCESS-G | 🌍 Global | BOM | Australia | 15 km | 10 días | Cada 6 h |
| GFS GRAPES | 🌍 Global | CMA | China | 15 km | 10 días | Cada 6 h |
| HARMONIE | Europa y Países Bajos | KNMI | Países Bajos | 2 km | 2,5 días | Cada hora |
| HARMONIE | Europa | DMI | Dinamarca | 2 km | 2,5 días | Cada 3 h |
| ARPAE | Italia | ItaliaMeteo | Italia | 2 km | 3 días | Cada 12 h |
| AROME | Europa central | GeoSphere | Austria | 2,5 km | 2,5 días | Cada 3 h |
| ALADIN | Europa central | CHMI | Chequia | 1–2,3 km | 3 días | Cada 6 h |

**Qué significa esto para España** 🟢: no hay ningún modelo de alta resolución **específico** para la
península. La cobertura fina llega por los modelos europeos (`icon_eu`, ARPEGE Europa) y por IFS a
9 km. AROME de Météo-France sí alcanza el centro peninsular y Baleares con 1–1,5 km, pero **no llega
a Cádiz ni a Canarias**. Matriz medida en
[`01-prevision-meteorologica.md`](01-prevision-meteorologica.md#cobertura-real-en-españa-). Para uso
general, `best_match`.

> [!NOTE]
> Open-Meteo **no integra el modelo HARMONIE-AROME de AEMET** 🟡 (deducido de la lista oficial de
> proveedores, donde AEMET no aparece; no hay una declaración expresa al respecto). Si se necesita el modelo operativo
> del servicio meteorológico español, hay que ir a la API de AEMET
> ([`docs/apis/aemet/`](../aemet/README.md)). Las dos APIs no son intercambiables: cubren cosas
> distintas.

---

## Estado del servicio 🔵

- Panel de estado e histórico de incidencias: `status.open-meteo.com`.
- La página `/en/docs/model-updates` muestra en amarillo los modelos con más de 20 minutos de
  retraso y en rojo los que se han saltado varias pasadas. Su propio texto admite que **los retrasos
  pequeños son bastante habituales**.
- La tabla de esa página se genera con JavaScript, así que la transcripción de `src/web-texto/` no
  la contiene: para consultarla hay que abrir la página o llamar a la metadata API.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Si la metadata API tiene su propio subdominio para clientes de pago | Baja (sin suscripción no se puede probar) |
| 2 | Dominio geográfico exacto de `meteofrance_arome_france` sobre España — matriz parcial ya medida en [`01`](01-prevision-meteorologica.md#cobertura-real-en-españa-) | Media |
| 3 | Correspondencia completa entre los 148 identificadores del catálogo y los valores de `models=` | Baja |
| 4 | Si los tiempos de disponibilidad medidos se repiten en días sucesivos (una sola muestra por modelo) | Media |
