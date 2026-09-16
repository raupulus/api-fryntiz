# Revisar AEMET — qué productos nuevos añadir

> **Resuelto e implementado (2026-09-16), los tres candidatos.** Detalle
> completo de los tres productos (config, modelos, comandos, cadencia) en
> [`docs/info/apis/aemet.md`](../../info/apis/aemet.md). Este documento se
> conserva por el histórico de la investigación — en concreto, por qué la
> propuesta inicial de la predicción diaria estaba mal y qué hizo falta para
> corregirla.

✅ El lío de `aemet:ozono` (pedía el perfil vertical en vez del ozono de
superficie) y las tres rutas rotas de `AEMETService` se corrigieron el
2026-09-14 — detalle en [`docs/info/apis/aemet.md`](../../info/apis/aemet.md) y
[`docs/info/commands.md`](../../info/commands.md).

✅ **UVI y observación de estaciones, implementados (2026-09-16)**:
`aemet:uvi` y `aemet:station-observations`, modelos `AEMETUvi` y
`AEMETStationObservation`. Las tablas de propuesta de columnas de este
documento (más abajo) son las que se implementaron, sin cambios de última
hora.

✅ **Predicción diaria de municipio, implementada (2026-09-16), con la
propuesta original corregida.** La primera versión de la sección 1 (más
abajo) era una reconstrucción a partir de la predicción horaria, **y estaba
equivocada en varios puntos**: no existe `vientoAndRachaMax` combinado en la
diaria (`viento` y `rachaMax` van separados), no hay `precipitacion` en mm ni
`orto`/`ocaso`, y sí hay un campo `uvMax` que no se había previsto. Se
descubrió al fin porque **la máquina de desarrollo tenía la cuota de este
endpoint agotada** (429, "Se ha alcanzado uno de los límites de uso") y **una
clave nueva no lo arregló** — la cuota de AEMET va ligada a la IP, no a la
clave, documentado en `LIMITACIONES.md` y confirmado de nuevo aquí. Se
resolvió lanzando la petición real **desde un VPS con otra IP**, con la clave
pegada directamente en el comando (sin placeholders que editar a mano). Con
esa respuesta real se rehizo la sección 1 entera: JSON verdadero, tabla de
columnas corregida (menos `precipitacion`/`orto`/`ocaso`, más `snow_level` y
`uv_max`), comando y modelo — `aemet:daily-prediction`, `AEMETDailyPrediction`.

**Lección para la próxima vez que un endpoint dé 429 y haga falta seguir
trabajando:** no asumir la estructura por analogía con un producto parecido
("la diaria será como la horaria con otros tramos") y darla por buena sin
verificar — mejor decirlo explícitamente como pendiente de confirmar, que es
lo que se hizo, y confirmarlo en cuanto hubiera manera (otra IP).

## Candidatos revisados (2026-09-15)

AEMET publica 64 endpoints; en la revisión del 2026-09-15 se consumían 9 (hoy,
con los tres implementados, son 12). Se revisaron los 55 que quedaban entonces
(sin mapas — el usuario no los quiere) y la mayoría se descarta sin más:
predicción nacional en texto (redundante con lo ya estructurado), montaña,
nivológica y Antártida (no aplican a Chipiona), PDFs de balance
hídrico/resumen climatológico (no son datos consumibles), y valores extremos
climatológicos (descartado: poco interés real ahora mismo, son récords
históricos casi estáticos).

Quedan **tres candidatos** con interés real para el proyecto:

### 1. Predicción diaria de municipio

```
GET /api/prediccion/especifica/municipio/diaria/{municipio}
```

Complementa a la horaria ya implementada con un "hoy/mañana" resumido.
Publicación 4 veces/día. **Estructura verificada de verdad (2026-09-16),
ejecutando la petición real desde un VPS** — la de desarrollo tenía la cuota
agotada y una clave nueva no lo arregló (va ligada a IP). Recorte real de la
respuesta (2 de los 7 días que trae, para no repetir lo mismo siete veces):

