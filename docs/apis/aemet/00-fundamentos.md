# 🧱 Fundamentos de AEMET OpenData

> [!IMPORTANT]
> **Lectura obligatoria antes de cualquier otro archivo.** Sin entender el flujo de dos saltos y el
> problema de codificación, los archivos de endpoints no te servirán de nada.

Complementos obligatorios: [`ERRATAS.md`](ERRATAS.md) y [`LIMITACIONES.md`](LIMITACIONES.md).

Leyenda: 🟢 verificado con petición real (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas, parámetros y esquemas declarados) · `src/catalogos/faqs.json` (límites, caducidad de la clave, códigos HTTP) · URLs `metadatos` de los endpoints (diccionarios de campos, `formato`, `periodicidad`) · **verificación en vivo del 2026-08-26** (codificación, cabeceras, envelope real).

---

## Datos básicos

| | |
|---|---|
| **Base URL** | `https://opendata.aemet.es/opendata` 🟢 |
| **Prefijo de rutas** | Todas empiezan por `/api/` |
| **Método** | `GET` en los 64 endpoints. No hay `POST`, `PUT` ni `DELETE` 🟢 |
| **Autenticación** | API Key (JWT) en cabecera `api_key` 🟢 |
| **Codificación** | **ISO-8859-15**, no UTF-8 🟢 |
| **Especificación** | OpenAPI 3.0.1, `info.version` 2.0 |

> ⚠️ La URL base **no** incluye `/api`. Las rutas ya lo llevan. Si configuras
> `base_url = ".../opendata/api"` y luego concatenas `/api/...`, obtienes `/api/api/...`.

---

## Autenticación

La clave es un **JWT** con fecha de expiración 🟢. Vive en `.env` como `AEMET_API_KEY`.
**Nunca se escribe en documentación ni en código.**

### Cabecera (obligatorio usar esta vía) 🟢

```http
GET /opendata/api/prediccion/especifica/municipio/diaria/36057 HTTP/1.1
Host: opendata.aemet.es
api_key: <AEMET_API_KEY>
Accept: application/json
```

### Query string (funciona, pero NO usarla) 🟢

`?api_key=<jwt>` es equivalente y es lo que usan los ejemplos oficiales de AEMET. **No la uses:**
una credencial en la URL termina en los logs del servidor, en los del proxy, en el historial y en
la cabecera `Referer`. La cabecera evita todo eso sin coste.

### La clave caduca

