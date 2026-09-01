# 🌊 Caudal fluvial e inundaciones

> **Última actualización:** 2026-09-01

**1 endpoint.** Caudal diario de ríos a partir del modelo hidrológico GloFAS de Copernicus, con
previsión de meses y archivo desde 1984.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/flood.yml`, `src/web-texto/flood.txt` +
> verificación en vivo del 2026-08-31 (petición 14).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://flood-api.open-meteo.com/v1/flood` | 🟢 | JSON UTF-8, **solo bloque `daily`** |

---

## El endpoint 🟢

```
GET /v1/flood?latitude=…&longitude=…&daily=river_discharge
```

| | |
|---|---|
| Host | `flood-api.open-meteo.com` (pago: `customer-flood-api.open-meteo.com`) |
| Qué devuelve | El caudal del **río más caudaloso en un radio de 5 km** de la coordenada 🔵 |
| `forecast_days` | Defecto `92`; **acepta hasta `366`** 🟢 (la spec acierta, la web dice 210 y se queda corta). Pero **solo los ~185 primeros días traen dato**: el resto llega a `null` |
| `ensemble` | Booleano: devuelve todos los miembros en vez de la media 🔵 |
| Rango histórico | Desde `1984-01-01` 🔵 |
| Unidad | m³/s |
| Tamaño 🟢 | 325 B (1 variable × 3 días) |
| Actualización 🟢 | **Diaria.** `glofas_forecast_v4` publicó la pasada de 2026-08-31 00:00 UTC a las 12:49 UTC del mismo día |
| TTL 🟡 | 12 h — el dato llega a mediodía; refrescar antes no aporta |

Verificado el 2026-08-31 (Madrid, `daily=river_discharge`, 3 días): `[1.72, 1.71, 1.70]` m³/s.

---

## Variables 🔵

| Variable | Disponibilidad |
|---|---|
| `river_discharge` | Siempre |
| `river_discharge_mean`, `river_discharge_median`, `river_discharge_max`, `river_discharge_min`, `river_discharge_p25`, `river_discharge_p75` | **Solo en previsión**, no en el histórico consolidado |

Modelos 🔵: `seamless_v4`, `forecast_v4`, `consolidated_v4` y sus equivalentes `_v3`.

---

## Precauciones

> [!WARNING]
> **Esto no es un aviso de inundación.** Es la salida de un modelo hidrológico global de resolución
> gruesa. No sustituye a los avisos de una confederación hidrográfica ni de Protección Civil, y no
> debe presentarse como tal.

- El caudal es el del río **más caudaloso en 5 km**: en una ciudad atravesada por varios cauces, no
  hay forma de saber cuál se está devolviendo 🟡.
- **`0.0` y `null` significan cosas distintas** 🟢, y ninguna está documentada. Verificado el
  2026-09-01: en el Sáhara (`23, 13`) devuelve `[0.0, 0.0]` —hay celda, caudal cero— y en pleno
  Atlántico (`30, -40`) devuelve `[null, null]` —no hay celda—. Un `0.0` **no** quiere decir «aquí
  no hay río».
- Con `ensemble=true` la respuesta pasa a **51 series** (`river_discharge` más
  `river_discharge_member01` … ) 🟢, con la misma convención de sufijos que la
  [Ensemble API](04-ensemble.md).
- Las variables estadísticas desaparecen al consultar fechas pasadas consolidadas 🔵: un código que
  las dé por seguras fallará al mirar atrás.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Por qué el dato se corta hacia el día 185 si acepta 366 | Media |
| 2 | Si la hora de publicación de GloFAS (12:49 UTC) se repite en días sucesivos | Baja |
| 3 | `cell_selection`: por defecto coincide con `nearest` 🟢 (costa de Cádiz, misma celda); falta comparar con `land` | Baja |
