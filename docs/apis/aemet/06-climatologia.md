# 📊 Valores y productos climatológicos

**10 endpoints** (7 de valores climatológicos + 3 de productos). Series históricas y estadísticas,
no predicción.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`LIMITACIONES.md`](LIMITACIONES.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y parámetros) · **metadatos de los 8 endpoints** (los diccionarios de 27, 49, 486, 24 y 7 campos) · `src/web-texto/productosAEMET.txt` (productos del acceso general) · `src/catalogos/faqs.json` (FAQ 5.1–5.2: estaciones validadas y retraso de 4 días) · **verificación en vivo del 2026-08-26** (estructuras, tamaños y los dos PDF sin sobre).

---

## Resumen 🟢

**9 de 10 verificados.**

| Endpoint | Estado | Formato real | Tamaño |
|---|---|---|---|
| `GET /api/valores/climatologicos/normales/estacion/{idema}` | 🟢 | JSON `list[N]` | **139,7 KB** |
| `GET /api/valores/climatologicos/diarios/…/estacion/{idema}` | 🟢 | JSON `list[N]` | 2,4 KB (1 est. × 4 días) |
| `GET /api/valores/climatologicos/diarios/…/todasestaciones` | 🟢 | JSON | **402,8 KB** (1 día) |
| `GET /api/valores/climatologicos/mensualesanuales/…/estacion/{idema}` | 🟢 | JSON `list[13]` | 10,6 KB (1 año) |
| `GET /api/valores/climatologicos/valoresextremos/parametro/{parametro}/estacion/{idema}` | 🟢 | JSON **`dict[24]`** | 1,8 KB |
| `GET /api/valores/climatologicos/inventarioestaciones/todasestaciones` | 🟢 | JSON | **167,1 KB** |
| `GET /api/valores/climatologicos/inventarioestaciones/estaciones/{estaciones}` | 🟢 | JSON `list[N]` | 383 B (2 est.) |
| `GET /api/productos/climatologicos/balancehidrico/{anio}/{decena}` | 🟢 | ⚠️ **PDF, sin flujo de 2 saltos** | **4,6 MB** |
| `GET /api/productos/climatologicos/resumenclimatologico/nacional/{anio}/{mes}` | 🟢 | ⚠️ **PDF, sin flujo de 2 saltos** | **4–7 MB** |
| `GET /api/productos/climatologicos/capasshape/{tipoestacion}` | 🟢 | **ZIP** | 11,7 KB |

> [!IMPORTANT]
> **Las climatologías diarias llevan ~4 días de retraso** — ahora 🟢 confirmado por los metadatos del
> propio endpoint: `"1 vez al día, con un retardo de 4 días"`. No sirven para "ayer".
> Y solo se publican **estaciones validadas** 🔵 (FAQ 5.1).