```json
[ {
  "elaborado": "2026-09-16T12:39:07",
  "nombre": "Chipiona",
  "provincia": "Cádiz",
  "prediccion": {
    "dia": [
      {
        "fecha": "2026-09-16T00:00:00",
        "probPrecipitacion": [
          { "value": 0, "periodo": "00-24" },
          { "value": 0, "periodo": "12-24" }
        ],
        "cotaNieveProv": [{ "value": "", "periodo": "00-24" }],
        "estadoCielo": [
          { "value": "", "periodo": "00-24", "descripcion": "" },
          { "value": "12", "periodo": "12-24", "descripcion": "Poco nuboso" }
        ],
        "viento": [
          { "direccion": "", "velocidad": 0, "periodo": "00-24" },
          { "direccion": "O", "velocidad": 25, "periodo": "12-24" }
        ],
        "rachaMax": [
          { "value": "", "periodo": "00-24" },
          { "value": "40", "periodo": "12-24" }
        ],
        "temperatura": { "maxima": 27, "minima": 20, "dato": [] },
        "sensTermica": { "maxima": 27, "minima": 20, "dato": [] },
        "humedadRelativa": { "maxima": 90, "minima": 65, "dato": [] },
        "uvMax": 6
      },
      {
        "fecha": "2026-09-22T00:00:00",
        "probPrecipitacion": [{ "value": 0 }],
        "cotaNieveProv": [{ "value": "" }],
        "estadoCielo": [{ "value": "11", "descripcion": "Despejado" }],
        "viento": [{ "direccion": "C", "velocidad": 0 }],
        "rachaMax": [{ "value": "" }],
        "temperatura": { "maxima": 29, "minima": 20, "dato": [] },
        "sensTermica": { "maxima": 29, "minima": 20, "dato": [] },
        "humedadRelativa": { "maxima": 75, "minima": 35, "dato": [] }
      }
    ]
  },
  "id": 11016,
  "version": 1.0
} ]
```

**Lo que la primera versión de esta sección tenía mal**, comparado con la
respuesta real:

- ❌ No existe `vientoAndRachaMax` combinado. `viento` (con `direccion` y
  `velocidad`) y `rachaMax` (con `value`, como cadena) van **separados**.
- ❌ No hay `precipitacion` en mm, sólo `probPrecipitacion` (probabilidad).
- ❌ No hay `orto`/`ocaso` en la diaria (sí los tiene la horaria — productos
  con forma distinta, no la misma estructura con otros tramos).
- ❌ No existe `probTormenta` en absoluto.
- ✅ Lo que sí acertó: `temperatura`/`sensTermica`/`humedadRelativa` como
  objeto `{maxima, minima}`, y `elaborado` a nivel de boletín.
- 🆕 Lo que no se había previsto: `uvMax` (índice UV máximo del día) y
  `cotaNieveProv` (cota de nieve, casi siempre vacía en Cádiz).

**El número de tramos por campo varía según lo lejos que esté el día**: hasta
7 (día 1: `00-24`, `00-12`, `12-24`, `00-06`, `06-12`, `12-18`, `18-24`), 3
para los días intermedios, y **ningún** `periodo` (un único elemento) para
los tres o cuatro días más lejanos — ahí el array trae un solo elemento que
ya es el del día completo. Y **`uvMax` no viene siempre**: ausente en los dos
días más lejanos del rango de siete, verificado en la respuesta real.

**Propuesta de tabla** `meteorology_aemet_daily_predictions` (separada de la
horaria: aquí los campos son máximo/mínimo del día, no un valor por hora, así
que forzarlos en la misma tabla dejaría columnas duplicadas a medias).

Sin `municipality_code`/`city`/`province`: esta API es siempre Chipiona,
siempre Cádiz — el `id` fijo (`11016`) va en config (`env`/`config/aemet.php`,
igual que `GDACS_REFERENCE_LAT`/`LON`), no en cada fila de una tabla donde el
valor nunca cambia. Por lo mismo, `date` sola es la clave — no hace falta
combinarla con un municipio que siempre es el mismo:

| Campo | Tipo | De dónde sale |
|---|---|---|
| `date` | date | `dia[].fecha` |
| `sky_status` / `sky_status_code` | string | `estadoCielo[].descripcion` / `.value`, tramo `00-24` |
| `rain_prob` | int | `probPrecipitacion[].value`, tramo `00-24` — no hay milímetros en este producto |
| `snow_level` | string | `cotaNieveProv[].value`, tramo `00-24` |
| `wind_direction` / `wind_speed` | string / float | `viento[].direccion` / `.velocidad`, tramo `00-24` |
| `wind_gust` | float | `rachaMax[].value`, tramo `00-24` (llega como cadena, a veces vacía) |
| `temperature_max` / `temperature_min` | float | `temperatura.maxima` / `.minima` |
| `thermal_sensation_max` / `_min` | float | `sensTermica.maxima` / `.minima` |
| `humidity_max` / `_min` | float | `humedadRelativa.maxima` / `.minima` |
| `uv_max` | int, nullable | `uvMax` — ausente en los días más lejanos del rango |
| `elaborated_at` | timestamp | `elaborado` |
| `created_at` / `updated_at` | timestamp | — |

