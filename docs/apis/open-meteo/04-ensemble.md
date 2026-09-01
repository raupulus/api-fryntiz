# 🎲 Predicción por conjuntos (ensemble)

> **Última actualización:** 2026-09-01

**1 endpoint.** Previsión probabilística: en vez de un único valor, devuelve **todos los miembros**
del conjunto para cada variable e instante, hasta 51 por modelo y hasta 35 días.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/ensemble.yml`, `src/web-texto/ensemble.txt`,
> `src/web-texto/ensemble-mean.txt` + verificación en vivo del 2026-08-31 (petición 17).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://ensemble-api.open-meteo.com/v1/ensemble` | 🟢 | JSON UTF-8; **una serie por miembro**, con sufijo `_memberNN` |

La página «Ensemble Mean API» del sitio oficial **no es otro endpoint** 🟡: las cinco URLs de
ejemplo de esa página apuntan al mismo `ensemble-api.open-meteo.com/v1/ensemble`
(`src/web-original/ensemble-mean.html`, 2026-08-31), y los modelos con media de conjunto aparecen
como valores `*_ensemble_mean` del parámetro `models`. **No se ha hecho una petición específica
contra esa variante** 🔴.

---

## El endpoint 🟢

```
GET /v1/ensemble?latitude=…&longitude=…&hourly=…&models=…
```

