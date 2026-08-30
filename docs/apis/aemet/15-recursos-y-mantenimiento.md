# 🛠️ Recursos oficiales y mantenimiento

Lo que AEMET ofrece a los desarrolladores más allá de los endpoints, y **cómo enterarse de que la API
ha cambiado**. Es el archivo que hay que revisar cada cierto tiempo, no al implementar.

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/web-texto/info.txt` (modos de acceso y API Key) · `src/web-texto/altaUsuario.txt` · `src/web-texto/centrodedescargas-inicio.txt` · `src/web-texto/AEMETApi.txt` (HATEOAS, Codegen, GitLab) · `src/web-texto/ejemProgramas.txt` (los 14 lenguajes) · `src/web-texto/novedades.txt` (**el changelog**) · `src/catalogos/faqs.json` (FAQ 1.10–1.14, 2.1–2.7, 4.1–4.4).

---

## Obtención y renovación de la API Key 🟢

| | |
|---|---|
| **Dónde** | <https://opendata.aemet.es/centrodedescargas/altaUsuario> (`src/web-texto/altaUsuario.txt`) |
| **Qué pide** | Solo una dirección de correo (dos veces, como comprobación) |
| **Qué llega** | La clave, por correo |
| **Caducidad** | **~100 días** (la actual expira el 2026-12-04). Ver [`LIMITACIONES.md`](LIMITACIONES.md#caducidad-de-la-api-key) |

🔵 Según AEMET (`src/web-texto/info.txt`, y las FAQs 2.1–2.7 en `src/catalogos/faqs.json`):

- La clave se genera **a partir de la dirección de correo**.
- **El correo no se almacena** en ningún fichero ni base de datos externa.
- Se pueden solicitar **tantas claves como se quiera** para el mismo correo.

> ⚠️ 🟢 Pero **generar otra clave no desbloquea un endpoint con la cuota agotada**: el límite va
> ligado a la IP ([`LIMITACIONES.md`](LIMITACIONES.md#la-cuota-va-ligada-a-la-ip-no-solo-a-la-clave)).

---

## Los dos modos de acceso 🔵

AEMET distingue explícitamente (`src/web-texto/info.txt`, y la portada del servicio en
`src/web-texto/centrodedescargas-inicio.txt`):

| Modo | Para qué |
|---|---|
| **Acceso general** | Interfaz web guiada, para descargas puntuales y manuales por parte de una persona |
| **Acceso desarrolladores** | La API REST, para consumo periódico y programado por un sistema |

🟡 Nosotros usamos siempre el segundo. El primero solo sirve para inspeccionar manualmente qué
productos existen — y de hecho hay productos **visibles en el acceso general que no tienen endpoint**
(ver [`06-climatologia.md`](06-climatologia.md)).

---

## HATEOAS: la documentación interactiva 🔵

`src/web-texto/AEMETApi.txt` — dentro de "Acceso Desarrolladores", la opción
**"Documentación Aemet OpenData. HATEOAS"** despliega los recursos REST por apartados, con un
formulario **"Test Request"** para probar cada endpoint metiendo la API Key y los parámetros.

🟡 Es la vía cómoda para **explorar** un endpoint nuevo antes de programarlo: devuelve la URL
generada, el `curl` equivalente y el sobre de respuesta. Las FAQs 4.7 a 4.9 lo explican con ejemplos.

⚠️ No sustituye a la verificación real: el HATEOAS enseña el sobre, no el contenido del segundo salto
ni su codificación.

---

## Aemet Codegen y el cliente Python oficial 🔵

| Recurso | Qué es | Utilidad para nosotros |
|---|---|---|
| **Aemet Codegen** | Generador de clientes a partir de la especificación, con [swagger-codegen](https://github.com/swagger-api/swagger-codegen) y [editor.swagger.io](https://editor.swagger.io/) | 🟡 **Ninguna.** Genera clientes obsoletos a partir de una especificación que no describe bien la API. Ver [`src/_MANIFEST.md`](src/_MANIFEST.md#descartado-deliberadamente) |
| **Cliente Python oficial** | <https://gitlab.aemet.es/opendata/API> — MIT, © 2026 AEMET | 🟢 **Sí.** De ahí sale el patrón "RSS primero, endpoint después". Ver [`11-rss-y-sincronizacion.md`](11-rss-y-sincronizacion.md) |
| **Ejemplos de cliente** | <https://opendata.aemet.es/centrodedescargas/ejemProgramas> — 14 lenguajes | 🟡 Marginal. Todos hacen lo mismo: un `GET` con la clave. Y **todos la pasan por query string**, que es justo lo que no hay que hacer |

### Los 14 lenguajes de los ejemplos 🟢

cURL · Java (OkHttp y Unirest) · Python (`http.client` y `requests`) · PHP (`HttpRequest`, `pecl_http`
y cURL) · Ruby · HTTP crudo · C (libcurl) · C# (RestSharp) · Go · JavaScript (jQuery y XHR) · NodeJS
(nativo, `request` y Unirest) · Objective-C · OCaml · Shell (wget, HTTPie, cURL) · Swift.

⚠️ **Las claves que aparecen en esos ejemplos son muestras públicas caducadas de AEMET** (JWT con
`exp` de 2016) y algunas están alteradas. No sirven.

---

## 📰 Cómo enterarse de que la API ha cambiado

**Es la parte que importa a medio plazo.** AEMET no versiona la API ni avisa por cabeceras: los
cambios se anuncian en una página y en un canal RSS.

| Vía | URL |
|---|---|
| **Página de novedades** | <https://opendata.aemet.es/centrodedescargas/novedades> |
| **Canal RSS de noticias** | `https://opendata.aemet.es/centrodedescargas/feeds.rss` |
| **Canal ATOM** | `https://opendata.aemet.es/centrodedescargas/feeds.atom` |

