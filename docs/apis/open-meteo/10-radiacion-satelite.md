# 🛰️ Radiación solar por satélite

> **Última actualización:** 2026-09-01

**1 endpoint.** Irradiancia solar **observada** desde satélites geoestacionarios, no simulada por
modelos, desde 1983.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/web-texto/satellite-radiation.txt`, `src/web-texto/features.txt`
> + verificación en vivo del 2026-08-31 (petición 20). **No tiene especificación OpenAPI** ⚠️.

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://satellite-api.open-meteo.com/v1/archive` | 🟢 | JSON UTF-8, misma forma que la Forecast API |

Ojo con la ruta: es **`/v1/archive`**, igual que el histórico ERA5, pero en **otro host**.

---

## El endpoint 🟢

```
GET /v1/archive?latitude=…&longitude=…&hourly=shortwave_radiation&start_date=…&end_date=…
```

| | |
|---|---|
| Host | `satellite-api.open-meteo.com` |
| Unidad | W/m² |
| Resolución espacial | 2,5–5 km 🔵 |
| Resolución temporal nativa | 10, 15 o 30 min según satélite; **la API devuelve horario salvo que se pida `temporal_resolution=native`** 🔵 |
| Tamaño 🟢 | 856 B (1 variable horaria × 1 día) |
| Parámetros comunes 🟢 | Acepta `format=csv`, multi-localización (devuelve array) y `models=` |
| TTL 🟡 | 6 h para fechas recientes; indefinido para fechas pasadas |

Verificado el 2026-08-31 con Madrid y `start_date=end_date=2026-08-20`.

---

## Fuentes de datos

Lo que publica la web 🔵:

| Satélite / producto | Cobertura | Desde |
|---|---|---|
| EUMETSAT CM SAF SARAH3 | Europa, África, Sudamérica | 1983 |
| JMA Himawari-9 | Asia, Australia | 2015 |
| DWD MTG | Europa, África | Febrero de 2026 |

Lo que se puede comprobar 🟢 (2026-09-01, Madrid), pasando el nombre en `models=`:

| `models=` | Resultado en Madrid | `meta.json` en `archive-api` |
|---|---|---|
| `satellite_radiation_seamless` | 24/24 con dato; se corta en la hora real | — |
| `eumetsat_lsa_saf_msg` | 24/24 con dato | `eumetsat_lsa_saf_msg_15min`: paso nativo **900 s** |
| `eumetsat_sarah3` | Sin dato para fechas recientes | `eumetsat_sarah3_30min`: paso **1800 s**, última pasada **2026-07-02** |
| `jma_jaxa_himawari` | `nan` — no cubre España | — |

> [!IMPORTANT]
> **SARAH3 lleva sin actualizarse desde el 2026-07-02** 🟢. Es el archivo histórico largo (desde
> 1983), no la fuente en tiempo real. Para fechas recientes en España, quien responde es LSA SAF MSG
> con paso nativo de 15 minutos.

Del código del servidor 🔵 (`EumetsatSarahDomain.swift`): SARAH3 usa una rejilla regular de
2.600 × 2.600 celdas a 0,05° (~5 km) desde −65° hasta +65° en latitud y longitud, y se actualiza
cada 24 h. **El satélite se puede forzar con `models=`** 🟢: la petición
con `models=eumetsat_sarah3` devolvió datos y una celda distinta de la del valor por defecto
(`40.4, -3.7000008` frente a `40.386642, -3.6760864`), lo que confirma que cambia la fuente. Qué
fuente se elige por defecto sigue sin poder saberse desde la respuesta 🔴.

## Variables 🔵

`shortwave_radiation` (GHI), `diffuse_radiation`, `direct_radiation`, `direct_normal_irradiance`
(DNI), `global_tilted_irradiance` (con `tilt` y `azimuth`), `shortwave_radiation_clear_sky` y
`terrestrial_radiation`. Todas admiten el sufijo **`_instant`** para obtener el valor instantáneo en
lugar de la media de la hora anterior.

Detalles que la documentación sí explica y conviene no pasar por alto 🔵:

- **Himawari no mide radiación directa ni difusa**: Open-Meteo las deriva de la global con el modelo
  de separación de Razo/Müller/Witwer. Son valores calculados, no observados.
- **`shortwave_radiation_clear_sky` solo existe para los datos de DWD.**
- Un barrido completo del disco terrestre tarda 10–15 minutos, así que el borde superior y el
  inferior de cada imagen no son simultáneos. Open-Meteo corrige ese desfase y publica medias hacia
  atrás para que sean comparables con la salida de los modelos.

---

## Precauciones

> [!CAUTION]
> **Sin `models`, la respuesta mezcla observación con relleno** 🟢. Aislado el 2026-09-01 a las
> 09:45 UTC, cambiando un solo parámetro cada vez:
>
> | Petición | Última hora con dato |
> |---|---|
> | Sin `models` | **23:00** — catorce horas en el futuro |
> | Sin `models`, con `timezone=UTC` | 23:00 — la zona horaria no influye |
> | **`models=satellite_radiation_seamless`** | **09:00** — solo lo observado |
>
> **Para radiación observada hay que pasar `models=satellite_radiation_seamless`.** Con el valor por
> defecto se están mezclando medidas de satélite con valores de otra procedencia, sin ninguna marca
> que los distinga. Ver
> [`A9`](ERRATAS.md#a9--la-api-de-radiación-por-satélite-mezcla-observación-y-relleno-salvo-que-se-fije-el-modelo-).

- **`temporal_resolution=native` no tiene ningún efecto** 🟢: devuelve 24 pasos horarios tanto en
  Madrid como en Tokio (zona Himawari, con 10 minutos nativos). La resolución subhoraria que promete
  la documentación **no se ha podido obtener por ninguna vía**. Ver
  [`C8`](ERRATAS.md#c8--temporal_resolutionnative-no-hace-nada-en-la-api-de-satélite-).
- **`shortwave_radiation_clear_sky` llega vacío en España** 🟢: todos los valores a `null` el
  2026-08-31. Es coherente con la documentación, que la limita a los datos de DWD 🔵, pero conviene
  saberlo antes de construir nada sobre esa variable.
- Los valores por defecto son **medias de la hora anterior**, no instantáneos: para comparar con un
  piranómetro hay que usar las variantes `_instant` 🔵.
- Es un archivo, no una previsión: para radiación futura se usa la
  [Forecast API](01-prevision-meteorologica.md).

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | **De dónde salen** los valores de relleno cuando no se pasa `models`. Descartados: la Forecast API (valores distintos) y `shortwave_radiation_clear_sky` (vacío en España) | Media |
| 2 | Cómo obtener realmente el paso de 10/15/30 min: ¿otro nombre de parámetro? Preguntar en el repositorio de GitHub | Media |
| 3 | Lista completa de valores válidos de `models=` en esta API: no está publicada y los del catálogo interno no sirven aquí | Media |
