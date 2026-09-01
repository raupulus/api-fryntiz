# 🗄️ Archivo de predicciones pasadas


**3 endpoints.** Tres formas distintas de recuperar **lo que se predijo en el pasado** (no lo que
realmente ocurrió, que es el [histórico ERA5](02-historico-reanalisis.md)). Se usan para medir el
error de la previsión y para entrenar modelos de corrección.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/web-texto/historical-forecast.txt`,
> `src/web-texto/previous-runs.txt`, `src/web-texto/single-runs.txt`, `src/web-texto/features.txt`
> + verificación en vivo del 2026-08-31 (peticiones 21–23). **Ninguno de los tres tiene
> especificación OpenAPI** ⚠️.

---

## Resumen

| Endpoint | Estado | Qué devuelve |
|---|---|---|
| `GET https://historical-forecast-api.open-meteo.com/v1/forecast` | 🟢 | Serie continua del archivo de previsiones, desde ~2021 |
| `GET https://previous-runs-api.open-meteo.com/v1/forecast` | 🟢 | Serie a horizonte fijo (1–7 días), desde enero de 2024 |
| `GET https://single-runs-api.open-meteo.com/v1/forecast` | 🟢 | Una pasada concreta, elegida por su hora de inicialización |

Los tres comparten la ruta `/v1/forecast` y **cambian solo de host**: equivocarse de host es fácil y
no siempre da error ([`A3`](ERRATAS.md#a3--un-host-equivocado-devuelve-200-con-la-serie-entera-a-null-)).

---

## Cuál usar

| Necesito… | API |
|---|---|
| Lo que realmente ocurrió (verdad de referencia) | [ERA5](02-historico-reanalisis.md) |
| Una serie histórica **con el mismo formato que la previsión en vivo** | Historical Forecast |
| Medir el sesgo por horizonte de previsión (error a 1, 2, 3… días) | Previous Runs |
| La previsión **exacta que estaba disponible** en un momento del pasado | Single Runs |

---

## Historical Forecast API 🟢

```
GET https://historical-forecast-api.open-meteo.com/v1/forecast
   ?latitude=…&longitude=…&start_date=…&end_date=…&hourly=…
```

Archiva las **primeras horas de cada actualización de modelo** y las cose en una serie continua, con
la misma estructura, variables y unidades que la Forecast API 🔵. Es el conjunto indicado para
entrenar correcciones de sesgo, porque el histórico y el tiempo real son comparables.

| | |
|---|---|
| Cobertura | La web dice «desde ~2021» 🔵, pero **hay datos desde 2017** 🟢: `2017-06-01` devolvió 24/24 valores y `2016-06-01`, ninguno. El inicio real está entre esas dos fechas |
| Modelos | Todos los de la Forecast API 🔵 |
| Tamaño 🟢 | 839 B (1 variable horaria × 1 día) |
| TTL 🟡 | 24 h |

Verificado el 2026-08-31 con Madrid y `start_date=end_date=2026-08-01`: 24 valores horarios.

## Previous Runs API 🟢

```
GET https://previous-runs-api.open-meteo.com/v1/forecast
   ?latitude=…&longitude=…&hourly=temperature_2m,temperature_2m_previous_day1
```

Devuelve, para cada instante, el valor que se había predicho con **N días de antelación exactos**.
El sufijo `_previous_dayN` selecciona el desfase 🔵:

| Sufijo | Significado |
|---|---|
| *(sin sufijo)* o `_previous_day0` | La pasada actual — equivale a la Forecast API |
| `_previous_day1` | Lo predicho 24 h antes del instante de validez |
| `_previous_day2` … `_previous_day7` | 48 h … 168 h antes |

Verificado el 2026-08-31: `hourly=temperature_2m,temperature_2m_previous_day1` devolvió las dos
series en paralelo, con sus dos entradas en `hourly_units`.

| | |
|---|---|
| Cobertura | La web dice «desde enero de 2024» 🔵, pero **2023-06-01 devolvió 24/24 valores** 🟢 |
| Huecos | **La serie no es continua** 🟢: `2024-01-05` devolvió 0/24, mientras que `2024-01-20`, `2024-03-01`, `2024-06-01` y `2025-06-01` devolvieron 24/24. Hay días sueltos sin dato y nada lo señala |
| Límite | Solo se rellenan los desfases dentro del horizonte del modelo 🟢: `temperature_2m_previous_day7` con `models=icon_d2` (horizonte de 2 días) devuelve `200` con **la serie entera a `null`**, no un error |
| Tamaño 🟢 | 1.029 B (2 series horarias × 1 día) |

## Single Runs API 🟢

```
GET https://single-runs-api.open-meteo.com/v1/forecast
   ?latitude=…&longitude=…&hourly=…&run=2026-08-28T00:00
```

Devuelve el horizonte completo de **una pasada concreta**, identificada por su hora de
inicialización UTC en el parámetro `run` (formato `yyyy-mm-ddThh:mm`) 🔵.

| | |
|---|---|
| Cobertura | La mayoría de modelos desde el **2 de abril de 2026** · ECMWF IFS HRES 9 km desde marzo de 2024 (hindcasts) 🔵 |
| Horizonte por pasada | Normalmente 7–10 días 🔵 |
| Tamaño 🟢 | 838 B (1 variable horaria × 1 día) |

Verificado el 2026-08-31 con `run=2026-08-28T00:00`: la serie **empieza en la fecha de la pasada**
(`2026-08-28T00:00`), no en hoy. Es el comportamiento esperado, pero conviene tenerlo presente: el
`time[0]` no es «hoy» como en el resto de APIs.

Es la única de las tres que evita el sesgo de anticipación (*look-ahead bias*) al construir pares
(previsión, observación) para aprendizaje automático 🔵.

**Una pasada inexistente sí da error** 🟢, a diferencia de casi todo lo demás en esta API. Verificado
el 2026-08-31:

```json
// run=2020-01-01T00:00  → HTTP 400
{"error":true,"reason":"The requested model run is not available. Model: ncep_gfs013, run: 2020-01-01T00:00Z"}
// run=2026-08-28T03:00 (hora no canónica) → HTTP 400, mismo mensaje
```

El mensaje revela además que **el modelo por defecto de esta API es `ncep_gfs013`** (GFS 0,13°), no
`best_match` 🟢 — no está documentado en ninguna parte.

**`models` y `run` se combinan** 🟢: `models=icon_eu&run=2026-08-30T00:00` devolvió la serie
correcta (2026-09-01).

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Fecha exacta de inicio de Historical Forecast (entre 2016-06 y 2017-06) y de Previous Runs (anterior a 2023-06) | Media |
| 2 | Cuántos días sueltos faltan en Previous Runs y si el patrón se repite en otras coordenadas | Media |
| 3 | Si aceptan `POST` como la Forecast API (ya comprobados 🟢 `format=csv` en Historical Forecast, multi-localización en Previous Runs y `timeformat=unixtime` en Single Runs) | Baja |

> Creado: 2026-09-01 · Última revisión: 2026-09-01
