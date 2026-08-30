# ⚠️ Erratas y trampas de AEMET OpenData

> [!CAUTION]
> **Lectura obligatoria antes de implementar o modificar cualquier endpoint de AEMET.**
> Este archivo agrupa todo lo que la documentación oficial dice mal, dice a medias o
> directamente contradice lo que hace la API. Varias de estas erratas **rompen una
> integración en silencio**, sin lanzar ningún error.

- **Fecha de la última verificación en vivo:** 2026-08-26
- **Fuente verificada:** peticiones reales con clave válida (**64 de los 64 endpoints, 100 %**)
- **Especificación auditada:** `src/especificacion/AEMET_OpenData_specification.json` (OpenAPI 3.0.1, `info.version` 2.0)

Leyenda: 🟢 verificado con petición real · 🔵 afirmado por AEMET sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (auditoría completa de sus 64 rutas, 39 parámetros y 5 esquemas) · **URLs `metadatos`** de 27 productos (erratas de los diccionarios) · **verificación en vivo del 2026-08-26** con clave válida (**los 64 endpoints**) · y las desviaciones detectadas al contrastar documentación de terceros (ver [`DOCUMENTACION-TERCEROS.md`](DOCUMENTACION-TERCEROS.md)).

---

## 🔴 Bloque A — Erratas que rompen la integración sin avisar

Estas cuatro son las peligrosas: no producen excepciones, producen datos vacíos o falsos.

### A1. La API responde en ISO-8859-15, no en UTF-8 🟢

El spec no menciona la codificación. La realidad:

| Salto / producto | `Content-Type` real |
|---|---|
| Paso 1 (envelope) | `application/json;charset=ISO-8859-15` |
| Paso 2, mayoría de productos | `text/plain;charset=ISO-8859-15` |
| **Ozono total** 🟢 | `text/plain;charset=**UTF-8**` |
| **Radiación solar** 🟢 | `text/plain;charset=**UTF-8**` |
| **Resumen climatológico** 🟢 | `text/plain; charset=**UTF-8**` |
| Cuerpo del 429 🟢 | `text/plain; charset=**UTF-8**` |
| XML dentro del `tar` de avisos 🟢 | `UTF-8` (declarado en el propio XML) |
| Imágenes GIF 🟢 | `image/gif;**charset=ISO-8859-15**` ← un charset en un binario |
| Imágenes PNG 🟢 | `image/png` (sin charset) |
| ZIP de capas SHAPE 🟢 | `application/zip` (sin charset) |

> [!CAUTION]
> **No se puede convertir a ciegas desde ISO-8859-15: hay productos que ya vienen en UTF-8.**
> Aplicar la conversión sobre ellos los corrompe (`Estación` → `EstaciÃ³n`).
> **Hay que leer el `charset` de la cabecera `Content-Type` y respetarlo.** Y no aplicar ninguna
> conversión de texto a los binarios (GIF, PNG, ZIP, `tar`), aunque declaren charset.

**Por qué rompe:** `json_decode()` de PHP **exige UTF-8**. Con bytes ISO-8859-15 devuelve `null`
sin lanzar excepción. Es decir, `$response->json()` de Laravel devuelve `null` y parece que
"no hay datos", cuando en realidad la petición fue perfecta.

Verificado: 8 de 10 payloads lanzan `UnicodeDecodeError` al decodificar como UTF-8, en el byte
`0xed` (la `í` de "Meteorología").