> [!WARNING]
> ⚠️ **Los dos productos documentales rompen el flujo de dos saltos** 🟢: `balancehidrico` y
> `resumenclimatologico` devuelven el **PDF directamente** en el paso 1
> (`Content-Type: application/pdf;charset=ISO-8859-15`, 4–7 MB), sin sobre. Son las únicas dos
> excepciones en los 64 endpoints.
> Ver [`ERRATAS.md` D9](ERRATAS.md#d9--dos-endpoints-no-usan-el-flujo-de-dos-saltos-).
>
> ⚠️ Y si el periodo **no existe todavía**, devuelven **`HTTP 200` con el cuerpo VACÍO** — no un 404
> ni un `estado: 404`. Indistinguible de una petición sin `api_key`.
> Ver [`ERRATAS.md` A9](ERRATAS.md#a9-los-productos-documentales-devuelven-200-vacío-cuando-el-periodo-no-existe-).

## Climatologías normales 🟢

```
GET /api/valores/climatologicos/normales/estacion/{idema}
```

| | |
|---|---|
| `{idema}` | Indicativo climatológico. Ej.: `1495` |
| Periodo de referencia 🔵 | **1991-2020**, fijo (no es una ventana móvil) |
| Tamaño 🟢 | **139,7 KB para una sola estación** |
| TTL 🟡 | Meses. Es una serie de referencia que cambia cada década |

Verificado con `1495`.

### Estructura 🟢

Raíz: `list[N]` de objetos planos con **decenas de campos de nomenclatura muy comprimida**:

```
indicativo, w_racha_max, np_010_n, np_010_s, q_max_s, n_tor_n, n_tor_s,
q_max_n, tm_min_q4, …
```

🟡 Patrón deducido: prefijo de variable + sufijo de estadístico. `np_010` = número de días con
precipitación ≥ 1,0 mm; `_n`/`_s` = probablemente valor y desviación o normal/serie; `q_max` =
presión máxima; `n_tor` = número de días de tormenta; `tm_min_q4` = temperatura media de las mínimas
del cuarto trimestre.

> [!WARNING]
> **No adivines los nombres de campo.** Son decenas y la nomenclatura no está documentada en el
> spec. **La fuente autoritativa es la URL `metadatos`** del propio endpoint, que trae el
> diccionario completo con unidades. 🔴 Pendiente de volcarlo aquí.

⚠️ Este payload *sí* decodifica como UTF-8, pero solo por ser numérico
([`ERRATAS.md` A2](ERRATAS.md#a2-trampa-derivada-algunos-endpoints-parecen-utf-8-)). Convertir igual.

---

## Climatologías diarias 🟢

```
GET /api/valores/climatologicos/diarios/datos/fechaini/{fechaIniStr}/fechafin/{fechaFinStr}/estacion/{idema}
GET /api/valores/climatologicos/diarios/datos/fechaini/{fechaIniStr}/fechafin/{fechaFinStr}/todasestaciones
```

| | |
|---|---|
| Formato de fecha | `AAAA-MM-DDTHH:MM:SSUTC`, con `:` como `%3A` |
| Retraso 🔵 | **~4 días** |
| Multi-estación 🔵 | **Sí** — es el único endpoint donde el spec lo documenta: "puede introducir varios indicativos separados por comas" |

Ejemplo oficial (FAQ 4.8):

```
/api/valores/climatologicos/diarios/datos/fechaini/2024-03-01T00%3A00%3A00UTC/fechafin/2024-03-10T00%3A00%3A00UTC/estacion/8178D,8050X
```

### Estructura 🟢

Raíz `list`, un objeto por día y estación:

```
fecha, indicativo, nombre, provincia, altitud, tmed, prec, tmin, horatmin, …
```

Verificado con `1495` y rango 2026-08-15 → 2026-08-18 (`list[4]`, 2,4 KB).

### Volumen 🟢

| Consulta | Tamaño |
|---|---|
| 1 estación × 4 días | 2,4 KB |
| **Todas las estaciones × 1 día** | **402,8 KB** |

🟡 Extrapolando: un mes de todas las estaciones son ~12 MB, un año ~145 MB. **Sin paginación**
([`LIMITACIONES.md`](LIMITACIONES.md#qué-no-ofrece-aemet-opendata)): hay que trocear por rangos
cortos.

🔴 Sin verificar el límite máximo de rango que acepta.

---

## Climatologías mensuales y anuales 🟢

```
GET /api/valores/climatologicos/mensualesanuales/datos/anioini/{anioIniStr}/aniofin/{anioFinStr}/estacion/{idema}
```

Años en formato `AAAA`. ⚠️ Aquí `{idema}` **no** documenta multi-valor
([`ERRATAS.md` D1](ERRATAS.md#d1-varios-endpoints-aceptan-múltiples-valores-separados-por-comas-)).

🟢 Verificado con `1495` y `anioini/2024/aniofin/2024`: **`list[13]`** (12 meses + el resumen anual),
10,6 KB. Claves con la misma nomenclatura comprimida que las normales:
`indicativo, p_max, n_cub, hr, n_gra, n_fog, inso, q_max, nw_55, …`

🔴 Sin verificar el rango máximo de años que acepta.

---

## Valores extremos 🟢

```
GET /api/valores/climatologicos/valoresextremos/parametro/{parametro}/estacion/{idema}
```

| `{parametro}` | Variable |
|---|---|
| `P` | Precipitación |
| `T` | Temperatura |
| `V` | Viento |

⚠️ **Mayúsculas.** 🔴 Sin verificar si acepta minúsculas.

🟢 Verificado con `T` y estación `1495`: **raíz `dict` (no `list`)** con 24 claves, 1,8 KB.
Periodicidad de los metadatos: "1 vez al día".

```
indicativo, nombre, ubicacion, codigo, temMin, diaMin, anioMin, mesMin, temMax, …
```

⚠️ **Es uno de los dos únicos productos con raíz `dict`** en toda la API (el otro es UVI). Código que
haga `$data[0]` falla aquí. Ver
[`ERRATAS.md` E12](ERRATAS.md#e12-raíz-dict-en-vez-de-list-en-algunos-productos-).

---

## Inventario de estaciones 🟢

```
GET /api/valores/climatologicos/inventarioestaciones/todasestaciones
GET /api/valores/climatologicos/inventarioestaciones/estaciones/{estaciones}
```

`{estaciones}`: lista de indicativos separados por comas (`id1,id2,id3,...,idn`) 🔵.

🟡 Es la vía práctica para **descubrir indicativos de estación**, necesarios en casi todos los
endpoints climatológicos y en observación. Descargar **una vez** y persistir.

⚠️ Contiene estaciones **climatológicas**; 🟡 no necesariamente coincide con las de observación en
tiempo real. 🔴 Sin verificar el solapamiento.

### Verificado 🟢

| Consulta | Resultado |
|---|---|
| `todasestaciones` | **167,1 KB** |
| `estaciones/1495,8019` | `list[2]` — **el multi-valor por comas FUNCIONA** ✅ |

Claves: `latitud, provincia, altitud, indicativo, nombre, indsinop, longitud`

⚠️ **`latitud` viene en formato empaquetado**: `"394924N"` = 39°49'24" N. No es decimal ni tiene
separadores. Requiere parseo específico — y **es un formato distinto** del que usa el maestro de
municipios o la observación
([`ERRATAS.md` E14](ERRATAS.md#e14-coordenadas-en-tres-formatos-distintos-)).

`indsinop` es el indicativo SYNOP de la OMM, útil para cruzar con los mensajes de observación.

Este es el endpoint que usan todos los ejemplos oficiales de AEMET
(`src/web-texto/ejemProgramas.txt`).

---

## Balance hídrico 🟢 ⚠️ sin flujo de dos saltos

```
GET /api/productos/climatologicos/balancehidrico/{anio}/{decena}
```

| Parámetro | Formato |
|---|---|
| `{anio}` | `AAAA` |
| `{decena}` | `01` (primera) a `36` (última) 🔵 |

🟡 36 decenas = 3 por mes (días 1-10, 11-20, 21-fin).

> [!CAUTION]
> 🟢 **Devuelve el PDF directamente, sin sobre.** Verificado con `2026/20`:
> ```http
> HTTP/1.1 200 OK
> Content-Type: application/pdf;charset=ISO-8859-15
> Content-Length: 4568812
> Remaining-request-endpoint: 13
> aemet_mensaje: Se ha encontrado 1 balance hídrico nacional
> aemet_estado: 200 OK
> aemet_num: 1
> ```
> Tres cosas notables: **no hay flujo de dos saltos**, aparecen **cabeceras propietarias `aemet_*`**
> indocumentadas, y su cubo de cuota es de **~15** en vez de 40 (por ser un producto pesado).

---

## Resumen climatológico nacional 🟢

```
GET /api/productos/climatologicos/resumenclimatologico/nacional/{anio}/{mes}
```

`{anio}` en `AAAA`, `{mes}` en `mm` **o sin cero** (`1` funciona igual que `01`) 🟢.

🟢 Verificado: devuelve el **PDF directamente**, igual que `balancehidrico`:

| Petición | Resultado |
|---|---|
| `2025/12` | ✅ PDF de **4,18 MB** |
| `2024/1` | ✅ PDF de **7,18 MB** |
| `2026/7` | ⚠️ **`HTTP 200` con cuerpo VACÍO** (0 bytes) — el mes aún no está publicado |

> [!CAUTION]
> ⚠️ **Un periodo no publicado devuelve `200` con 0 bytes**, con
> `Content-Type: text/plain; charset=UTF-8` y `Transfer-Encoding: chunked` — ni PDF, ni sobre, ni
> error. **Hay que comprobar que el cuerpo no está vacío y que empieza por `%PDF-`.**
>
> 🟡 Es exactamente la misma respuesta que da la API cuando falta la `api_key`, así que un cuerpo
> vacío aquí es ambiguo: puede ser "mes no publicado" o "clave mal configurada".

> 🔵 El "Avance mensual climatológico" aparece en el acceso general de la web
> (`src/web-texto/productosAEMET.txt`) marcado como "Disponible en web" pero **no tiene endpoint**
> en la API.

---

## Capas SHAPE de estaciones 🟢

```
GET /api/productos/climatologicos/capasshape/{tipoestacion}
```

| `{tipoestacion}` | Contenido |
|---|---|
| `completas` | Estaciones climatológicas completas |
| `termometricas` | Solo termométricas |
| `pluviometricas` | Solo pluviométricas |
| `automaticas` | Automáticas |

🟢 Verificado con `completas`: **ZIP de 11,7 KB**, `Content-Type: application/zip` (sin charset —
uno de los pocos que no lo declara). 🔴 Contenido interno sin listar.

Metadatos 🟢: `formato: shapefile` · `periodicidad: anual`.

### Contenido del ZIP 🟢

Verificado con `completas`: **shapefile completo de ESRI**, 7 ficheros.

| Fichero | Tamaño | Qué es |
|---|---|---|
| `Estaciones_Completas.shp` | 3,2 KB | Geometrías (puntos) |
| `Estaciones_Completas.dbf` | **153,3 KB** | **Los atributos** — es donde están los datos |
| `Estaciones_Completas.shx` | 988 B | Índice |
| `Estaciones_Completas.prj` | 147 B | Sistema de referencia |
| `Estaciones_Completas.sbn` / `.sbx` | 1,3 KB | Índice espacial (opcional) |
| `Estaciones_Completas.shp.xml` | 10,5 KB | Metadatos ESRI |

🟡 El `.dbf` es 47 veces mayor que el `.shp`: el valor está en los atributos, no en la geometría.
Un `.dbf` se puede leer **sin librería GIS** (es un formato dBase antiguo y simple: cabecera de 32
bytes, definición de campos de 32 bytes cada uno, y registros de longitud fija).

🔴 El `.shp.xml` **no contiene diccionario de atributos** (sin etiquetas `attrlabl`), así que los
nombres de campo del `.dbf` hay que leerlos del propio fichero.

### ⚠️ Producto probablemente prescindible 🟡

**Para qué serviría:** situar las estaciones en un mapa, o encontrar la más cercana a una coordenada.

**Por qué probablemente no hace falta:** el
[inventario de estaciones](#inventario-de-estaciones-) ya devuelve `latitud` y `longitud` de cada una
en JSON (aunque en formato empaquetado `394924N`). Con eso se calcula la distancia sin tocar un
shapefile.

🟡 El shapefile solo aporta si se necesita geometría real (polígonos de cobertura) o integración con
un SIG. **No se va a analizar más** mientras no haya un motivo concreto.

⚠️ Su campo `campos` **no es un array de campos** sino una **cadena con una URL externa**:
`https://www.miteco.gob.es/es/cartografia-y-sig/ide/descargas/otros/default.aspx`

Es decir, AEMET remite al portal cartográfico del **MITECO** para el diccionario de atributos del
shapefile. 🔴 Sin contrastar. Ver
[`ERRATAS.md` E31](ERRATAS.md#e31-el-campo-campos-de-los-metadatos-cambia-de-tipo).

🟡 Útil para situar estaciones en un mapa o buscar la más cercana a una coordenada. Un `.shp`
requiere librería específica; 🟡 para "la estación más cercana" probablemente salga más barato usar
las coordenadas del inventario.

---

## 📖 Diccionarios de campos

Todos tomados de la URL `metadatos` de cada endpoint (campo `campos[]`), no inferidos.
Verificados el 2026-08-26. Ver [`00-fundamentos.md`](00-fundamentos.md#los-metadatos-son-el-diccionario-de-datos-).

### Climatologías diarias — 27 campos 🟢

`formato: application/json` · `periodicidad: 1 vez al día, con un retardo de 4 días`

**Obligatorios:** `fecha` (`AAAA-MM-DD`), `indicativo`, `nombre`, `provincia`, `altitud` (m).

| Campo | Tipo | Significado |
|---|---|---|
| `tmed` | float | Temperatura media diaria |
| `tmin` / `tmax` | float | Temperatura mínima / máxima del día |
| `horatmin` / `horatmax` | string | Hora y minuto de la mínima / máxima |
| `prec` | float | ⚠️ Precipitación diaria **de 07 a 07**, no de medianoche a medianoche |
| `pintmax` / `horapintmax` | float / string | Intensidad máxima de precipitación y su hora |
| `velmedia` | float | Velocidad media del viento |
| `racha` / `horaracha` | float / string | Racha máxima y su hora |
| `dir` | float | Dirección de la racha máxima |
| `sol` | float | Insolación |
| `presmax` / `presmin` | float | Presión máxima / mínima **al nivel de la estación** |
| `horapresmax` / `horapresmin` | string | Su hora, ⚠️ **redondeada a la hora entera más próxima** |
| `hrmedia` / `hrmax` / `hrmin` | float | Humedad relativa media / máxima / mínima |
| `horahrmax` / `horahrmin` | string | Hora de la máxima / mínima |

⚠️ **`prec` va de 07 a 07 UTC.** Sumar precipitaciones diarias asumiendo días naturales desplaza los
totales.

### Climatologías mensuales/anuales — 49 campos 🟢

`formato: application/json` · `periodicidad: 1 vez al día`

⚠️ **`fecha` tiene formato `AAAA-X`** donde `X` va de **1 a 13**: 1-12 son los meses y **13 es el
valor anual**. De ahí que la respuesta traiga `list[13]` para un año.

**Obligatorios:** `fecha`, `indicativo`, `nombre`, `provincia`, `altitud`.

| Campo | Significado |
|---|---|
| `tm_mes` | Temperatura media mensual/anual |
| `tm_max` / `tm_min` | Media de las máximas / de las mínimas |
| `ta_max` / `ta_min` | Máxima / mínima absoluta **y fecha** |
| `ts_min` | Temperatura mínima más alta del periodo |
| `ti_max` | Temperatura máxima más baja del periodo |
| `nt_30` / `nt_00` | Nº de días con máxima ≥ 30 °C / mínima ≤ 0 °C |
| `p_mes` | Precipitación total |
| `p_max` | Precipitación máxima diaria **y fecha** |
| `np_001` / `np_010` / `np_100` / `np_300` | Nº de días con precipitación ≥ 0,1 / 1 / 10 / 30 mm |

🟡 **La nomenclatura de los umbrales es en décimas de mm**: `np_001` = 0,1 mm, `np_010` = 1 mm,
`np_100` = 10 mm, `np_300` = 30 mm. No son "1, 10, 100, 300 mm".

**Los 29 restantes:**

| Campo | Significado |
|---|---|
| `hr` | Humedad relativa media |
| `e` | Tensión de vapor media |
| `n_llu` `n_nie` `n_gra` `n_tor` `n_fog` | Nº de días de lluvia / nieve / granizo / tormenta / niebla |
| `n_des` `n_nub` `n_cub` | Nº de días despejados / nubosos / cubiertos |
| `inso` | Media de la insolación diaria |
| `p_sol` | % medio de insolación diaria frente a la teórica |
| `glo` | Radiación global |
| `evap` | Evaporación total |
| `w_rec` | Recorrido medio diario **(de 07 a 07 UTC)** |
| `w_racha` | **Dirección, velocidad y fecha** de la racha máxima |
| `w_med` | Velocidad media (de las observaciones de **07, 13 y 18 UTC**) |
| `nw_55` `nw_91` | Nº de días con viento ≥ 55 / 91 km/h |
| `q_med` `q_mar` | Presión media al nivel de la estación / del mar |
| `q_max` `q_min` | Presión máxima / mínima absoluta **y fecha** |
| `ts_10` `ts_20` `ts_50` | Temperatura media del suelo a 10 / 20 / 50 cm |
| `nv_0050` `nv_0100` `nv_1000` | Nº de días con visibilidad < 50 m / 50-100 m / 100 m-1 km |

⚠️ Varios campos **no son un número, sino un texto compuesto**: `ta_max`, `ta_min`, `p_max`, `q_max`,
`q_min` incluyen **la fecha** además del valor, y `w_racha` incluye **dirección, velocidad y fecha**.
No se pueden castear a float directamente.

⚠️ Erratas de AEMET en estas descripciones 🟢: `ts_50` dice *"a 20 cm"* (debería ser 50),
`q_min` dice *"presión máxima mínima"*, `q_med` tiene un paréntesis suelto y `fecha` dice
*"del 1 aa 13"*.

### Climatologías normales — 486 campos, con un patrón 🟢

`formato: application/json` · Periodo de referencia **1991-2020**.

No hay que documentar 486 campos: **son 44 variables × 11 estadísticos, más `mes` e `indicativo`.**

#### Los 11 sufijos estadísticos

| Sufijo | Significado |
|---|---|
| `_md` | Media aritmética |
| `_mn` | Mediana |
| `_max` / `_min` | Valor máximo / mínimo |
| `_s` | Desviación típica |
| `_cv` | Coeficiente de variación de Pearson |
| `_n` | Frecuencia absoluta (nº de años con dato disponible) |
| `_q1` `_q2` `_q3` `_q4` | Primer a cuarto **quintil** |

Así, `nw_55_md` es "media aritmética del nº de días con viento ≥ 55 km/h", y `q_max_q3` es "tercer
quintil de la presión máxima absoluta".

#### Las 43 variables (más `ta_max`, ver la errata)

| Prefijo | Variable |
|---|---|
| `e_*` | Tensión de vapor media |
| `evap_*` | Evaporación total (décimas de mm) |
| `glo_*` | Radiación global |
| `hr_*` | Humedad relativa media |
| `inso_*` | Media de la insolación diaria |
| `p_sol_*` | % medio de insolación diaria frente a la teórica |
| `p_mes_*` | Precipitación total |
| `p_max_*` | Precipitación máxima diaria y fecha |
| `np_001_*` `np_010_*` `np_100_*` `np_300_*` | Nº de días con precipitación ≥ 0,1 / 1 / 10 / 30 mm |
| `n_llu_*` `n_nie_*` `n_gra_*` `n_tor_*` `n_fog_*` | Nº de días de lluvia / nieve / granizo / tormenta / niebla |
| `n_cub_*` `n_nub_*` | Nº de días cubiertos / nubosos |
| `nv_0050_*` `nv_0100_*` `nv_1000_*` | Nº de días con visibilidad < 50 m / 50-100 m / 100 m-1 km |
| `nt_30_*` `nt_00_*` | Nº de días con máxima ≥ 30 °C / mínima ≤ 0 °C |
| `nw_55_*` `nw_91_*` | Nº de días con viento ≥ 55 / 91 km/h |
| `tm_mes_*` `tm_max_*` `tm_min_*` | Temperatura media / media de máximas / media de mínimas |
| `ta_max_*` `ta_min_*` | Máxima / mínima absoluta y fecha |
| `ti_max_*` `ti_min_*` | Máxima más baja / mínima más alta |
| `ts_min_*` | Mínima más alta |
| `ts_10_*` `ts_20_*` `ts_50_*` | Temperatura del suelo a 10 / 20 / 50 cm de profundidad |
| `q_med_*` `q_mar_*` | Presión media al nivel de la estación / del mar |
| `q_max_*` `q_min_*` | Presión máxima / mínima absoluta y fecha |
| `w_med_*` | Velocidad media (de las observaciones de 07, 13 y 18 UTC) |
| `w_racha_*` | Velocidad de la racha máxima |

⚠️ **`mes` vale 1-12 o `13` para el valor anual**, igual que en las mensuales.

⚠️ Tres erratas en las descripciones de AEMET 🟢:
[`ta_max_*` está duplicado y descrito como mínima](ERRATAS.md#e26-en-las-normales-ta_max-está-duplicado-y-con-la-descripción-equivocada),
`q_min_*` dice "presión **máxima mínima**", y `ts_min_*` repite la descripción de `ti_min_*`.

### Valores extremos — 24 campos 🟢

`formato: json/xml` · `periodicidad: 1 vez al día` · Raíz **`dict`**, no `list`.

**Obligatorios (los 24 lo son):** `indicativo`, `nombre`, `ubicacion`, `codigo` (código de la
variable), y luego 5 grupos de estadísticos:

| Grupo | Campos |
|---|---|
| Mínima absoluta | `temMin`, `diaMin`, `anioMin`, `mesMin` |
| Máxima absoluta | `temMax`, `diaMax`, `anioMax`, `mesMax` |
| Media mensual más baja | `temMedBaja`, `anioMedBaja`, `mesMedBaja` |
| Media mensual más alta | `temMedAlta`, `anioMedAlta`, `mesMedAlta` |
| Media de mínimas más baja | `temMedMin`, `anioMedMin`, `mesMedMin` |
| Media de máximas más alta | `temMedMax`, `anioMedMax`, `mesMedMax` |

> [!IMPORTANT]
> ⚠️ **Los campos `array de string` contienen 13 elementos**: los 12 meses **más el valor anual** en
> la última posición. Lo dice la propia descripción de AEMET. Tratarlos como 12 pierde el dato anual;
> iterarlos como meses cuenta el anual como un mes más.
>
> Los campos `mesMin`, `mesMax`, `mesMedBaja`… (sin `array`) son cadenas simples: el mes en que se dio
> el extremo anual.

### Inventario de estaciones — 7 campos 🟢

`formato: application/json` · Los 7 obligatorios.

| Campo | Significado |
|---|---|
| `indicativo` | Indicativo climatológico de la estación — **es el `idema`** que piden los demás endpoints |
| `indsinop` | Indicativo **sinóptico** de la OMM, para cruzar con los mensajes SYNOP |
| `nombre` | Ubicación de la estación |
| `provincia` | Provincia |
| `altitud` | Altitud (cadena, no número) |
| `latitud` / `longitud` | ⚠️ Formato **empaquetado** `394924N` = 39°49'24" N. Ni decimal ni con separadores |

---

## Pendiente de verificar

**Cobertura: 9 de 10** (queda el detalle interno de varios).

| # | Qué | Prioridad |
|---|---|---|
| 1 | ✅ *Resuelto*: [diccionarios de los 5 productos de valores climatológicos](#-diccionarios-de-campos) | — |
| 2 | Rango máximo aceptado en diarias y mensuales | Alta |
| 3 | ✅ *Resuelto*: `resumenclimatologico` devuelve PDF directo; `capasshape` sí usa el flujo de 2 saltos | — |
| 4 | Atributos del `.dbf` y el diccionario que remite al MITECO | **Aparcado** — producto prescindible, ver arriba |
| 8 | Los 29 campos de mensuales/anuales que no se han volcado (viento, presión, humedad, fenómenos) | Baja — el patrón está claro |
| 5 | Si el inventario climatológico sirve para obtener `idema` de observación | Media |
| 6 | Si `{parametro}` acepta minúsculas | Baja |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