| | |
|---|---|
| Host | `ensemble-api.open-meteo.com` (pago: `customer-ensemble-api.open-meteo.com`) |
| `models` | **Opcional en la práctica** 🟢: omitirlo devuelve 31 series (2026-09-01), lo que apunta a GEFS 0,25° como modelo por defecto 🟡. La web lo declara obligatorio ([`C2`](ERRATAS.md#c2--parámetros-marcados-como-obligatorios-en-la-web-y-opcionales-en-la-spec)) |
| `forecast_days` | Defecto `7`; máximo `36` en la spec y `35` en la web ([`C1`](ERRATAS.md#c1--contradicciones-entre-la-spec-openapi-y-la-web-sobre-forecast_days)) |
| `temporal_resolution` | `native` \| `hourly` \| `hourly_3` \| `hourly_6` — propio de esta API. **Funciona** 🟢: con `hourly_6` un día son 4 pasos en vez de 24 |
| Variables | 174 horarias y 41 diarias declaradas en la spec 🔵 |
| Tamaño 🟢 | **7.857 B** para *una* variable, *un* día, `icon_seamless` |
| TTL 🟡 | 3 h (los modelos EPS se actualizan cada 3–12 h) |

> [!CAUTION]
> **Un modelo regional fuera de su dominio devuelve `200` con JSON inválido.** Verificado el
> 2026-08-31: `dwd_icon_d2_eps` en Madrid → `{"latitude":nan,…}`; el mismo modelo en Múnich → las 20
> series correctas. En esta API es especialmente fácil de provocar, porque `models` es obligatorio y
> casi todos los modelos EPS son regionales. Ver
> [`A7`](ERRATAS.md#a7--un-modelo-fuera-de-su-dominio-devuelve-json-inválido-con-200-ok-).

> [!CAUTION]
> **El volumen se multiplica por el número de miembros.** Una petición con 10 variables, 15 días y
> 51 miembros son cientos de miles de valores. Es el endpoint más fácil de convertir en un problema
> de memoria y de ancho de banda. Medir antes de automatizar.

---

## Estructura de la respuesta 🟢

Verificada el 2026-08-31 (Madrid, `models=icon_seamless`, `hourly=temperature_2m`,
`forecast_days=1`):

```json
"hourly_units": {
  "time": "iso8601",
  "temperature_2m": "°C",
  "temperature_2m_member01": "°C",
  "temperature_2m_member02": "°C",
  …
}
```

- **La serie sin sufijo es un miembro más, no un resumen** 🟢. Verificado el 2026-08-31 con
  `dwd_icon_d2_eps` en Múnich: la respuesta trae **20 series** — `temperature_2m` más
  `_member01` … `_member19` — y ICON-D2-EPS tiene exactamente 20 miembros. Los valores de la serie
  sin sufijo **no coinciden con ningún miembro numerado** (`[19.5, 19.3, 18.8…]` frente a
  `[19.5, 19.2, 19.1…]` de `member01`), así que es el miembro de control y ocupa el hueco del
  `member00` que no existe.
- **No es la media del conjunto**: para eso están los modelos `*_ensemble_mean`.
- Los miembros van numerados con **dos dígitos y cero a la izquierda**: `_member01`, `_member02`…
  Ordenar por nombre funciona hasta `_member99`.
- El número de miembros **depende del modelo**, no es fijo. Contados el 2026-09-01 en Madrid 🟢
  (series totales, incluida la que va sin sufijo):

  | `models=` | Series | Cobertura en Madrid |
  |---|---|---|
  | `ecmwf_ifs025` | 51 | ✅ |
  | `dwd_icon_eu_eps` | 40 | ✅ |
  | `dwd_icon_global_eps` | 40 | ✅ |
  | `ncep_gefs025` | 31 | ✅ |
  | sin `models` | 31 | ✅ (coincide con GEFS 0,25° 🟡) |
  | `dwd_icon_d2_eps` | 20 | ❌ `nan` — no cubre España |

  Coinciden con los miembros que declara la documentación para cada modelo. Aun así, hay que
  descubrirlos leyendo las claves de `hourly_units`, no darlos por fijos.

---

## Modelos disponibles 🔵

16 declarados en la spec: `dwd_icon_seamless_eps`, `dwd_icon_global_eps`, `dwd_icon_eu_eps`,
`dwd_icon_d2_eps`, `ncep_gefs_seamless`, `ncep_gefs025`, `ncep_gefs05`, `ncep_aigefs025` y otros.
El alias corto `icon_seamless` también funciona 🟢.

Características publicadas 🔵:

| Servicio | Modelo | Resolución | Miembros | Horizonte | Actualización |
|---|---|---|---|---|---|
| DWD | ICON-D2-EPS | 2 km, horaria | 20 | 2 días | Cada 3 h |
| DWD | ICON-EU-EPS | 13 km, horaria | 40 | 5 días | Cada 6 h |
| DWD | ICON-EPS | 26 km, horaria | 40 | 7,5 días | Cada 12 h |
| NOAA | GFS Ensemble 0,25° | ~25 km, 3-horaria | 31 | 10 días | Cada 6 h |
| NOAA | GFS Ensemble 0,5° | 50 km, 3-horaria | 31 | **35 días** | Cada 6 h |
| ECMWF | IFS 0,25° | ~25 km, 3-horaria | 51 | 15 días | Cada 6 h |
| ECMWF | IFS Europa (O1280 nativo) | 9 km, horaria | 51 | 15 días | **Solo pasadas 0z y 6z** |
| ECMWF | AIFS 0,25° | ~25 km, 6-horaria | 51 | 15 días | Cada 6 h |
| CMC | GEM | ~25 km, 3-horaria | 21 | 16 días (39 los lunes y jueves) | Cada 12 h |
| BOM | ACCESS-GE | 40 km, 3-horaria | 18 | 10 días | Cada 6 h |
| UK Met Office | MOGREPS-UK | 2 km, horaria | 3 | 5 días | Cada hora |
| UK Met Office | MOGREPS-G | 20 km, horaria | 18 | 8 días | Cada 6 h |

Todos los datos se interpolan a paso horario 🔵; algunos modelos bajan a 6-horario en el tramo
final del horizonte.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Miembros de los modelos no probados (GEM, BOM, MOGREPS, AIFS) | Baja |
| 2 | Por qué `ncep_hgefs025_ensemble_mean` devuelve **una sola serie pero con valores nulos** en Madrid: ¿cobertura o producto vacío? | Media |
| 3 | Cobertura de los EPS no probados en España (comprobados 🟢: IFS, GEFS e ICON global/EU sí; ICON-D2 no) | Baja |
