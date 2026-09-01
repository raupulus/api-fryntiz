# 📜 Histórico y reanálisis (ERA5)

> **Última actualización:** 2026-09-01

**1 endpoint.** Datos meteorológicos reales del pasado a partir de reanálisis: series horarias y
diarias globales **desde 1940**.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/historical-weather.yml`,
> `src/web-texto/historical-weather.txt` + verificación en vivo del 2026-08-31 (petición 12).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://archive-api.open-meteo.com/v1/archive` | 🟢 | JSON UTF-8, misma forma que la Forecast API |

> [!IMPORTANT]
> **El retraso depende del modelo que se pida, y eso cambia la recomendación.** Medido el
> 2026-08-31:
>
> | `models=` | Último día con dato | Desfase real |
> |---|---|---|
> | `era5` | `2026-08-25` | **6 días** (la documentación dice 5) |
> | `best_match` (por defecto) | `2026-08-31` — el mismo día de la consulta | Ninguno |
>
> Es decir: **`best_match` sí sirve para «ayer»**, porque para las fechas recientes usa ECMWF IFS,
> que se actualiza cada 6 h sin retraso. Lo que no sirve para el pasado reciente es fijar `era5` o
> `era5_land`.
>
> Confirmado desde la propia API, con los metadatos de cada conjunto 🟢:
>
> | `meta.json` en `archive-api` | Última pasada | Disponible | Desfase |
> |---|---|---|---|
> | `copernicus_era5` | 2026-08-25 00:00 | 2026-08-31 00:39 | 6 días |
> | `copernicus_era5_land` | 2026-08-26 00:00 | 2026-09-01 00:06 | 6 días |
> | `copernicus_era5_ensemble` | 2026-08-26 00:00 | 2026-09-01 00:35 | 6 días |
>
> Los tres van con el mismo desfase, todos con `update_interval_seconds: 86400` (diario).

---

## El endpoint 🟢

```
GET /v1/archive?latitude={lat}&longitude={lon}&start_date={fecha}&end_date={fecha}&daily={vars}
```

| | |
|---|---|
| Host | `archive-api.open-meteo.com` (pago: `customer-archive-api.open-meteo.com`) |
| Obligatorios | `latitude`, `longitude`, `start_date`, `end_date` |
| Cobertura | Desde 1940-01-01. El final **depende del modelo**: `best_match` llega al día de hoy, `era5` se queda a 6 días 🟢 |
| Periodicidad | Diaria con **6 días** de desfase medido en ERA5/ERA5-Land 🟢 (la web dice 5) · cada 6 h sin retraso en ECMWF IFS 🔵 |
| Tamaño 🟢 | 325 B (1 variable diaria × 2 días) |
| TTL 🟡 | 24 h — los datos históricos ya no cambian; el TTL solo evita repetir la consulta |

Verificado el 2026-08-31 con Madrid y el rango `2026-08-01` – `2026-08-02` (`daily=temperature_2m_max`,
`timezone=Europe/Madrid`): devolvió `[37.5, 35.7]` °C. Segunda comprobación el mismo día con
`models=era5` sobre `2026-08-18` – `2026-08-31`: valores hasta el día 25 y `null` a partir del 26.

`forecast_days` existe pero con `minimum=0` y `maximum=0` 🔵: aquí no tiene sentido.

---

## Conjuntos de datos (`models`)

