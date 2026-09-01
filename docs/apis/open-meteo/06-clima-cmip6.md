# 🌍 Proyecciones climáticas (CMIP6)


**1 endpoint.** Datos climáticos diarios de modelos CMIP6 reescalados a 10 km y corregidos de sesgo
contra ERA5, de **1950 a 2050**.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/climate.yml`, `src/web-texto/climate.txt` +
> verificación en vivo del 2026-08-31 (peticiones 15 y 16).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://climate-api.open-meteo.com/v1/climate` | 🟢 | JSON UTF-8, solo datos diarios |

> [!CAUTION]
> **Fuera del rango real de un modelo devuelve `null` con `200 OK`.** Verificado el 2026-08-31:
> `start_date=2050-01-01` con `EC_Earth3P_HR` → `[null, null]`; la misma petición para 2030 →
> `[32.0, 33.5]`. Ver [`A5`](ERRATAS.md#a5--los-valores-null-conviven-con-200-ok-) y
> [`C4`](ERRATAS.md#c4--rango-final-de-la-climate-api-tres-fuentes-tres-respuestas).

---

## El endpoint 🟢

```
GET /v1/climate?latitude=…&longitude=…&start_date=…&end_date=…&models=…&daily=…
```

| | |
|---|---|
| Host | `climate-api.open-meteo.com` (pago: `customer-climate-api.open-meteo.com`) |
| Obligatorios | Solo `latitude`, `longitude`, `start_date` y `end_date` 🟢. La web declara `models` y `daily` obligatorios y **no lo son**: sin `models` responde con una serie sin sufijo de modelo (2026-09-01); sin `daily`, `200` con solo metadatos ([`C2`](ERRATAS.md#c2--parámetros-marcados-como-obligatorios-en-la-web-y-opcionales-en-la-spec)) |
| Rango | **`1950-01-01` a `2050-12-31`** 🟢 — lo dice la propia API al salirse: «Parameter 'start_date' is out of allowed range from 1950-01-01 to 2050-12-31». La spec acierta y la web (que dice 2050-01-01) se equivoca ([`C4`](ERRATAS.md#c4--rango-final-de-la-climate-api-tres-fuentes-tres-respuestas)) |
| Resolución | Solo **diaria**; no hay `hourly` |
| Variables | 38 diarias 🔵 |
| Tamaño 🟢 | 307 B (1 variable × 2 días × 1 modelo) |
| TTL 🟡 | Semanas: es un conjunto de datos estático |

Verificado el 2026-08-31 con Madrid, `models=EC_Earth3P_HR`, `daily=temperature_2m_max`.

---

## Modelos 🔵

Siete, con el nombre en mayúsculas y guiones bajos (**distinto del resto de APIs**, donde los
identificadores van en minúsculas):

`CMCC_CM2_VHR4`, `FGOALS_f3_H`, `HiRAM_SIT_HR`, `MRI_AGCM3_2_S`, `EC_Earth3P_HR`, `MPI_ESM1_2_XR`,
`NICAM16_8S`.

La documentación recomienda **usar el rango completo 1950–2050** y comparar varios modelos en vez de
fiarse de uno 🔵.

**Hasta dónde llega cada uno** — medido el 2026-08-31 pidiendo los siete a la vez con el rango
`2049-12-28` – `2050-12-31` 🟢:

| Modelo | Último día con dato |
|---|---|
| `CMCC_CM2_VHR4`, `FGOALS_f3_H`, `HiRAM_SIT_HR`, `MRI_AGCM3_2_S`, `MPI_ESM1_2_XR`, `NICAM16_8S` | `2050-12-31` |
| **`EC_Earth3P_HR`** | **`2049-12-31`** |

`EC_Earth3P_HR` termina **un año antes** que el resto y ninguna fuente oficial lo menciona. Si se
promedian los siete modelos sin comprobarlo, 2050 sale calculado con seis y nadie se entera.

## Corrección de sesgo

Por defecto los datos se reescalan estadísticamente y se corrigen contra ERA5-Land, con coeficientes
mensuales calculados sobre 50 años 🔵. `disable_bias_correction=true` desactiva ambas cosas.

La corrección lineal **no altera la señal de cambio climático** 🔵, solo ajusta el nivel local.

---

## Precauciones

- No es una previsión: es una **proyección** bajo un escenario de emisiones. No usarla para hablar
  del tiempo de la semana que viene.
- Los nombres de modelo son sensibles a mayúsculas 🟡.
- Un rango de un siglo con varias variables genera respuestas grandes: 100 años × 365 días son
  36.500 valores por variable y modelo.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | **Qué modelo usa** cuando se omite `models`: la respuesta no lo identifica | Media |
| 2 | Efecto medible de `disable_bias_correction` | Baja |
| 3 | Si acepta `format=csv` (no probado en esta API) | Baja |
| 4 | Fecha inicial de los cuatro modelos no probados (comprobado 🟢 que `CMCC_CM2_VHR4`, `EC_Earth3P_HR` y `NICAM16_8S` tienen dato el 1950-01-01) | Baja |

> Creado: 2026-09-01 · Última revisión: 2026-09-01
