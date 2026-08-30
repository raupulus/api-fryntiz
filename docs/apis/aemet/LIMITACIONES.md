# 🚧 Limitaciones y condiciones de AEMET OpenData

> [!CAUTION]
> **Lectura obligatoria antes de diseñar cualquier automatismo** (comando programado, job, cron,
> sincronización, widget que refresque solo). Estos límites no son recomendaciones: son el marco
> dentro del que hay que diseñar. Descubrirlos en producción significa un servicio caído.

- **Fecha de la última verificación en vivo:** 2026-08-26
- Leyenda: 🟢 verificado con petición real · 🔵 afirmado por AEMET sin comprobar · 🟡 inferido · 🔴 sin verificar


> **Fuentes de este archivo:** `src/catalogos/faqs.json` (FAQ 4.5 límite de uso, 2.8–2.12 caducidad de la clave, 5.1–5.3 retrasos y productos ausentes) · `src/web-texto/novedades.txt` (changelog: caducidad de claves e incidencias) · `src/web-texto/nota_legal.txt` (condiciones) · **URLs `metadatos`** de 27 productos (periodicidades) · **verificación en vivo del 2026-08-26** (cabecera `Remaining-request-endpoint`, tamaños medidos y comportamiento del 429).
---

## Límites de uso

### 🎯 La API expone un contador de cuota que no documenta 🟢

**`Remaining-request-endpoint`** es una cabecera de respuesta, presente en las respuestas correctas,
que dice **cuántas peticiones quedan para ese endpoint**. No aparece en la especificación, ni en las
FAQs, ni en ninguna documentación de AEMET. Es el mecanismo con el que se debe gestionar la cuota.

```http
HTTP/1.1 200 OK
Remaining-request-endpoint: 39
Content-Type: application/json;charset=ISO-8859-15
```

### El cubo es POR PLANTILLA DE ENDPOINT, no global 🟢

Medido con 4 llamadas consecutivas al mismo endpoint y una quinta cambiando el parámetro:

| Llamada | Ruta | `Remaining-request-endpoint` |
|---|---|---|
| 1 | `/api/maestro/municipio/id36057` | **39** |
| 2 | `/api/maestro/municipio/id36057` | **38** |
| 3 | `/api/maestro/municipio/id36057` | **37** |
| 4 | `/api/maestro/municipio/id36057` | **36** |
| 5 | `/api/maestro/municipio/id28079` ← **otro parámetro** | **35** |

Conclusiones 🟢:

1. **El cubo por defecto es de 40 peticiones.** La primera llamada deja 39.
2. **Se contabiliza por plantilla de endpoint** (`/api/maestro/municipio/{municipio}`), **no por
   URL concreta**: cambiar el parámetro no da un cubo nuevo.
3. **Los "40 por minuto" de la FAQ son por endpoint, no un agregado global.** La FAQ induce a error.
4. **Endpoints distintos tienen cubos independientes.** Con un endpoint agotado, otros responden
   `200` con normalidad.

### El tamaño del cubo varía mucho según el endpoint 🟢

| Endpoint | Cubo observado |
|---|---|
| `/api/maestro/municipio/{municipio}` | **40** |
| `/api/prediccion/especifica/municipio/horaria/{municipio}` | **15** |
| `/api/prediccion/especifica/municipio/{diaria,horaria}/todos` | **13** y **15** |
| `/api/productos/climatologicos/balancehidrico/{anio}/{decena}` | **~15** |

🟡 Los productos pesados tienen cubos más pequeños. Es lo que la FAQ 4.5 llama "restricciones
adicionales" en determinados recursos, sin concretar cuáles. **No supongas 40: lee la cabecera.**

### ⚠️ Pero el contador NO garantiza la siguiente petición 🟢

```
GET …/municipio/horaria/36057,28079  → 200 · Remaining-request-endpoint: 15
GET …/municipio/horaria/36057        → 429   (6 segundos después)
```

Decía que quedaban 15 y la siguiente falló. 🟡 **Hay al menos dos límites solapados**: el que refleja
la cabecera (ventana larga) y otro más corto que no se expone en ningún sitio.

**`rem` sirve para no agotar la cuota diaria, no para saber si puedes pedir ahora.** Hay que
combinarlo con espaciado y con tolerancia al 429.

