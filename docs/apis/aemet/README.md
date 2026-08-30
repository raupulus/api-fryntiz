# 🌦️ AEMET OpenData — Referencia técnica

Documentación destilada de la API REST oficial de la **Agencia Estatal de Meteorología**.
Índice de esta carpeta y normas de uso obligatorias.

- **Base URL:** `https://opendata.aemet.es/opendata`
- **Especificación:** OpenAPI 3.0.1, `info.version = 2.0` — **64 endpoints, todos `GET`**
- **Autenticación:** API Key (JWT) en cabecera `api_key` → variable `AEMET_API_KEY`
- **Documentación oficial:** <https://opendata.aemet.es/dist/index.html>
- **Obtener / renovar clave:** <https://opendata.aemet.es/centrodedescargas/altaUsuario>
- **Última verificación contra la API real:** **2026-08-26** — **los 64 endpoints (100 %)** ✅

> [!TIP]
> Este directorio documenta **la API oficial de AEMET**. Para saber **cómo la usa Sansimar**
> (servicios, comandos, modelos, caché), ver `docs/info/apis/`. Ver la
> [distinción entre ambos](../README.md#no-confundir-con-docsinfoapis).


> **Fuentes:** este archivo es el índice; cada archivo declara las suyas al principio. El inventario completo de originales está en **[`src/_MANIFEST.md`](src/_MANIFEST.md)**.
---

## 🚨 Normas de uso — OBLIGATORIAS

### 1. Nunca configurar nada a partir de la especificación sin verificarlo

**La especificación de AEMET no describe fielmente lo que devuelve la API.** No es una precaución
teórica: está medido. El spec declara `application/json` en los 64 endpoints y **22 devuelven texto
plano**; declara respuestas de error en JSON y **los 404 devuelven HTML de Tomcat**; y hay endpoints
que responden `200 OK` con **datos de hace cuatro años**.

Antes de implementar o modificar cualquier endpoint:

1. **Haz la petición real** y mira el `Content-Type`, la codificación y la forma del cuerpo.
2. **Comprueba la frescura del contenido**, no solo el código HTTP.
3. **Anota lo observado** en el archivo de módulo correspondiente con marca 🟢 y fecha.

Un endpoint marcado 🔴 **no está verificado**: trátalo como desconocido, no como funcional.

### 2. Consulta [`ERRATAS.md`](ERRATAS.md) antes de tocar cualquier endpoint

Recoge agrupados **todos** los errores conocidos, en seis bloques por gravedad:

| Bloque | Contenido |
|---|---|
| **A** | Erratas que **rompen la integración sin lanzar ningún error** (codificación, `estado: 404` dentro de un 200, datos rancios) |
| **B** | La especificación miente sobre el formato de respuesta |
| **C** | Erratas de contenido de la especificación (códigos duplicados, parámetros con el mismo nombre y distinto significado) |
| **D** | Cosas que la API hace y la especificación no cuenta |
| **E** | Inconsistencias dentro de los propios payloads |
| **F** | Pendientes de confirmar |

**Es lectura previa, no de consulta.**

### 3. Consulta [`LIMITACIONES.md`](LIMITACIONES.md) antes de diseñar cualquier automatismo

Recoge límites de uso, caducidad de la clave, retrasos de publicación y productos no disponibles.
Cualquier tarea programada, comando o job que llame a AEMET debe diseñarse **contra esos límites**,
no descubrirlos en producción.

### 4. Toda afirmación va marcada y fechada

| Marca | Significado |
|---|---|
| 🟢 **Verificado** | Comprobado con petición real. Se indica fecha y parámetros usados. |
| 🔵 **Oficial** | Lo dice AEMET (spec, FAQ, changelog) pero **no lo hemos comprobado**. |
| 🟡 **Inferido** | Deducción nuestra, con el razonamiento explicado. |
| 🔴 **Sin verificar** | Pendiente. **No implementar sobre esto.** |
| ⚠️ **Errata** | La fuente oficial está mal. Detalle en [`ERRATAS.md`](ERRATAS.md). |

### 5. `src/` no se toca y no se lee de rutina

[`src/`](src/_MANIFEST.md) guarda las fuentes oficiales originales (especificación, FAQs, catálogos,
páginas web). **Nunca se editan.** Existen para auditar o regenerar esta documentación, no para
consultarlas a diario: para eso están los archivos de esta carpeta.

### 6. La clave nunca se escribe aquí

La API Key vive en `.env` (`AEMET_API_KEY`). En documentación se cita la variable, jamás el valor.
La clave **caduca**: ver [`LIMITACIONES.md`](LIMITACIONES.md#caducidad-de-la-api-key).

---

## 📑 Índice de archivos

**Empieza siempre por [`00-fundamentos.md`](00-fundamentos.md).** Sin entender el flujo de dos pasos
y el problema de codificación, ningún archivo de endpoints te servirá.

| Archivo | Contenido | Endpoints |
|---|---|---|
| [`00-fundamentos.md`](00-fundamentos.md) | **Lectura obligatoria.** Autenticación, flujo de dos pasos, codificación ISO-8859-15, envelope, códigos de error reales, estrategia de reintentos y caché, formatos de fecha | — |
| [`ERRATAS.md`](ERRATAS.md) | **Lectura obligatoria.** Errores de la especificación y desviaciones medidas | — |
| [`LIMITACIONES.md`](LIMITACIONES.md) | **Lectura obligatoria.** Límites de uso, caducidad de clave, retrasos, productos ausentes | — |
| [`01-predicciones-municipios.md`](01-predicciones-municipios.md) | Predicción diaria y horaria por municipio, y maestro de municipios | 6 |
| [`02-predicciones-texto.md`](02-predicciones-texto.md) | Predicciones normalizadas en **texto plano**: nacional, CCAA y provincia | 22 |
| [`03-predicciones-especificas.md`](03-predicciones-especificas.md) | Montaña, nivológica, playa y radiación ultravioleta | 5 |
| [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md) | Avisos de fenómenos adversos (CAP) e índices de incendios | 4 |
| [`05-observacion.md`](05-observacion.md) | Observación convencional y mensajes SYNOP / TEMP / CLIMAT | 3 |
| [`06-climatologia.md`](06-climatologia.md) | Valores climatológicos y productos climatológicos | 10 |
| [`07-maritima.md`](07-maritima.md) | Predicción marítima costera y de alta mar | 2 |
| [`08-imagenes-y-mapas.md`](08-imagenes-y-mapas.md) | Mapas y gráficos, radares, rayos y satélite | 7 |
| [`09-redes-especiales.md`](09-redes-especiales.md) | Ozono, radiación, contaminación de fondo y Antártida | 5 |
| [`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md) | Todas las tablas de códigos (CCAA, provincias, áreas, radares, costas…) | — |
| [`11-rss-y-sincronizacion.md`](11-rss-y-sincronizacion.md) | Los 41 canales RSS/ATOM y el patrón oficial para no chocar con el límite | — |
| [`12-uso-legal-y-atribucion.md`](12-uso-legal-y-atribucion.md) | Condiciones de reutilización y obligación de citar a AEMET | — |
| [`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md) | Los 35 códigos de `estadoCielo` y su mapeo a iconos sin dependencias | — |
| [`14-zonas-de-aviso.md`](14-zonas-de-aviso.md) | Las 233 zonas de aviso meteorológico con código, nombre y provincia | — |
| [`15-recursos-y-mantenimiento.md`](15-recursos-y-mantenimiento.md) | Obtención de la clave, HATEOAS, clientes oficiales, y **cómo enterarse de que la API ha cambiado** | — |
| [`DOCUMENTACION-TERCEROS.md`](DOCUMENTACION-TERCEROS.md) | Afirmaciones de fuentes ajenas contrastadas: qué aciertan, qué no y qué es peligroso | — |

Los endpoints suman **64**, que es el total de la especificación.

---

## 🧭 Qué leer según la tarea

| Necesito… | Leer |
|---|---|
| Empezar a integrar AEMET desde cero | `00-fundamentos.md` + `ERRATAS.md` + `LIMITACIONES.md` |
| El tiempo de una localidad para el frontal | `00-fundamentos.md` + `01-predicciones-municipios.md` |
| Mostrar alertas / avisos meteorológicos | `00-fundamentos.md` + `04-avisos-y-riesgos.md` |
| Saber qué código usar para una provincia, radar, costa… | `10-catalogos-de-codigos.md` |
| Diseñar un comando programado o un job | `LIMITACIONES.md` + `11-rss-y-sincronizacion.md` |
| Publicar datos de AEMET en la web | `12-uso-legal-y-atribucion.md` |
| Pintar el icono del tiempo | `13-iconos-estado-cielo.md` |
| Filtrar avisos por municipio o por coordenadas | `04-avisos-y-riesgos.md` + `14-zonas-de-aviso.md` |
| Saber qué campos trae un producto y en qué unidades | `00-fundamentos.md` (sección de metadatos) + el módulo del producto |
| Renovar la API Key o saber si AEMET ha cambiado algo | `15-recursos-y-mantenimiento.md` |
| Me han pasado un informe/documento sobre esta API | `DOCUMENTACION-TERCEROS.md` |
| Depurar un `null` o un error raro | `ERRATAS.md` + `00-fundamentos.md` |
| Datos históricos o series climáticas | `06-climatologia.md` |

---

## ⚡ Resumen ejecutivo — las siete cosas que hay que saber

Detalle completo en `00-fundamentos.md`, `ERRATAS.md` y `LIMITACIONES.md`.

1. **Toda petición son dos peticiones.** El endpoint devuelve un sobre con una URL; los datos están
   en esa URL. El segundo salto **no** lleva autenticación. ⚠️ Con **una excepción conocida**:
   `balancehidrico` devuelve el PDF directamente.
2. **La API responde en ISO-8859-15… salvo algunos productos que vienen en UTF-8.** `json_decode()`
   (y `$response->json()` de Laravel) devuelve **`null` en silencio** con los primeros, y convertir
   los segundos los corrompe. **Hay que leer el `charset` de la cabecera `Content-Type`.**
3. **`200 OK` no significa que haya datos.** El sobre puede traer `"estado": 404`, y hay endpoints
   que sirven contenido de hace **cuatro años** con un 200 impecable.
4. **La cuota se gestiona con `Remaining-request-endpoint`**, una cabecera indocumentada: **40
   peticiones por plantilla de endpoint** (menos en productos pesados). Los "40 por minuto" de la
   FAQ no son un agregado global. El 429 **no trae `Retry-After`** y la recuperación tarda **más de
   una hora**.
5. **No todo es JSON.** 22 endpoints devuelven texto plano, 5 GIF, 1 PNG, 2 `tar` sin comprimir,
   **3 `gzip`**, 1 ZIP, 1 PDF de 4,6 MB, 2 CSV y 1 texto de ancho fijo. ⚠️ **El `Content-Type` no
   permite distinguir el `tar` plano del `gzip`**: hay que comprobar el magic.
6. **La URL `metadatos` del sobre es el diccionario de datos.** Trae `campos[]` con nombre, tipo,
   unidad y **obligatoriedad** de cada campo, más el `formato` real y la `periodicidad`. Nada de eso
   está en la especificación. Recogidos los de **27 productos**. ⚠️ Pero **también fallan**: hay
   diccionarios incompletos (playa, UVI, municipio), vacíos (maestro), con campos duplicados
   (normales) y con `campos` como cadena en vez de array (avisos, capas SHAPE).
7. **Hay endpoints documentados que nunca devuelven nada**: radar nacional, mapas significativos,
   incendios *estimado*, y los productos de texto `hoy` y `tendencia` responden `estado: 404` o
   sirven datos de años atrás. ⚠️ **Y varía por parámetro**: las predicciones provinciales de Galicia
   están congeladas en 2022 mientras Cádiz devuelve el mismo día. **Verifica el parámetro concreto
   que vayas a usar.**

> [!TIP]
> Si alguien te pasa un informe o documentación sobre esta API, contrástalo con
> [`DOCUMENTACION-TERCEROS.md`](DOCUMENTACION-TERCEROS.md) antes de aplicarlo. Las dos fuentes
> analizadas hasta ahora traían endpoints inexistentes, nombres de campo erróneos y un consejo de
> seguridad peligroso.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