**Hay que convertir la codificación antes de parsear.** Detalle en
[`00-fundamentos.md`](00-fundamentos.md#codificación-leer-el-charset-de-la-cabecera).

### A2. Trampa derivada: algunos endpoints *parecen* UTF-8 🟢

Dos de los productos verificados **sí** decodifican como UTF-8 correctamente:
`/api/observacion/convencional/datos/estacion/{idema}` y
`/api/valores/climatologicos/normales/estacion/{idema}`.

No es que estén en UTF-8: es que su contenido es **numérico y sin acentos**, así que los bytes
coinciden con ASCII. Si pruebas la integración solo con estos, concluyes erróneamente que la API
es UTF-8 y el fallo aparece más tarde, con otro endpoint, en producción.

**Nunca deduzcas la codificación de un solo endpoint.**

### A2-bis. ⚠️ El atajo `JSON_INVALID_UTF8_SUBSTITUTE` "funciona" pero pierde los acentos 🟢

Es el atajo que se encuentra en integraciones que llevan años en marcha:

```php
json_decode($response, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
```

Con esa bandera `json_decode` **no devuelve `null`**: sustituye cada secuencia UTF-8 inválida por el
carácter de reemplazo `U+FFFD` (`�`). Es decir, **parsea correctamente y devuelve datos**… con todos
los acentos y las `ñ` destruidos.

| Enfoque | Resultado |
|---|---|
| `json_decode($body, true)` | **`null` silencioso** — parece que no hay datos |
| `json_decode($body, true, 512, JSON_INVALID_UTF8_SUBSTITUTE)` | Datos correctos, **texto corrompido** (`Meteorolog�a`) |
| Convertir la codificación **y luego** decodificar | ✅ Datos y texto correctos |

⚠️ Es el peor de los tres para depurar: la integración parece sana, los tests de estructura pasan, y
el defecto solo se ve al mirar una cadena con tilde. **No es una solución, es un parche que oculta el
problema.** Lo correcto es leer el `charset` de la cabecera y convertir antes de decodificar.

### A3. Un `200 OK` puede traer `"estado": 404` en el cuerpo 🟢

```
GET /api/maestro/municipio/36057   →   HTTP 200 OK
{
  "descripcion" : "No hay datos que satisfagan esos criterios",
  "estado" : 404
}
```

El sobre **no trae `datos`**, así que cualquier código que haga `$body['datos']` revienta con
"undefined key" en vez de gestionar un "no encontrado".

**Hay que validar `estado === 200` y la presencia de `datos`**, no el código HTTP.

### A4. Hay endpoints que devuelven datos rancios con un 200 impecable 🟢

Medido el 2026-08-26:

| Endpoint | Fecha del contenido devuelto | Antigüedad |
|---|---|---|
| `/api/prediccion/provincia/hoy/{15,27,32,36}` (Galicia) | `DÍA 3 DE NOVIEMBRE DE 2022` | ~4 años — **error conocido, fuera de alcance** |
| `/api/prediccion/nacional/tendencia` | `DÍA 29 DE ENERO DE 2025` | ~19 meses |
| `/api/prediccion/ccaa/hoy/gal` | `DÍA 23 DE JUNIO DE 2026` | ~2 meses |
| `/api/prediccion/ccaa/hoy/mad` | `DÍA 20 DE AGOSTO DE 2026` | 6 días |

⚠️ **No se puede generalizar por familia.** Cádiz (`11`) devuelve el mismo día; las cuatro provincias
gallegas, 2022 — esa avería regional queda como **error conocido y no se investiga**. Cuadro completo en
[`02-predicciones-texto.md`](02-predicciones-texto.md#-aviso-de-frescura--leer-antes-de-usar-este-grupo).

Ambos responden `200 OK` con el sobre correcto y contenido bien formado. Nada en la respuesta
HTTP indica que el dato esté obsoleto.

**Hay que extraer y validar la fecha de elaboración del contenido** antes de publicarlo.
En los productos JSON viene en el campo `elaborado`; en los de texto plano, en la cabecera
(`DÍA … A LAS … HORA OFICIAL`).

> 🟡 Son averías del lado de AEMET. **No se investigan ni se monitorizan**: se documentan como errores
> conocidos. Lo único obligatorio por nuestra parte es **validar la frescura del contenido**, que los
> detecta todos sin necesidad de saber cuáles están rotos.

---

### A5. Endpoints que responden `estado: 404` de forma permanente 🟢

Verificado el 2026-08-26. Estos endpoints existen en la especificación y responden `HTTP 200`, pero
el sobre trae `estado: 404`:

| Endpoint | `descripcion` del sobre |
|---|---|
| `/api/red/radar/nacional` | `"Error al obtener los datos"` |
| `/api/incendios/mapasriesgo/estimado/area/p` | `"No hay datos que satisfagan esos criterios"` |
| `/api/mapasygraficos/mapassignificativos/fecha/2026-08-25/gal/a` | `"No hay datos que satisfagan esos criterios"` |
| `/api/prediccion/provincia/hoy/36/elaboracion/2026-08-25` | `"No hay datos que satisfagan esos criterios"` |

Ojo: **`/api/red/radar/regional/co` sí funciona** (GIF, 23 KB, "cada 10 minutos"). Es el
**nacional** el que está roto. Y **`incendios/mapasriesgo/previsto`** sí funciona (PNG, 501 KB)
mientras el `estimado` no.

🟡 No sabemos si es permanente o intermitente. **Cualquier integración debe tolerar que un endpoint
documentado no devuelva nada nunca.**

### A6. El grado de obsolescencia varía por endpoint, no por familia 🟢

Medido el 2026-08-26 leyendo la fecha de cabecera de cada texto:

| Endpoint | Fecha del contenido | Desfase |
|---|---|---|
| `/api/prediccion/nacional/manana` | 25/08/2026 | ✅ 1 día |
| `/api/prediccion/nacional/pasadomanana` | 25/08/2026 | ✅ 1 día |
| `/api/prediccion/nacional/medioplazo` | 25/08/2026 | ✅ 1 día |
| `/api/prediccion/ccaa/manana/gal` | 25/08/2026 | ✅ 1 día |
| `/api/prediccion/ccaa/medioplazo/gal` | 25/08/2026 | ✅ 1 día |
| `/api/prediccion/ccaa/pasadomanana/gal` | 25/08/2026 | ✅ 1 día |
| `/api/prediccion/nacional/hoy` | 24/08/2026 | ⚠️ 2 días |
| `/api/prediccion/ccaa/hoy/gal` | 23/06/2026 | ⚠️ **2 meses** |
| **`/api/prediccion/nacional/tendencia`** | **29/01/2025** | 🔴 **19 meses** |
| **`/api/prediccion/provincia/hoy/36`** | **03/11/2022** | 🔴 **~4 años** |
| **`/api/prediccion/provincia/manana/36`** | **02/11/2022** | 🔴 **~4 años** |

**No se puede generalizar por familia**: dentro de `ccaa`, `manana` está al día y `hoy` lleva dos
meses. **Hay que validar la frescura endpoint por endpoint, en cada petición.**

### A7. La variante `/elaboracion/{fecha}` refleja la misma laguna 🟢

Era la hipótesis obvia para sortear A6. **No sirve de rodeo**: donde el producto está vivo el archivo
funciona, y donde está roto devuelve `estado: 404`.

| Variante de archivo | Resultado |
|---|---|
| `ccaa/{manana,pasadomanana,medioplazo}/gal/elaboracion/2026-08-25` | ✅ funcionan |
| `nacional/{manana,pasadomanana,medioplazo}/elaboracion/2026-08-25` | ✅ funcionan |
| `provincia/manana/11/elaboracion/2026-08-25` | ✅ funciona |
| `nacional/hoy/elaboracion/2026-08-25` | ⚠️ `estado: 404` |
| `ccaa/hoy/gal/elaboracion/2026-08-25` | ⚠️ `estado: 404` |
| `nacional/tendencia/elaboracion/2026-08-25` | ⚠️ `estado: 404` |
| `provincia/hoy/36/elaboracion/2026-08-25` | ⚠️ `estado: 404` |

🟡 Es decir: no es que "el de hoy aún no se haya generado y devuelva el anterior". **Es que no
existe**: los productos `hoy` y `tendencia` han dejado de generarse o de publicarse.

### A8. ⚠️ Advertencia sobre generalizar (error cometido) 🟢

En una versión anterior de esta documentación se afirmó que **"las predicciones provinciales están
muertas"**, a partir de haber comprobado **una sola provincia** (Pontevedra). Era falso: la avería
afecta solo a Galicia, y Cádiz devuelve el mismo día.

**Con esta API, un endpoint verificado con un valor de parámetro no dice nada de los demás valores.**
Hay que comprobar el parámetro concreto que se vaya a usar.

---

### A9. Los productos documentales devuelven `200` VACÍO cuando el periodo no existe 🟢

`/api/productos/climatologicos/resumenclimatologico/nacional/{anio}/{mes}` con un mes no publicado:

```http
HTTP/1.1 200 OK
Content-Type: text/plain; charset=UTF-8
Transfer-Encoding: chunked
(0 bytes)
```

| Petición | Resultado |
|---|---|
| `2025/12` | ✅ PDF de 4,18 MB |
| `2024/1` | ✅ PDF de 7,18 MB |
| **`2026/7`** (mes no publicado) | ⚠️ **200 con 0 bytes** |

**No hay 404, no hay `estado: 404`, no hay mensaje.** Y es **la misma respuesta que da la API cuando
falta la cabecera `api_key`** ([A3](#a3-un-200-ok-puede-traer-estado-404-en-el-cuerpo-)), así que un
cuerpo vacío aquí es ambiguo.

**Comprueba que el cuerpo no esté vacío y que empiece por `%PDF-`.**

---

## 🟠 Bloque B — La especificación miente sobre el formato de respuesta

### B1. El spec declara `application/json` en los 64 endpoints. Es falso ⚠️🟢

| Grupo | Endpoints | Formato real |
|---|---|---|
| `predicciones-normalizadas-texto` | **22** | **Texto plano** (verificado en 2 de 22) |
| `avisos_cap` | 2 | **`tar`** con XML CAP dentro (verificado en 1 de 2) |
| `observacion` mensajes | 1 | 🔵 `tar.gz` según la propia descripción del spec |
| `red-radares`, `informacion-satelite`, `red-rayos`, `mapas-y-graficos`, `indices-incendios` | 14 | 🔴 imágenes, sin verificar |
| `productos-climatologicos` | 3 | 🔴 documentos y capas SHAPE, sin verificar |

Es decir: **el `Content-Type` declarado en el spec no sirve para nada**. Hay que mirar el real.

### B2. Los avisos CAP vienen en un `tar` SIN comprimir, pese al `Content-Type` 🟢

`GET /api/avisos_cap/ultimoelaborado/area/71` → paso 2 con
`Content-Type: application/x-gtar;charset=ISO-8859-15`, 501.760 bytes.

Pero **no está comprimido**: no tiene magic gzip (`1f8b`), y en el offset 257 aparece `ustar`.
Es un `tar` plano. Dentro, **56 ficheros XML** en formato **CAP 1.2 de OASIS**
(`urn:oasis:names:tc:emergency:cap:1.2`), nombrados
`Z_CAP_C_LEMM_<AAAAMMDDHHMMSS>_AFAZ<zona><fenómeno><…>.xml`.

Si lo tratas como `gzip` (por el `x-gtar` o por la costumbre) falla la descompresión.

### B2-bis. `tar` sin comprimir en avisos, `gzip` de verdad en mensajes 🟢

Resuelve la duda que quedaba abierta: **no son iguales**.

| Endpoint | `Content-Type` | Compresión real | Tamaño |
|---|---|---|---|
| `/api/avisos_cap/ultimoelaborado/area/71` | `application/x-gtar;charset=ISO-8859-15` | **`tar` plano** (`ustar` en offset 257) | 490 KB |
| `/api/avisos_cap/archivo/…` (1 día) | `application/x-gtar;charset=ISO-8859-15` | **`tar` plano** | 3,8 MB |
| `/api/observacion/convencional/mensajes/tipomensaje/synop` | `application/octet-stream;charset=ISO-8859-15` | **`gzip` real** (magic `1f8b`) | **6,5 MB** |

⚠️ **El `Content-Type` no permite distinguirlos** (`x-gtar` para el que NO está comprimido,
`octet-stream` para el que SÍ). **Hay que comprobar el magic (`1f8b`) antes de descompimir.**

### B4-bis. Formatos reales medidos por producto 🟢

| Producto | Formato real | Notas |
|---|---|---|
| Mapa de rayos | **GIF** | `image/gif;charset=ISO-8859-15` |
| Mapas de análisis | **GIF** | 117,8 KB |
| Satélite SST | **GIF** | 100,7 KB |
| Satélite NDVI (`nvdi`) | **GIF** | 246,2 KB |
| Radar regional | **GIF** | 23,1 KB |
| **Incendios previsto** | **PNG** | `image/png`, sin charset — el único PNG |
| Capas SHAPE | **ZIP** | `application/zip`, sin charset |
| **Balance hídrico** | **PDF** | `application/pdf;charset=ISO-8859-15`, 4,6 MB |
| Ozono total | **CSV** (`;`, comillas) | UTF-8 |
| Radiación solar | **CSV** (`;`, comillas) | UTF-8 |
| Perfil de ozono | **Texto tabulado** | 612 KB, tabla científica |
| Nivológica | **Texto plano** | 588 B |

⚠️ **Las imágenes no son todas del mismo formato**: 5 GIF y 1 PNG. No asumas uno.

### B3. Los errores no siguen el esquema documentado ⚠️🟢

El spec define esquemas `401`, `403`, `404` y `429` con forma `{descripcion, estado}`. Realidad:

| Situación | Lo que devuelve de verdad |
|---|---|
| Ruta inexistente | **HTML** de `Apache Tomcat/8.0.32 - Error report`. No es JSON. |
| Clave inválida / expirada | ✅ JSON `{descripcion, estado: 401}` |
| Límite de uso superado | ✅ JSON `{descripcion, estado: 429}` |
| Sin cabecera `api_key` | **HTTP 200 con cuerpo vacío** y `Content-Type: text/plain`. No es un 401. |

**Un `json_decode` sobre la respuesta de un 404 de ruta falla.** Y la ausencia de clave no produce
el error que esperarías, sino un vacío silencioso.

### B4. Los esquemas `401` y `403` son idénticos ⚠️

Ambos declaran `default: "Unauthorized"`. No permiten distinguir "clave inválida" de
"sin permisos para el recurso". Hay que fiarse del campo `estado`.

---

## 🟡 Bloque C — Erratas de contenido en la especificación

Todas verificadas leyendo `src/especificacion/AEMET_OpenData_specification.json`.

### C1. `ccaa`: Asturias está escrito "Astrrias" ⚠️

Aparece mal en **7 de los 8** endpoints que usan `ccaa`. Solo
`/api/prediccion/ccaa/hoy/{ccaa}` lo escribe bien.

El **código** (`ast`) es correcto en todos, así que solo afecta a la etiqueta legible.
La tabla corregida está en [`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md).

### C2. `provincia`: el código `01` aparece duplicado ⚠️

La tabla tiene **60 filas para 59 códigos reales**:

```
| 01 | Araba/Álaba |     ← errata ortográfica
| 01 | Araba/Álava |     ← correcto
```

Si construyes un enum o un `select` directamente desde la tabla del spec, obtienes una clave
duplicada.

### C3. `area` reutiliza el mismo nombre para 5 dominios incompatibles ⚠️

**Es la errata con más potencial de bug real.** El mismo parámetro `area` significa cinco cosas
distintas según el endpoint, con juegos de códigos que no se solapan:

| Endpoint | Dominio de `area` | Códigos |
|---|---|---|
| `/api/avisos_cap/ultimoelaborado/area/{area}` | CCAA para avisos | `esp`, `61`–`79` |
| `/api/prediccion/especifica/montaña/pasada/area/{area}` | Áreas montañosas | `peu1`, `nav1`, `arn1`… |
| `/api/prediccion/especifica/nivologica/{area}` | Áreas nivológicas | `0`, `1` |
| `/api/prediccion/maritima/altamar/area/{area}` | Áreas de alta mar | `0`, `1`, `2` |
| `/api/incendios/mapasriesgo/*/area/{area}` | Península o Canarias | `p`, `c` |

Nótese que `0` y `1` son válidos en **dos** dominios distintos con significados sin relación.
**No modelar `area` como un único tipo o enum compartido.**

### C4. `dia` reutiliza el nombre para 4 escalas distintas ⚠️

| Endpoint | Valores |
|---|---|
| `/api/incendios/mapasriesgo/previsto/dia/{dia}/…` | `1`–`7` (mañana … 7 días) |
| `/api/prediccion/especifica/uvi/{dia}` | `0`–`4` (hoy … +4) |
| `/api/prediccion/especifica/montaña/…/dia/{dia}` | `0`–`3` |
| `/api/mapasygraficos/mapassignificativos/…/{dia}` | `a`–`f` (tramos de 12 h) |

Mismo problema que `area`: el `1` significa "mañana" en uno y "hoy+1" en otro.

### C5. Dos formatos distintos de código de municipio en la misma API ⚠️🟢

| Endpoint | Formato que acepta | Verificado |
|---|---|---|
| `/api/maestro/municipio/{municipio}` | **`id36057`** (con prefijo `id`) | 🟢 `36057` → `estado: 404`; `id36057` → 200 |
| `/api/prediccion/especifica/municipio/{diaria,horaria}/{municipio}` | **`36057`** (desnudo) | 🟢 |

Y el spec no lo dice: describe el parámetro como "Municipio" en el primero y enlaza al diccionario
del INE en los otros. El propio maestro devuelve `"id": "id36057"` como identificador canónico, más
un `"id_old": 36560` heredado que no es el código INE.

### C6. Los códigos de provincia mezclan 2 y 3 dígitos, y llevan ceros a la izquierda ⚠️

Provincias en dos dígitos (`01`, `36`, `50`) convivien con islas en tres (`071` Menorca,
`353` Gran Canaria, `384` El Hierro). **Hay que tratarlos como cadenas**: si se convierten a
entero se pierde el cero inicial y `01` deja de existir.

### C7. Ruta con carácter no ASCII ⚠️

Dos endpoints llevan `ñ` en la ruta:

```
/api/prediccion/especifica/montaña/pasada/area/{area}
/api/prediccion/especifica/montaña/pasada/area/{area}/dia/{dia}
```

Requiere codificación correcta de la URL. 🔴 Sin verificar cuál acepta AEMET (`montaña`
literal en UTF-8, `monta%C3%B1a`, o ambas).

### C8. Endpoint con nombre contradictorio ⚠️

`/api/prediccion/especifica/montaña/pasada/area/{area}/dia/{dia}` se titula
**"Predicción de montaña. Tiempo actual."** pese a llevar `/pasada/` en la ruta. Y su parámetro
`dia` admite `0`–`3`, o sea futuro. El `/pasada/` de la ruta parece ser un resto histórico.

### C9. El endpoint de satélite se llama `nvdi`, no `ndvi` ⚠️

`/api/satelites/producto/nvdi` — el índice real es **NDVI** (*Normalized Difference Vegetation
Index*). Es un error tipográfico consolidado en la ruta: **hay que escribirlo mal para que
funcione**.

### C10. Ningún parámetro declara `enum` ⚠️

Los 39 parámetros del spec son `type: string` libre. Los códigos válidos existen **solo como tablas
markdown embutidas dentro del campo `description`**. Consecuencia:

- Ningún generador de clientes produce validación de valores.
- No hay forma programática de obtener los valores válidos: hay que parsear prosa.
- Por eso existe [`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md).

### C12. `provincia` no tiene variante `pasadomanana` 🟢

A diferencia de `nacional` y `ccaa`, el ámbito provincial **solo tiene `hoy` y `manana`** (más sus
variantes de archivo). Verificado: `/api/prediccion/provincia/pasadomanana/11` devuelve **404** con
HTML de Tomcat.

⚠️ Documentación de terceros que ofrezca `pasadomanana` para provincia está equivocada.

### C13. Los códigos de provincia de las islas NO son los del INE de 2 dígitos 🟢

Verificado el 2026-08-26:

| Código probado | Resultado |
|---|---|
| `07` (Illes Balears, INE) | ⚠️ `estado: 404` |
| `071` (Isla de Menorca, AEMET) | ✅ 200 — "PREDICCIÓN PARA LA ISLA DE MENORCA" |
| `35` (Las Palmas, INE) | ⚠️ `estado: 404` |
| `353` (Isla de Gran Canaria, AEMET) | ✅ 200 — "PREDICCIÓN PARA LA ISLA DE GRAN CANARIA" |

**AEMET desglosa Baleares y Canarias por isla con códigos de 3 dígitos y no acepta los códigos
provinciales del INE.** Cualquier tabla que liste "Baleares = 07" o "Las Palmas = 35" para este
endpoint es incorrecta. Tabla correcta en
[`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md#islas-códigos-de-3-dígitos).

### C11. Erratas menores de redacción

| Dónde | Errata |
|---|---|
| `idema` en observación | "**Í**ndicativo climatológico" (tilde incorrecta) |
| `dia` en montaña | "d+3 (**siguente** a pasado mañana)" |
| `identificacion` Antártida | "(hasta 08/03/2007**))**" — paréntesis duplicado |
| `fecha` | 4 redacciones distintas para el mismo formato: "Fecha de elaboración", "Día de elaboración", "Fecha en formato", "Día" |
| `idema` | 3 descripciones distintas; **solo una** menciona que admite varios valores separados por comas |

---

## 🔵 Bloque D — Cosas que la API hace y el spec no cuenta

### D1. Varios endpoints aceptan múltiples valores separados por comas 🔵

`/api/valores/climatologicos/diarios/.../estacion/{idema}` lo documenta ("puede introducir varios
indicativos separados por comas"), y la FAQ 4.8 da el ejemplo `estacion/8178D,8050X`. Pero los
otros cuatro endpoints con `idema` **no lo mencionan**.

🔴 Sin verificar en qué endpoints funciona realmente. Si se usa, hay que comprobarlo antes.

### D2. El segundo salto no necesita autenticación 🟢

La URL `datos` (`https://opendata.aemet.es/opendata/sh/<8 hex>`) devuelve exactamente lo mismo con
y sin cabecera `api_key` (verificado: 13.621 bytes en ambos casos). El spec no lo indica.

Implicación de seguridad: esas URLs son **públicas mientras vivan**. No incrustarlas en HTML
público asumiendo que están protegidas.

### D3. La API Key acepta también query string 🟢

`?api_key=<jwt>` funciona igual que la cabecera (verificado: ambos producen 401 con clave
inválida). Los ejemplos oficiales de AEMET usan la query string.

**Usar siempre la cabecera.** Una clave en la URL acaba en logs de servidor, de proxy y en el
`Referer`. Detalle en [`00-fundamentos.md`](00-fundamentos.md#autenticación).

### D4. Los metadatos traen la periodicidad real de cada producto 🟢

La URL `metadatos` del sobre devuelve, entre otros campos, `periodicidad` en lenguaje natural
(p. ej. `"Dos veces al día (12:00 y 20:00) h.o.p"`). El spec no lo documenta.

Es la **única fuente objetiva** para decidir TTL de caché sin inventarse los números. Valores
recogidos en [`LIMITACIONES.md`](LIMITACIONES.md#periodicidad-real-de-actualización).

### D5. El maestro enlaza municipio con zona de aviso 🟢

`/api/maestro/municipio/id36057` devuelve `"zona_comarcal": "713601"`, que corresponde al prefijo
de zona usado en los nombres de los XML CAP (`…AFAZ7136…`). Es la vía para cruzar "municipio" con
"avisos que le aplican". No está documentado en ningún sitio.

### D7. La API expone un contador de cuota indocumentado 🟢

**`Remaining-request-endpoint`**, cabecera de respuesta que dice cuántas peticiones quedan para ese
endpoint. No aparece en ninguna documentación de AEMET. Es el mecanismo con el que hay que gestionar
la cuota. Medido: cubo de **40 por plantilla de endpoint** (no por URL), y de **~15** en los
productos pesados. Detalle completo en
[`LIMITACIONES.md`](LIMITACIONES.md#-la-api-expone-un-contador-de-cuota-que-no-documenta-).

⚠️ **Desaparece precisamente en el 429**, justo cuando más falta haría.

### D7-bis. ⚠️ `Remaining-request-endpoint` NO garantiza que la siguiente petición funcione 🟢

Medido el 2026-08-26:

```
GET /api/prediccion/especifica/municipio/horaria/36057,28079
  → HTTP 200 · Remaining-request-endpoint: 15

GET /api/prediccion/especifica/municipio/horaria/36057   (6 s después)
  → HTTP 429
```

La cabecera decía que quedaban **15** peticiones y la siguiente al mismo endpoint fue rechazada.

🟡 Conclusión: **hay al menos dos límites solapados**. `Remaining-request-endpoint` refleja uno
(probablemente una cuota de ventana larga) y existe otro más corto que no se expone en ninguna
cabecera. Usar `rem` como única defensa **no basta**: hay que combinarlo con espaciado entre
peticiones y con tolerancia al 429.

🟢 Dato adicional: el cubo de `/municipio/horaria/{municipio}` es de **15**, no de 40. Los cubos
varían mucho por endpoint.

### D8. Cabeceras propietarias `aemet_*` 🟢

Al menos `/api/productos/climatologicos/balancehidrico/{anio}/{decena}` devuelve:

```http
aemet_mensaje: Se ha encontrado 1 balance hídrico nacional
aemet_estado: 200 OK
aemet_num: 1
```

Sin documentar. 🔴 Sin verificar en qué otros endpoints aparecen ni si son fiables.

### D9. ⚠️ Dos endpoints NO usan el flujo de dos saltos 🟢

**`balancehidrico` y `resumenclimatologico` devuelven el PDF directamente en el paso 1**, sin sobre:

```http
HTTP/1.1 200 OK
Content-Type: application/pdf;charset=ISO-8859-15
Content-Length: 4568812
```

Son **las dos únicas excepciones** en los 64 endpoints. Cualquier código que dé por hecho el sobre
**falla aquí** intentando parsear un PDF como JSON.

🟢 Verificado que **`capasshape` sí usa el flujo normal** de dos saltos, aunque también sea un
producto documental. No hay regla: hay que saberlo endpoint por endpoint.

| Producto documental | ¿Flujo de dos saltos? |
|---|---|
| `balancehidrico` | ❌ PDF directo |
| `resumenclimatologico` | ❌ PDF directo |
| `capasshape` | ✅ sobre + ZIP en `datos` |

### D10. El multi-valor por comas funciona (al menos en inventario) 🟢

`/api/valores/climatologicos/inventarioestaciones/estaciones/1495,8019` devuelve `list[2]`, un
objeto por estación. Confirma el patrón que la FAQ 4.8 sugería.
🔴 Sigue sin verificar en los demás endpoints con `idema`.

### D10-bis. El multi-valor por comas NO es general 🟢

Verificado producto a producto:

| Endpoint | ¿Acepta lote? |
|---|---|
| `/api/valores/climatologicos/inventarioestaciones/estaciones/{estaciones}` | ✅ **Sí** — `1495,8019` → 2 registros |
| `/api/valores/climatologicos/diarios/…/estacion/{idema}` | 🔵 Documentado por AEMET, sin probar |
| `/api/maestro/municipio/{municipio}` | ❌ **No** — `estado: 404` |
| `/api/prediccion/especifica/municipio/horaria/{municipio}` | ❌ **No** — `estado: 404` |
| `/api/prediccion/especifica/municipio/diaria/{municipio}` | 🟡 sin probar, previsiblemente no |
| Los otros 3 endpoints con `idema` | 🔴 sin probar |

**Solo los endpoints cuya descripción lo menciona lo soportan.** En los demás la lista se interpreta
como un identificador único inexistente y devuelve `estado: 404` — sin error de ruta, así que es fácil
confundirlo con "ese municipio no existe".

### D11. La ruta con `ñ` exige UTF-8 percent-encoded en forma NFC 🟢

| Codificación | Resultado |
|---|---|
| `monta%C3%B1a` (UTF-8 NFC, `ñ` = U+00F1) | ✅ **200** |
| `montan%CC%83a` (UTF-8 NFD, `n` + tilde combinante) | ❌ **404** |

Resuelve la duda de [C7](#c7-ruta-con-carácter-no-ascii-). Ojo si el nombre viene de un sistema
macOS, que normaliza a NFD por defecto: **hay que normalizar a NFC** antes de construir la URL.

### D6. El 429 no trae `Retry-After` 🟢

Volcado completo de cabeceras de un 429 real: no hay `Retry-After`, ni `RateLimit-Limit`, ni
`RateLimit-Remaining`, ni `RateLimit-Reset`. Solo `Date`, `Content-Type`, `Cache-Control`,
`Pragma`, `Expires`, `Connection`, `Vary` y `Set-Cookie`.

**El backoff hay que hacerlo a ciegas.** Ver [`LIMITACIONES.md`](LIMITACIONES.md#límites-de-uso).

---

## 🟣 Bloque E — Inconsistencias dentro de los propios payloads

No son erratas de la especificación (que no describe los payloads en absoluto), sino incoherencias
de los datos que devuelve la API. Todas 🟢 verificadas en respuestas reales.

### E1. La predicción de playa duplica campos en dos grafías 🟢

`/api/prediccion/especifica/playa/{playa}` devuelve, dentro del mismo objeto de día:

| camelCase | minúscula |
|---|---|
| `tAgua` | `tagua` |
| `sTermica` | `stermica` |
| `tMaxima` | `tmaxima` |

Con el mismo contenido. Elige una grafía y sé consistente. 🔴 Sin verificar si alguna vez difieren.

### E2. Valores codificados que parecen magnitudes 🟢

En la predicción de playa:

```json
"sTermica": { "value": "", "valor1": 450, "descripcion1": "suave" }
"estadoCielo": { "value": "", "f1": -116, "descripcion1": "muy nuboso con lluvia" }
"viento": { "value": "", "f1": 220, "descripcion1": "moderado" }
```

`450` **no son 450 °C**: es un código cuya lectura está en `descripcion1`. Igual con `f1`/`f2`.
**Usa siempre las descripciones, nunca los números.** 🔴 Tablas de códigos desconocidas: no están en
la especificación ni en ninguna fuente oficial localizada.

Los únicos campos con magnitudes físicas reales en ese payload son `tMaxima`, `tAgua` y `uvMax`.

### E3. Un identificador negativo 🟢

La predicción de playa devuelve `"localidad": -29479`. Negativo y sin sentido como identificador —
parece un desbordamiento. **No usar ese campo**; el identificador válido es `id` (`3605706`).

### E4. Formatos de fecha incoherentes entre productos 🟢

| Producto | Campo | Formato |
|---|---|---|
| Municipio (diaria/horaria) | `elaborado` | `"2026-08-26T07:09:13"` (ISO 8601, cadena) |
| Municipio, día | `fecha` | `"2026-08-26T00:00:00"` (ISO 8601, cadena) |
| **Playa, día** | `fecha` | **`20260826` (entero `AAAAMMDD`)** |
| Observación | `fint` | `"2026-08-25T19:00:00+0000"` (desplazamiento sin `:`) |
| Marítima | `origen.elaborado` | `"2026-08-25T20:00:00"` |
| Avisos CAP | `<sent>` | `"2026-08-25T09:03:36-00:00"` |

Cuatro convenciones distintas en la misma API. ⚠️ El `+0000` de observación **no es ISO 8601
estricto** y algunos parseadores lo rechazan.

### E5. El campo `elaborado` cambia de sitio según el producto 🟢

| Producto | Ubicación |
|---|---|
| Municipio, playa | En la **raíz** del objeto |
| Marítima costera y alta mar | Dentro de **`origen`** |
| Observación | No existe: la marca temporal es `fint` **por registro** |

Código que busque `elaborado` en la raíz falla con marítima.

### E6. `origen.web` llega sucio en algunos productos 🟢

| Producto | Valor |
|---|---|
| Municipio | `"https://www.aemet.es"` ✅ |
| **Marítima costera** | `" http://www.aemet.es"` ⚠️ **espacio inicial** y `http` |
| Playa | `"http://www.aemet.es"` ⚠️ `http` |

Hay que recortar espacios y normalizar el esquema antes de usarlo como enlace.

### E7. Los números vienen como cadenas… menos en observación 🟢

En predicción de municipio: `"value": "18"`, `"value": "0.1"` — cadenas.
En observación convencional: `"prec": 0.0`, `"pres": 983.6` — números de verdad.

No se puede aplicar el mismo casteo a todos los productos.

### E8. `periodo` tiene dos semánticas en el mismo objeto 🟢

En la predicción horaria de municipio:

- En series horarias: `"periodo": "02"` = **la hora** 02:00.
- En series de probabilidad: `"periodo": "0208"` = **el tramo** de 02:00 a 08:00.

Mismo nombre de campo, dentro del mismo `dia`, con significados distintos.

### E9. Arrays de longitud inconsistente y sin cubrir el día 🟢

En un mismo `dia` de la predicción horaria: `estadoCielo[22]`, `temperatura[21]`,
`vientoAndRachaMax[42]`, `probPrecipitacion[4]`. **No asumas 24 elementos ni indexes por posición**:
usa el campo `periodo` para emparejar.

### E10. Zonas que se duplican en su propia subzona 🟢

En marítima costera, cuando una zona no se subdivide, `zona.id` y `subzona.id` son idénticos con el
mismo nombre (Galicia: ambos `8112710`, "Aguas costeras de Lugo"). **Hay que recorrer siempre hasta
`subzona`** para obtener los textos.

### E11. `aviso.texto` usa una cadena literal para "no hay nada" 🟢

Marítima costera devuelve `"texto": "No hay avisos."` — no un vacío ni un `null`. La existencia del
campo `aviso` **no significa que haya un aviso activo**.

---

### E12. Raíz `dict` en vez de `list` en algunos productos 🟢

La mayoría de los productos JSON devuelven `list`, incluso para un solo elemento. Excepciones:

| Producto | Raíz |
|---|---|
| `/api/prediccion/especifica/uvi/{dia}` | **`dict`** (4 claves) |
| `/api/valores/climatologicos/valoresextremos/…` | **`dict`** (24 claves) |
| Resto de productos JSON verificados | `list` |

Código que haga `$data[0]` a ciegas falla en estos dos.

### E13. UVI usa claves en MAYÚSCULAS 🟢

`/api/prediccion/especifica/uvi/0` devuelve `FECHA_ELABORACION`, `FECHA_MOD`, `FECHA_VALIDEZ`,
`CIUDAD`. Es el único producto con esa convención: el resto usa `camelCase` o minúsculas. Cuarta
convención de nombres en la misma API.

### E14. Coordenadas en tres formatos distintos 🟢

| Producto | Formato de latitud |
|---|---|
| Maestro de municipio | `"42º14'22.112988\""` (grados/minutos/segundos, texto) **y** `42.23947583` (decimal) |
| Inventario de estaciones | `"394924N"` (grados-minutos-segundos **empaquetados**, con hemisferio) |
| Observación | `42.238616` (decimal) |
| Antártida | `-62.97697` (decimal con signo) |
| CSV de playas | `"42º 13' 12\""` (GMS con espacios) |

El formato empaquetado del inventario (`394924N` = 39°49'24" N) exige un parseo específico.

### E15. `id_old` no guarda relación con el código INE 🟢

Verificado en dos municipios: Vigo (`id36057`) tiene `id_old: 36560`; Ababuj (`id44001`) tiene
`id_old: "44004"`. Ni siquiera el tipo es consistente (entero en uno, cadena en el otro).
**No usar `id_old` para nada.**

---

### E16. Cada XML CAP trae el aviso DUPLICADO en dos idiomas 🟢

56 ficheros → **112 bloques `<info>`**: 56 en `es-ES` y 56 en `en-GB`. Recorrer todos los `<info>`
duplica cada aviso. **Hay que filtrar por `<language>es-ES`.** No está documentado.

### E17. Los `valueName` de los CAP son literales largos, no nombres cortos 🟢

| Real 🟢 | Lo que suele documentarse mal |
|---|---|
| `AEMET-Meteoalerta zona` | ~~`EMMA_ID`~~ |
| `AEMET-Meteoalerta nivel` | ~~`Nivel`~~ |
| `AEMET-Meteoalerta probabilidad` | ~~`Probabilidad`~~ |
| `AEMET-Meteoalerta parametro` | — |

Buscar los nombres cortos **no encuentra nada**, y el filtro de nivel verde queda inoperante sin
lanzar ningún error. Ver
[`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md#los-parámetros-propietarios-aemet-meteoalerta-).

### E18. Los avisos de nivel `verde` no traen `parametro` ni `probabilidad` 🟢

Los 20 avisos `verde` de la muestra carecen de ambos campos, mientras los 92 `amarillo` los traen.
Código que espere esos campos falla **justo en los que hay que descartar**.

### E19. El sufijo `C` de las zonas de aviso marca zonas costeras 🟢

6 de las 22 zonas de Galicia acaban en `C` (`711501C`, `713601C`…) y sus `areaDesc` empiezan por
`"Costa - "`. Un filtro por prefijo de 4 dígitos las incluye sin querer. No documentado.

---

### E20. La clave `posición_txt` de los metadatos cambia de grafía 🟢

En los metadatos de contaminación de fondo, el **primer** campo usa `posición_txt` (con tilde) y
**todos los demás** usan `posicion_txt` (sin tilde), dentro del mismo documento JSON:

```json
{ "id": "Fecha", "posición_txt": "1-10" }     ← con tilde
{ "id": "Hora",  "posicion_txt": "12-16" }    ← sin tilde
```

Un parser que busque solo una de las dos formas **pierde campos silenciosamente**. Hay que aceptar
ambas (o buscar cualquier clave que acabe en `_txt`).

### E21. IDs duplicados en el diccionario de campos 🟢

En los metadatos de contaminación de fondo, **`Codigo_validacion_O3` aparece dos veces**. Por la
`descripcion` se ve que el segundo es en realidad el código de validación de **PM10**.

Construir un mapa `id → campo` **pierde uno de los dos**. Hay que desambiguar leyendo la
`descripcion`.

### E22. Erratas ortográficas en las descripciones de los metadatos 🟢

| Texto real | Debería ser |
|---|---|
| "Indicativo climat**óg**ico" | climatológico |
| "estación meteoro**lógia** automática" | meteorológica |
| "**errónero** por fallo técnico" (×3) | erróneo |
| "Espesor de la capa de nieve **medid**" | medida |
| "en los 10 minutos anteriores **a la a la** fecha" | a la |

Irrelevante para el parseo, pero si se muestran esas descripciones al usuario conviene saberlo.

---

### E23. El mismo producto tiene DOS estructuras según el endpoint 🟢

`/municipio/diaria/{municipio}` y `/municipio/diaria/todos` devuelven la misma predicción con
**estructuras y nombres de campo distintos**:

| | Individual | Agregado (`/todos`) |
|---|---|---|
| Raíz | `list[1]` | **`dict` con clave `root`** |
| Atributos XML | no | **`xmlns:xsd`, `xmlns:xsi`, `xsi:noNamespaceSchemaLocation`** |
| Campos de día | `estadoCielo`, `probPrecipitacion`, `sensTermica` (camelCase) | **`estado_cielo`, `prob_precipitacion`, `sens_termica` (snake_case)** |
| Viento | `vientoAndRachaMax` (un campo) | **`viento` + `racha_max` (dos campos)** |
| Campos extra | — | **`cota_nieve_prov`, `uv_max`** |
| Días | 🔴 sin contar en diaria | **7** |

**No se puede reutilizar el mismo parseador para los dos.** El envoltorio `root` y los atributos de
espacio de nombres delatan que el agregado es una conversión mecánica de XML a JSON.

Detalle en [`01-predicciones-municipios.md`](01-predicciones-municipios.md#la-estructura-interna-no-es-la-del-endpoint-individual).

### E24. Los agregados de municipio son `gzip`, y el `Content-Type` no lo dice 🟢

`/municipio/{diaria,horaria}/todos` devuelven `Content-Type: application/octet-stream` y son
**`gzip`** (magic `1f 8b 08 08`) conteniendo un **`tar`** con **8.124 ficheros JSON**.

⚠️ **Tercer patrón distinto de empaquetado en la misma API**:

| Producto | `Content-Type` | Realidad |
|---|---|---|
| Avisos CAP | `application/x-gtar` | **`tar` SIN comprimir** |
| Mensajes de observación | `application/octet-stream` | **`gzip`** |
| Agregados de municipio | `application/octet-stream` | **`gzip`** |

**El `Content-Type` no sirve para decidir cómo desempaquetar.** Hay que comprobar el magic:
`1f 8b` = gzip; `ustar` en el offset 257 = tar plano.

🟢 La cabecera gzip lleva la bandera `FNAME` con el nombre original
(`<epoch>_municipios_7d_json.tar`), que es de donde se deduce que la diaria trae **7 días**.

### E25. ⚠️ La cuota parece ligada a la IP, no solo a la API Key 🟢

Con el cubo de `/municipio/diaria/{municipio}` agotado, se probó una **API Key recién generada, con
otro `sub`, que nunca había llamado a ese endpoint**. Resultado: **429 inmediato**.

🟡 Es decir: el contador no es solo por clave. Va por **IP + endpoint**, o hay un límite de IP por
encima del de clave. Consecuencias:

- **Generar otra API Key no desbloquea un endpoint agotado.**
- Varios procesos o entornos en la **misma IP comparten cuota**.
- 🔴 Sin determinar si es estrictamente por IP o alguna combinación. **No se sondeará a propósito.**

---

### E26. En las normales, ta_max está duplicado y con la descripción equivocada

`/api/valores/climatologicos/normales/estacion/{idema}` declara **486 campos**, pero solo hay 44
variables reales × 11 estadísticos + 2 = 486 **porque `ta_max_*` aparece dos veces**: cada uno de sus
11 estadísticos (`ta_max_md`, `ta_max_cv`, …) está listado **por duplicado**.

Y las 11 descripciones de `ta_max_*` dicen **"temperatura mínima absoluta"**, copiadas de `ta_min_*`:

```
ta_max_md → "media aritmética de la temperatura mínima absoluta del mes/año y fecha"   ← debería decir máxima
ta_min_md → "media aritmética de la temperatura mínima absoluta del mes/año y fecha"
```

Construir un mapa `id → descripción` colapsa los duplicados **y** deja `ta_max` mal etiquetado.
Hay **22 descripciones repetidas** entre ids distintos por esta causa.

Otras dos erratas de descripción en el mismo documento:
- `q_min_*` → "presión **máxima mínima** mensual/anual" (debería ser solo mínima)
- `ts_min_*` → repite literalmente la descripción de `ti_min_*` ("temperatura mínima mas alta")

### E27. Los metadatos pueden estar INCOMPLETOS o traer entradas vacías 🟢

No todos los productos documentan sus campos. Medido:

| Producto | Campos declarados | Realidad |
|---|---|---|
| Observación convencional | 39 | ✅ completo |
| Climatologías normales | 486 | ✅ completo (con la errata E26) |
| Climatologías mensuales | 49 | ✅ completo |
| Climatologías diarias | 27 | ✅ completo |
| Valores extremos | 24 | ✅ completo |
| **Playa** | **6** | ❌ documenta `id`, `elaborado`, `nombre`, `localidad` y **nada del bloque `prediccion`**. Los códigos `f1`/`f2` **no están** |
| **UVI** | **4** | ❌ documenta `version`, `FECHA_ELABORACION`, `FECHA_VALIDEZ`; **faltan `FECHA_MOD` y `CIUDAD`**, que sí vienen en el payload |

⚠️ Además, playa y UVI incluyen **entradas de `campos` con `id: null` y descripción vacía**: elementos
del array que no documentan nada. Iterar `campos[]` sin comprobar que `id` existe produce claves
nulas.

🟡 Conclusión: los metadatos son **la mejor** fuente del diccionario de datos, pero **no siempre son
completos**. Para playa y UVI hay que quedarse con lo observado en el payload.

### E28. La codificación varía entre documentos de metadatos 🟢

Incluso dentro de la misma familia:

| Metadatos de | Codificación real |
|---|---|
| Climatologías **diarias** | **UTF-8** |
| Climatologías **mensuales** | **UTF-8** |
| Climatologías **normales** | ISO-8859-15 |
| Valores extremos | ISO-8859-15 |
| Observación | ISO-8859-15 |

Decodificar todos como ISO-8859-15 produce `climatolÃ³gico` en los dos primeros; como UTF-8, falla en
los otros tres. **Hay que leer el `charset` de la cabecera también en las URLs de metadatos**, no solo
en las de datos.

### E29. Los metadatos de playa contradicen el propio payload 🟢

Los metadatos definen `localidad` como *"Indicativo del municipio al que pertenece la playa"*. Para
Samil (municipio `36057`) el payload devuelve **`localidad: -29479`**: un número negativo que no es
ningún municipio.

Es la confirmación de que [E3](#e3-un-identificador-negativo-) es un defecto de los datos y no una
mala interpretación nuestra: **AEMET define el campo correctamente y luego lo sirve mal.**

### E30. Valores de `tipo_datos` inconsistentes y con erratas 🟢

| Valor real | Dónde | Debería ser |
|---|---|---|
| `datatime` | playa, campo `elaborado` | `datetime` |
| `dataTime` | UVI, campos de fecha | `datetime` |
| `string (AAAA-MM-DDTHH:MM:SS)` | observación, `fint` | (formato embutido en el tipo) |
| `array de string` | valores extremos | (en castellano, no un tipo formal) |
| `json/xml` como `formato` | playa, UVI, extremos | no es un tipo MIME válido |

**No se puede usar `tipo_datos` para casteo automático**: no es un vocabulario cerrado.

---

### E31. El campo `campos` de los metadatos cambia de tipo

En todos los productos, `campos` es un **array de objetos** `{id, descripcion, tipo_datos,
requerido}`. En los avisos CAP es una **cadena de texto**:

```json
"campos": "Anexo 3 del Plan Meteoalerta (https://www.aemet.es/documentos/…/plan_meteoalerta.pdf)"
```

Y en las capas SHAPE es **otra cadena**, esta vez con una URL externa:

```json
"campos": "https://www.miteco.gob.es/es/cartografia-y-sig/ide/descargas/otros/default.aspx"
```

Código que haga `foreach (campos as campo)` **recorre los caracteres uno a uno** en vez de fallar.
No hay error, solo basura.

🟡 El patrón parece ser: cuando el diccionario vive en un documento externo, AEMET mete la referencia
en `campos` como cadena en vez de dejarlo vacío o usar otro campo.

### E32. Los metadatos del maestro de municipios están vacíos 🟢

`/api/maestro/municipio/{municipio}` declara:

- **1 solo campo**, con `id: "string"`, sin `descripcion` ni `tipo_datos`
- `periodicidad: null`
- `formato: "JSON"` — en **mayúsculas**, mientras el resto usa `application/json`
- `descripcion` con errata: *"Retorna información especíca del municipio de España"*

El payload real trae **13 campos**. El diccionario hay que sacarlo observando la respuesta.

### E33. Los metadatos de ozono contradicen el formato de fecha del payload 🟢

| Fuente | Formato de fecha |
|---|---|
| Metadatos | `dd/mm/yyyy` |
| **Payload real** | **`dd-mm-aa`** (`"25-08-26"`) |

Ni el separador ni la longitud del año coinciden. Manda el payload.

### E34. Periodicidades que no se cumplen o que contradicen la especificación 🟢

**Declaradas y no cumplidas:**

| Producto | `periodicidad` declarada | Antigüedad observada |
|---|---|---|
| Perfil vertical de ozono | "Cada 7 días" | **28 días** |
| Datos de la Antártida | "anualmente" | (coherente: datos de enero) |

**Especificación contra metadatos:**

| Producto | Especificación 🔵 | Metadatos 🟢 |
|---|---|---|
| Mapas de análisis | "cada 12 horas (00, 12)" | **"Dos veces al día, a las 02:00 y 14:00 h.o.p. en invierno y a las 03:00 y 15:00 en verano"** |

⚠️ Los metadatos no solo son más concretos: avisan de que **las horas cambian con el horario de
verano**, algo que la especificación omite. Un cron fijo a 00:00/12:00 pide el mapa antes de que
exista.

🟡 La periodicidad declarada es una intención, no una garantía. **Muestra siempre la fecha del dato.**

### E35. `formato` usa valores que no son tipos MIME 🟢

Recuento de los valores observados en 17 productos:

| Valor de `formato` | Productos |
|---|---|
| `application/json` | observación, climatologías diarias/mensuales/normales, inventario, Antártida |
| `json/xml` | valores extremos, playa, UVI, marítima costera y de alta mar, montaña |
| `txt/csv` | radiación, ozono, perfil de ozono |
| `ascii/txt` | contaminación de fondo, nivológica |
| `JSON` (mayúsculas) | maestro de municipio |
| `application/x-gtar (contiene ficheros CAP v1.2)` | avisos |

**Solo el primero es un tipo MIME válido.** `formato` sirve para orientarse, no para elegir un
parseador automáticamente.

---

### E36. El número de municipios no coincide entre endpoints 🟢

| Fuente | Municipios |
|---|---|
| `GET /api/maestro/municipios` | **8.122** |
| `GET /api/prediccion/especifica/municipio/diaria/todos` (ficheros del `tar`) | **8.124** |
| `GET /api/prediccion/especifica/municipio/horaria/todos` | **8.124** |

Sobran **dos** en los agregados. 🔴 Sin identificar cuáles. Si validas completitud contando
registros, **los dos endpoints no cuadran entre sí**.

---

## 🔴 Bloque F — Erratas pendientes de confirmar

### Resueltas desde la última revisión ✅

| Antes | Resultado |
|---|---|
| ~~F1: ¿`mapassignificativos` está muerto?~~ | ✅ **Sí.** `estado: 404` con fecha reciente. Ver [A5](#a5-endpoints-que-responden-estado-404-de-forma-permanente-) |
| ~~F2: ¿el `tar` de mensajes es `tar` o `tar.gz`?~~ | ✅ **`gzip` real**, a diferencia de los avisos. Ver [B2-bis](#b2-bis-tar-sin-comprimir-en-avisos-gzip-de-verdad-en-mensajes-) |
| ~~F3: ¿los demás endpoints de texto dan datos rancios?~~ | ✅ **Varía por endpoint.** Ver [A6](#a6-el-grado-de-obsolescencia-varía-por-endpoint-no-por-familia-) |
| ~~F4: codificación de la ruta con `ñ`~~ | ✅ **UTF-8 NFC percent-encoded.** Ver [D11](#d11-la-ruta-con-ñ-exige-utf-8-percent-encoded-en-forma-nfc-) |
| ~~F5: ¿funciona el multi-valor por comas?~~ | ✅ **Sí** en inventario de estaciones. Ver [D10](#d10-el-multi-valor-por-comas-funciona-al-menos-en-inventario-) |
| ~~F6: umbral del límite por endpoint~~ | ✅ **40 por plantilla de endpoint**, expuesto en `Remaining-request-endpoint`. Ver [D7](#d7-la-api-expone-un-contador-de-cuota-indocumentado-) |
| ~~F11: descripciones de `estadoCielo`~~ | ✅ **35 códigos** identificados vía el servidor de imágenes de AEMET; 12 descripciones verificadas. Ver [`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md) |
| ~~Cruce municipio ↔ zona de aviso~~ | ✅ `zona_comarcal` del maestro **es** el `geocode AEMET-Meteoalerta zona`. Ver [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md#-cruzar-avisos-con-un-municipio--resuelto-) |

### Aún pendientes

| # | Sospecha | Cómo verificarlo |
|---|---|---|
| F7 | Ventana real de recuperación de la cuota (la familia `municipio/*` seguía bloqueada tras >1 h) | Observación a lo largo de días. **No sondear a propósito** |
| F8 | Si `resumenclimatologico` y `capasshape` también se saltan el flujo de dos saltos, como `balancehidrico` | Inspeccionar cabeceras del paso 1 |
| F9 | Si `A5` (endpoints con `estado: 404`) es permanente o intermitente | Reintentar en días distintos |
| F10 | Si el multi-valor funciona en los 4 endpoints con `idema` que no lo documentan (🟢 ya descartado en los de municipio) | Petición con dos valores en cada uno |
| F16 | Cuál es el segundo límite solapado que provoca 429 con `rem > 0` (ver [D7-bis](#d7-bis--remaining-request-endpoint-no-garantiza-que-la-siguiente-petición-funcione-)) | Observación, **sin sondear** |
| F11 | Descripción real de los 23 códigos de `estadoCielo` marcados 🟡 | Recoger `descripcion` de payloads variados. Ver [`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md) |
| F17 | Códigos `f1`/`f2` y `sTermica` de playa: **los metadatos no los documentan** (E27), así que no hay fuente oficial | Deducir por observación o descartar su uso |
| ~~F12: volumen de los agregados de municipio~~ | ✅ **2,2 MB y 6,5 MB** de descarga; **73,9 MB y 142,9 MB** descomprimidos. Ver [E24](#e24-los-agregados-de-municipio-son-gzip-y-el-content-type-no-lo-dice-) |
| F13 | En qué endpoints aparecen las cabeceras `aemet_*` | Inspección sistemática de cabeceras |

| F15 | Cuánto viven las URLs `sh/<token>` del paso 2 | Reutilizar una URL tras horas |

---

## Cómo añadir una errata

1. **Verifícala con una petición real.** Una sospecha no es una errata.
2. Colócala en el bloque que le corresponda (A rompe en silencio, B formato, C contenido del spec,
   D indocumentado, E payloads, F pendiente).
3. Incluye **la evidencia literal**: ruta, código HTTP, cabecera o fragmento de cuerpo.
4. Marca la fiabilidad y **actualiza la fecha de verificación** de la cabecera de este archivo.
5. Si afecta a un endpoint concreto, añade un aviso en su archivo de módulo enlazando aquí.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
