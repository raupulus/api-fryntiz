# 🌤️ Previsión meteorológica


**1 endpoint.** El núcleo de Open-Meteo: previsión horaria, diaria, cuarto-horaria y condiciones
actuales para cualquier coordenada del mundo, combinando más de 30 modelos numéricos.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/forecast.yml`, `src/web-texto/forecast.txt`,
> `src/web-texto/model-updates.txt` + verificación en vivo del 2026-08-31 (peticiones 01–06, 24–26,
> 30–33 del registro).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://api.open-meteo.com/v1/forecast` | 🟢 | JSON UTF-8, objeto (o array con varias coordenadas) |

> [!CAUTION]
> Dos trampas de este endpoint, ambas verificadas y ambas silenciosas:
> **sin `latitude` ni `longitude` devuelve `200` con el cuerpo vacío**
> ([`A1`](ERRATAS.md#a1--faltar-latitudelongitude-devuelve-200-con-el-cuerpo-vacío-)) y
> **sin variables devuelve `200` con solo metadatos** ([`A6`](ERRATAS.md#a6--sin-variables-pedidas-200-con-solo-metadatos-)).

---

## El endpoint 🟢

```
GET /v1/forecast?latitude={lat}&longitude={lon}&hourly={vars}
```

| | |
|---|---|
| Host | `api.open-meteo.com` (pago: `customer-api.open-meteo.com`) |
| Autenticación | Ninguna en el nivel gratuito |
| Periodicidad de los datos | Según modelo: entre 1 h (GFS, AROME, UKMO) y 6 h (IFS) 🔵 |
| Tamaño 🟢 | 841 B (1 variable horaria × 1 día) · 1.771 B comprimido (1 variable × 16 días) |
| TTL 🟡 | 1 h para previsión general · 15 min si se usa `current` |

Verificado el 2026-08-31 con `latitude=40.4168&longitude=-3.7038` (Madrid) y varias combinaciones de
`hourly`, `daily`, `current`, `minutely_15`, `models`, `timezone`, `format` y multi-localización.

### Parámetros

Los comunes están en [`00-fundamentos.md`](00-fundamentos.md#parámetros-comunes-a-casi-todas-las-apis).
Los propios o con límite específico de este endpoint:

| Parámetro | Formato | Defecto | Límite |
|---|---|---|---|
| `hourly` | Lista | — | 147 variables de superficie + 208 de niveles de presión declaradas en la spec, **y más** ([`C7`](ERRATAS.md#c7--los-enum-de-las-specs-están-incompletos-no-son-un-catálogo-cerrado-)) |
| `daily` | Lista | — | 22 en la spec; la web documenta bastantes más y la API las acepta 🟢 |
| `current` | Lista | — | 15 variables |
| `minutely_15` | Lista | — | 26 variables |
| `forecast_days` | Entero | `7` | `0`–`16` |
| `past_days` | Entero | `0` | `0`–`92` |
| `forecast_hours` / `past_hours` | Entero | — | Referencia: la hora actual, no el día |
| `forecast_minutely_15` / `past_minutely_15` | Entero | — | Referencia: el cuarto de hora actual |
| `start_hour` / `end_hour` | `yyyy-mm-ddThh:mm` | — | No está en la spec ⚠️ |
| `tilt` / `azimuth` | Decimal o `nan` | `0` | Solo para `global_tilted_irradiance` |
| `cell_selection` | `land` \| `sea` \| `nearest` | `land` | |
| `models` | Lista | `best_match` | 50 en la spec, más alias no declarados 🟢 |

**Ventana temporal por defecto:** la serie empieza a las **00:00 del día de hoy** en la zona horaria
pedida, no en la hora actual 🔵. Con 7 días son 168 valores horarios.

---

## Bloques de datos

### `hourly` — variables de superficie

Agrupadas por familia (nombres tal cual se pasan a la API). La lista completa está en
`src/especificacion/forecast.yml`; aquí las familias y las de uso habitual:

| Familia | Variables |
|---|---|
| Temperatura | `temperature_2m`, `temperature_2m_min`, `temperature_2m_max`, `apparent_temperature`, `dew_point_2m`, `wet_bulb_temperature_2m`, `surface_temperature`, `temperature_{20,40,50,80,100,120,150,180,200}m` |
| Humedad y presión | `relative_humidity_2m`, `pressure_msl`, `surface_pressure`, `vapour_pressure_deficit`, `total_column_integrated_water_vapour` |
| Precipitación | `precipitation`, `rain`, `showers`, `snowfall`, `snow_depth`, `snowfall_water_equivalent`, `snow_depth_water_equivalent`, `snowfall_height`, `precipitation_type` |
| Probabilidades | `precipitation_probability`, `rain_probability`, `snowfall_probability`, `freezing_rain_probability`, `ice_pellets_probability`, `thunderstorm_probability` |
| Nubosidad y visibilidad | `cloud_cover`, `cloud_cover_low`, `cloud_cover_mid`, `cloud_cover_high`, `visibility`, `convective_cloud_base`, `convective_cloud_top` |
| Viento | `wind_speed_{10..200}m`, `wind_direction_{10..200}m`, `wind_gusts_10m` (14 alturas: 10, 20, 30, 40, 50, 70, 80, 100, 120, 140, 150, 160, 180 y 200 m) |
| Radiación solar | `shortwave_radiation`, `direct_radiation`, `diffuse_radiation`, `direct_normal_irradiance`, `global_tilted_irradiance`, `terrestrial_radiation` y sus seis variantes `*_instant` |
| Suelo | `soil_temperature_*` y `soil_moisture_*` (las profundidades **cambian según el modelo**: `0cm/6cm/18cm/54cm…` en ICON, `0_to_7cm/7_to_28cm…` en IFS) |
| Convección y tormenta | `cape`, `lifted_index`, `convective_inhibition`, `boundary_layer_height`, `freezing_level_height`, `lightning_potential`, `lightning_density`, `updraft`, `k_index` |
| Otras | `weather_code`, `is_day`, `uv_index`, `uv_index_clear_sky`, `sunshine_duration`, `evapotranspiration`, `et0_fao_evapotranspiration`, `runoff`, `albedo`, `roughness_length`, `mass_density_8m`, `snow_height` |
| Mar (dentro de esta API) | `sea_surface_temperature`, `sea_level_height_msl`, `sea_ice_thickness`, `ocean_current_velocity`, `ocean_current_direction` |

> [!IMPORTANT]
> **Los nombres de las profundidades del suelo dependen del modelo, y equivocarse no da error** 🟢.
> Verificado el 2026-09-01 en Madrid:
>
> | Petición | Resultado |
> |---|---|
> | `soil_temperature_0_to_7cm` con `models=icon_eu` | `200` con la serie **entera a `null`** |
> | `soil_temperature_0cm` con `models=ecmwf_ifs025` | `200` con 24/24 valores |
>
> Es decir: la nomenclatura de IFS (`0_to_7cm`) sobre un modelo ICON devuelve nulos en silencio,
> mientras que la de ICON (`0cm`) sobre IFS sí funciona —la API la convierte—. Fijar el modelo antes
> que las profundidades, y comprobar que la serie trae valores.

**Momento de validez de cada variable** 🔵 — es lo que más se malinterpreta:

| Tipo | Variables | Significado del valor de las 14:00 |
|---|---|---|
| Instantáneo | `temperature_2m`, `cloud_cover`, `pressure_msl`, viento, `weather_code`… | El estado a las 14:00 |
| Suma de la hora anterior | `precipitation`, `rain`, `showers`, `snowfall`, `evapotranspiration` | Lo acumulado entre las 13:00 y las 14:00 |
| Media de la hora anterior | Todas las de radiación | La media de 13:00 a 14:00 |
| Máximo de la hora anterior | `wind_gusts_10m` | La racha máxima entre 13:00 y 14:00 |

### `daily`

Las 22 de la spec: `weather_code`, `temperature_2m_max`, `temperature_2m_min`,
`apparent_temperature_max`, `apparent_temperature_min`, `sunrise`, `sunset`, `daylight_duration`,
`sunshine_duration`, `uv_index_max`, `uv_index_clear_sky_max`, `rain_sum`, `showers_sum`,
`snowfall_sum`, `precipitation_sum`, `precipitation_hours`, `precipitation_probability_max`,
`wind_speed_10m_max`, `wind_gusts_10m_max`, `wind_direction_10m_dominant`,
`shortwave_radiation_sum`, `et0_fao_evapotranspiration`.

La web documenta además, entre otras, `temperature_2m_mean`, `apparent_temperature_mean`,
`precipitation_probability_mean/min`, `cloud_cover_mean/max/min`, `cape_mean/max/min`,
`pressure_msl_mean/max/min`, `surface_pressure_mean/max/min`, `visibility_mean/max/min`,
`growing_degree_days_base_0_limit_50`, `leaf_wetness_probability_mean`, `updraft_max`,
`snowfall_water_equivalent_sum`, `moonrise`, `moonset`, `moon_phase`,
`vapour_pressure_deficit_max`. Verificado el 2026-08-31 que `cloud_cover_mean` y
`growing_degree_days_base_0_limit_50` funcionan pese a no estar en la spec 🟢.

Las agregaciones diarias son **agregaciones de 24 h de los valores horarios** 🔵. `sunrise` y
`sunset` llegan como cadena ISO8601, no como número.

### `current` 🟢

15 variables: `temperature_2m`, `relative_humidity_2m`, `apparent_temperature`, `is_day`,
`precipitation`, `rain`, `showers`, `snowfall`, `weather_code`, `cloud_cover`, `pressure_msl`,
`surface_pressure`, `wind_speed_10m`, `wind_direction_10m`, `wind_gusts_10m`.

Respuesta real (2026-08-31, Madrid):

```json
"current_units": {"time":"iso8601","interval":"seconds","temperature_2m":"°C","weather_code":"wmo code"},
"current":       {"time":"2026-08-31T08:15","interval":900,"temperature_2m":17.3,"weather_code":0}
```

`interval: 900` significa que las variables acumuladas (`precipitation`…) corresponden a los
**15 minutos** anteriores, no a una hora 🟢. Los datos actuales se basan en la salida
cuarto-horaria del modelo 🔵.

### `minutely_15` 🟢

26 variables. Datos **nativos** solo en Centroeuropa (ICON-D2, AROME) y Norteamérica (HRRR); en el
resto del mundo, incluida la mayor parte de España, **se interpolan desde los valores horarios** 🔵.

Verificado el 2026-08-31 con `forecast_minutely_15=4` en Madrid: devolvió cuatro pasos de 15 minutos
a partir del cuarto de hora en curso.

### Niveles de presión

8 familias (`temperature`, `relative_humidity`, `dew_point`, `cloud_cover`, `wind_speed`,
`wind_direction`, `geopotential_height`, `vertical_velocity`) × 26 niveles desde `1000hPa` hasta
`10hPa`, con el nombre formado como `temperature_850hPa`. Todas son valores instantáneos 🔵.

Altitudes aproximadas 🔵: 1000 hPa ≈ 110 m · 850 hPa ≈ 1.500 m · 500 hPa ≈ 5,6 km · 200 hPa ≈
11,8 km. Para la altitud exacta se usa `geopotential_height_*`.

---

## Códigos WMO (`weather_code`) 🔵

Llegan como **entero**, no como cadena.

| Código | Significado |
|---|---|
| `0` | Cielo despejado |
| `1`, `2`, `3` | Mayormente despejado · parcialmente nuboso · cubierto |
| `45`, `48` | Niebla · niebla engelante |
| `51`, `53`, `55` | Llovizna débil · moderada · densa |
| `56`, `57` | Llovizna engelante débil · densa |
| `61`, `63`, `65` | Lluvia débil · moderada · fuerte |
| `66`, `67` | Lluvia engelante débil · fuerte |
| `71`, `73`, `75` | Nieve débil · moderada · fuerte |
| `77` | Cinarra (granos de nieve) |
| `80`, `81`, `82` | Chubascos débiles · moderados · violentos |
| `85`, `86` | Chubascos de nieve débiles · fuertes |
| `95` | Tormenta débil o moderada |
| `96`, `99` | Tormenta con granizo débil · fuerte |

> [!NOTE]
> **`96` y `99` solo se predicen en Centroeuropa** 🔵. En España no aparecerán aunque haya granizo:
> la tormenta se codificará como `95`. Una interfaz que dependa de distinguir granizo no funcionará
> igual en toda la península.
>
> La tabla **no es continua**: faltan códigos WMO intermedios (`4`–`44`, `50`, `52`…). Un `switch`
> debe tener siempre rama por defecto.

---

## Selección de modelo

Por defecto `models=best_match`: Open-Meteo elige para cada coordenada el modelo de mayor resolución
disponible y **cose las pasadas sucesivas en una serie continua** 🔵.

Modelos declarados en la spec (50), por proveedor:

| Proveedor | Identificadores |
|---|---|
| ECMWF | `ecmwf_ifs`, `ecmwf_ifs025`, `ecmwf_aifs025_single` |
| DWD | `icon_seamless`, `icon_global`, `icon_eu`, `icon_d2` (alias `dwd_icon_d2` 🟢) |
| NOAA | `ncep_gfs_seamless`, `ncep_gfs_global`, `ncep_hrrr_conus`, `ncep_nbm_conus`, `ncep_nam_conus`, `ncep_gfs_graphcast025`, `ncep_aigfs025`, `ncep_hgefs025_ensemble_mean` |
| Météo-France | `meteofrance_seamless`, `meteofrance_arpege_world`, `meteofrance_arpege_europe`, `meteofrance_arome_france`, `meteofrance_arome_france_hd` |
| UK Met Office | `ukmo_seamless`, `ukmo_global_deterministic_10km`, `ukmo_uk_deterministic_2km` |
| JMA | `jma_seamless`, `jma_msm`, `jma_gsm` |
| KMA | `kma_seamless`, `kma_ldps`, `kma_gdps` |
| Canadá (CMC) | `cmc_gem_seamless`, `cmc_gem_gdps`, `cmc_gem_rdps`, `cmc_gem_hrdps`, `cmc_gem_hrdps_west` |
| MeteoSwiss | `meteoswiss_icon_seamless`, `meteoswiss_icon_ch1`, `meteoswiss_icon_ch2` |
| MET Norway | `metno_seamless`, `metno_nordic` |
| KNMI | `knmi_seamless`, `knmi_harmonie_arome_europe`, `knmi_harmonie_arome_netherlands` |
| DMI | `dmi_seamless`, `dmi_harmonie_arome_europe` |
| Otros | `cma_grapes_global`, `bom_access_global`, `italia_meteo_arpae_icon_2i`, `geosphere_seamless`, `geosphere_arome_austria` |

**No declarados en la spec pero funcionales** 🟢: `chmi_aladin_seamless` (y sus variantes
`chmi_aladin_central_europe_2km`, `chmi_aladin_cz_1km`), además de los alias con prefijo de
proveedor. Ver [`C7`](ERRATAS.md#c7--los-enum-de-las-specs-están-incompletos-no-son-un-catálogo-cerrado-).

> [!CAUTION]
> **Fijar un modelo que no cubre la coordenada devuelve `200` con JSON inválido** (`nan`), no un
> error. Verificado con `metno_nordic` en Madrid el 2026-08-31:
> [`A7`](ERRATAS.md#a7--un-modelo-fuera-de-su-dominio-devuelve-json-inválido-con-200-ok-). Con
> `best_match` no ocurre.

### Cobertura real en España 🟢

Medida el **2026-08-31** con una petición por combinación de modelo y coordenada
(`hourly=temperature_2m&forecast_days=1`). `OK` = datos; `400` = error legible «No data is available
for this location»; **`nan`** = `200` con JSON inválido.

| Modelo | Península (Madrid) | Noreste (Barcelona) | Baleares | Cádiz | Canarias |
|---|---|---|---|---|---|
| `best_match` | OK | OK | — | — | OK |
| `ecmwf_ifs025` | OK | OK | — | — | OK |
| `ncep_gfs_seamless` | OK | — | — | — | — |
| `ukmo_global_deterministic_10km` | OK | — | — | — | — |
| `icon_eu` | OK | OK | OK | — | **`400`** |
| `meteofrance_arome_france` | OK | — | OK | **`nan`** | **`nan`** |
| `meteofrance_arome_france_hd` | OK | — | — | — | — |
| `icon_d2` | **`400`** | **`400`** | — | — | **`400`** |
| `metno_nordic` | **`nan`** | — | — | — | — |
| `knmi_harmonie_arome_europe` | **`nan`** | — | — | — | — |
| `dmi_harmonie_arome_europe` | **`nan`** | — | — | — | — |
| `italia_meteo_arpae_icon_2i` | **`nan`** | — | — | — | — |

(«—» = combinación no probada.)

Conclusiones 🟢:

- **`best_match` y `ecmwf_ifs025` funcionan en todo el territorio probado**, Canarias incluida.
  `best_match` respondió también en Melilla.
- **`icon_eu` cubre la península y Baleares pero no Canarias** — y ahí avisa con un `400` correcto.
- **`meteofrance_arome_france` es el caso peligroso**: funciona en Madrid y Baleares, y devuelve
  `nan` en Cádiz y Canarias. Es de 1–1,5 km, así que resulta tentador; su recorte por el sur
  peninsular no está documentado en ninguna parte 🔴.
- Los modelos nórdico, neerlandés, danés e italiano **devuelven `nan` en Madrid**: no usarlos.

**Recomendación 🟡: `best_match` salvo motivo concreto.** Si se fija un modelo, hay que probarlo en
cada coordenada donde vaya a usarse, no solo en una.

El detalle de resolución y frecuencia de cada modelo está en
[`12-modelos-y-actualizaciones.md`](12-modelos-y-actualizaciones.md).

### Pedir varios modelos a la vez 🟢

Verificado el 2026-08-31 con `models=icon_eu,ecmwf_ifs025`:

```json
"hourly_units": {"time":"iso8601","temperature_2m_icon_eu":"°C","temperature_2m_ecmwf_ifs025":"°C"},
"hourly":       {"time":[…],"temperature_2m_icon_eu":[21.6,20.5,…],"temperature_2m_ecmwf_ifs025":[19.8,18.7,…]}
```

**Cada variable lleva el sufijo del modelo y desaparece la serie sin sufijo.** Es decir, el nombre
de la clave cambia según cuántos modelos se pidan: con uno es `temperature_2m`, con dos o más es
`temperature_2m_<modelo>`. Un cliente que fije el nombre a mano se rompe al añadir un segundo
modelo.

---

## Multi-localización 🟢

```
?latitude=40.4168,41.3874,37.3826&longitude=-3.7038,2.1686,-5.9963
```

Una sola llamada para N puntos: la palanca más eficaz para no consumir cuota. La raíz pasa a ser un
**array**, y el primer elemento **no trae `location_id`**
([`A2`](ERRATAS.md#a2--location_id-no-existe-en-el-primer-elemento-de-una-respuesta-multi-localización-)).

`timezone=auto` resuelve la zona de cada coordenada por separado 🟢: en la prueba con Madrid y
Barcelona ambas devolvieron `Europe/Madrid` con `utc_offset_seconds: 7200`.

**No hay límite de 100 coordenadas aquí** 🟢: una petición con 200 pares devolvió un array de 200
elementos (2026-09-01). Ese límite es exclusivo de [`/v1/elevation`](11-geocodificacion-y-elevacion.md#elevación-).
El techo real de `/v1/forecast`, si lo hay, sigue sin medirse 🔴.

---

## Ejemplo verificado 🟢

```bash
curl -s "https://api.open-meteo.com/v1/forecast?latitude=40.4168&longitude=-3.7038&daily=temperature_2m_max&timezone=Europe%2FMadrid&forecast_days=1"
```

```json
{"latitude":40.4375,"longitude":-3.6875,"generationtime_ms":0.027,"utc_offset_seconds":7200,
 "timezone":"Europe/Madrid","timezone_abbreviation":"GMT+2","elevation":666.0,
 "daily_units":{"time":"iso8601","temperature_2m_max":"°C"},
 "daily":{"time":["2026-08-31"],"temperature_2m_max":[32.7]}}
```

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Recorte exacto de `meteofrance_arome_france` por el sur peninsular (funciona en Madrid, falla en Cádiz) | Media |
| 2 | Techo real de coordenadas en multi-localización: 200 funcionan, no se ha buscado el límite | Baja |
| 3 | Si `forecast_days=16` es realmente el máximo (la spec y la web coinciden, pero no se probó) | Baja |
| 4 | Lista completa y actual de variables `daily` (la spec está incompleta y la web no la numera) | Baja |

> Creado: 2026-09-01 · Última revisión: 2026-09-01