Detalle en [`LIMITACIONES.md`](LIMITACIONES.md#caducidad-de-la-api-key). La actual expira el
**2026-12-04**. Un 401 con `"La API Key ha expirado"` no es un bug: es la fecha.

### Comportamiento sin clave 🟢 ⚠️

**Sin la cabecera `api_key`, la API devuelve `HTTP 200` con el cuerpo VACÍO** y
`Content-Type: text/plain`. No devuelve 401. Es decir: una configuración con la clave sin definir
no falla de forma visible, simplemente no trae datos.

---

## Gestión de cuota: `Remaining-request-endpoint`

🟢 La API devuelve en cada respuesta correcta una cabecera **indocumentada** que dice cuántas
peticiones quedan para ese endpoint:

```http
HTTP/1.1 200 OK
Remaining-request-endpoint: 39
```

- Cubo de **40 peticiones por plantilla de endpoint** (no por URL: cambiar el parámetro no da cubo
  nuevo). Los productos pesados tienen cubos menores (~15 en balance hídrico).
- **Los "40 por minuto" de la FAQ son por endpoint, no un agregado global.**
- ⚠️ **La cabecera desaparece en el 429**, justo cuando la necesitarías.
- **Leerla y frenar antes de agotarla** es la única forma fiable de gestionar la cuota.

Detalle en [`LIMITACIONES.md`](LIMITACIONES.md#-la-api-expone-un-contador-de-cuota-que-no-documenta-).

---

## Flujo de dos saltos

**Toda consulta a AEMET son dos peticiones HTTP.** El endpoint no devuelve datos: devuelve un sobre
con la URL donde están.

```
      ┌─────────────────────────────────────────────┐
      │  PASO 1 — con api_key                       │
      │  GET /opendata/api/<endpoint>               │
      └───────────────────┬─────────────────────────┘
                          ▼
              { descripcion, estado, datos, metadatos }
                          │
        ┌─────────────────┴──────────────────┐
        ▼                                    ▼
┌───────────────────────┐         ┌──────────────────────────┐
│ PASO 2 — SIN api_key  │         │ METADATOS — SIN api_key  │
│ GET <datos>           │         │ GET <metadatos>          │
│ → los datos reales    │         │ → periodicidad, unidades │
└───────────────────────┘         └──────────────────────────┘
```

> [!WARNING]
> ⚠️ **Hay exactamente dos excepciones** 🟢: `balancehidrico` y `resumenclimatologico`
> **devuelven el PDF directamente en el paso 1**, sin sobre
> (`Content-Type: application/pdf`, 4–7 MB). Código que dé por hecho el sobre **falla ahí intentando
> parsear un PDF como JSON**. Y si el periodo no está publicado, devuelven **200 con 0 bytes**.
> Ver [`ERRATAS.md` D9](ERRATAS.md#d9--dos-endpoints-no-usan-el-flujo-de-dos-saltos-)
> y [A9](ERRATAS.md#a9-los-productos-documentales-devuelven-200-vacío-cuando-el-periodo-no-existe-).

### El sobre (paso 1) 🟢

```json
{
  "descripcion" : "exito",
  "estado" : 200,
  "datos" : "https://opendata.aemet.es/opendata/sh/7334645a",
  "metadatos" : "https://opendata.aemet.es/opendata/sh/b3aa9d28"
}
```

| Campo | Notas |
|---|---|
| `descripcion` | `"exito"` en minúscula y **sin tilde** 🟢. El spec declara `"Éxito"` como valor por defecto: no coincide. No compares por esta cadena. |
| `estado` | El código real de la operación. **Es este el que hay que mirar**, no el HTTP. |
| `datos` | URL efímera `https://opendata.aemet.es/opendata/sh/<8 hex>` |
| `metadatos` | URL efímera con la descripción del producto |

### El paso 2 🟢

- **No requiere autenticación.** Devuelve lo mismo con y sin `api_key` (verificado: 13.621 bytes en
  ambos casos).
- Su `Content-Type` es **`text/plain;charset=ISO-8859-15`** incluso cuando el cuerpo es JSON.
- **Son URLs efímeras.** 🟢 A los 2,5 minutos siguen vivas; 🟡 se estima que duran minutos, no horas.

> [!IMPORTANT]
> ✅ **Decisión del proyecto: la URL `datos` se consume una vez y su contenido se persiste.**
> No se guarda la URL para reutilizarla, ni se referencia desde ningún sitio. Todo lo que venga de
> AEMET —datos y binarios— se descarga, se procesa y se almacena en nuestra base de datos o nuestro
> almacenamiento. Con eso la caducidad de la URL deja de ser un problema.

### Los metadatos merecen la pena 🟢

Traen `unidad_generadora`, `descripcion`, `periodicidad`, y el diccionario de campos con unidades.
El campo `periodicidad` es la **única fuente objetiva** para decidir el TTL de caché
(ver [`LIMITACIONES.md`](LIMITACIONES.md#periodicidad-real-de-actualización)).

Conviene consultarlos **una vez al documentar** un producto, no en cada petición: duplicarían el
consumo de cuota sin aportar nada nuevo.

---

## Los metadatos son el diccionario de datos 🟢

**La URL `metadatos` del sobre no es un extra: es la documentación real de cada producto.** Es la
única fuente del nombre, tipo, unidad y obligatoriedad de cada campo — nada de eso está en la
especificación OpenAPI.

### Estructura 🟢

```json
{
  "unidad_generadora": "Servicio de Observación",
  "periodicidad": "continuamente",
  "formato": "application/json",
  "copyright": "© AEMET. Autorizado el uso de la información…",
  "notaLegal": "https://www.aemet.es/es/nota_legal",
  "campos": [
    { "id": "ta", "descripcion": "Temperatura instantánea del aire … (grados Celsius)",
      "tipo_datos": "float", "requerido": false }
  ]
}
```

| Campo | Para qué sirve |
|---|---|
| **`campos[]`** | **El diccionario de datos.** `id`, `descripcion` (con **la unidad** entre paréntesis), `tipo_datos` y `requerido` |
| **`formato`** | **El formato REAL del producto** (`application/json`, `ascii/txt`…). Más fiable que el `Content-Type`, y desde luego más que la especificación |
| `periodicidad` | Cada cuánto se genera → base del TTL de caché |
| `copyright`, `notaLegal` | Textos de atribución obligatoria |
| `unidad_generadora` | Servicio de AEMET responsable |

### `requerido` importa mucho 🟢

En observación convencional, **de 39 campos solo 5 son `requerido: true`** (`idema`, `lon`, `lat`,
`alt`, `ubi`). Los 34 restantes **pueden no venir**, según el instrumental de cada estación.

Comprobado: la estación `1495` (Vigo/Peinador) devolvió 14 campos y **no incluía `ta`**, la
temperatura del aire. Dar por hecho cualquier campo opcional es un fallo esperando a ocurrir.

### En formatos de ancho fijo, trae las posiciones 🟢

Los productos `ascii/txt` añaden a cada campo un `posición_txt` con el rango de caracteres:

```json
{ "id": "SO2", "descripcion": "SO2 en microgramos/m3", "tipo_datos": "string",
  "requerido": true, "posicion_txt": "28-36" }
```

⚠️ **La clave viene con dos grafías distintas en el mismo documento**: `posición_txt` (con tilde) en
el primer campo y `posicion_txt` (sin tilde) en el resto. Hay que aceptar las dos.
Ver [`ERRATAS.md` E20](ERRATAS.md#e20-la-clave-posición_txt-de-los-metadatos-cambia-de-grafía-).

### ⚠️ Pero los metadatos también fallan 🟢

Son **la mejor** fuente del diccionario de datos, no una infalible:

| Problema | Ejemplo verificado |
|---|---|
| **Incompletos** | Playa declara 6 campos y no documenta el bloque `prediccion`; UVI declara 4 y omite `FECHA_MOD` y `CIUDAD` |
| **Entradas vacías** | Playa y UVI traen elementos de `campos[]` con `id: null` |
| **Campos duplicados** | En las normales, `ta_max_*` está listado dos veces |
| **Descripciones equivocadas** | `ta_max_*` descrito como "temperatura mínima absoluta" |
| **Contradicen el payload** | Playa define `localidad` como el municipio; devuelve `-29479` |
| **Codificación variable** | Los de climatologías diarias y mensuales son **UTF-8**; los de normales, extremos y observación, **ISO-8859-15** |
| **`tipo_datos` no es un vocabulario cerrado** | `datatime`, `dataTime`, `array de string`, `string (AAAA-MM-DD…)` |

Detalle en [`ERRATAS.md` E26–E30](ERRATAS.md#e26-en-las-normales-ta_max-está-duplicado-y-con-la-descripción-equivocada).

### Cómo usarlos

🟡 **Consúltalos una vez al documentar cada producto y vuelca el diccionario en el archivo del
módulo**, no en cada petición: duplicarían el consumo de cuota sin aportar nada nuevo (los metadatos
casi nunca cambian). Y **compara siempre con el payload real**: donde discrepen, manda el payload.

---

## Codificación: leer el `charset` de la cabecera

> [!CAUTION]
> **Es el fallo que se come a cualquiera que integre esta API sin avisar.**

La mayoría de la API responde en **ISO-8859-15** 🟢. Y `json_decode()` de PHP **exige UTF-8**: con
bytes ISO-8859-15 devuelve `null` **sin lanzar ninguna excepción**.

Traducido a Laravel: **`$response->json()` devuelve `null`** y parece que el endpoint no trae datos,
cuando la petición ha ido perfecta.

Verificado: 8 de los primeros 10 payloads fallan al decodificar como UTF-8, en el byte `0xed` — la
`í` de "Meteorología".

### Pero NO se puede convertir a ciegas 🟢

Hay productos que **ya vienen en UTF-8**. Convertirlos desde ISO-8859-15 los corrompe
(`Estación` → `EstaciÃ³n`):

| Producto | `charset` real |
|---|---|
| Mayoría de productos | `ISO-8859-15` |
| **Ozono total** | **`UTF-8`** |
| **Radiación solar** | **`UTF-8`** |
| **Resumen climatológico** | **`UTF-8`** |
| **Cuerpo del 429** | **`UTF-8`** |
| XML dentro del `tar` de avisos | `UTF-8` (declarado en el XML) |

### La regla correcta

**Leer el `charset` de la cabecera `Content-Type` y respetarlo.** Nunca fijar `ISO-8859-15` a mano.
Si no viene charset, 🟡 asumir `ISO-8859-15` como valor por defecto.

Y **no aplicar ninguna conversión de texto a los binarios** (GIF, PNG, ZIP, `tar`, PDF), aunque
declaren charset — que lo hacen: los GIF llegan como `image/gif;charset=ISO-8859-15`, un charset en
un binario, que no significa nada.

### La trampa 🟢

Dos productos verificados **decodifican como UTF-8 sin error**: observación convencional y
climatologías normales. No porque estén en UTF-8 — declaran `ISO-8859-15` — sino porque su contenido
es numérico y **no lleva ni un acento**, así que coincide con ASCII.

**Si pruebas la integración solo con esos, concluyes que la API es UTF-8** y el fallo aparece
después, con otro endpoint, en producción.

---

## Validación de respuestas

**Ni el código HTTP ni `$response->successful()` bastan.** Los cuatro casos verificados que lo
demuestran:

| Situación 🟢 | HTTP | Cuerpo |
|---|---|---|
| Todo bien | 200 | sobre con `estado: 200` y `datos` |
| Recurso inexistente | **200** | sobre con **`estado: 404`** y **sin `datos`** |
| Sin cabecera `api_key` | **200** | **vacío** |
| Ruta inexistente | 404 | **HTML** de Apache Tomcat, no JSON |
| Clave inválida o expirada | 401 | JSON `{descripcion, estado: 401}` |
| Límite superado | 429 | JSON `{descripcion, estado: 429}` |

### Orden de comprobaciones necesario

1. ¿El cuerpo está **vacío**? → falta la clave o está mal configurada.
2. ¿El cuerpo **empieza por `<`**? → es HTML de error: ruta inexistente o error del servidor.
3. ¿**Parsea** como JSON tras convertir la codificación? Si no, no es un producto JSON (puede ser
   texto plano, `tar` o una imagen: mira el archivo del módulo).
4. ¿`estado === 200`? Si no, gestiona ese código, no el HTTP.
5. ¿Existe la clave `datos`? Sin ella no hay segundo salto.
6. Tras el paso 2: ¿el contenido **está fresco**? Comprueba la fecha de elaboración
   (ver [`ERRATAS.md` A4](ERRATAS.md#a4-hay-endpoints-que-devuelven-datos-rancios-con-un-200-impecable-)).

Saltarse el paso 6 es lo que hace que se publique en la web una predicción de 2022 con toda la
apariencia de estar correcta.

---

## Formatos de respuesta

El spec declara `application/json` en los 64 endpoints. **Es falso**
([`ERRATAS.md` B1](ERRATAS.md#b1-el-spec-declara-applicationjson-en-los-64-endpoints-es-falso-)):

| Formato real | Endpoints | Detalle |
|---|---|---|
| **JSON** (servido como `text/plain`) | ~24 🟢 | Raíz normalmente `list`; **`dict` en UVI y valores extremos** |
| **Texto plano** | 22 🟢 | Todo `predicciones-normalizadas-texto`. Formato de teletipo, en mayúsculas |
| **CSV** (`;` con comillas) | 2 🟢 | Ozono total y radiación solar. **En UTF-8** |
| **GIF** | 5 🟢 | Rayos, mapas de análisis, satélite SST y NDVI, radar regional |
| **PNG** | 1 🟢 | Incendios previsto |
| **`tar` sin comprimir** | 2 🟢 | Avisos CAP (último y archivo) |
| **`gzip`** | 1 🟢 | Mensajes de observación (6,5 MB) |
| **ZIP** | 1 🟢 | Capas SHAPE |
| **PDF** | 1 🟢 | Balance hídrico — **y sin flujo de dos saltos** |
| Resto | 🔴 | Sin verificar |

⚠️ **Las imágenes no comparten formato** (5 GIF y 1 PNG) y **los archivos comprimidos tampoco**
(2 `tar` planos y 1 `gzip`). No asumas ninguno: comprueba el magic o el `Content-Type` real.

**Mira siempre el archivo del módulo antes de asumir el formato.**

---

## Formatos de fecha y parámetros

| Parámetro | Formato | Ejemplo |
|---|---|---|
| `fechaIniStr`, `fechaFinStr` | `AAAA-MM-DDTHH:MM:SSUTC` | `2026-03-01T00:00:00UTC` |
| `fecha` | `AAAA-MM-DD` | `2026-08-26` |
| `anio`, `anioIniStr`, `anioFinStr` | `AAAA` | `2026` |
| `mes` | `mm` | `03` |
| `decena` | `01`–`36` | `04` |

### Precauciones

- **Los `:` de las fechas largas se codifican como `%3A`** en la URL. La FAQ oficial lo muestra así:
  `fechaini/2026-03-01T00%3A00%3A00UTC/`. 🟡 Sin codificar probablemente también funcione, pero no
  está verificado: codifícalos.
- El sufijo es el literal `UTC`, no un desplazamiento (`+00:00`) ni una `Z`.
- **Los códigos con ceros a la izquierda son cadenas.** `01` (Álava), `09` (Campisábalos), `071`
  (Menorca). Convertirlos a entero los destruye.
- **Los códigos de provincia mezclan 2 y 3 dígitos**: las islas usan tres (`353` Gran Canaria).
- `area` y `dia` significan **cosas distintas según el endpoint**. No los modeles como un tipo
  único ([`ERRATAS.md` C3 y C4](ERRATAS.md#c3-area-reutiliza-el-mismo-nombre-para-5-dominios-incompatibles-)).

Catálogos completos en [`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md).

---

## Estrategia de consumo recomendada

🟡 Inferido de los límites medidos y de la recomendación oficial de AEMET.

```
   RSS/ATOM (¿ha cambiado?)  ──no──▶  no hacer nada
            │ sí
            ▼
   PASO 1 con api_key  ──429/5xx──▶  backoff exponencial a ciegas
            │ estado 200
            ▼
   PASO 2 sin api_key
            │
            ▼
   convertir ISO-8859-15 → UTF-8
            │
            ▼
   validar frescura del contenido
            │
            ▼
   persistir en BD / caché  ──▶  la web lee de aquí, NUNCA de AEMET
```

Reglas que se derivan:

1. **La web nunca llama a AEMET.** Lee de base de datos. Un proceso en segundo plano alimenta ese
   almacén. **Ni las URLs `datos` se reutilizan**: son efímeras, se consumen y se descartan.
2. **Consultar el RSS antes del endpoint** cuando el producto tenga feed: es lo que AEMET
   recomienda para no chocar con el límite. Ver [`11-rss-y-sincronizacion.md`](11-rss-y-sincronizacion.md).
3. **TTL según la periodicidad real** del producto, no según lo que apetezca refrescar.
4. **Reintentar solo 429 y 5xx**, con backoff exponencial y sin `Retry-After` que respetar.
5. **Espaciar y rotar** entre familias de endpoints.
6. **Registrar siempre qué falló y con qué `estado`.** Un `null` silencioso por codificación y un
   429 son problemas opuestos y no se distinguen si no se registran.

---

## Un ejemplo completo, de principio a fin 🟢

Verificado el 2026-08-26.

**Paso 1:**

```bash
curl -s -H "api_key: $AEMET_API_KEY" -H "Accept: application/json" \
  "https://opendata.aemet.es/opendata/api/prediccion/especifica/municipio/diaria/36057"
```

```json
{
  "descripcion" : "exito",
  "estado" : 200,
  "datos" : "https://opendata.aemet.es/opendata/sh/7334645a",
  "metadatos" : "https://opendata.aemet.es/opendata/sh/b3aa9d28"
}
```

**Paso 2** (sin autenticación, y convirtiendo la codificación):

```bash
curl -s "https://opendata.aemet.es/opendata/sh/7334645a" | iconv -f ISO-8859-15 -t UTF-8
```

```json
[ {
  "origen" : {
    "productor" : "Agencia Estatal de Meteorología - AEMET. Gobierno de España",
    "web" : "https://www.aemet.es",
    "enlace" : "https://www.aemet.es/es/eltiempo/prediccion/municipios/..."
  },
  "elaborado" : "...",
  "nombre" : "...",
  "provincia" : "...",
  "prediccion" : { "dia" : [ ... ] },
  "id" : ...,
  "version" : ...
} ]
```

Fíjate en tres cosas: la raíz es una **lista de un elemento**; sin el `iconv` el `json_decode`
habría devuelto `null`; y el campo `elaborado` es el que hay que mirar para saber si el dato está
fresco.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
