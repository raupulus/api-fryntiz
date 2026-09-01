# 🗓️ Predicción estacional

> **Última actualización:** 2026-09-01

**1 endpoint.** Previsión de largo alcance basada en ECMWF SEAS5 y EC46, con
agregaciones semanales y mensuales, anomalías e índices de extremos.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/seasonal.yml`,
> `src/web-texto/seasonal-forecast.txt`, `src/web-texto/features.txt` + verificación en vivo del
> 2026-08-31 (peticiones 18 y 19).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://seasonal-api.open-meteo.com/v1/seasonal` | 🟢 | JSON UTF-8, con los 51 miembros por variable |

> [!CAUTION]
> **`/v1/forecast` en este mismo host responde `200` con todos los valores a `null`.** El formulario
> de la página oficial apunta ahí. La ruta correcta es **`/v1/seasonal`**. Verificado el 2026-08-31:
> [`A3`](ERRATAS.md#a3--un-host-equivocado-devuelve-200-con-la-serie-entera-a-null-).

---

## El endpoint 🟢

```
GET /v1/seasonal?latitude=…&longitude=…&daily=…
```

| | |
|---|---|
| Host | `seasonal-api.open-meteo.com` (pago: `customer-seasonal-api.open-meteo.com`) |
| Resoluciones | `hourly` (nativo **6-horario**), `daily`, `weekly`, `monthly` |
| Variables | 47 «horarias», 17 diarias, 64 semanales, 73 mensuales 🔵 |
| `forecast_days` | Defecto `183`, máximo `217` (≈ **7 meses**) 🔵 — pese a que la web anuncia «hasta 9 meses» ⚠️ |
| Miembros | 51 🔵 |
| Horizonte real | **7 meses**, no 9 🟡 — ver el aviso de abajo |
| Resolución espacial | 36 km 🔵 |
| Tamaño 🟢 | 4.209 B (1 variable diaria × 2 días × 51 miembros) |
| TTL 🟡 | 24 h — un modelo estacional no cambia por horas |

Verificado el 2026-08-31 (Madrid, `daily=temperature_2m_max`, `forecast_days=2`): devolvió
`temperature_2m_max` más `temperature_2m_max_member01` … con la misma convención de sufijos que la
[Ensemble API](04-ensemble.md).

---

## Qué contiene

| Producto | Horizonte | Modelo | Verificado el 2026-09-01 |
|---|---|---|---|
| Diario / 6-horario | Hasta 217 días 🔵 | SEAS5 + EC46 | Con miembros (`_member01`…) 🟢 |
| Semanal | Hasta 6 semanas 🔵 | EC46 | **27 pasos semanales**, de 2026-08-31 a 2027-03-01 — seis *meses*, no seis semanas 🟢 |
| Mensual | Hasta 7 meses 🔵 | SEAS5 | **6 pasos mensuales**, de 2026-09 a 2027-02 🟢 |

> [!WARNING]
> **«Hasta 9 meses» es la cifra comercial, no la que permite la API.** La web y la descripción de
> SEAS5 hablan de 9 meses, pero `forecast_days` está topado en **217 días (≈ 7 meses)** en la spec, y
> la propia página dice que los datos mensuales llegan «hasta 7 meses». No se ha probado si `217` es
> realmente el techo 🔴. Planificar sobre 7 meses, no sobre 9.

> [!NOTE]
> **`weekly` y `monthly` no traen miembros**: devuelven una sola serie por variable 🟢, a diferencia
> de `daily` y `hourly`. Las variables `*_anomaly` funcionan y se piden igual que las demás
> (`monthly=temperature_2m_mean,temperature_2m_anomaly` devolvió las dos series).

Variables específicas de esta API 🔵, además de las habituales:

- **Anomalías** (`*_anomaly`): diferencia frente a la climatología del propio modelo, construida con
  20–30 años de *hindcasts*. Una anomalía no es una temperatura: es una desviación.
- **EFI** (*Extreme Forecast Index*): cuán inusual es la previsión frente al clima del modelo.
  Cercano a +1, mucho más cálido o húmedo de lo normal; cercano a −1, lo contrario.
- **SOT** (*Shift of Tails*): complementa al EFI mirando las colas de la distribución, calculado
  sobre los percentiles 10 y 90 de los 100 miembros de EC46.

> [!IMPORTANT]
> **EFI y SOT solo existen en `weekly`** 🟢. Los nombres son `temperature_2m_efi`,
> `temperature_2m_sot10`, `temperature_2m_sot90`, `precipitation_efi` y `precipitation_sot90`.
> Pedirlos en `monthly` devuelve `400`: «Cannot initialize ForecastVariableMonthly from invalid
> String value temperature_2m_efi». En `weekly` funcionan y devuelven valores en el rango esperado
> (`[0.9, 0.6]` y `[0.7, 0.0]` el 2026-09-01).

Modelos 🔵: `best_match`, `ecmwf_seasonal_seamless`, `ecmwf_seas5`, `ecmwf_ec46` y sus tres
variantes `*_ensemble_mean`.

---

## Precauciones

> [!WARNING]
> **Los datos no están corregidos de sesgo** 🔵. La documentación oficial insiste en que deben
> interpretarse como **orientación probabilística de área** —si el mes vendrá más cálido, frío,
> seco o húmedo de lo normal— y **no como valores locales precisos**. Publicar un
> «máxima prevista para dentro de cuatro meses» a partir de esta API sería un uso incorrecto del
> dato.

- La resolución nativa es de **6 horas**; interpolar a 1 hora es posible pero no añade precisión 🔵.
- SEAS5 no modela radiación directa ni difusa: Open-Meteo las deriva de la global con el modelo de
  separación de Razo/Müller/Witwer 🔵.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Confirmar que los 51 miembros llegan siempre y con la misma numeración | Media |
| 2 | Por qué `weekly` devuelve 27 pasos (6 meses) cuando la documentación habla de 6 semanas | Media |
| 3 | Si `forecast_days=217` es realmente el máximo | Baja |
| 4 | Si `sot10` y las variables de precipitación se comportan igual que las probadas | Baja |
