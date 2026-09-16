# Open-Meteo Marine — cómo la usamos

Cómo consume esta plataforma **Open-Meteo Marine** para calcular la marea de
Chipiona: qué pedimos, cada cuánto, cómo se detectan pleamar/bajamar y qué
hay que vigilar.

> **Esto no es la documentación de Open-Meteo.** Lo que devuelve cada
> endpoint, sus límites y su licencia está en
> [`docs/apis/open-meteo/`](../../apis/open-meteo/README.md) — es donde hay
> que mirar antes de tocar nada aquí.

---

## 0. Por qué esto y no AEMET

AEMET **no publica altura de marea**. Sus dos únicos productos marítimos
(`/prediccion/maritima/costera/costa/{costa}` y `/altamar/area/{area}`) son
boletines de texto libre —viento en Beaufort, estado de la mar, visibilidad—,
verificado contra la API real el 2026-09-16 (ver
`docs/apis/aemet/07-maritima.md`). La fuente oficial española de mareas sería
Puertos del Estado, que no ofrece una API pública sencilla de integrar.
Open-Meteo Marine sí sirve `sea_level_height_msl` horario por coordenadas,
gratis y sin registro, así que es lo que se usa aquí.

---

## 1. Las piezas

```
Schedule::command('marine:sync')->twiceDailyAt(12, 20, 25)
        │
        ▼
  OpenMeteoMarineSyncCommand
        │
        ▼
  OpenMeteoMarineService::fetchSeaLevelHeights()
        │  · GET a config('open_meteo_marine.base_url'), coordenadas de Chipiona
        │  · hourly=sea_level_height_msl, forecast_days=2
        ▼
  TideExtremesCalculator::extremes()
        │  · interpolación parabólica de 3 puntos sobre cada máximo/mínimo local
        │  · funde extremos del mismo tipo separados menos de min_hours_between_same_type
        ▼
  open_meteo_marine_tides (upsert por `happens_at` redondeado al minuto)
        │
        ▼
  WeatherStationController::index() → tarjeta "Marea" en /weatherstation
```

| Pieza | Qué hace |
|---|---|
| `App\Services\WeatherStation\OpenMeteoMarineService` | La única petición HTTP: sin envelope, sin API key, sin cuota que gestionar |
| `App\Support\WeatherStation\TideExtremesCalculator` | Lógica pura (sin HTTP): detecta y funde extremos. Testeada con una serie real (`tests/Unit/WeatherStation/TideExtremesCalculatorTest.php`) |
| `App\Console\Commands\WeatherStation\OpenMeteoMarineSyncCommand` | `marine:sync`. Delgado: pide, calcula, guarda |
| `App\Models\WeatherStation\OpenMeteo\OpenMeteoMarineTide` | Una fila por extremo (`happens_at` único) |
| `App\Enums\TideExtremeTypeEnum` | `Pleamar`/`Bajamar`, con icono |

---

## 2. Config (`config/open_meteo_marine.php`)

| Clave | Por defecto | Qué es |
|---|---|---|
| `base_url` | `https://marine-api.open-meteo.com/v1/marine` | Sin API key, es pública |
| `reference.lat` / `reference.lon` | Chipiona (`OPEN_METEO_MARINE_LAT`/`LON`) | Punto fijo, no por fila — no hay ningún caso de uso de este proyecto que pida marea de otro sitio |
| `forecast_days` | `2` | De sobra para siempre tener el próximo extremo calculado, sin pedir más ventana de la necesaria |
| `timezone` | `Europe/Madrid` | Las horas de `time[]` ya vienen en esta zona, no hay que convertir |
| `min_hours_between_same_type` | `3` | Umbral de fusión de `TideExtremesCalculator::merge()` — ver más abajo |

---

## 3. El algoritmo de detección

Sobre la serie horaria `(times[i], heights[i])`, para cada punto interior se
compara con sus dos vecinos: es **Pleamar** si `h ≥ h_prev` y `h ≥ h_next` (con
al menos una desigualdad estricta), **Bajamar** si es al revés. El instante y
la altura exactos se afinan con la **interpolación de vértice de parábola**
sobre esos 3 puntos (`x = -1, 0, 1` en horas):

```
denom  = h_prev - 2·h + h_next
offset = clamp(0.5 · (h_prev - h_next) / denom, -1, 1)     // horas desde el punto central
altura = h - 0.25 · (h_prev - h_next) · offset
```

Es la misma fórmula que se usa para afinar picos de FFT (Jacobsen/Candan) —
verificada a mano contra el vértice analítico de la parábola antes de
implementarla, y contra una serie real de Chipiona el 2026-09-16.

### La meseta: por qué hace falta `merge()`

Esa misma verificación con datos reales encontró un caso que la detección
punto a punto no cubre: cuando dos horas seguidas tienen la **misma** altura
(ej. `-1.06` a las 00:00 y a la 01:00), ambas se marcan como Bajamar
independientes, a 1 hora de diferencia. Físicamente es imposible — la marea
semidiurna de Chipiona tiene ~6 h entre un extremo y el siguiente del mismo
tipo—, así que `TideExtremesCalculator::merge()` funde los extremos del mismo
tipo separados menos de `min_hours_between_same_type` (3 h) y se queda con el
más extremo de los dos. El test
`merges_the_flat_plateau_into_a_single_low_tide` fija este caso con la serie
real que lo produjo.

---

## 4. La tabla `open_meteo_marine_tides`

Ver la migración para el detalle completo (todas las columnas están
comentadas). Sin lat/lon por fila — el punto de referencia es fijo (Chipiona)
y vive en config.

`happens_at` es único: dos sondeos con ventanas de predicción solapadas
(forecast_days=2, dos veces al día) producen el mismo extremo con segundos de
diferencia, y `saveExtremes()` hace `updateOrCreate` redondeando al minuto
para no acumular casi-duplicados.

---

## 5. Planificador

`marine:sync` corre dos veces al día (`twiceDailyAt(12, 20, 25)`, hora de
Madrid), 15 minutos después de `aemet:coast` para no juntar las dos peticiones
salientes en el mismo minuto. La marea es astronómicamente predecible y
Open-Meteo no rehace su modelo con más frecuencia que eso — pedirlo más a
menudo no trae datos nuevos.

---

## 6. Atribución obligatoria

Los datos de Open-Meteo son **CC BY 4.0**: toda vista que los muestre necesita
un enlace visible a Open-Meteo (ver
`docs/apis/open-meteo/LIMITACIONES.md#condiciones-legales`). La tarjeta de
Marea de `/weatherstation` lleva el enlace `https://open-meteo.com/` junto a
la atribución de AEMET, en el mismo pie de la fila de datos ambientales.

---

## Referencias

- Documentación de la API: [`docs/apis/open-meteo/`](../../apis/open-meteo/README.md)
- Modelos y rutas del módulo: [`weather-station.md`](../weather-station.md)
- Por qué no se usa AEMET para esto: [`aemet.md`](aemet.md)

---

> Creado: 2026-09-16
