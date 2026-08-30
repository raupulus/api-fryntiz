# 🏘️ Predicciones por municipio y maestro de municipios

**6 endpoints.** Es el grupo más útil para mostrar "el tiempo" de una localidad en el frontal.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md) · [`LIMITACIONES.md`](LIMITACIONES.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y parámetro `municipio`) · **metadatos de los endpoints** (los 5 campos que documentan) · `src/catalogos/diccionario_municipios_INE.xlsx` y `src/catalogos/Playas_codigos.csv` (códigos de municipio) · **verificación en vivo del 2026-08-26** (estructura del payload, el bloque `prediccion` que los metadatos no documentan, y los agregados `gzip`).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET /api/prediccion/especifica/municipio/diaria/{municipio}` | 🟢 | JSON `list[1]` |
| `GET /api/prediccion/especifica/municipio/horaria/{municipio}` | 🟢 | JSON `list[1]` |
| `GET /api/prediccion/especifica/municipio/diaria/todos` | 🟢 | **`gzip` → `tar` → 8.124 JSON**, 2,2 MB |
| `GET /api/prediccion/especifica/municipio/horaria/todos` | 🟢 | **`gzip` → `tar` → 8.124 JSON**, 6,5 MB |
| `GET /api/maestro/municipio/{municipio}` | 🟢 | JSON `list[1]` |
| `GET /api/maestro/municipios` | 🟢 | JSON, **3,0 MB** |

> [!WARNING]
> **Dos formatos de código de municipio en el mismo grupo** 🟢:
> el maestro exige `id36057` (con prefijo `id`), la predicción específica exige `36057` (desnudo).
> Usar el formato equivocado devuelve `HTTP 200` con `"estado": 404`.
> Ver [`ERRATAS.md` C5](ERRATAS.md#c5-dos-formatos-distintos-de-código-de-municipio-en-la-misma-api-).

---

## Predicción diaria por municipio 🟢

```
GET /api/prediccion/especifica/municipio/diaria/{municipio}
```

| | |
|---|---|
| `{municipio}` | Código INE de 5 dígitos, **sin** prefijo. Ej.: `36057` (Vigo), `28079` (Madrid) |
| Periodicidad 🟢 | "Cuatro veces al día que afectan a todas las variables, excepto a las temperaturas máxima y mínima, que pueden actualizarse más a menudo" |
| Tamaño 🟢 | ~13,6 KB |
| RSS 🔵 | Sí — canal "Predicción municipio" ([`11-rss-y-sincronizacion.md`](11-rss-y-sincronizacion.md)) |
| TTL 🟡 sugerido | 3 h |

Verificado con `28079` y `36057`.

### ⚠️ Los metadatos no documentan la predicción 🟢

`formato: json/xml` · `periodicidad: Cuatro veces al día…` · pero **solo declaran 5 campos útiles**
(más dos entradas vacías con `id: null`):

| Campo | Tipo | Significado |
|---|---|---|
| `id` | string | Indicativo del municipio |
| `nombre` | string | Nombre del municipio |
| `provincia` | string | Provincia a la que pertenece |
| `elaborado` | `dataTime` | Fecha de elaboración |
| `version` | float en diaria, string en horaria ⚠️ | Versión |

**No documentan nada del bloque `prediccion`**: ni `estadoCielo`, ni `vientoAndRachaMax`, ni los
periodos. Todo lo que sigue se ha obtenido **observando el payload real**.

🔵 Descripción oficial del producto: *"Predicciones en municipios de España. Se generan de forma
automática mediante el tratamiento estadístico de los resultados de modelos numéricos de
predicción"*. Y de la horaria: *"presenta la información de hora en hora hasta 48 horas"* — aunque
🟢 se observaron 3 días.

⚠️ El tipo de `version` **cambia entre los dos endpoints**: `float` en la diaria, `string` en la
horaria. Los metadatos lo declaran así.

### Estructura 🟢

Raíz: `list` de **un** elemento.

```
[0]
├── origen: { productor, web, enlace, language, copyright, notaLegal }
├── elaborado: "2026-08-26T07:09:13"   ← comprobar frescura AQUÍ
├── nombre: "Vigo"
├── provincia: "Pontevedra"
├── prediccion.dia[]: un objeto por día
└── id: "36057"
    version: "1.0"