### La cuota va ligada a la IP, no solo a la clave

Con el cubo de `/municipio/diaria/{municipio}` agotado, se probó una **API Key recién generada, de
otra cuenta, que nunca había llamado a ese endpoint**: **429 inmediato**.

🟡 El contador es por **IP + endpoint**, o hay un límite de IP superpuesto. Consecuencias:

- **Generar otra clave no desbloquea un endpoint agotado.**
- Varios procesos, workers o entornos en la **misma IP comparten cuota**. Si hay staging y producción
  en el mismo servidor, se pisan.
- 🔴 Sin determinar el mecanismo exacto. **No se sondeará a propósito.**

### La recuperación es MUY lenta 🟢

⚠️ La familia `/api/prediccion/especifica/municipio/*` quedó agotada y **seguía devolviendo 429 más
de una hora después**, incluso con 5 s entre peticiones. El mensaje del 429 dice "Vuelva a intentarlo
el próximo minuto", pero **eso no se cumplió**.

🔴 Ventana real de recuperación sin determinar. 🟡 Podría ser diaria en algunos endpoints. **No se
sondeará a propósito**: sería abusar de un servicio público gratuito.

### El 429 no dice cuándo reintentar 🟢

Volcado completo de cabeceras de varios 429 reales: **no hay `Retry-After`**, ni `RateLimit-Limit`,
ni `RateLimit-Remaining`, ni `RateLimit-Reset`, **ni `Remaining-request-endpoint`** (la cabecera útil
desaparece justo cuando la necesitarías). Solo `Date`, `Content-Type`, `Cache-Control`, `Pragma`,
`Expires`, `Connection`, `Vary` y `Set-Cookie`.

Cuerpo del 429 🟢:

```json
{
  "descripcion" : "Se ha alcanzado uno de los límites de uso. Vuelva a intentarlo el próximo minuto.",
  "estado" : 429
}
```

⚠️ Su `Content-Type` es `text/plain; charset=UTF-8`, distinto del `ISO-8859-15` del resto.

### Comportamiento observado con espaciado 🟢

| Prueba | Resultado |
|---|---|
| ~10 peticiones en ráfaga sin pausa | **429** |
| 23 peticiones con 5 s de pausa, rotando 10 familias (~12 pet./min) | **0 errores** |
| 19 + 23 + 19 + 12 + 10 peticiones en tandas de 5 s con pausa de 60 s cada 10 | **0 errores**, salvo la familia ya agotada |

### Consecuencias para el diseño

1. **Leer `Remaining-request-endpoint` en cada respuesta** y frenar cuando baje de un umbral (p. ej.
   5). Es la mejor señal de cuota disponible, pero ⚠️ **no garantiza que la siguiente petición
   funcione**: hay un segundo límite más corto que no se expone.
