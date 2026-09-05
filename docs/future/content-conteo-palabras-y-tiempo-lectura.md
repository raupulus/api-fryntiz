# Conteo de caracteres/palabras y tiempo de lectura en Content

> **Estado:** idea, sin diseñar en detalle. No bloquea nada.

## Qué se quiere

Datos nuevos relacionados entre sí, calculados a partir del HTML de `content_pages.content`:

1. **Caracteres (letras) de la página**, guardados como atributo propio de cada `ContentPage` en el
   momento de guardar (no calculados al vuelo en cada request). Métrica confirmada: caracteres del
   texto, no "keywords" SEO (eso ya existe en `ContentSeo`, ver skill `seo`).
2. **Palabras de la página**, guardadas también como atributo propio junto al conteo de caracteres.
   **A valorar**: si merece la pena guardar las dos métricas o sólo una — el tiempo de lectura
   (punto 3) puede salir de cualquiera de las dos, así que guardar ambas es redundante para ese
   fin. Se guardan las dos de entrada porque cada una tiene un uso distinto más allá del tiempo de
   lectura (caracteres para mostrar "handle" o límites de UI, palabras porque es la unidad que
   entiende la gente al leer "quedan X palabras"), pero decidir si compensa el coste de mantener
   dos columnas derivadas en vez de una.
3. **Totales del contenido**, en la respuesta de la API de `Content`: el sumatorio de caracteres y
   el sumatorio de palabras de todas sus páginas.
4. **Tiempo estimado de lectura**, en segundos, calculado a partir de esos conteos:
   - En la respuesta de `Content`, el tiempo total (suma de todas las páginas).
   - En la respuesta de una `ContentPage` individual, su propio recuento (caracteres y/o palabras)
     y su propio tiempo estimado.

## Por qué guardarlo al guardar la página, no calcularlo al leer

El HTML no cambia entre lecturas. Recalcular estos conteos (y por tanto el tiempo de lectura) en
cada respuesta de la API es trabajo repetido sobre un dato que sólo cambia cuando se edita la
página. Se guardan como columnas en `content_pages` cuando se persiste el contenido, igual que ya
se hace con otros derivados (p. ej. las miniaturas de imagen se generan al guardar, no al servir).

## Qué hay que decidir antes de implementarlo

1. **Si se guardan las dos métricas (caracteres y palabras) o sólo una.** Ver el punto 2 de
   "Qué se quiere". Si sólo se necesitan para el tiempo de lectura, sobra una; si tienen otros usos
   en las webs consumidoras, guardar ambas.
2. **Cómo se limpia el HTML antes de contar**: hay que hacer `strip_tags` + decodificar entidades
   (ya existe una utilidad similar en `ContentPage::sanitizeTitle()`) para no contar etiquetas ni
   `&nbsp;` como caracteres o palabras del texto real.
3. **Codificación multibyte**: usar `mb_strlen` (no `strlen`) para que las tildes y la `ñ` cuenten
   como un carácter, no como varios bytes. Para palabras, `str_word_count` no soporta bien acentos
   ni español; hace falta una alternativa (p. ej. `preg_split` con una clase de caracteres Unicode).
4. **Fórmula del tiempo de lectura**: la habitual parte de velocidad media de lectura en palabras
   por minuto (200-250 ppm). Si al final sólo se guardan caracteres, hay que convertir a una
   estimación de palabras (p. ej. dividiendo entre una longitud media de palabra en español,
   ~5-6 caracteres) antes de aplicar la velocidad. Decidir la constante y si es configurable.
5. **Dónde vive el cálculo**: como método en `ContentPage` (p. ej. un evento `saving` que recalcula
   los conteos cuando cambia `content`), no en el Resource ni en el controlador.
6. **Migración**: añadir columnas a `content_pages` (caracteres, palabras y/o tiempo, según lo que
   se decida en el punto 1) y decidir si el sumatorio de `Content` se guarda también en caché
   (columna en `contents`) o se calcula con `withSum()` al vuelo sobre las páginas cargadas, como ya
   se hace con `views_count` en `ContentResource`.

## Dónde tocar cuando se aborde

| Sitio | Qué |
|---|---|
| `database/migrations/` | Nueva migración: columnas de conteo (caracteres y/o palabras) y tiempo en `content_pages` (y en `contents` si se cachea el sumatorio) |
| `App\Models\Content\ContentPage` | Cálculo al guardar (evento `saving` o método explícito llamado desde donde se persiste `content`) |
| `App\Http\Resources\V2\Content\ContentPageResource` | Exponer el/los conteo(s) y el tiempo de lectura de la página |
| `App\Http\Resources\V2\Content\ContentResource` | Exponer el/los sumatorio(s) y el tiempo de lectura total del contenido |
| `docs/info/api/v2/content.md` | Documentar los campos nuevos en los payloads de ejemplo |

> Creado: 2026-09-05 · Última revisión: 2026-09-05