```

`origen.copyright` y `origen.notaLegal` traen ya el texto de atribución obligatorio: conviene
propagarlo en vez de escribirlo a mano ([`12-uso-legal-y-atribucion.md`](12-uso-legal-y-atribucion.md)).

---

## Predicción horaria por municipio 🟢

```
GET /api/prediccion/especifica/municipio/horaria/{municipio}
```

| | |
|---|---|
| `{municipio}` | Código INE de 5 dígitos, sin prefijo |
| Periodicidad 🟢 | "Cuatro veces al día" |
| Tamaño 🟢 | ~30,0 KB (el doble que la diaria) |
| Cobertura | 🔵 el spec dice "hora en hora hasta 48 horas" · 🟢 medido: `prediccion.dia[]` trae **3 días** |
| TTL 🟡 sugerido | 3 h |

Verificado con `36057`.

### Estructura 🟢

```
[0]
├── origen, elaborado, nombre, provincia, id, version   ← igual que la diaria
└── prediccion.dia[3]
    ├── fecha: "2026-08-26T00:00:00"
    ├── orto: "07:53"      ← amanecer
    ├── ocaso: "21:18"     ← atardecer
    ├── estadoCielo[22]:        { value: "43n", periodo: "02", descripcion: "Intervalos nubosos con lluvia escasa" }
    ├── precipitacion[22]:      { value: "0.1", periodo: "02" }
    ├── nieve[22]:              { value: "0",   periodo: "02" }
    ├── temperatura[21]:        { value: "18",  periodo: "03" }
    ├── sensTermica[21]:        { value: "18",  periodo: "03" }
    ├── humedadRelativa[21]:    { value: "87",  periodo: "03" }
    ├── vientoAndRachaMax[42]:  { direccion: [...], velocidad: [...], periodo: "03" }
    ├── probPrecipitacion[4]:   { value: "80",  periodo: "0208" }   ← periodo = TRAMO
    ├── probTormenta[4]:        { value: "15",  periodo: "0208" }
    └── probNieve[4]:           { value: "0",   periodo: "0208" }
```

### Detalles que importan 🟢

- **`periodo` tiene dos semánticas distintas en el mismo objeto.** En las series horarias es una
  hora (`"02"` = 02:00). En las series de probabilidad es un **tramo** (`"0208"` = de 02:00 a
  08:00). No se pueden tratar igual.
- **Los valores numéricos vienen como cadenas** (`"18"`, `"0.1"`), no como números.
- **Las longitudes de los arrays no coinciden** entre variables (22, 21, 42, 4) y no siempre cubren
  las 24 horas. **No asumas 24 elementos ni los indexes por posición**: usa el campo `periodo`.
- ✅ **`vientoAndRachaMax` resuelto** 🟢: 42 entradas = 21 horas × **2 registros de forma distinta**,
  que se distinguen por sus claves:

  ```json
  {"direccion": ["S"], "velocidad": ["9"], "periodo": "03"}   ← viento
  {"value": "19", "periodo": "03"}                            ← racha máxima
  ```

  Van **alternados** y comparten `periodo`. Se identifican por la presencia de `direccion`/`velocidad`
  frente a `value`. ⚠️ `direccion` y `velocidad` son **arrays de un elemento**, no cadenas.
- `estadoCielo.value` es un **código** (`"43n"`, con sufijo `n` de nocturno) que AEMET usa para
  elegir el icono. ✅ **Resuelto**: la tabla completa de los 35 códigos y su mapeo a iconos está en
  [`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md). El propio payload trae `descripcion`
  junto a `value`, así que el texto siempre está disponible sin depender de la tabla.

---

## Predicción de todos los municipios 🟢

```
GET /api/prediccion/especifica/municipio/diaria/todos
GET /api/prediccion/especifica/municipio/horaria/todos
```

Endpoints **nuevos**: anunciados el 10/07/2026 y disponibles desde el 27/07/2026 🔵.
Verificados el 2026-08-26.

> [!CAUTION]
> **No devuelven JSON: devuelven un `gzip` que contiene un `tar` con 8.124 ficheros JSON**, uno por
> municipio. Y **su estructura interna es distinta** a la del endpoint individual (ver más abajo).

### Lo medido 🟢

| | diaria/todos | horaria/todos |
|---|---|---|
| `Content-Type` | `application/octet-stream` | `application/octet-stream` |
| **Descarga** | **2,2 MB** | **6,5 MB** |
| **Descomprimido** | **73,9 MB** | **142,9 MB** |
| Ratio de compresión | 32× | 21× |
| Nombre interno (cabecera gzip `FNAME`) | `<epoch>_municipios_7d_json.tar` | `<epoch>_municipios_h_json.tar` |
| Ficheros en el `tar` | **8.124** | **8.124** |
| Patrón de nombre | `localidad_NNNNN.json` | `localidad_h_NNNNN.json` |
| Tamaño por fichero | 7,0–7,7 KB | 15,6–16,4 KB |
| Rango de códigos | `01001` … `52001`, los 8.124 únicos | ídem |