🔵 La propia FAQ 1.14 dice que la página de novedades **se alimenta automáticamente del canal RSS de
Noticias**, así que vigilar el feed equivale a vigilar la página.

Las publicaciones se clasifican en cuatro categorías 🔵: **Novedad** (funcionalidades), **Aviso**
(comunicaciones relevantes), **Mantenimiento** (actuaciones técnicas) y **Noticia** (general).

### Lo que ha pasado en los últimos meses 🟢

Transcrito de `src/web-texto/novedades.txt` (capturado el 2026-08-26). Sirve de muestra del **tipo** de
cambios que AEMET introduce, y de por qué hay que vigilarlo:

| Fecha | Tipo | Qué pasó | Impacto |
|---|---|---|---|
| 27/07/2026 | Novedad | Disponibles los endpoints `municipio/{diaria,horaria}/todos` | 🔥 **Nuevos endpoints** |
| 22/07/2026 | Novedad | Publicado el cliente Python oficial en GitLab | Referencia útil |
| **20/07/2026** | **Aviso** | **Desde el 15/10/2026 las API Keys sin fecha de expiración dejan de valer (→ 401)** | 🔥🔥 **Rompe integraciones** |
| **16/07/2026** | **Aviso** | **Todas las claves nuevas caducan a los 3 meses** | 🔥🔥 **Mantenimiento recurrente** |
| 16/07/2026 | Novedad | Nueva documentación de API en "Acceso Desarrolladores" | — |
| 10/07/2026 | Novedad | Sección de Novedades y Comunicados | — |
| 10/07/2026 | Novedad | Anuncio de los dos endpoints `.../todos` | — |
| 14/05/2026 | Incidencia | **Laguna en la disponibilidad de datos de avisos** | Datos incompletos |
| 12/05/2026 | Mantenimiento | Servicio afectado 09:00–12:30 | Corte planificado |
| 28/04/2026 | Mantenimiento | Conjuntos de datos posiblemente no actualizados | Datos rancios |
| 01/12/2025 | Mantenimiento | Corte 10:00–11:30 | Corte planificado |
| 15–17/10/2025 | Incidencia | **Fallo en la generación de API Keys** durante 2 días | No se podían crear claves |

### Lo que se aprende de ese historial 🟡

1. **Los cambios que rompen no se avisan con mucha antelación.** La caducidad de las claves se anunció
   el 16/07 y entra en vigor el 15/10: tres meses.
2. **Hay cortes de servicio planificados** varias veces al año, y avisos de "datos posiblemente no
   actualizados" — otra razón para validar la frescura del contenido.
3. **La propia generación de claves ha fallado** durante días. Si la renovación es crítica, no dejarla
   para el último día.
4. **Aparecen endpoints nuevos** sin cambiar la versión de la especificación (sigue en `2.0`).

### Rutina de mantenimiento propuesta 🟡

| Cuándo | Qué |
|---|---|
| **Semanal (automático)** | Leer el RSS de noticias y avisar al equipo si hay entradas nuevas de tipo *Aviso* |
| **Al recibir un 401** | Comprobar si la clave caducó antes de investigar otra cosa |
| **Cada ~2 meses** | Renovar la API Key con margen; actualizar `AEMET_API_KEY_EXPIRES_AT` |
| **Cada 6 meses** | Volver a descargar la especificación y **diffear** contra `src/especificacion/` |
| **En invierno** | Recosechar los códigos de `estadoCielo` con el endpoint agregado, para completar los de nieve ([`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md)) |
| **Al detectar un cambio** | Actualizar `src/`, la fecha del [manifiesto](src/_MANIFEST.md) y la de verificación de los archivos afectados |

---

## Consultas y soporte 🔵

| Vía | URL |
|---|---|
| Formulario de consultas | <https://sede.aemet.gob.es/AEMET/es/GestionPeticiones/NuevaConsulta> |
| Atención al ciudadano | <http://www.aemet.es/es/lineas_de_interes/atencion_al_ciudadano> |
| Sede electrónica (productos de pago o a medida) | Enlazada desde las FAQs 1.6 y 1.7 |

🔵 Para pedir un producto que **no está** en OpenData (modelos numéricos, formatos a medida) hay que
pasar por la sede electrónica, y lleva **coste de gestión**. Ver
[`LIMITACIONES.md`](LIMITACIONES.md#qué-no-ofrece-aemet-opendata).

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
