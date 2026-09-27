# Contrato API V2 — Plataformas y contenido (CMS)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, relaciones, decisiones de producto) ver
> [`docs/info/content.md`](../../content.md) y
> [`docs/info/platform.md`](../../platform.md).

## Índice

- [Base y convenciones](#base-y-convenciones-comunes-a-toda-la-api-v2)
- [Huella (`ETag`), 304 y caché](#huella-etag-304-y-caché)
- [Cómo usarla: tres ejemplos](#cómo-usarla-tres-ejemplos)
- [Plataformas](#plataformas-platforms): listado, ficha, categorías, etiquetas
- [Contenidos](#contenidos-platformsplatformslugcontents): listado, destacados,
  detalle, páginas, relacionados, SEO, galerías, ficheros
- [Cambios de contrato del 2026-09-27](#cambios-de-contrato-del-2026-09-27)

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Éxito, colección paginada
  { "success": true, "message": "Operación exitosa", "data": [ ... ], "meta": { "total": 42, "per_page": 25, "current_page": 1, "last_page": 2, "from": 1, "to": 25 } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

  `errors` solo aparece si hay detalle. `meta` solo aparece en las colecciones
  paginadas (aquí, el listado de contenidos y el de plataformas).
- **Autenticación**: **todo este módulo es público**. Ningún endpoint de este
  archivo requiere `Authorization: Bearer <token>` ni cookie de sesión.
- **Límite de peticiones**: sólo el techo global del grupo `api`, **300 por
  minuto** por token o por IP (`RATE_LIMIT_API_GLOBAL`). Pasado, `429`.
- **Ruta inexistente**: cualquier método/URL no documentado responde `404` con
  `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`. Esto
  incluye rutas con parámetros que no casan por su restricción (p. ej.
  `{order}` no numérico en `GET .../pages/{order}`).
- **404 de recurso**: cada endpoint devuelve su propio mensaje
  (`Plataforma no encontrada`, `Contenido no encontrado`, `Página no
  encontrada`…). Un contenido que no está publicado responde igual que uno que
  no existe.
- **Paginación y filtros de las colecciones** (`App\Http\Api\CollectionQuery`),
  donde se indique que un endpoint la usa:
  - `?page=1&per_page=25` — `per_page` por defecto 25, máximo 100.
  - `?campo=valor` — igualdad exacta, sólo sobre las columnas filtrables de
    cada endpoint. Un campo fuera de esa lista se ignora.
  - `?campo=a,b,c` — igualdad múltiple (`IN`).
  - `?campo[gte]=x&campo[lte]=y` — rango (`gte`, `gt`, `lte`, `lt`, `ne`).
  - `?from=&to=` — alias de `created_at[gte]` / `created_at[lte]`.
  - `?sort=campo` / `?sort=-campo` — orden; el guion es descendente. Los
    `NULL` van siempre al final.
- **Imágenes** (`image`, `cover`, portadas de relacionados…): siempre con la
  misma forma, la de `SocialImageResource`. Las claves vacías se omiten:

  ```json
  {
    "url": "https://api.raupulus.dev/file/get/content/16/PcDPH5Qz.png",
    "width": 2699,
    "height": 1519,
    "type": "image/png",
    "alt": "Pantalla e-paper",
    "thumbnails": {
      "micro": "https://api.raupulus.dev/file/thumbnail/get/content/301/PcDPH5Qz.webp",
      "small": "https://api.raupulus.dev/file/thumbnail/get/content/302/PcDPH5Qz.webp",
      "medium": "https://api.raupulus.dev/file/thumbnail/get/content/303/PcDPH5Qz.webp",
      "large": "https://api.raupulus.dev/file/thumbnail/get/content/304/PcDPH5Qz.webp"
    }
  }
  ```

---

## Huella (`ETag`), 304 y caché

Todas las rutas de este archivo:

- Llevan `ETag` (huella del cuerpo) y `Cache-Control: max-age=60, public`.
- Con `If-None-Match: <la huella>` responden **`304` sin cuerpo** si nada ha
  cambiado; si algo ha cambiado, `200` con la respuesta nueva y otra huella.
  Sin esa cabecera, siempre la respuesta completa.
- El servidor guarda las respuestas montadas (detalle, listado, destacados y
  ficha de plataforma) y las renueva **en cuanto se edita cualquier cosa que
  enseñen**: el contenido, sus páginas, SEO, metadatos, categorías, etiquetas,
  tecnologías, colaboradores, relacionados, galerías y sus fotos, ficheros, la
  plataforma o su autor. Además, como mucho cada hora: las visitas que se
  enseñan (`views_count`) y la tendencia se refrescan a ese ritmo.

> Con `APP_DEBUG=true` (desarrollo) las respuestas llevan un bloque `debug`;
> la huella se calcula igual, con él dentro.

---

## Cómo usarla: tres ejemplos

**1 · Artículo rápido.** El detalle trae los datos, el índice de páginas y la
primera página con su texto: con eso se pinta la cabecera, el menú de páginas
y el primer trozo. Lo demás, cuando haga falta:

```bash
# Primera carga: datos + índice + página 1 + SEO para los <meta>
GET /api/v2/platforms/portfolio/contents/estacion-meteorologica?include=seo&format=html
# Al pasar a la página 2 (por número o por su slug)
GET /api/v2/platforms/portfolio/contents/estacion-meteorologica/pages/2?format=html
GET /api/v2/platforms/portfolio/contents/estacion-meteorologica/pages/slug/montaje?format=html
```

**2 · Todo de golpe** (SSR, exportar, una app sin conexión):

```bash
GET /api/v2/platforms/portfolio/contents/estacion-meteorologica?include=all&format=html
```

**3 · Galería en otra pestaña.** El artículo no carga las fotos; la pestaña
de la galería las pide al abrirse:

```bash
GET /api/v2/platforms/portfolio/contents/estacion-meteorologica
GET /api/v2/platforms/portfolio/contents/estacion-meteorologica/galleries   # al abrir la pestaña
```

Y la portada de una web suele ser:

```bash
GET /api/v2/platforms/portfolio                       # ficha: SEO de la web, autor, redes, páginas
GET /api/v2/platforms/portfolio/contents/highlights   # destacados, últimos y tendencia por tipo
```

---

## Plataformas (`/platforms`)

### `GET /platforms` — Listado de plataformas

- **Colección paginada**: filtrable por `slug`, `domain`, `created_at`;
  ordenable por `title`, `created_at`; por defecto `title` ascendente.
- **Respuesta 200** (`PlatformResource`, con `meta`):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 3,
      "name": "Porfolio",
      "title": "Porfolio",
      "slug": "portfolio",
      "domain": "raupulus.dev",
      "description": "Portfolio Personal con mis proyectos",
      "image": { "url": "…", "width": 4266, "height": 4266, "type": "image/png", "alt": "Porfolio", "thumbnails": { "…": "…" } },
      "created_at": "2023-08-04T22:00:05.000000Z"
    }
  ],
  "meta": { "total": 3, "per_page": 25, "current_page": 1, "last_page": 1, "from": 1, "to": 3 }
}
```

  - `name` y `title` llevan el mismo valor (la columna es `title`; `name` es la
    clave que consumen las webs).
  - `image` no aparece si la plataforma no tiene imagen.

### `GET /platforms/{platform:slug}` — Ficha de una plataforma

La ficha completa, la que daba la v1 en `/v1/platform/{p}/info`: primera carga
de una web y metadatos del SSR. **Nunca** lleva las claves de publicación
(`twitter_token`, `mastodon_token`…).

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "id": 3,
    "name": "Porfolio",
    "title": "Porfolio",
    "slug": "portfolio",
    "description": "Portfolio Personal con mis proyectos",
    "domain": "raupulus.dev",
    "url_about": "https://raupulus.dev/about",
    "image": { "url": "…", "width": 4266, "height": 4266, "type": "image/png", "alt": "Porfolio", "thumbnails": { "…": "…" } },
    "social_networks": {
      "youtube_channel_id": "UCZescg-D1m_yCSgTdCLRBpw",
      "youtube_presentation_video_id": "https://www.youtube.com/@raupulus",
      "twitter": "@raupulus",
      "mastodon": "@raupulus",
      "twitch": "@raupulus",
      "tiktok": null,
      "instagram": "@raupulus"
    },
    "author": {
      "name": "Raúl Caro Pastorino",
      "nick": "raupulus",
      "image": "https://api.raupulus.dev/storage/profile-photos/abc.jpg",
      "url_image_micro": "…",
      "url_image_small": "…",
      "profession": "Developer",
      "web": "https://raupulus.dev",
      "social_networks": [
        { "slug": "github", "name": "GitHub", "color": "#000000", "nick": "raupulus", "url": "https://github.com/raupulus", "url_image": "…" }
      ]
    },
    "technologies": [
      { "id": 20, "slug": "bootstrap", "name": "Bootstrap", "color": "#6531b2", "image": "https://api.raupulus.dev/file/thumbnail/get/technology/272/Bvz.webp" }
    ],
    "contents": {
      "total": 12,
      "types": [
        { "id": 3, "slug": "blog", "name": "Blog", "plural_name": "Blogs", "description": "…", "total": 7 },
        { "id": 5, "slug": "project", "name": "Proyecto", "plural_name": "Proyectos", "description": "…", "total": 5 }
      ]
    },
    "pages": [
      { "id": 30, "title": "Sobre mí", "slug": "sobre-mi", "excerpt": "…", "image": null, "type": { "id": 1, "slug": "page", "name": "Página" }, "is_featured": false, "published_at": "2026-09-01T10:00:00.000000Z" }
    ],
    "created_at": "2023-08-04T22:00:05.000000Z"
  }
}
```

- `author`: el usuario dueño de la plataforma. `profession` y `web` salen de
  sus datos (`user_details`); si no los tiene, `null` (antes salían «Developer»
  y «raupulus.dev» escritos a mano). `image` es su foto de perfil o `null`.
- `technologies`: las de sus **proyectos publicados** (tipo `project`), por
  nombre.
- `contents`: cuántos contenidos **publicados** tiene, en total y por tipo
  (sólo los tipos con alguno).
- `pages`: sus contenidos publicados de tipo `page` (Sobre mí, Aviso legal…),
  por título, con la forma compacta de los relacionados.
- **Errores**: `404` `Plataforma no encontrada`.

### `GET /platforms/{platform:slug}/categories` — Categorías de una plataforma

- **Respuesta 200**: `data` es un array de categorías raíz con sus
  subcategorías anidadas:

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "slug": "electronica",
      "name": "Electrónica",
      "description": "Proyectos y artículos de electrónica.",
      "icon": "fa fa-microchip",
      "color": "#3788d8",
      "urlImageMicro": "https://api.raupulus.dev/…/cat-micro.webp",
      "urlImageSmall": "https://api.raupulus.dev/…/cat-small.webp",
      "subcategories": [
        { "slug": "arduino", "name": "Arduino", "description": "…", "icon": "fa fa-bolt", "color": "#2e7d32", "urlImageMicro": "…", "urlImageSmall": "…", "parent": "electronica" }
      ]
    }
  ]
}
```

  `urlImageMicro`/`urlImageSmall` nunca son `null` (caen a la imagen por
  defecto). No hay `id` ni `image_id`. Se renueva al añadir una categoría a la
  plataforma o al editarla (antes se guardaba para siempre y no se enteraba de
  las categorías nuevas).
- **Errores**: `404` `Plataforma no encontrada`.

### `GET /platforms/{platform:slug}/tags` — Etiquetas de una plataforma

Todas las etiquetas de la plataforma, por nombre, con cuántos contenidos
**publicados** las usan (las quitadas de un contenido no cuentan):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    { "id": 2, "slug": "iot", "name": "IoT", "color": "#000000", "contents_count": 4 },
    { "id": 3, "slug": "sensors", "name": "Sensores", "color": "#1e88e5", "contents_count": 0 }
  ]
}
```

- **Errores**: `404` `Plataforma no encontrada`.

---

## Contenidos (`/platforms/{platform:slug}/contents`)

Todos los contenidos de esta sección exigen **plataforma existente y
contenido publicado y activo** (estado «publicado» e `is_active`). Un
contenido en borrador, programado, oculto o en la papelera responde `404`
exactamente igual que uno que no existe.

El slug `highlights` está reservado (es la ruta de destacados): el panel no
deja ponérselo a un contenido.

### El contenido (`ContentResource`)

Es lo que sale en el listado y la base del detalle:

```json
{
  "id": 42,
  "title": "Estación meteorológica con ESP32",
  "slug": "estacion-meteorologica",
  "excerpt": "Guía paso a paso para montar tu propia estación.",
  "type": { "id": 5, "slug": "project", "name": "Proyecto", "plural_name": "Proyectos" },
  "status": { "id": 3, "slug": "published", "name": "Publicado" },
  "is_featured": true,
  "image": { "url": "…", "width": 1200, "height": 630, "type": "image/webp", "alt": "…", "thumbnails": { "…": "…" } },
  "seo_title": "Título para compartir",
  "seo_description": "Descripción para buscadores",
  "platform": { "id": 3, "slug": "portfolio", "title": "Porfolio" },
  "visibility": {
    "home": true, "menu": false, "footer": false, "sidebar": false, "search": true,
    "archive": true, "rss": true, "sitemap": true, "sitemap_news": false
  },
  "comments": { "enabled": false, "anonymous": false },
  "copyright_valid": null,
  "pages_count": 3,
  "views_count": 412,
  "published_at": "2026-08-20T10:00:00.000000Z",
  "created_at": "2026-08-15T09:00:00.000000Z",
  "updated_at": "2026-08-20T10:00:00.000000Z"
}
```

- `type` y `status`: compactos. `image`: `null` si no tiene.
- `seo_title` / `seo_description`: `og_title` y `description` de su SEO; si no
  tiene, su título y su extracto. El SEO completo va en `?include=seo`.
- `visibility`: dónde quiere el autor que se enseñe (portada, menú, pie,
  barra lateral, búsqueda, archivo, RSS, sitemap y sitemap de noticias). Lo
  aplica cada web.
- `comments`: aún no hay comentarios, pero el interruptor ya está.
- `copyright_valid`: `true`/`false`, o `null` si no se ha comprobado.
- `views_count`: visitas de siempre; `0` si no tiene. Se refresca cada hora
  como mucho.

### `GET /platforms/{platform:slug}/contents` — Contenidos publicados

- **Filtros propios**:

  | Parámetro | Qué hace |
  |---|---|
  | `featured=1` | Sólo destacados |
  | `type={slug}` | Por tipo (`blog`, `project`, `page`…). Un tipo que no existe → `404` `Tipo de contenido no reconocido` |
  | `category={slug}` | Con esa categoría o subcategoría de la plataforma |
  | `tag={slug}` | Con esa etiqueta de la plataforma |
  | `technology={slug}` | Con esa tecnología |
  | `q={texto}` | El texto en el título o el extracto, sin distinguir mayúsculas. `%` y `_` se buscan tal cual |

  Las categorías, etiquetas y tecnologías quitadas de un contenido no cuentan.
- **Colección paginada**: filtrable por `is_featured`, `type_id`,
  `published_at`, `created_at`; ordenable por `published_at`, `created_at`,
  `title`; por defecto `published_at` descendente.
- **Respuesta 200**: `data` es una lista de [contenidos](#el-contenido-contentresource), con `meta`.

```bash
GET /api/v2/platforms/portfolio/contents?type=project&tag=iot&q=esp32&per_page=10
```

### `GET /platforms/{platform:slug}/contents/highlights` — Destacados, últimos y tendencia

| Parámetro | Valores | Por defecto |
|---|---|---|
| `type` | `featured`, `latest`, `trend`, `all` | `all` |
| `limit` | 1 a 24, **por tipo de contenido** | 6 |

Agrupado primero por lista y después por el slug del tipo de contenido, como
en `main`. Cada elemento tiene la forma compacta de los
[relacionados](#get-platformsplatformslugcontentscontentslugrelated--relacionados):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "featured": { "project": [ { "id": 42, "title": "Estación meteorológica", "…": "…" } ] },
    "latest": {
      "blog": [ { "id": 51, "title": "…" } ],
      "project": [ { "id": 40, "title": "…" } ]
    },
    "trend": { "project": [ { "id": 42, "title": "…" } ] }
  }
}
```

- `featured`: destacados, del más reciente al más antiguo.
- `latest`: los más recientes **sin los destacados**, que ya salen aparte.
- `trend`: los más vistos de los **últimos 3 días**; sin visitas, detrás, por
  fecha.
- Una lista sin nada sale como `[]`; un tipo sin contenidos no aparece.
- **Errores**: `404` `Plataforma no encontrada`; `422` `El tipo tiene que ser
  featured, latest, trend o all.`

### `GET /platforms/{platform:slug}/contents/{content:slug}` — Detalle

Ligero por defecto: el [contenido](#el-contenido-contentresource), el
**índice de páginas sin texto** y **la primera página con su texto**. Lo
demás, con `include` o por su ruta.

| Parámetro | Valores | Qué hace |
|---|---|---|
| `include` | lista separada por comas de `seo`, `metadata`, `taxonomies`, `technologies`, `contributors`, `galleries`, `files`, `related`, `pages`; o `all` | Añade esas partes. Lo que no se conoce se ignora |
| `format` | `editorjs`, `markdown`, `html` | Formato del texto de las páginas (por defecto, el de cada una) |

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "id": 42,
    "title": "Estación meteorológica con ESP32",
    "…": "el resto del contenido",
    "pages": [
      { "id": 101, "order": 1, "title": "Introducción", "slug": "introduccion", "format": "editorjs" },
      { "id": 102, "order": 2, "title": "Montaje", "slug": "montaje", "format": "editorjs" },
      { "id": 103, "order": 3, "title": "Resultados", "slug": "resultados", "format": "markdown" }
    ],
    "first_page": {
      "id": 101, "content_id": 42, "order": 1, "title": "Introducción",
      "format": "editorjs", "source_format": "editorjs",
      "body": { "time": 1790002185625, "blocks": [ { "id": "h1", "type": "header", "data": { "text": "Introducción", "level": 2 } } ], "version": "2.31.7" },
      "slug": "introduccion", "current_page_raw_id": 2,
      "created_at": "…", "updated_at": "…"
    }
  }
}
```

- `pages`: el índice, por orden, **sin texto**; `format` es el formato de cada
  página en el panel. Con `include=pages` (o `all`), `pages` pasa a ser la
  lista de [páginas completas](#la-página-contentpageresource).
- `first_page`: la primera página, completa; `null` si no tiene páginas.
- Cada `include` añade **sólo** su clave, con la misma forma que su ruta:

  | `include` | Clave | Forma |
  |---|---|---|
  | `seo` | `seo` | [SEO](#get-platformsplatformslugcontentscontentslugseo--seo) o `null` |
  | `metadata` | `metadata` | `{web, youtube_video, youtube_video_id, youtube_channel, github, gitlab, telegram_channel, mastodon, twitter}` o `null` |
  | `taxonomies` | `taxonomies` | ver abajo |
  | `technologies` | `technologies` | `[{id, slug, name, color, image}]`, por nombre; `image` es la miniatura pequeña o `null` |
  | `contributors` | `contributors` | `[{name, nick, image}]`, por nombre; `image` es la foto o `null` |
  | `galleries` | `galleries` | [galerías](#get-platformsplatformslugcontentscontentsluggalleries--galerías) |
  | `files` | `files` | [ficheros](#get-platformsplatformslugcontentscontentslugfiles--ficheros) |
  | `related` | `related` | [relacionados](#get-platformsplatformslugcontentscontentslugrelated--relacionados), 5 como mucho |
  | `pages` | `pages` | todas las [páginas](#la-página-contentpageresource) con su texto |

  `taxonomies` (las quitadas del contenido no salen):

  ```json
  {
    "categories": [ { "id": 2, "slug": "iot", "name": "IoT", "color": "#000000", "icon": null, "is_main": true } ],
    "subcategories": [ { "id": 9, "slug": "sensores", "name": "Sensores", "color": "#123456", "icon": null, "is_main": false, "parent": "iot" } ],
    "tags": [ { "id": 2, "slug": "iot", "name": "IoT", "color": "#000000" } ]
  }
  ```

- **Visitas**: cada petición del detalle suma una, también si se responde desde
  la caché o con `304`. Se cuenta después de responder, sin retrasar la
  respuesta y sin depender de la cola. Pedir páginas o partes no suma.
- **Errores**: `404` `Contenido no encontrado`; `422` si `format` no es uno de
  los tres.

### La página (`ContentPageResource`)

```json
{
  "id": 102,
  "content_id": 42,
  "order": 2,
  "title": "Sobre mí",
  "format": "markdown",
  "source_format": "markdown",
  "body": "## Sobre mí\n\nTexto en **Markdown**.\n",
  "slug": "sobre-mi",
  "current_page_raw_id": 1,
  "created_at": "2026-08-15T09:00:00.000000Z",
  "updated_at": "2026-08-20T10:00:00.000000Z"
}
```

- `format`: el formato en el que viene `body`. **Es lo que tiene que mirar el
  frontend para saber cómo pintarlo.** `source_format`: en el que se escribe
  en el panel. Sin `?format=`, coinciden.
- `body`:
  - `editorjs` → **objeto** `{time, blocks, version}`, no texto. Vacía:
    `{"blocks": []}`. Los bloques `list` llegan en dos formatos según cuándo
    se guardó la página (desde el 2026-09-24 el panel usa Editor.js 2.31 y
    `@editorjs/list` 2.x):

    ```json
    {"style": "unordered", "items": ["Uno", "Dos"]}
    {"style": "ordered", "meta": {"counterType": "numeric", "start": 3},
     "items": [{"content": "Uno", "meta": {}, "items": [
       {"content": "Uno bis", "meta": {}, "items": []}]}]}
    ```

    El nuevo admite listas anidadas, `style: "checklist"` (con `meta.checked`)
    y numeración con letras o romanos (`counterType`: `numeric`,
    `lower-roman`, `upper-roman`, `lower-alpha`, `upper-alpha`). Con
    `?format=html` no cambia nada.
  - `markdown` → texto Markdown (GitHub: tablas y listas de tareas). Lo que
    Markdown no sabe expresar (imágenes del módulo de ficheros, alertas,
    vídeos, tarjetas de enlace…) va como HTML dentro de un
    `<div data-editorjs-block="…">`.
  - `html` → el HTML que se sirve de siempre. Desde el 2026-09-24 el código de
    los bloques de código sale escapado, una tarjeta de enlace sin datos sale
    como enlace normal (`<p class="r-web-preview-simple"><a …>`) y el HTML de
    los textos viene limpio (sólo formato en línea).
- Una página se puede pedir en cualquier formato: se devuelve la versión que
  se guardó desde el panel o, en páginas antiguas, se convierte una vez y se
  guarda.
- `current_page_raw_id`: id del **tipo** de la fuente (`2` = Editor.js,
  `1` = Markdown, `6` = HTML); puede ser `null` en páginas antiguas.

### `GET /platforms/{platform:slug}/contents/{content:slug}/pages` — Páginas

| Parámetro | Valores | Qué hace |
|---|---|---|
| `format` | `editorjs`, `markdown`, `html` | Formato de `body` |
| `from` | entero ≥ 1 | Desde la página con ese número (su `order`) |
| `limit` | 1 a 100 | Cuántas como mucho |

- **Respuesta 200**: lista de [páginas](#la-página-contentpageresource) por
  orden, sin `meta`. Sin páginas, `"data": []`.
- **Errores**: `404` `Contenido no encontrado`; `422` con
  `errors.format`, `errors.from` o `errors.limit`:

  ```json
  { "success": false, "message": "…", "errors": { "from": ["La página de inicio empieza en 1."] } }
  ```

### `GET /platforms/{platform:slug}/contents/{content:slug}/pages/{order}` — Una página por su número

- `{order}` es numérico (si no, 404 genérico de ruta). `?format=` como arriba.
- **Respuesta 200**: una [página](#la-página-contentpageresource).
- **Errores**: `404` `Contenido no encontrado`; `404` `Página no encontrada`;
  `422` por `format`.

### `GET /platforms/{platform:slug}/contents/{content:slug}/pages/slug/{pageSlug}` — Una página por su slug

Igual que la anterior, buscando por el slug de la página.

### `GET /platforms/{platform:slug}/contents/{content:slug}/related` — Relacionados

- `?limit=` de 1 a 20 (por defecto 5).
- Primero los **elegidos a mano** en el panel, en el orden en que se
  vincularon; si no llegan al límite, se completa con los publicados más
  recientes de la misma plataforma y tipo (sin repetir ni incluir el propio
  contenido). Sólo publicados y de la misma plataforma.

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 39,
      "title": "Sensores de humedad para tu huerto",
      "slug": "sensores-humedad-huerto",
      "excerpt": "Comparativa de sensores de humedad de suelo.",
      "image": { "url": "…", "width": 1200, "height": 630, "type": "image/webp", "alt": "…", "thumbnails": { "…": "…" } },
      "type": { "id": 5, "slug": "project", "name": "Proyecto" },
      "is_featured": false,
      "published_at": "2026-08-10T10:00:00.000000Z"
    }
  ]
}
```

- **Errores**: `404` `Contenido no encontrado`. Sin relacionados, `[]`.

### `GET /platforms/{platform:slug}/contents/{content:slug}/seo` — SEO

`data` es el SEO del contenido, o `null` si no tiene:

```json
{
  "description": "Descripción para buscadores",
  "keywords": "esp32, meteorología",
  "robots": "index, follow",
  "revisit_after": "7 days",
  "distribution": "global",
  "og_title": "Título para compartir",
  "og_type": "website",
  "twitter_card": "summary_large_image",
  "twitter_creator": "@raupulus",
  "image": { "url": "…", "width": 1200, "height": 630, "type": "image/webp", "alt": "…", "thumbnails": { "…": "…" } },
  "image_alt": "Estación en el tejado"
}
```

### `GET /platforms/{platform:slug}/contents/{content:slug}/galleries` — Galerías

Las galerías vinculadas, en su orden, con las fotos en el suyo:

```json
[
  {
    "id": 7,
    "name": "Montaje en fotos",
    "description": "…",
    "aspect_ratio": "16:9",
    "cover": { "url": "…", "…": "…" },
    "images": [
      { "order": 1, "caption": "La caja", "image": { "url": "…", "width": 1600, "height": 900, "alt": "…", "thumbnails": { "…": "…" } } }
    ]
  }
]
```

### `GET /platforms/{platform:slug}/contents/{content:slug}/files` — Ficheros

Los ficheros subidos al contenido que **siguen en uso** (los que ya no usa
ninguna página esperan a borrarse y no salen):

```json
[
  {
    "id": 310,
    "name": "esquema.pdf",
    "title": "Esquema eléctrico",
    "alt": "Esquema eléctrico",
    "type": "application/pdf",
    "is_image": false,
    "size": 245678,
    "width": null,
    "height": null,
    "url": "https://api.raupulus.dev/file/get/content/310/Xy.pdf",
    "download_url": "https://api.raupulus.dev/file/download/content/310/Xy.pdf"
  }
]
```

---

## Cambios de contrato del 2026-09-27

Lo que cambia para quien ya consumía esta API (F9 del plan de contenidos):

| Antes | Ahora |
|---|---|
| `type` y `status` eran la fila entera de la tabla (con `file_id`, `icon`, fechas…) | `{id, slug, name, plural_name}` y `{id, slug, name}` |
| El detalle traía `technologies` y cargaba páginas y metadatos | El detalle trae `pages` (índice sin texto) y `first_page`; lo demás con `include` |
| `image` de los relacionados era el modelo `File` entero (con `storage_path`, `is_private`…) | La forma de todas las imágenes (`SocialImageResource`) |
| Relacionados: siempre 5, los últimos del mismo tipo | Los elegidos a mano primero, `?limit=` |
| La ficha de plataforma era el mismo objeto que el listado | La ficha completa de la v1 (redes, autor, tecnologías, recuentos, páginas) |
| Página inexistente: `Pagina no encontrada` | `Página no encontrada` |
| La visita se encolaba (sin trabajador, no se contaba) | Se cuenta siempre, después de responder |
| Sin `ETag` ni caché | `ETag`, `304` y `Cache-Control: max-age=60, public` |

Nuevas: `…/contents/highlights`, `…/tags`, `…/pages/slug/{slug}`, `…/seo`,
`…/galleries`, `…/files`, y los filtros `category`, `tag`, `technology` y `q`
del listado.

## Lo que existió y ya no tiene ruta (no lo reimplementes igual)

| Ruta antigua | Qué pasó |
|---|---|
| `GET /platforms/{slug}/featured` | Es `GET /platforms/{slug}/contents?featured=1`, o los destacados de `…/contents/highlights` |
| `GET /platforms/{slug}/content/type/{tipo}` | Es `GET /platforms/{slug}/contents?type={tipo}` |

---

> Creado: 2026-08-30 · Última revisión: 2026-09-27