⚠️ **Discrepancia de conteo** 🟢: el agregado trae **8.124** ficheros y `maestro/municipios` devuelve
**8.122** municipios. Sobran dos en el agregado. 🔴 Sin identificar cuáles. Si validas completitud
contando registros, **usa el maestro como referencia**.

🟢 **Es `gzip` de verdad** (magic `1f 8b 08 08`), con la bandera `FNAME` que lleva el nombre original
dentro de la cabecera. Ni el `Content-Type` (`application/octet-stream`) ni la especificación
(`application/json`) lo indican.

⚠️ **Ojo con la relación entre descarga y memoria**: 2,2 MB de transferencia se convierten en
**73,9 MB** al descomprimir, y 6,5 MB en **142,9 MB**. Descomprimir en memoria de golpe es
desaconsejable: conviene ir extrayendo fichero a fichero del `tar`.

### La estructura interna NO es la del endpoint individual

⚠️ 🟢 **Esta es la trampa gorda.** Comparación de `localidad_36057.json` (Vigo) del agregado frente al
endpoint individual:

| | Individual `/municipio/diaria/{municipio}` | Agregado `/municipio/diaria/todos` |
|---|---|---|
| Raíz | `list[1]` 🟢 | **`dict` con una única clave `root`** 🟢 |
| Claves de primer nivel | `origen, elaborado, nombre, provincia, prediccion, id, version` 🟢 | Las mismas **más** `xmlns:xsd`, `xmlns:xsi`, `xsi:noNamespaceSchemaLocation` 🟢 |
| Nombres de los campos de día | `estadoCielo`, `probPrecipitacion`, `sensTermica`… (**camelCase**) 🟡 | `estado_cielo`, `prob_precipitacion`, `sens_termica`… (**snake_case**) 🟢 |
| Días devueltos | 🔴 sin contar | **7** 🟢 (coherente con el `7d` del nombre del tar) |

Los **atributos de espacio de nombres XML** (`xmlns:xsd`, `xsi:noNamespaceSchemaLocation`) y el
envoltorio `root` delatan que el fichero es una **conversión mecánica de XML a JSON**, no un JSON
nativo. El endpoint individual no los trae.

Estructura real de un fichero del agregado 🟢:

```
{ "root": {
    "xmlns:xsd": "…", "xmlns:xsi": "…", "xsi:noNamespaceSchemaLocation": "…",
    "id": "36057", "version": "1.0",
    "origen": { … },
    "elaborado": "2026-08-26T09:21:11",
    "nombre": "Vigo",
    "provincia": "Pontevedra",
    "prediccion": { "dia": [ 7 elementos ] }
} }
```

Campos de cada día en el agregado 🟢: `fecha`, `prob_precipitacion`, `cota_nieve_prov`,
`estado_cielo`, `viento`, `racha_max`, `temperatura`, `sens_termica`, `humedad_relativa`, `uv_max`.

⚠️ El agregado **separa `viento` y `racha_max`** en dos campos, mientras el individual los junta en
`vientoAndRachaMax`. Y añade `cota_nieve_prov` y `uv_max`, que no aparecen en el individual.

#### Los `periodo` del agregado son 7 tramos anidados 🟢

```json
[{"periodo":"00-24"}, {"periodo":"00-12"}, {"periodo":"12-24"},
 {"periodo":"00-06"}, {"periodo":"06-12"}, {"periodo":"12-18"}, {"periodo":"18-24"}]
```

Día completo, mitades y cuartos, **en ese orden fijo**. Los tramos sin dato traen el objeto **sin la
clave del valor** (no con valor nulo): `{"periodo":"00-24"}` a secas. Sumar tramos anidados
**duplica** el dato: hay que elegir una granularidad.

#### `temperatura` no es una lista, es un objeto 🟢

```json
"temperatura": {"maxima":"21","minima":"15",
                "dato":[{"hora":"06","valor":"18"},{"hora":"12","valor":"17"},
                        {"hora":"18","valor":"17"},{"hora":"24","valor":"15"}]}
```

⚠️ **Distinto de todos los demás campos del día**, que sí son listas de tramos. Y `sens_termica` y
`humedad_relativa` siguen este mismo formato de objeto con `maxima`/`minima`/`dato[]`.

#### Otros detalles del agregado 🟢

- El código de estado del cielo se llama **`valor`**, no `value` como en el individual.
- `viento` trae `direccion` como **letra cardinal** (`"S"`, `"SE"`) y `velocidad` en km/h, ambos como
  cadenas, y `null` cuando no hay dato.
