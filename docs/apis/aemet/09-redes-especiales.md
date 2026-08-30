# 🔬 Redes especiales y Antártida

**5 endpoints.** Redes de medición especializadas (ozono, radiación, contaminación de fondo) y las
estaciones antárticas españolas.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y catálogos de estación) · **metadatos de los 5 endpoints** (diccionarios de campos, posiciones de carácter y códigos de validación) · **verificación en vivo del 2026-08-26** (formatos y codificaciones reales).

---

## Resumen 🟢

**Los 5 verificados.**

| Endpoint | Estado | Formato real | Tamaño | Codificación |
|---|---|---|---|---|
| `GET /api/red/especial/radiacion` | 🟢 | **CSV** (`;` con comillas) | 20,6 KB | ⚠️ **UTF-8** |
| `GET /api/red/especial/ozono` | 🟢 | **CSV** (`;` con comillas) | 285 B | ⚠️ **UTF-8** |
| `GET /api/red/especial/perfilozono/estacion/{estacion}` | 🟢 | Texto tabulado | **612,7 KB** | ISO-8859-15 |
| `GET /api/red/especial/contaminacionfondo/estacion/{nombre_estacion}` | 🟢 | **Texto de ancho fijo (formato FINN)** | 🔴 | ISO-8859-15 |
| `GET /api/antartida/datos/…/estacion/{identificacion}` | 🟢 | JSON | **280,2 KB** (5 días) | ISO-8859-15 |

