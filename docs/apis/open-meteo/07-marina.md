# 🌊 Meteorología marina

> **Última actualización:** 2026-09-01

**1 endpoint.** Oleaje, mar de fondo, corrientes y temperatura superficial del mar.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/marine.yml`, `src/web-texto/marine.txt` +
> verificación en vivo del 2026-08-31 (petición 13).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://marine-api.open-meteo.com/v1/marine` | 🟢 | JSON UTF-8, misma forma que la Forecast API |

---

## El endpoint 🟢

```
GET /v1/marine?latitude=…&longitude=…&hourly=wave_height
```

| | |
|---|---|
| Host | `marine-api.open-meteo.com` (pago: `customer-marine-api.open-meteo.com`) |
| `cell_selection` | Por defecto **`sea`** 🟢 — verificado: omitirlo da el mismo resultado que `sea` explícito, y distinto de `land` |
| `length_unit` | `metric` (defecto) \| `imperial` — propio de esta API |
| `forecast_days` | Defecto `7`, máximo `16` 🔵 |
| Variables | 22 horarias, 11 diarias, 14 actuales, 3 cuarto-horarias 🔵 |
| Actualización | Cada 6 h 🔵 |
| Tamaño 🟢 | 835 B (1 variable horaria × 1 día) |
| TTL 🟡 | 2 h |

Verificado el 2026-08-31 en el golfo de Cádiz (`36.5, -6.5`): devolvió `wave_height` en metros y
`elevation: 0.0`. Cobertura comprobada el 2026-09-01 con `best_match` 🟢: **golfo de Cádiz,
Mediterráneo balear y Canarias**, los tres con las 24 horas completas.

Nota: en celdas costeras `elevation` **no es 0** (16 m en Cádiz, 65 m en Baleares, 31 m en
Canarias): es la altitud del modelo digital del punto, no la del mar.

---

## Variables 🔵

| Grupo | Variables |
|---|---|
| Oleaje total | `wave_height`, `wave_direction`, `wave_period`, `wave_peak_period` |
| Mar de viento | `wind_wave_height`, `wind_wave_direction`, `wind_wave_period`, `wind_wave_peak_period` |
| Mar de fondo | `swell_wave_*`, `secondary_swell_wave_*`, `tertiary_swell_wave_*` (altura, dirección, periodo) |
| Mar | `sea_surface_temperature`, `sea_level_height_msl` |
| Corrientes | `ocean_current_velocity`, `ocean_current_direction` |

Diarias: los máximos (`*_height_max`, `*_period_max`) y las direcciones dominantes
(`*_direction_dominant`). Cuarto-horarias: solo `ocean_current_velocity`,
`ocean_current_direction` y `sea_level_height_msl`.

> [!IMPORTANT]
> **Las direcciones indican de dónde viene el oleaje** 🔵, igual que el viento: 0° = del norte hacia
> el sur, 90° = del este. Invertir el criterio es un error clásico al pintar flechas.

Modelos 🔵: `best_match`, `meteofrance_wave`, `meteofrance_currents`, `dwd_ewam`, `dwd_gwam`,
`ecmwf_wam`, `ecmwf_wam025`, `ncep_gfswave025`, `ncep_gfswave016`, `era5_ocean`.

**Cobertura comprobada el 2026-09-01** 🟢:

| Modelo | Mediterráneo (Baleares) | Canarias |
|---|---|---|
| `ecmwf_wam025` | ✅ | — |
| `meteofrance_wave` | ✅ | — |
| `dwd_ewam` (europeo) | ✅ | **`400` «No data is available for this location»** |
| `ncep_gfswave025` (global) | — | ✅ |

Para Canarias hay que ir a un modelo global (`ncep_gfswave025`, `ecmwf_wam025`) o dejar
`best_match`, que ya se comprobó que responde allí.

---

## Precauciones

- **Una coordenada en tierra devuelve `200` con toda la serie a `null`** 🟢. Verificado el
  2026-08-31 con Madrid (`40.4168, -3.7038`): la respuesta trae `elevation: 666.0`, la coordenada
  **de tierra** (`40.458336, -3.7083282`) y `wave_height: [null, null, …]`. Es decir, **no** busca
  la celda de mar más cercana: se queda donde se le dice y no hay dato.
  Consecuencia: validar que la coordenada es marítima antes de consultar, o tratar «serie
  completamente nula» como «este punto no es mar».
- `elevation: 0.0` en la respuesta es lo normal en mar abierto, no un error.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | El `cell_selection` por defecto de las **demás** APIs (en marina ya está confirmado: `sea`) | Media |
| 2 | Cobertura de los modelos de oleaje no probados (`dwd_gwam`, `ecmwf_wam`, `ncep_gfswave016`, `era5_ocean`) | Baja |
| 3 | Distancia máxima a la que `cell_selection=sea` busca celda de mar | Baja |