`unique(date)` para que un sondeo del mismo día actualice en vez de duplicar
(mismo patrón que `AEMETOzoneTotal`, que sí necesita `station_code` en su
`unique` porque ese producto sí trae varias estaciones a la vez — este no).
"Tramo `00-24`" es una simplificación: cuando el array no tiene `periodo` (un
único elemento, días lejanos), se coge ese elemento directamente — el
resultado final es el mismo, "el valor del día completo".

### 2. UVI (índice de radiación ultravioleta)

```
GET /api/prediccion/especifica/uvi/{dia}
```

`dia` de `0` (hoy) a `4`. Publicación 1 vez/día. Raíz `dict`, claves en
MAYÚSCULAS (único producto así junto a valores extremos), `CIUDAD` es una
lista de 59 capitales — Cádiz sería la más cercana a Chipiona, no hay punto
exacto para la ciudad. Ejemplo real capturado en
[`03-predicciones-especificas.md`](../../apis/aemet/03-predicciones-especificas.md)
(Albacete/Alicante; 🔴 sin verificar el `id` real de Cádiz capital — hay que
comprobarlo contra `maestro/municipios` antes de implementar):

```json
{
  "FECHA_ELABORACION": "2026-09-15",
  "FECHA_MOD": "2026-09-15",
  "FECHA_VALIDEZ": "2026-09-15",
  "CIUDAD": [
    { "id": "02003", "valor": "Albacete", "uv": "8", "canarias": "0" },
    { "id": "03014", "valor": "Alacant/Alicante", "uv": "7", "canarias": "0" }
  ]
}
```

**Propuesta de tabla** `meteorology_aemet_uvi`:

Mismo razonamiento que la anterior: de las 59 ciudades de la respuesta solo se
queda la de Cádiz (config, no columna), así que `city_code`/`city_name` sobran
— y `is_canary` también, porque Cádiz nunca es Canarias: sería una columna que
vale `false` en cada fila para siempre.

| Campo | Tipo | De dónde sale |
|---|---|---|
| `uv_index` | int | `CIUDAD[].uv` (de la entrada de Cádiz) |
| `valid_date` | date | `FECHA_VALIDEZ` |
| `elaborated_at` | timestamp | `FECHA_ELABORACION` |
| `modified_at` | timestamp | `FECHA_MOD` |
| `created_at` / `updated_at` | timestamp | — |

`unique(valid_date)`.

### 3. Observación de estación (datos reales, no predicción) — 3 estaciones

```
GET /api/observacion/convencional/datos/estacion/{idema}
```

**Verificado en directo (2026-09-16) contra el endpoint de observación de cada
una. Las tres dan dato — ojo con el `idema` de Rota:**

| Zona | `idema` | Distancia a Chipiona | ¿Da dato? |
|---|---|---|---|
| `chipiona_eca` | `5906X` | 3,4 km | ✅ Sí — dato de hoy, con temperatura |
| `rota_base_naval` | `5910X` | 14,2 km | ✅ Sí — "ROTA BASE NAVAL", dato de hoy con temperatura |
| `almonte` | `5858X` | 28,0 km | ✅ Sí — AEMET la llama literalmente "ALMONTE DOÑANA", dato de hoy con temperatura |

⚠️ **Corregido (2026-09-16):** el `idema` de Rota se sacó primero del
inventario de valores climatológicos (`valores/climatologicos/
inventarioestaciones/todasestaciones`), que listaba `5910` sin `X` — y con
ese código el endpoint de observación convencional daba 404 (verificado que
no era cuota: `Remaining-request-endpoint` de sobra y HTTP real 200, no 429).
**El inventario climatológico y el de observación convencional no comparten
necesariamente el mismo `idema` para la misma estación física** — con `5910X`
(sufijo `X`, el mismo patrón que ya tienen Chipiona y Almonte) sí funciona,
mismo emplazamiento. Antes de dar un `idema` por bueno para este producto,
verificarlo contra el propio endpoint de observación, no fiarse del
inventario climatológico.

Ejemplo real capturado hoy, estación de Chipiona:

```json
{
  "idema": "5906X",
  "ubi": "CHIPIONA  ECA",
  "lat": 36.75,
  "lon": -6.400558,
  "alt": 10.0,
  "fint": "2026-09-16T10:00:00+0000",
  "ta": 23.0,
  "tamin": 22.4,
  "tamax": 23.0,
  "hr": 74.0,
  "prec": 0.0,
  "vv": 4.7,
  "vmax": 7.4,
  "dv": 271.0,
  "dmax": 270.0
}
```