| Modelo | Resolución | Temporal | Cobertura | Actualización 🔵 |
|---|---|---|---|---|
| `best_match` (defecto) | — | — | Combina IFS, ERA5 y ERA5-Land | — |
| `ecmwf_ifs` | 9 km | Horaria | 2017 → hoy | Cada 6 h, sin retraso |
| `era5` | 0,25° (~25 km) | Horaria | 1940 → hoy − 6 días 🟢 | Diaria; la web dice 5 días de retraso, **medidos 6** 🟢 |
| `era5_land` | 0,1° (~11 km) | Horaria | 1950 → hoy − 6 días 🟢 | Diaria; **6 días** medidos 🟢 |
| `era5_ensemble` | 0,5° (~55 km) | 3-horaria | 1940 → hoy − 6 días 🟢 | Diaria; **6 días** medidos 🟢 |
| `cerra` | 9 km | 6-horaria | **~1985 → 2021** 🟢 (la web dice «2024 → hoy»: es falso, ver [`C9`](ERRATAS.md#c9--la-web-describe-cerra-como-un-producto-en-tiempo-real-y-termina-en-2021-)) | No se actualiza 🟢 |
| `ecmwf_ifs_analysis_long_window` | 🔴 | 🔴 | Responde en España 🟢 | 🔴 |

> [!IMPORTANT]
> **Para estudiar tendencias de décadas hay que fijar `era5` o `era5_land`** 🔵. `best_match` mezcla
> fuentes: cambia de modelo en 2017 y eso introduce un salto artificial en la serie que se puede
> confundir con una señal climática.

---

## Variables

**59 horarias** 🔵, un subconjunto de las de previsión: temperatura, humedad, precipitación,
nubosidad, presión, viento a 10 y 100 m, radiación (con variantes `*_instant`), suelo en cuatro
capas IFS (`0_to_7cm`, `7_to_28cm`, `28_to_100cm`, `100_to_255cm`) más `soil_moisture_index_*`,
`sunshine_duration`, `growing_degree_days_base_0_limit_50`, `leaf_wetness_probability` y algunas
marinas (`wave_height`, `wave_direction`, `wave_period`, `sea_surface_temperature`).

**38 diarias** 🔵, con más agregaciones que la Forecast API: además de máximos y mínimos hay
`temperature_2m_mean`, `dew_point_2m_{mean,max,min}`, `relative_humidity_2m_{mean,max,min}`,
`cloud_cover_mean`, `pressure_msl_mean`, `wind_speed_10m_mean`, `wet_bulb_temperature_2m_mean`,
`vapour_pressure_deficit_max` y las medias de humedad y temperatura del suelo por capa.

Lista completa en `src/especificacion/historical-weather.yml`. Recordar que los `enum` de las specs
están incompletos ([`C7`](ERRATAS.md#c7--los-enum-de-las-specs-están-incompletos-no-son-un-catálogo-cerrado-)).

---

## Precauciones

- **No hay huecos**: el reanálisis es espacialmente completo, sin valores ausentes por falta de
  estación 🔵. Un `null` aquí apunta a un problema de rango o de variable, no a un dato que falte.
- **La celda devuelta no es la coordenada pedida.** Verificado: para `40.4168,-3.7038` devolvió
  `40.386642,-3.6760864`.
- **Series muy largas**: medido el 2026-09-01 con una variable horaria y 30 años (1996–2025,
  `models=era5`) 🟢:

  | | |
  |---|---|
  | Sin comprimir | **6,23 MB** en 0,69 s |
  | Con `Accept-Encoding: gzip, deflate` | **950 KB** — 6,5 veces menos, mismo tiempo |
  | La misma serie en `daily` | 196 KB |

  Son ~263.000 valores por variable. **Pedir siempre compresión** en series largas, y usar `daily`
  cuando el detalle horario no haga falta.
- El reanálisis **no es una observación**: es un modelo reconstruido con observaciones asimiladas. No
  sustituye a los datos de una estación de AEMET para efectos legales o contractuales 🟡.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Qué cubre `ecmwf_ifs_analysis_long_window`: responde con datos en España y tiene `meta.json` en `archive-api`, pero no está documentado en ninguna fuente | Media |
| 2 | Si `era5_land` cubre toda España peninsular e islas con la misma resolución | Baja |
| 3 | Fecha exacta en que termina `cerra` (hay dato en 2021-06 y no en 2022-06) | Baja |
