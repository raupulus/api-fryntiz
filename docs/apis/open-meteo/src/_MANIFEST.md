# Manifiesto de fuentes originales — Open-Meteo

> **Última actualización:** 2026-09-01

> [!WARNING]
> **Este es el ÚNICO archivo de `src/` escrito por nosotros.** Todo lo demás son documentos
> oficiales capturados tal cual. **No se edita nada de `src/`.**
> Para consultar la API, usa la documentación de `docs/apis/open-meteo/*.md`, no estos originales.

- **Fecha de captura:** `2026-08-31`
- **Método:** `curl` sobre las URLs oficiales; el HTML se transcribió a texto con un extractor
  propio (sin editar el original).
- **Versión de la API en el momento de la captura:** las 9 specs declaran `version: '1.0'` y
  `openapi: 3.1.0`. El repositorio no publica una versión global de la API; su último *release*
  etiquetado es `1.4.0` (2024-12-31).
- **Servidores base:** un subdominio por API bajo `open-meteo.com` — ver
  [`00-fundamentos.md`](../00-fundamentos.md#mapa-de-endpoints-).

---

## Inventario

### `especificacion/`

Descargadas de `https://raw.githubusercontent.com/open-meteo/open-meteo/main/openapi/<archivo>`.

| Archivo | Origen | Fiabilidad |
|---|---|---|
| `forecast.yml` | `openapi/forecast.yml` (137 KB) | 🟢 **Oficial.** El endpoint principal: 355 variables horarias, 50 modelos |
| `historical-weather.yml` | `openapi/historical-weather.yml` | 🟢 **Oficial.** `/v1/archive` (ERA5) |
| `ensemble.yml` | `openapi/ensemble.yml` | 🟢 **Oficial.** 174 variables horarias |
| `seasonal.yml` | `openapi/seasonal.yml` | 🟢 **Oficial.** Incluye `weekly` y `monthly` |
| `climate.yml` | `openapi/climate.yml` | 🟢 **Oficial.** CMIP6 |
| `marine.yml` | `openapi/marine.yml` | 🟢 **Oficial.** |
| `air-quality.yml` | `openapi/air-quality.yml` | 🟢 **Oficial.** CAMS |
| `flood.yml` | `openapi/flood.yml` | 🟢 **Oficial.** GloFAS |
| `elevation.yml` | `openapi/elevation.yml` | 🟢 **Oficial.** La más pequeña (3 parámetros) |

> [!IMPORTANT]
> **No existe especificación para 7 de los 16 endpoints**: Historical Forecast, Previous Runs,
> Single Runs, Satellite Radiation, Geocoding (`/v1/search` y `/v1/get`) y la metadata API. Para
> esos, la única fuente es la web.

### `web-original/`

HTML tal cual lo sirvió `https://open-meteo.com/<ruta>` el 2026-08-31. Contenido renderizado en
servidor: el HTML **sí** trae la documentación (a diferencia de AEMET). Lo que **no** trae son las
tablas que se rellenan por JavaScript, en particular la de estado de `model-updates`.

| Archivo | Ruta de origen |
|---|---|
| `forecast.html` | `/en/docs` |
| `historical-weather.html` | `/en/docs/historical-weather-api` |
| `historical-forecast.html` | `/en/docs/historical-forecast-api` |
| `previous-runs.html` | `/en/docs/previous-runs-api` |
| `single-runs.html` | `/en/docs/single-runs-api` |
| `ensemble.html` | `/en/docs/ensemble-api` |
| `ensemble-mean.html` | `/en/docs/ensemble-mean-api` |
| `seasonal-forecast.html` | `/en/docs/seasonal-forecast-api` |
| `climate.html` | `/en/docs/climate-api` |
| `marine.html` | `/en/docs/marine-weather-api` |
| `air-quality.html` | `/en/docs/air-quality-api` |
| `flood.html` | `/en/docs/flood-api` |
| `satellite-radiation.html` | `/en/docs/satellite-radiation-api` |
| `geocoding.html` | `/en/docs/geocoding-api` |
| `elevation.html` | `/en/docs/elevation-api` |
| `model-updates.html` | `/en/docs/model-updates` |
| `features.html` | `/en/features` |
| `pricing.html` | `/en/pricing` |
| `terms.html` | `/en/terms` |
| `licence.html` | `/en/licence` (ojo: **`licence`**, no `license` — esa ruta devuelve 100 bytes) |
| `about.html` | `/en/about` |

Todos 🟢 **oficiales**. Los más valiosos: `terms.html` y `pricing.html` (los límites de uso y la
restricción de uso comercial), `licence.html` (atribución) y `forecast.html` (la definición de cada
variable, que la spec no da).

### `web-texto/`

Transcripción a texto plano de cada HTML anterior, mismo nombre y extensión `.txt`. Existen para
poder hacer `grep` sin pelearse con el marcado. **La fidelidad la da el HTML; el texto es una
comodidad.**

### `web-texto/modelos/`

Transcripción de las 18 páginas por modelo (`/en/docs/<modelo>-api`): `bom`, `chmi`, `cma`, `dmi`,
`dwd`, `ecmwf`, `gem`, `geosphere-austria`, `gfs`, `google-weathernext`, `italia-meteo-arpae`,
`jma`, `kma`, `knmi`, `meteofrance`, `meteoswiss`, `metno`, `ukmo`.

Sus HTML originales están en `web-original/modelos/`, con el mismo nombre. Pesan entre 190 y
450 KB cada uno (≈ 4,7 MB en total): es el grueso de este directorio, pero sigue muy por debajo de
los 32 MB de `docs/apis/aemet/src/`. Contienen la tabla de variables y la cobertura de cada modelo,
que no aparecen en ninguna especificación.

### `repositorio/`

De `https://raw.githubusercontent.com/open-meteo/open-meteo/main/`.

| Archivo | Contenido | Fiabilidad |
|---|---|---|
| `README.md` | Descripción del proyecto y del servidor auto-alojable | 🟢 Oficial |
| `LICENSE` | AGPLv3 — licencia **del software**, no de los datos (los datos son CC BY 4.0) | 🟢 Oficial |
| `CITATION.cff` | Cita académica (DOI de Zenodo) | 🟢 Oficial |
| `DomainRegistry.swift` | `Sources/App/Helper/DomainRegistry.swift`. **El catálogo de los 148 dominios**, con sus identificadores exactos para la metadata API y los comentarios que marcan los obsoletos. No existe equivalente publicado en la web | 🟢 Oficial |

---

## Descartado deliberadamente

| Fuente | Por qué |
|---|---|
| `openapi/*.yml` del repositorio `open-meteo-website` | Son copias byte a byte de las del repositorio principal (`static/docs/openapi/`) |
| Blog en `openmeteo.substack.com` | Fuente de novedades, no de referencia técnica. Útil para vigilar cambios, no para documentar el comportamiento actual |
| `status.open-meteo.com` | Estado en tiempo real: no tiene sentido congelarlo en una captura |
| Resto de ficheros de código Swift | El comportamiento se verifica contra la API real, no leyendo su implementación. **Excepción**: `DomainRegistry.swift`, que no es implementación sino el único catálogo de identificadores que existe. Puntualmente se han consultado —sin guardarlos— `routes.swift` (que reveló el soporte de `POST`) y `EumetsatSarahDomain.swift` (rejilla y paso nativo del satélite) |
| Clientes de terceros (Python, TypeScript…) | Documentan su propia envoltura, no la API. Con AEMET, fiarse de documentación de terceros ya salió mal |

---

## Cómo actualizar estas fuentes

Las fuentes son una **foto del 2026-08-31**. Open-Meteo anuncia sus cambios en el repositorio de
GitHub (`open-meteo/open-meteo`) y en su blog de Substack; la web no tiene página de novedades. Al
refrescar:

1. Vuelve a descargar los archivos afectados a la misma ruta.
2. Actualiza la **fecha de captura** de la cabecera de este manifiesto.
3. Revisa si la documentación derivada sigue siendo correcta y actualiza su fecha de verificación.
4. Comprueba en particular si han aparecido specs para los 7 endpoints que hoy no la tienen, y si
   los `enum` de las existentes se han puesto al día.