**Propuesta de tabla** `meteorology_aemet_station_observations`. Con tres
estaciones ya no vale el razonamiento de "un solo valor fijo" de las dos
tablas anteriores — aquí sí varía de fila a fila, así que se identifican con
dos campos, sin nada más de la estación (ni lat/lon/altitud, que no aportan
nada que el nombre/id no digan ya):

| Campo | Tipo | De dónde sale |
|---|---|---|
| `station_zone` | string | Fijo por estación: `chipiona_eca`, `rota_base_naval`, `almonte` — no viene de la API, lo ponemos nosotros |
| `station_id` | string | `idema` (`5906X`, `5910X`, `5858X`) |
| `observed_at` | timestamp | `fint` |
| `temperature` / `temperature_min` / `temperature_max` | float | `ta` / `tamin` / `tamax` |
| `dew_point` | float | `tpr` |
| `humidity` | float | `hr` |
| `precipitation_mm` | float | `prec` |
| `pressure` | float | `pres` |
| `visibility` | float | `vis` |
| `snow_depth` | float | `nieve` |
| `wind_speed` / `wind_gust` | float | `vv` / `vmax` |
| `wind_direction` / `wind_gust_direction` | float | `dv` / `dmax` |
| `created_at` / `updated_at` | timestamp | — |

`unique(station_id, observed_at)` — con varias estaciones sí hace falta la
combinación, a diferencia de las dos tablas anteriores (mismo patrón que
`airflight_routes`, que combina `airplane_id` con el momento).

**`dew_point`/`pressure`/`visibility`/`snow_depth` se añaden aunque hoy no
llegue ninguno de los cuatro en las tres estaciones** (decisión del usuario,
2026-09-16): se dejan `nullable` a la espera, por si empiezan a reportarlos
más adelante. Si en unas semanas/meses siguen sin llegar y no aparece otra
estación cercana que sí los dé, se valora quitar las columnas.

#### Campos opcionales: los que sí llegan y los que no (verificado en las tres estaciones, ~12 registros de cada una)

La API declara **39 campos, 34 opcionales** — "no des ninguno por
garantizado" ([`05-observacion.md`](../../apis/aemet/05-observacion.md)). Y aquí
el aviso se confirma **entre las propias tres estaciones**, no solo frente al
diccionario: **no traen las mismas**.

| Campo | Chipiona (`5906X`) | Rota (`5910X`) | Almonte (`5858X`) |
|---|---|---|---|
| `fint`, `ta`, `tamin`, `tamax`, `hr`, `prec` | ✅ | ✅ | ✅ |
| `vv`, `vmax`, `dv`, `dmax` (viento) | ✅ | ❌ | ✅ |
| `tpr`, `pres`, `vis`, `nieve` | ❌ | ❌ | ❌ |

**Rota no reporta viento en ninguno de sus 12 registros** — ni una sola vez,
no es que falte algún tramo. Si se implementan las tres en la misma tabla,
`wind_speed`/`wind_gust`/`wind_direction`/`wind_gust_direction` tienen que
ser `nullable` de verdad, no "opcional en la práctica pero siempre viene".

El resto — 20 de los 34 opcionales — **no llega en ninguna de las tres, y no
se añaden columna** (a diferencia de `tpr`/`pres`/`vis`/`nieve`, que sí se
guardan a la espera — ver la tabla de arriba). Se anotan aparte por si alguno
interesa igual más adelante:

| Campo(s) | Qué sería |
|---|---|
| `ts`, `tss5cm`, `tss20cm` | Temperatura junto al suelo / subsuelo a 5 y 20 cm |
| `pres_nmar` | Presión reducida al nivel del mar |
| `pacutp`, `pliqtp`, `psolt` | Precipitación por disdrómetro / líquida / sólida |
| `vvu`, `vmaxu`, `dvu`, `dmaxu` | Viento (velocidad/dirección, media y máxima) por sensor ultrasónico, en vez del mecánico |
| `stdvv`, `stddv`, `stdvvu`, `stddvu` | Desviación estándar de velocidad/dirección de viento (mecánico y ultrasónico) |
| `rviento` | Recorrido del viento en 60 min |
| `inso` | Duración de la insolación |
| `geo700`, `geo850`, `geo925` | Altura de superficies barométricas |

## Referencias

- AEMET OpenData: https://opendata.aemet.es/
- Alta de API key: https://opendata.aemet.es/centrodedescargas/altaUsuario
- Cómo se usa aquí: [`docs/info/apis/aemet.md`](../../info/apis/aemet.md)
- Documentación oficial destilada: [`docs/apis/aemet/`](../../apis/aemet/README.md)

> Creado: 2026-08-30 · Última revisión: 2026-09-16