> [!CAUTION]
> ⚠️ **Radiación y ozono son los únicos productos de la API que llegan en UTF-8 de verdad** 🟢
> (`Content-Type: text/plain;charset=UTF-8`). **Convertirlos desde ISO-8859-15 los corrompe**
> (`Estación` → `EstaciÃ³n`). Es la prueba de que hay que **leer el `charset` de la cabecera** y no
> fijarlo a mano. Ver [`00-fundamentos.md`](00-fundamentos.md#pero-no-se-puede-convertir-a-ciegas-).

## ⚠️ Dos rutas que se documentan mal a menudo

Verificado el 2026-08-26 🟢. Estas dos rutas incorrectas circulan en documentación de terceros y
**devuelven 404 con HTML de Tomcat**:

| Ruta incorrecta | Resultado 🟢 | Ruta correcta 🟢 |
|---|---|---|
| `/api/red/especial/radiacionsolar` | **404** | `/api/red/especial/radiacion` |
| `/api/red/especial/contaminacionfondo` (sin estación) | **404** | `/api/red/especial/contaminacionfondo/estacion/{nombre_estacion}` |

La segunda es un buen ejemplo de por qué hay que verificar: es el mismo endpoint, pero **la estación
no es opcional**.

---

## Radiación solar 🟢

```
GET /api/red/especial/radiacion
```

🔵 "Datos de radiación global, directa o difusa. Tiempo actual." Sin parámetros: devuelve el conjunto
de la red.

🟢 Verificado de punta a punta. **CSV con `;` y campos entrecomillados, en UTF-8**, 20,6 KB.
`formato: txt/csv` · `periodicidad: Cada 24h`. TTL 🟡: 12 h.

> [!IMPORTANT]
> ⚠️ **Las horas son HORA SOLAR VERDADERA**, no UTC ni hora local. Lo dice la descripción oficial.
> Convertirlas asumiendo UTC desplaza toda la serie.

### Diccionario — 9 campos 🟢

| Campo | Significado |
|---|---|
| `Estación` | Nombre de la estación |
| `Indicativo` | Indicativo climatológico |
| `Tipo` | Variable medida: **Global / Difusa / Directa / UV Eritemática / Infrarroja** |
| `Hora Solar Verdadera GL/DF/DT` | Radiación **horaria** acumulada entre (hora−1) y (hora), **de las 5 a las 20** HSV |
| `Suma GL/DF/DT` | Radiación diaria acumulada (global / difusa / directa) |
| `Hora Solar Verdadera UVER` | Radiación **semihoraria** acumulada (bloques de 30 min) |
| `Suma UVER` | Diaria acumulada de UV eritemática |
| `Hora Solar Verdadera INFRARROJA` | Radiación horaria acumulada, **de la 1 a las 24** HSV |
| `Suma IR` | Diaria acumulada de infrarroja |

🟡 De ahí las columnas numéricas del CSV (`"5"`…`"20"`): son las **horas solares verdaderas**. La
columna `Tipo` indica a qué variable corresponde cada fila, y el rango de horas **cambia según la
variable** (5-20 para global/difusa/directa, 1-24 para infrarroja, medias horas para UVER).

```
"RADIACION SOLAR"
"25-08-26"
"Estación";"Indicativo";"Tipo";"5";"6";"7";"8";…;"20";"SUMA";"Ti…"
```

Estructura 🟡: dos líneas de cabecera (título y fecha `DD-MM-AA`), luego una fila de encabezados
donde **las columnas numéricas son las horas del día** (5 a 20), más totales.

⚠️ La fecha de la segunda línea usa formato **`DD-MM-AA`** (`"25-08-26"`), distinto de todos los
demás productos.

---

## Contaminación de fondo 🟢

```
GET /api/red/especial/contaminacionfondo/estacion/{nombre_estacion}
```

Red **EMEP** de contaminación atmosférica de fondo. 🟢 Verificado con `09` (Campisábalos): el paso 1
responde `200` con sobre correcto.

⚠️ 🔴 En un intento del 2026-08-26 la URL `datos` cerró la conexión sin responder
(`Remote end closed connection without response`). 🟡 Parece transitorio; el paso 1 y los metadatos
funcionan con normalidad.

### Formato: texto de ancho fijo (FINN), no CSV ni JSON 🟢

Los metadatos declaran `formato: ascii/txt` y `periodicidad: Cada 1h`. El contenido son **ficheros
diarios con datos diezminutales** en formato **FINN** (propio del Ministerio de Medio Ambiente):

```
18-01-2023 00:10 SO2(001): +00000.42 ug/m3 CV: V FC: 2.66 NO(007): +00000.20 ug/m3 CV: V FC: 1.248
NO2(008): +00000.24 ug/m3 CV: V FC: 1.91 O3(014): +00089.69 ug/m3 CV: V FC: 1.99
VEL(081): +00002.72 m/s CV: V FC: 1 DIR(082): +00275.95 GRA CV: V FC: 1 …
```

**Se parsea por posiciones de carácter, no por separador.** Los metadatos traen el rango exacto de
cada campo en `posicion_txt`:

| Campo | `posicion_txt` | Unidad |
|---|---|---|
| `Fecha` | `1-10` | `dd-mm-aaaa` |
| `Hora` | `12-16` | `hh:mm` (**UTC**) |
| `SO2` | `28-36` | µg/m³ |
| `Codigo_validacion_SO2` | `48` | 1 carácter |
| `NO` | `68-76` | µg/m³ |
| `Codigo_validacion_NO` | `88` | 1 carácter |
| `NO2` | `110-118` | µg/m³ |
| `Codigo_validacion_NO2` | `130` | 1 carácter |

26 campos en total: los químicos **O3, SO2, NO, NO2 y PM10**, más meteorología (temperatura, presión,
humedad, viento en dirección y velocidad, radiación global y precipitación), cada uno con su código
de validación.

> [!IMPORTANT]
> ### Hay que respetar los códigos de validación 🟢
>
> Cada medida lleva un `Codigo_validacion_*` de un carácter. Según la propia descripción de AEMET:
>
> | Válidos | Significado |
> |---|---|
> | `V` | válido |
> | `O` | corregido |
> | `J` | calma |
>
> | No válidos | Significado |
> |---|---|
> | `C` | perturbado por calibración |
> | `D` | erróneo por fallo técnico |
> | `E` | erróneo por fallo eléctrico |
> | `F` | erróneo por causa desconocida |
> | `M` | perturbado por mantenimiento |
> | `S`, `N` | no válido |
> | `P` | analizador fuera de servicio |
> | `Z` | perturbado por cero |
>
> **Usar una medida sin comprobar su código publica datos que AEMET marca como erróneos.**

⚠️ **Dos erratas en los metadatos de este producto** 🟢:
[la clave `posición_txt` cambia de grafía](ERRATAS.md#e20-la-clave-posición_txt-de-los-metadatos-cambia-de-grafía-)
y [`Codigo_validacion_O3` está duplicado](ERRATAS.md#e21-ids-duplicados-en-el-diccionario-de-campos-)
(el segundo es en realidad el de PM10).

### Estaciones EMEP 🔵 — 13

| Código | Estación | | Código | Estación |
|---|---|---|---|---|
| `01` | San Pablo de los Montes (Toledo) | | `11` | Barcarrota (Badajoz) |
| `05` | Noia (A Coruña) | | `12` | Zarra (Valencia) |
| `06` | Mahón (Illes Balears) | | `13` | Peñausende (Zamora) |
| `07` | Víznar (Granada) | | `14` | Els Torms (Lleida) |
| `08` | Niembro-Llanes (Asturias) | | `16` | O Saviñao (Lugo) |
| `09` | Campisábalos (Guadalajara) | | `17` | Doñana (Huelva) |
| `10` | Cabo de Creus (Girona) | | | |

⚠️ **Los códigos llevan cero a la izquierda y son cadenas.** Y **la secuencia tiene huecos**: no
existen `02`, `03`, `04` ni `15`. No iteres del 1 al 17.

⚠️ El parámetro se llama `nombre_estacion` pero recibe un **código numérico**, no un nombre. Es el
único parámetro de la API con guion bajo.

---

## Contenido total de ozono 🟢

```
GET /api/red/especial/ozono
```

🟢 Verificado: **CSV con `;`, en UTF-8**, solo **285 B**. El producto más pequeño de la API.

```
"CAPA DE OZONO"
"25-08-26"
"Estación";"Indicativo";"OZONO"
"A Coruña";"1387";"339"
```

### Diccionario — 5 campos 🟢

`formato: txt/csv` · `periodicidad: Cada 24 h`

| Campo | Significado |
|---|---|
| *(sin id)* | Literal `CAPA DE OZONO` — línea de título |
| *(sin id)* | Fecha. ⚠️ Los metadatos dicen `dd/mm/yyyy`; **el payload devuelve `dd-mm-aa`** (`"25-08-26"`) |
| `Estación` | Nombre de la estación |
| `Indicativo` | Indicativo climatológico |
| `Ozono` | **Dato medio diario** del contenido total de ozono |

> 🔵 Advertencia literal de AEMET en los metadatos: *"Datos sometidos a controles automáticos de
> calidad en tiempo real, **no puede garantizarse la ausencia de errores**"*.

TTL 🟡: 12-24 h.

---

## Perfiles verticales de ozono 🟢

```
GET /api/red/especial/perfilozono/estacion/{estacion}
```

| `{estacion}` | Ubicación |
|---|---|
| `canarias` | Izaña |
| `peninsula` | Madrid |

⚠️ Solo **2** estaciones, y aquí el parámetro (`estacion`, sin guion bajo) recibe **palabras**, no
códigos numéricos — al contrario que `nombre_estacion` en contaminación de fondo. Nomenclatura
inconsistente dentro del mismo grupo.

🟢 Verificado con `peninsula`: **texto tabulado de 612,7 KB** — el mayor producto de texto de la API.
Es una tabla científica de radiosondeo:

```
Started at      29 July 2026 10:54 UTC
Integrated Ozone [DU]: 262.9866
Residual Ozone   [DU]: 45.84075
Time Pressure   Height  Temperature  RH  …
```

⚠️ **Cabecera en inglés y con formato propio**, no JSON ni CSV. Requiere parseo específico por
posiciones o espacios.

`formato: txt/csv` · `periodicidad: **Cada 7 días**` 🟢

⚠️ **El dato tenía 28 días** (lanzamiento del 29 de julio, medido el 26 de agosto) frente a una
periodicidad declarada de 7 días. **Muestra siempre la fecha de lanzamiento**: puede estar muy
desactualizado.

### Diccionario — 14 campos 🟢

Tres primeras entradas sin `id` (son las líneas de cabecera del fichero): **fecha y hora de
lanzamiento**, **ozono integrado** y **ozono residual de la columna**. Después, una fila por nivel:

| Campo | Significado |
|---|---|
| `Tiempo` | Tiempo desde el lanzamiento del sondeo |
| `Presión` | Presión atmosférica |
| `Altura` | Altura alcanzada por el globo |
| `Temperatura` | Temperatura del aire |
| `Humedad` | Humedad relativa |
| `Temperatura Virtual` | La que tendría el aire seco |
| `Depresión punto de rocío` | Diferencia entre temperatura y punto de rocío |
| `Gradiente vertical de Temperatura` | Variación de temperatura por cada 1.000 m en vertical |
| `Velocidad de ascenso de la ozonosonda` | Velocidad de ascensión |
| `Presión parcial de ozono` | Presión del ozono aislado, sin variación de temperatura |
| `Temperatura interior de la ozonosonda` | Temperatura interna del instrumento |

---

## Datos de la Antártida 🟢

```
GET /api/antartida/datos/fechaini/{fechaIniStr}/fechafin/{fechaFinStr}/estacion/{identificacion}
```

Datos de las bases antárticas españolas, disponibles en el marco del convenio entre la Agencia
Estatal de Investigación y AEMET 🔵.

| `{identificacion}` | Estación |
|---|---|
| `89064` | Estación Meteorológica Juan Carlos I |
| `89064R` | Estación Radiométrica Juan Carlos I |
| `89064RA` | Estación Radiométrica Juan Carlos I (hasta 08/03/2007) |
| `89070` | Estación Meteorológica Gabriel de Castilla |

⚠️ El spec escribe la tercera con **paréntesis duplicado**: `"(hasta 08/03/2007))"`.

Fechas en `AAAA-MM-DDTHH:MM:SSUTC`, con `:` como `%3A`.

🟢 Verificado con `89070` (Gabriel de Castilla) y rango de 5 días de enero de 2026: **JSON de
280,2 KB**.

`formato: application/json` · `periodicidad: **anualmente**` 🟢 — se publica **una vez al año**, así
que los datos recientes no existen. Es un producto de archivo, no de tiempo real.

### Diccionario — 41 campos 🟢

**Identificación:** `nombre`, `identificacion`, `latitud`, `longitud`, `altitud` (m), `srs` (sistema
de referencia espacial), `fecha` (`AAAA-MM-DD`), `hora` (`HH:MM:SS`).

| Grupo | Campos (unidad) |
|---|---|
| Viento | `vel` (m/s), `ddd` (grados), `rec` (hm), `velx` (m/s), `dddx` (grados), `dddstd` (grados) |
| Temperatura del aire | `temp`, `tmx`, `tmn` (°C) |
| Temperatura del suelo | `ts`, `tsb` (subsuelo), `tsmx`, `tsmn`, `ttierra` (°C) |
| Otras temperaturas | `tcielo` (°C) |
| Humedad y presión | `hr` (%), `pres` (hPa) |
| Precipitación y nieve | `lluv` (mm), `alt_nieve` (m) |
| Radiación | `rad_kj_m2` (10 kJ/m²), `rad_w_m2`, `global`, `difusa`, `directa`, `neta`, `uvab`, `ir_solar` (W/m²), `uvb` (mW/m²), `par` (µmol/m²) |
| Índices | `uvi` (adimensional), `albedo` (tanto por 1) |
| Insolación | `ins` (horas) |
| **Calidad** | **`qdato`** — binario: **`1` = Malo, `0` = Bueno** |

> [!IMPORTANT]
> ⚠️ **`qdato` marca la calidad del registro y la lógica está invertida respecto a lo intuitivo:
> `1` es MALO y `0` es BUENO.** Filtrar por `qdato == 1` esperando quedarte con los buenos te deja
> exactamente con los defectuosos.

⚠️ **280 KB por 5 días de una estación** es mucho: son datos de alta frecuencia. Un rango de un mes
rondaría los 1,7 MB 🟡. Trocear.

🟡 `89064RA` es una estación **histórica** (hasta 2007): pedirle fechas recientes previsiblemente
devolverá vacío. 🔴 Sin verificar.

🟡 Producto de nicho: solo tiene sentido para investigación o divulgación polar.

---

## Nota común: el diccionario de campos está en los metadatos

Ninguno de estos cinco productos documenta sus campos en la especificación. Como en el resto de la
API, la **URL `metadatos`** del sobre trae el diccionario con unidades. Consultarla **una vez** al
documentar cada producto y volcarla aquí. 🔴 Pendiente en los cinco.

---

## Pendiente de verificar

**Cobertura: 5 de 5** (con el paso 2 de contaminación de fondo fallido).

| # | Qué | Prioridad |
|---|---|---|
| 1 | Reintentar el paso 2 de contaminación de fondo (falló la conexión una vez) | Media |
| 2 | ✅ *Resuelto*: diccionarios de los 5 productos volcados desde los metadatos | — |
| 3 | Periodicidad de ozono y perfil de ozono | Media |
| 4 | Estructura completa del perfil de ozono (612 KB tabulados) | Media si se usa |
| 5 | Si `89064RA` devuelve algo con fechas actuales | Baja |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
