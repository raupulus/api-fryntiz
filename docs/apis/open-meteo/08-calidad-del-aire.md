# 💨 Calidad del aire y polen

> **Última actualización:** 2026-09-01

**1 endpoint.** Contaminantes, índices AQI europeo y estadounidense, polen y UV, a partir del
servicio Copernicus CAMS.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/air-quality.yml`, `src/web-texto/air-quality.txt`
> + verificación en vivo del 2026-08-31 (petición 11).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://air-quality-api.open-meteo.com/v1/air-quality` | 🟢 | JSON UTF-8, misma forma que la Forecast API |

---

## El endpoint 🟢

```
GET /v1/air-quality?latitude=…&longitude=…&hourly=pm10,european_aqi
```

| | |
|---|---|
| Host | `air-quality-api.open-meteo.com` (pago: `customer-air-quality-api.open-meteo.com`) |
| `domains` | `auto` (defecto) \| `cams_europe` \| `cams_global` — propio de esta API |
| `forecast_days` | Defecto `5`, máximo `7` 🔵 |
| `past_days` | 0–92 🔵 |
| Variables | 44 horarias, 19 actuales 🔵 |
| **No tiene** | Bloque `daily` ni `minutely_15` 🔵 |
| Tamaño 🟢 | 911 B (2 variables horarias × 1 día) |
| Actualización 🟢 | **Diaria.** `cams_europe` publicó la pasada de 2026-08-30 00:00 UTC a las 11:32 UTC del mismo día (`update_interval_seconds: 86400`) |
| TTL 🟡 | 6 h — la pasada es diaria, pero conviene reintentar dentro del día por si llega tarde |

Verificado el 2026-08-31 (Madrid, `hourly=pm10,european_aqi`): unidades `μg/m³` y `EAQI`. La
frecuencia se obtuvo el mismo día de
`https://air-quality-api.open-meteo.com/data/cams_europe/static/meta.json` — la metadata API también
existe en este host, no solo en `api.open-meteo.com`
(ver [`12-modelos-y-actualizaciones.md`](12-modelos-y-actualizaciones.md#metadata-api-)).

---

## Variables 🔵

| Grupo | Variables |
|---|---|
| Partículas y gases | `pm10`, `pm2_5`, `carbon_monoxide`, `nitrogen_dioxide`, `sulphur_dioxide`, `ozone`, `dust`, `ammonia` (solo Europa), `methane`, `carbon_dioxide` (en ppm) |
| Aerosoles y UV | `aerosol_optical_depth`, `uv_index`, `uv_index_clear_sky` |
| Polen (**solo Europa**) | `alder_pollen`, `birch_pollen`, `grass_pollen`, `mugwort_pollen`, `olive_pollen`, `ragweed_pollen` — en granos/m³ |
| AQI europeo | `european_aqi` y sus desgloses `european_aqi_{pm2_5,pm10,nitrogen_dioxide,ozone,sulphur_dioxide}` |
| AQI estadounidense | `us_aqi` y sus desgloses, incluido `us_aqi_carbon_monoxide` |

Las unidades son `μg/m³` salvo `carbon_dioxide` (ppm), los índices (adimensionales) y el polen
(granos/m³).

### Escalas de los índices 🔵

**AQI europeo** — el consolidado es el **máximo** de los individuales:

| Rango | Calidad |
|---|---|
| 0–20 | Buena |
| 20–40 | Aceptable |
| 40–60 | Moderada |
| 60–80 | Mala |
| 80–100 | Muy mala |
| > 100 | Extremadamente mala |

**AQI estadounidense**: 0–50 buena · 51–100 moderada · 101–150 dañina para grupos sensibles ·
151–200 dañina · 201–300 muy dañina · 301–500 peligrosa.

> [!IMPORTANT]
> Los dos índices **no son comparables entre sí** y usan escalas invertidas en su interpretación
> intermedia. Elegir uno y ser coherente. Para España, el europeo.

---

## Precauciones

- **El polen y el amoníaco solo existen en el dominio europeo** 🟢. Verificado el 2026-09-01:

  | Petición | `grass_pollen` | `ammonia` |
  |---|---|---|
  | Madrid, `domains` por defecto | 24/24 con dato | 24/24 con dato |
  | Nueva York | **0/24 — todo `null`** | **0/24 — todo `null`** |
  | Madrid, `domains=cams_global` | **0/24 — todo `null`** | — |

  Es decir: forzar `cams_global` en España **vacía el polen** aunque la coordenada sea europea. Y
  fuera de Europa no hay error, solo nulos.
- `domains=auto` (por defecto) combina el dominio europeo y el global 🔵; en Madrid `pm10` llega
  igual con `cams_global`, así que lo que se pierde al forzarlo son las variables exclusivas del
  dominio europeo 🟢.
- El valor por defecto documentado de `cell_selection` es contradictorio en la propia página
  ([`C3`](ERRATAS.md#c3--la-columna-default-de-cell_selection-contradice-a-su-propia-descripción-)).

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Diferencia numérica entre `cams_europe` y `auto` en España (que el polen desaparece con `cams_global` ya está confirmado) | Baja |
| 2 | `cell_selection`: en la costa de Cádiz las tres opciones dan la **misma** celda, así que la prueba no discrimina; repetir donde la rejilla sea más fina | Baja |
| 3 | Hora exacta de publicación en días sucesivos (solo se ha medido una) | Baja |
| 4 | Si `current` incluye `interval` como en la Forecast API | Baja |