2. **Contabilizar por plantilla de endpoint**, no por URL. Y **no asumir 40**: el cubo de
   `/municipio/horaria/{municipio}` es de **15**. Iterar municipios sobre el endpoint individual se
   agota enseguida — para más de ~15 localizaciones, usar el agregado `/todos`
   ([`01-predicciones-municipios.md`](01-predicciones-municipios.md#-los-endpoints-de-municipio-no-aceptan-lotes-)).
3. **Espaciar y rotar entre familias.** 5 s entre peticiones se ha comprobado seguro.
4. **Backoff exponencial largo y a ciegas.** No hay `Retry-After`, y "el próximo minuto" del mensaje
   no se cumple: asume minutos u horas, no segundos. Reintentar solo en **429 y 5xx**.
5. **Cachear de forma agresiva**, con TTL basado en la periodicidad real (ver más abajo).
6. **Usar los feeds RSS** para detectar cambios en lugar de sondear
   ([`11-rss-y-sincronizacion.md`](11-rss-y-sincronizacion.md)).
7. **Nunca llamar a AEMET dentro de una petición HTTP de usuario.** Dos saltos, sin `Retry-After`,
   con payloads de hasta 6,5 MB: la latencia no es acotable.
8. **Un endpoint agotado no debe tumbar el resto del proceso.** Los cubos son independientes: si
   falla uno, los demás siguen sirviendo.

---

## Caducidad de la API Key

### La clave expira 🟢

Es un **JWT** (verificado: una clave malformada devuelve
`"JWT strings must contain exactly 2 period characters"`), y su `payload` incluye `exp`.

Clave actual del proyecto (`AEMET_API_KEY` en `.env`):

| Campo | Valor             |
|---|-------------------|
| `iss` | `AEMET`           |
| `sub` | `info@dominio.es` |
| Emitida (`iat`) | **2026-08-26**    |
| Expira (`exp`) | **2026-12-04**    |
| Validez real | **100 días**      |

⚠️ La FAQ 2.9 dice "3 meses"; el JWT real dice **100 días**. Fiarse del `exp`, no de la FAQ.

### Qué pasa al caducar 🔵

FAQ 2.10: la clave deja de funcionar y la API responde

```json
{
  "descripcion": "La API Key ha expirado. Genere una nueva API Key.",
  "estado": 401
}
```

No hay trámite: se genera otra en <https://opendata.aemet.es/centrodedescargas/altaUsuario>
y se sustituye. **No hay límite de renovaciones** y se puede generar la nueva antes de que caduque
la actual.

### Fecha límite de las claves antiguas 🔵

Changelog oficial (20/07/2026) y FAQ 2.8: **a partir del 15 de octubre de 2026**, las API Keys
emitidas *sin* fecha de expiración dejan de aceptarse (→ 401). No afecta a la clave actual del
proyecto, que ya tiene `exp`.

### Consecuencias para el diseño

- La renovación es **trabajo de mantenimiento recurrente cada ~3 meses**, no un alta única. Hay que
  preverlo: aviso al equipo antes del `exp`, y un 401 debe generar una alerta accionable
  ("renovar clave"), no un log perdido.
- `AEMET_API_KEY_EXPIRES_AT` está en `.env` justo para poder avisar con antelación.
- Una integración que no contemple la caducidad **se caerá sola** a los tres meses de desplegarla.

---

## Periodicidad real de actualización

Valores 🟢 del campo `periodicidad` de los metadatos de **27 productos** (2026-08-26).
**Es la base objetiva para el TTL de caché**: refrescar más a menudo solo gasta cuota.

| Producto | `periodicidad` literal de AEMET | TTL 🟡 |
|---|---|---|
| **Radar regional** | "cada 10 minutos" | 10 min |
| Avisos CAP | "Disponible en cualquier momento en el que se emite un fenómeno meteorológico adverso, con horas preferentes: 09:00, 11:…" | 15–30 min |
| Observación convencional | "continuamente" | 30–60 min |
| Mensajes de observación | "Horaria" | 1 h |
| Contaminación de fondo | "Cada 1h" | 1 h |
| Mapa de rayos | "Cada seis horas o 00Z, 06Z, 12Z, 18Z" | 3 h |
| Municipio, diaria | "Cuatro veces al día que afectan a todas las variables, excepto a las temperaturas máxima y mínima, que pueden actualizarse más a menudo" | 3 h |
| Municipio, horaria | "Cuatro veces al día" | 3 h |
| Marítima costera | "Dos veces al día (12:00 y 20:00) h.o.p" | 6 h |
| Marítima alta mar | "Dos veces al día (08:00 y 20:00) UTC" | 6 h |
| Playa | "Dos veces al día" | 6 h |
| **Mapas de análisis** | "Dos veces al día, a las **02:00 y 14:00 h.o.p. en invierno y a las 03:00 y 15:00 en verano**" | 6 h |
| Nivológica | "Disponible en **periodo de campaña**. Diaria a las 18:00 h.o.p." | 12 h (en temporada) |
| Montaña | "Una vez al día" | 12 h |
| UVI | "Una vez al día" | 12 h |
| Satélite SST y NDVI | "1 vez al día" | 12 h |
| Incendios previsto | "diario" | 12 h |
| Radiación solar | "Cada 24h" | 12 h |
| Ozono total | "Cada 24 h" | 12 h |
| Valores extremos | "1 vez al día" | 24 h |
| Climatologías normales | "1 vez al día" | 24 h (o mucho más: periodo fijo 1991-2020) |
| Climatologías mensuales | "1 vez al día" | 24 h |
| **Climatologías diarias** | "1 vez al día, **con un retardo de 4 días**" | 24 h |
| Inventario de estaciones | "1 vez al día" | días |
| Perfil vertical de ozono | "Cada 7 días" ⚠️ observado con 28 días de retraso | días |
| Capas SHAPE | "anual" | meses |
| **Datos de la Antártida** | **"anualmente"** | meses |
| Maestro de municipios | ⚠️ `periodicidad: null` — no la declara | 🟡 meses (datos administrativos) |

### Precauciones sobre las horas 🟢

| Producto | Referencia horaria |
|---|---|
| Marítima costera, mapas de análisis, nivológica | **hora oficial peninsular** (h.o.p.) |
| Marítima alta mar, mapa de rayos, observación (`fint`) | **UTC** |
| **Radiación solar** | ⚠️ **hora solar verdadera** |
| Contaminación de fondo | UTC |

⚠️ **Los mapas de análisis cambian de hora con el horario de verano.** Un cron fijo pide el producto
antes de que exista la mitad del año.

⚠️ **La radiación solar usa hora solar verdadera**, que no coincide con UTC ni con la hora local en
ningún momento del año.

---

## Retrasos y disponibilidad de datos

| Limitación | Detalle |
|---|---|
| **Climatologías diarias: ~4 días de retraso** 🔵 | FAQ 5.2. No se pueden usar para "ayer". |
| **Solo estaciones validadas** 🔵 | FAQ 5.1: en valores climatológicos solo aparecen las estaciones con datos validados. Que una estación exista físicamente no implica que esté en la API. |
| **Avisos: laguna histórica reconocida** 🔵 | Aviso oficial del 14/05/2026: hueco en la disponibilidad de datos de avisos, "que se irá rellenando". |
| **Archivo de avisos desde 18/06/2018** 🔵 | El endpoint `avisos_cap/archivo` no tiene datos anteriores. |
| **Climatologías normales: periodo 1991-2020** 🔵 | Es el periodo de referencia fijo del endpoint, no una ventana móvil. |
| **Mapas significativos: "hasta el 22/01/2020"** 🔵 | La propia descripción del spec lo dice. 🔴 Sin verificar si el endpoint sigue devolviendo algo. |
| **Datos rancios sin avisar** 🟢 | Endpoints que devuelven `200 OK` con contenido de años atrás. Ver [`ERRATAS.md` A4](ERRATAS.md#a4-hay-endpoints-que-devuelven-datos-rancios-con-un-200-impecable-). |

---

## Qué NO ofrece AEMET OpenData

Para no perder tiempo buscándolo:

| Ausente | Detalle |
|---|---|
| **Salidas de modelos numéricos** 🔵 | HARMONIE-AROME, Centro Europeo (ECMWF), modelos de polvo. FAQ 5.3: no son datos abiertos. Se piden por la sede electrónica, con coste. |
| **Formatos a medida** 🔵 | FAQ 1.7: no hay adaptaciones (otro formato, otro recorte geográfico, otros umbrales). O lo programas tú, o lo pides con coste de gestión. |
| **Datos no incluidos en la resolución de precios públicos** 🔵 | El catálogo abierto es el del Anexo II de la resolución de 30/12/2015 (BOE nº 4 de 05/01/2016). Lo demás es de pago. |
| **Estaciones no validadas** 🔵 | Ver arriba. |
| **Webhooks / push** 🟡 | No existen. Lo más parecido son los feeds RSS/ATOM. |
| **Paginación** 🟡 | No hay. Los endpoints "todas / todos" devuelven el conjunto entero de una vez (las climatologías normales de una sola estación ya son 139 KB 🟢). |

---

## Volumen de las respuestas

Medido 🟢 el 2026-08-26. Relevante para memoria, timeouts y almacenamiento.

| Producto | Tamaño |
|---|---|
| Ozono total (CSV) | 285 B |
| Predicción provincial (texto) | 327 B |
| Inventario de 2 estaciones | 383 B |
| Maestro de un municipio | 350 B |
| Nivológica | 588 B |
| Predicción CCAA pasado mañana (texto) | 692 B |
| Predicción nacional tendencia (texto) | 281 B |
| Predicción CCAA medio plazo (texto) | 1,2 KB |
| Valores extremos de una estación | 1,8 KB |
| Montaña | 2,0 KB |
| Climatologías diarias, 1 estación × 4 días | 2,4 KB |
| Predicción nacional (texto) | 2,7 KB |
| Marítima costera | 3,0 KB |
| Playa | 3,8 KB |
| Marítima alta mar | 5,1 KB |
| Observación de una estación | 5,4 KB |
| UVI | 5,5 KB |
| Climatologías mensuales, 1 estación × 1 año | 10,6 KB |
| **Capas SHAPE (ZIP)** | **11,7 KB** |
| Municipio, predicción diaria | 13,6 KB |
| Mapa de rayos (GIF) | 13,3 KB |
| Radiación solar (CSV) | 20,6 KB |
| Radar regional (GIF) | 23,1 KB |
| Municipio, predicción horaria | 30,0 KB |
| Satélite SST (GIF) | 100,7 KB |
| Mapas de análisis (GIF) | 117,8 KB |
| **Climatologías normales, 1 estación** | **139,7 KB** |
| **Inventario de estaciones completo** | **167,1 KB** |
| **Satélite NDVI (GIF)** | **246,2 KB** |
| **Antártida, 1 estación × 5 días** | **280,2 KB** |
| **Climatologías diarias, todas las estaciones × 1 día** | **402,8 KB** |
| **Avisos CAP de una CCAA (`tar`, 56 XML)** | **490 KB** |
| **Incendios, mapa previsto (PNG)** | **501,4 KB** |
| **Perfil de ozono (texto)** | **612,7 KB** |
| **Maestro de TODOS los municipios** | **3,0 MB** |
| **Observación de TODAS las estaciones** | **3,6 MB** |
| **Avisos CAP de España** | **3,4 MB** |
| **Avisos CAP, archivo de 1 día** | **3,8 MB** |
| **Balance hídrico (PDF)** | **4,6 MB** |
| **Mensajes SYNOP (`gzip`)** | **6,5 MB** |
| **Municipios, predicción diaria de TODOS (`gzip`)** | **2,2 MB** → **73,9 MB descomprimido** |
| **Municipios, predicción horaria de TODOS (`gzip`)** | **6,5 MB** → **142,9 MB descomprimido** |

### Reglas que se derivan 🟡

- **Los agregados hay que traerlos en segundo plano y persistirlos**, nunca en caliente.
- **Nada de esto cabe cómodamente en memoria si se procesa en bruto.** Los de varios MB conviene
  procesarlos en flujo (streaming) o al menos con un límite de memoria previsto.
- **`Content-Length` viene informado** en los grandes, así que se puede decidir antes de descargar.
- ⚠️ **Descarga ≠ memoria.** Los agregados de municipio transfieren 2,2 y 6,5 MB pero se expanden a
  **73,9 y 142,9 MB** (ratios de 32× y 21×). Descomprimir de golpe en memoria es desaconsejable:
  hay que extraer del `tar` fichero a fichero. Son **8.124 ficheros JSON** cada uno.
- 🟢 **Los productos pesados tienen cubos de cuota más pequeños**: los agregados de municipio marcaron
  `Remaining-request-endpoint` de **13** y **15** en la primera llamada, no 39.

---

## Condiciones legales

Resumen. Detalle en [`12-uso-legal-y-atribucion.md`](12-uso-legal-y-atribucion.md).

| Condición | Obligación |
|---|---|
| **Citar a AEMET** 🔵 | Obligatorio. `"Fuente: AEMET"` o el texto largo alternativo. |
| **Indicar fecha de actualización** 🔵 | Obligatorio cuando el documento original la incluya. |
| **No sugerir patrocinio** 🔵 | No se puede insinuar que AEMET participa o apoya nuestro uso. |
| **Conservar los metadatos** 🔵 | Hay que preservar los metadatos de fecha y condiciones de reutilización. |
| **Uso comercial permitido** 🔵 | La reutilización comercial está autorizada. |
| **AEMET no se responsabiliza** 🔵 | Ni del uso, ni de la interpretación, ni de perjuicios derivados. |

---

## Cómo añadir una limitación

1. Indica **la fuente**: FAQ concreta, changelog con fecha, o medición propia con la evidencia.
2. Marca la fiabilidad y, si es medición, la fecha.
3. Añade siempre **la consecuencia para el diseño**. Una limitación sin consecuencia práctica es
   un dato inútil.
4. Actualiza la fecha de verificación de la cabecera.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