- `uv_max` es una **cadena suelta** (`"6"`), no una lista de tramos.

> 🔴 **Limitación de esta comparación:** no se pudo volver a descargar el endpoint **individual
> diaria** para comparar los nombres de campo de día uno a uno (cuota agotada). Los nombres en
> camelCase del individual están verificados en la variante **horaria**, no en la diaria. El
> envoltorio `root` y los atributos `xmlns` **sí** están verificados como diferencia real: el
> individual diaria de Madrid devolvió raíz `list` sin ninguno de los dos.

### ¿Merece la pena usarlos? 🟡

| Escenario | Recomendación |
|---|---|
| Pocas localizaciones (< 40) | **Peticiones individuales.** Más simples y sin descomprimir 74 MB |
| Muchas localizaciones o poblar la base de datos entera | **El agregado.** Consume **1 petición** de cuota en vez de N |
| Necesitas 7 días de predicción diaria | **El agregado** — es el único verificado que los da |

El argumento decisivo no es el ancho de banda, sino la cuota: **el cubo es de 40 peticiones por
plantilla de endpoint**, así que iterar 200 municipios sobre el endpoint individual es imposible,
mientras el agregado los trae todos en una. Ver
[`LIMITACIONES.md`](LIMITACIONES.md#el-cubo-es-por-plantilla-de-endpoint-no-global-).

⚠️ Pero su cubo es **más pequeño**: `Remaining-request-endpoint` marcó **13** y **15** en la primera
llamada, no 39. Son productos pesados y AEMET los limita más.

## ❌ Los endpoints de municipio NO aceptan lotes 🟢

Verificado el 2026-08-26. **Ninguna variante acepta varios municipios separados por comas**, aunque
sí lo haga el endpoint de climatologías diarias con `idema` y el inventario de estaciones.

| Petición | Resultado |
|---|---|
| `/api/maestro/municipio/id36057` (control) | ✅ `estado: 200`, 1 registro (Vigo) |
| `/api/maestro/municipio/id36057,id28079` | ❌ **`estado: 404`** — "No hay datos que satisfagan esos criterios" |
| `/api/maestro/municipio/36057,28079` (sin prefijo `id`) | ❌ **`estado: 404`** |
| `/api/prediccion/especifica/municipio/horaria/36057,28079` | ❌ **`estado: 404`** |
| `/api/prediccion/especifica/municipio/diaria/36057,28079` | 🟡 sin probar (cuota agotada). Previsiblemente igual |

⚠️ Nótese que la ruta **es válida** (no da un 404 de HTTP con HTML, como una ruta inexistente): la
API la acepta y responde con el sobre, pero con `estado: 404`. Es decir, **la lista de códigos se
interpreta como un único identificador inexistente**, no como un lote.

### Consecuencia práctica 🟡

Para varias localizaciones hay dos caminos, y **no hay término medio**:

| Nº de municipios | Estrategia |
|---|---|
| **1 – 15** | Peticiones individuales, espaciadas. Simple y suficiente |
| **más de ~15** | **El agregado `/todos`** — trae los 8.124 en una sola petición |

El límite no lo marca el ancho de banda sino la cuota: **el cubo de
`/municipio/horaria/{municipio}` es de solo 15 peticiones**, no de 40
([`LIMITACIONES.md`](LIMITACIONES.md#el-tamaño-del-cubo-varía-mucho-según-el-endpoint-)). Con 6
localizaciones ya vas por el 40 % del cubo en cada ronda de actualización.

🟡 Para un puñado de ubicaciones fijas que se refrescan varias veces al día, **el agregado sale mejor
de lo que parece**: 1 petición en vez de N, y da 7 días de predicción en vez de los 3 del individual.
El coste es descomprimir 74 MB y quedarse solo con los ficheros que interesan.

---

## Maestro de un municipio 🟢

```
GET /api/maestro/municipio/{municipio}
```

| | |
|---|---|
| `{municipio}` | ⚠️ **`id` + código INE**, ej. `id36057`. **Con el código desnudo devuelve `estado: 404`** 🟢 |
| Tamaño 🟢 | ~350 B |
| TTL 🟡 | Muy largo (semanas). Son datos administrativos, no meteorológicos |

### Respuesta real (Vigo) 🟢

```json
[{
  "latitud": "42º14'22.112988\"",
  "id_old": 36560,
  "url": "vigo-id36057",
  "latitud_dec": 42.23947583,
  "altitud": 19,
  "capital": "Vigo",
  "num_hab": 294997,
  "zona_comarcal": "713601",
  "destacada": 1,
  "nombre": "Vigo",
  "longitud_dec": -8.72637733,
  "id": "id36057",
  "longitud": "-8º43'34.958388\""
}]
```

> ⚠️ **Los metadatos de este endpoint no sirven de nada** 🟢: declaran **1 solo campo**, con
> `id: "string"`, sin descripción y sin tipo; y `periodicidad: null`. Su `descripcion` incluso trae
> una errata (*"información especíca del municipio"*). Todo el diccionario de abajo se ha obtenido
> **observando el payload real**.

### Para qué sirve de verdad 🟢

| Campo | Utilidad |
|---|---|
| `zona_comarcal` | **Enlaza el municipio con su zona de aviso CAP.** Vigo → `713601`, y los XML de avisos se llaman `…AFAZ7136…`. Es la única vía conocida para saber qué avisos aplican a un municipio. Ver [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md) |
| `latitud_dec`, `longitud_dec` | Coordenadas decimales listas para usar (las versiones en grados vienen como texto) |
| `altitud`, `num_hab` | Datos de ficha |
| `id` | Identificador canónico de AEMET, **con** el prefijo `id` |
| `id_old` | ⚠️ Código heredado (Vigo → `36560`) que **no** es el INE. No usarlo |
| `url` | Slug de la web de AEMET (`vigo-id36057`) |
| `destacada` | 🔴 Significado sin confirmar. Probablemente marca capitales / municipios destacados |

---

## Maestro de todos los municipios 🟢

```
GET /api/maestro/municipios
```

🔵 El spec lo recomienda expresamente como forma de obtener los identificadores necesarios para los
demás endpoints.

🟢 Verificado: **3,0 MB**, **8.122 municipios**, con **las 13 claves** del maestro individual —
incluido **`zona_comarcal` en los 8.122 sin excepción** 🟢.

> [!IMPORTANT]
> ✅ **Una sola petición resuelve el mapa municipio → zona de aviso de toda España.**
> Los 8.122 municipios referencian **exactamente las 182 zonas terrestres** del
> [catálogo](14-zonas-de-aviso.md), sin ninguna zona huérfana. Es la vía canónica para poblar la
> relación municipio↔avisos: no hacen falta 8.122 peticiones ni el PDF de 221 páginas que publica
> AEMET con esa misma correspondencia.

Estructura, como `list` de todos los municipios:

```json
[{ "latitud": "40º32'54.450744\"", "id_old": "44004", "url": "ababuj-id44001", … }]
```

⚠️ Nótese que aquí `id_old` es una **cadena** (`"44004"`) mientras en Vigo era un **entero**
(`36560`), y en ningún caso coincide con el código INE. **No usar `id_old`.**
Ver [`ERRATAS.md` E15](ERRATAS.md#e15-id_old-no-guarda-relación-con-el-código-ine-).

**Es la fuente preferible al diccionario del INE** que referencia el spec: es la lista que AEMET
reconoce, con sus propios identificadores y con `zona_comarcal`.

🟡 Descargarlo **una vez** y persistirlo. No es un dato que cambie.

---

## Cómo obtener el código de un municipio

1. **`GET /api/maestro/municipios`** (🔴 sin verificar) — la vía canónica.
2. **Diccionario del INE** — `src/catalogos/diccionario_municipios_INE.xlsx`. Es lo que referencia el
   spec, pero es de un tercero.
3. **`src/catalogos/Playas_codigos.csv`** 🟢 — trae `ID_MUNICIPIO` y `NOMBRE_MUNICIPIO` de los
   municipios costeros. Atajo útil si la localidad está en la costa. Ojo: **ISO-8859**, separador `;`.

Recuerda: los códigos llevan **ceros a la izquierda** y son cadenas.

---

## Pendiente de verificar

| # | Qué | Por qué importa |
|---|---|---|
| 1 | ✅ *Resuelto*: volumen y estructura de los dos agregados | — |
| 6 | Comparar campo a campo el día del **individual diaria** con el del agregado (pendiente por cuota) | Media |
| 7 | Confirmar que `diaria` tampoco acepta lotes (verificado en `horaria` y en el maestro) | Baja |
| 2 | ✅ *Resuelto*: tabla de `estadoCielo` en [`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md) (12 de 35 descripciones verificadas) | — |
| 3 | Estructura de `vientoAndRachaMax` (42 entradas / 21 horas) | Para separar viento de racha |
| 4 | Significado de `destacada` | Menor |
| 5 | Cobertura real de la horaria individual (spec dice 48 h, se observaron 3 días; el agregado da 7 en la diaria) | Afecta a qué se puede mostrar |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
