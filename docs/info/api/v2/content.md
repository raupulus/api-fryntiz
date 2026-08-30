# Contrato API V2 — Plataformas y contenido (CMS)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, relaciones, decisiones de producto) ver
> [`docs/info/content.md`](../../content.md) y
> [`docs/info/platform.md`](../../platform.md).

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
  que pasan por `paginatedResponse()` (aquí, únicamente el índice de
  contenidos y el índice de plataformas); sale directamente del paginador de
  Laravel, no se construye a mano.
- **Autenticación**: **todo este módulo es público**. Ningún endpoint de este
  archivo requiere `Authorization: Bearer <token>` ni cookie de sesión — no
  hay ningún middleware `auth:sanctum`/`ability` en `routes/api/v2.php` sobre
  el grupo `platforms`.
- **Rate limit**: ninguno propio. El grupo `platforms` no lleva middleware
  `throttle:...` (a diferencia de `/auth/tokens` o `/contact-messages`), y la
  aplicación no registra un limiter global para `api` (`RateLimiter::for('api',
  fn () => Limit::none())`), así que en la práctica no hay límite de
  peticiones en ningún entorno.
- **Ruta inexistente**: cualquier método/URL no documentado responde `404` con
  `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`. Esto
  incluye rutas con parámetros que no casan por su restricción (p. ej.
  `{order}` no numérico en `GET .../pages/{order}`, ver más abajo).
- **404 de recurso vs 404 de ruta**: ninguna de las rutas de este módulo usa
  binding implícito de modelo de Laravel (`{platform:slug}`/`{content:slug}`
  son sólo el nombre de la clave de ruta; los métodos de los controladores
  reciben el slug como `string`, no como `Platform`/`Content`). El 404 "recurso
  no encontrado" de cada endpoint sale siempre de una llamada explícita a
  `notFoundResponse()` en el controlador, con su propio mensaje — nunca del
  `ModelNotFoundException` automático de Laravel.
- **Paginación y filtros de las colecciones** (`App\Http\Api\CollectionQuery`),
  donde se indique que un endpoint la usa:
  - `?page=1&per_page=25` — `per_page` por defecto 25, máximo 100 (un valor
    mayor se recorta, uno menor o inválido cae al valor por defecto).
  - `?campo=valor` — igualdad exacta, sólo sobre las columnas que el
    controlador declara como filtrables (se listan en cada endpoint). Un
    campo fuera de esa lista se ignora en silencio.
  - `?campo=a,b,c` — igualdad múltiple (`WHERE campo IN (...)`).
  - `?campo[gte]=x&campo[lte]=y` — rango (`gte`, `gt`, `lte`, `lt`, `ne`).
  - `?from=&to=` — alias histórico de `created_at[gte]` / `created_at[lte]`
    (sólo si `created_at` está en la lista de filtrables del endpoint).
  - `?sort=campo` / `?sort=-campo` — orden; el guion es descendente. Admite
    varias columnas separadas por coma. Sólo se puede ordenar por las columnas
    que el controlador declara como ordenables; si `sort` no viene o no casa
    con ninguna columna permitida, se aplica el orden por defecto del
    endpoint. Los `NULL` van siempre al final (`NULLS LAST` explícito),
    tanto en ascendente como en descendente.

---

## Plataformas (`/platforms`)

### `GET /platforms` — Listado de plataformas

- **Auth**: pública.
- **Colección paginada** (`CollectionQuery`):
  - Filtrable: `slug`, `domain`, `created_at` (más el alias `from`/`to`).
  - Ordenable: `title`, `created_at`.
  - Orden por defecto: `title` ascendente.
- **Respuesta 200** (`PlatformResource`, con `meta` de paginación):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 1,
      "name": "Raupulus",
      "title": "Raupulus",
      "slug": "raupulus",
      "domain": "raupulus.es",
      "description": "Blog personal de tecnología y electrónica.",
      "image": {
        "url": "https://cdn.raupulus.es/files/abc123.webp",
        "width": 1200,
        "height": 630,
        "type": "image/webp",
        "alt": "Logo de Raupulus",
        "thumbnails": {
          "micro": "https://cdn.raupulus.es/files/abc123-micro.webp",
          "small": "https://cdn.raupulus.es/files/abc123-small.webp",
          "medium": "https://cdn.raupulus.es/files/abc123-medium.webp",
          "large": "https://cdn.raupulus.es/files/abc123-large.webp"
        }
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 8,
    "per_page": 25,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 8
  }
}
```

  - `name` y `title` llevan siempre el mismo valor (la columna real es
    `title`; `name` se mantiene porque es la clave que consumen las webs).
  - `image` sólo aparece si la plataforma tiene imagen asociada (viene
    eager-loaded en este endpoint, así que si `image_id` es `null` la clave
    `image` se omite del todo, no sale como `null`). Cualquier subclave de
    `image`/`thumbnails` con valor vacío también se omite (`SocialImageResource`
    aplica `array_filter`).

### `GET /platforms/{platform:slug}` — Detalle de una plataforma

- **Auth**: pública.
- **Parámetros de ruta**: `{platform:slug}` — slug de la plataforma.
- **Respuesta 200**: un único objeto `PlatformResource` (misma forma que cada
  elemento del listado de arriba), sin envelope de paginación.
- **Errores**: `404` `{"success": false, "message": "Plataforma no encontrada"}`
  si el slug no existe.

### `GET /platforms/{platform:slug}/categories` — Categorías de una plataforma

- **Auth**: pública.
- **Parámetros de ruta**: `{platform:slug}` — slug de la plataforma.
- **Respuesta 200**: `data` es un array plano (no pasa por ningún `JsonResource`,
  es la colección de Eloquent tal cual, cacheada **para siempre** por slug de
  plataforma — `Cache::rememberForever('api-categories-{slug}', ...)` — y sólo
  se invalida cuando se guarda una `Platform`, un `Content` o una `Category`).
  Sólo incluye categorías raíz (`parent_id = null`) con sus subcategorías
  anidadas:

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
      "urlImageMicro": "https://cdn.raupulus.es/files/cat-micro.webp",
      "urlImageSmall": "https://cdn.raupulus.es/files/cat-small.webp",
      "subcategories": [
        {
          "slug": "arduino",
          "name": "Arduino",
          "description": "Proyectos con Arduino.",
          "icon": "fa fa-bolt",
          "color": "#2e7d32",
          "urlImageMicro": "https://cdn.raupulus.es/files/subcat-micro.webp",
          "urlImageSmall": "https://cdn.raupulus.es/files/subcat-small.webp",
          "parent": "electronica"
        }
      ]
    }
  ]
}
```

  `urlImageMicro`/`urlImageSmall` nunca son `null`: si la categoría no tiene
  imagen, caen a la imagen por defecto de la aplicación
  (`File::urlDefaultImage()`). No hay `id` ni `image_id` en la respuesta: se
  eliminan explícitamente antes de devolverla.
- **Errores**: `404` `{"success": false, "message": "Plataforma no encontrada"}`
  si el slug no existe.

---

## Contenidos (`/platforms/{platform:slug}/contents`)

Todos los contenidos de esta sección exigen **plataforma existente y
contenido en estado publicado** (`status_id = 2`, columna interna; no
comprueban `is_active`). Un contenido en borrador, programado o archivado
responde `404` exactamente igual que uno que no existe: no hay forma de
distinguirlos desde fuera, y así debe seguir siendo (evita enumerar
contenido no público).

### `GET /platforms/{platform:slug}/contents` — Contenidos publicados de una plataforma

- **Auth**: pública.
- **Parámetros de ruta**: `{platform:slug}` — slug de la plataforma.
- **Query params propios** (sustituyen a las antiguas rutas `GET
  .../featured` y `GET .../content/type/{tipo}`, que ya no existen):
  - `?featured=1` — sólo contenidos destacados (`is_featured = true`).
    Cualquier valor "verdadero" de Laravel (`1`, `true`, `"on"`, `"yes"`)
    activa el filtro; su ausencia u otro valor no filtra.
  - `?type=<slug-del-tipo>` — filtra por el slug del tipo de contenido (tabla
    `content_available_types`, p. ej. `articulo`, `tutorial`, `noticia`). Si el
    slug no corresponde a ningún tipo, la petición responde `404`
    `{"success": false, "message": "Tipo de contenido no reconocido"}` **antes**
    de intentar paginar.
- **Colección paginada** (`CollectionQuery`, además de `page`/`per_page`/`sort`
  genéricos):
  - Filtrable: `is_featured`, `type_id`, `published_at`, `created_at` (más el
    alias `from`/`to`).
  - Ordenable: `published_at`, `created_at`, `title`.
  - Orden por defecto: `published_at` descendente.
- **Respuesta 200** (`ContentResource`, con `meta` de paginación):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 42,
      "title": "Cómo montar una estación meteorológica con ESP32",
      "slug": "estacion-meteorologica-esp32",
      "excerpt": "Guía paso a paso para montar tu propia estación.",
      "type": {
        "id": 3,
        "file_id": null,
        "name": "Tutorial",
        "plural_name": "Tutoriales",
        "slug": "tutorial",
        "description": "Guías paso a paso.",
        "icon": "fa fa-graduation-cap",
        "color": "#3788d8",
        "created_at": "2021-05-08T20:21:49.000000Z",
        "updated_at": "2021-05-08T20:21:49.000000Z"
      },
      "status": {
        "id": 2,
        "file_id": null,
        "name": "Publicado",
        "slug": "publicado",
        "description": null,
        "icon": null,
        "color": "#000000",
        "created_at": "2021-05-08T20:21:49.000000Z",
        "updated_at": "2021-05-08T20:21:49.000000Z"
      },
      "is_featured": true,
      "image": {
        "url": "https://cdn.raupulus.es/files/def456.webp",
        "width": 1200,
        "height": 630,
        "type": "image/webp",
        "alt": "Estación meteorológica ESP32",
        "thumbnails": { "medium": "https://cdn.raupulus.es/files/def456-medium.webp" }
      },
      "seo_title": null,
      "seo_description": null,
      "pages_count": null,
      "views_count": 0,
      "published_at": "2026-08-20T10:00:00.000000Z",
      "created_at": "2026-08-15T09:00:00.000000Z",
      "updated_at": "2026-08-20T10:00:00.000000Z"
    }
  ],
  "meta": {
    "total": 30,
    "per_page": 25,
    "current_page": 1,
    "last_page": 2,
    "from": 1,
    "to": 25
  }
}
```

  Quirks verificados en `ContentResource::toArray()` (código real, no lo
  cambies sin revisar antes con quien mantenga esto):

  - `type` y `status` **no** son el slug/nombre sueltos: son el modelo
    completo (`content_available_types` / `content_available_status`) tal
    cual sale de la base de datos, con `file_id`/`icon`/`color`/timestamps
    incluidos. Van precargados (`with(['type', 'status', ...])`) tanto en el
    índice como en `show`/`getBySlug` — accederlos sin precarga llegó a
    lanzar `LazyLoadingViolationException` (500) fuera de producción con 2+
    contenidos; arreglado el 2026-08-30, con test de regresión en
    `ContentTest::index_does_not_error_with_several_typed_contents`.
  - `platform`, `categories` y `tags` van con `whenLoaded(...)` en el
    Resource, pero **ninguno de los controladores de este endpoint los
    eager-carga** — y `categories`/`tags` ni siquiera son relaciones Eloquent
    reales en el modelo `Content` (son atributos calculados), así que
    `whenLoaded` nunca puede detectarlas como cargadas. En la práctica, estas
    tres claves **nunca aparecen** en la respuesta de este endpoint tal como
    está el código hoy. `technologies` sí se carga en `show` (ver más abajo).
  - `seo_title` y `seo_description` no son columnas ni accesores de `Content`
    (el SEO real vive en la relación `seo` → modelo `ContentSeo`, con
    `description`, `og_title`, `twitter_card`, etc., que este Resource no
    expone). Salen siempre `null`.
  - `pages_count` depende de `whenCounted('pages')`; ningún controlador hace
    `withCount('pages')`, así que esta clave **nunca aparece** en ningún
    endpoint de contenido actual (ni aquí ni en `show`).
  - `views_count` no es una columna ni relación contada de `Content` (existe
    `daily_views_count` vía la relación `dailyViews`, no `views_count`), así
    que sale siempre `0` pase lo que pase con las visitas reales.

### `GET /platforms/{platform:slug}/contents/{content:slug}` — Un contenido publicado

- **Auth**: pública.
- **Parámetros de ruta**: `{platform:slug}` — slug de la plataforma;
  `{content:slug}` — slug del contenido, único por plataforma (dos
  plataformas sí pueden compartir el mismo slug de contenido).
- **Efecto secundario**: encola `ProcessContentViewJob` para contabilizar la
  visita; no bloquea ni retrasa la respuesta.
- **Respuesta 200**: un único `ContentResource` (misma forma que cada elemento
  del índice de arriba, con las mismas salvedades de `seo_title`/
  `seo_description`/`pages_count`/`views_count`). Aquí también carga
  `technologies` además de `type`, `status`, `seo`, `metadata`, `pages` e
  `image.fileType`, así que `technologies` **sí** puede aparecer en esta
  respuesta (según lo que tenga asociado el contenido); `platform`,
  `categories` y `tags` siguen sin aparecer nunca, por lo mismo que en el
  índice.
- **Errores**: `404` `{"success": false, "message": "Contenido no encontrado"}`
  si la plataforma no existe, el contenido no existe, no pertenece a esa
  plataforma o no está publicado.

### `GET /platforms/{platform:slug}/contents/{content:slug}/pages` — Páginas de un contenido

- **Auth**: pública.
- **Parámetros de ruta**: igual que el endpoint anterior.
- **Sin paginación**: `data` es un array con **todas** las páginas del
  contenido, ordenadas por `order` ascendente (no hay `meta`).
- **Respuesta 200** (`ContentPageResource`):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 101,
      "content_id": 42,
      "order": 1,
      "title": "Introducción",
      "body": "<p>Contenido HTML procesado de la página...</p>",
      "slug": "introduccion",
      "current_page_raw_id": 55,
      "created_at": "2026-08-15T09:00:00.000000Z",
      "updated_at": "2026-08-20T10:00:00.000000Z"
    }
  ]
}
```

  `body` viene de la columna real `content` (se mantiene la clave `body`
  porque es la que ya consumen las webs). `current_page_raw_id` es `null`
  cuando la página usa directamente el campo `body`/`content` sin haberse
  generado desde un formato en bruto (Markdown, editor.js, etc.).
- **Errores**: `404` `{"success": false, "message": "Contenido no encontrado"}`
  si la plataforma o el contenido no existen o el contenido no está
  publicado. Si el contenido existe pero no tiene páginas, responde `200`
  con `"data": []`, no `404`.

### `GET /platforms/{platform:slug}/contents/{content:slug}/pages/{order}` — Una página por su orden

- **Auth**: pública.
- **Parámetros de ruta**: `{platform:slug}`, `{content:slug}` como arriba;
  `{order}` — posición de la página dentro del contenido (entero, empieza en
  `1`). La ruta tiene `->whereNumber('order')`: si `{order}` no es numérico,
  la ruta **ni siquiera casa** y la petición cae en el 404 genérico `"API V2 -
  Endpoint no encontrado"`, no en el 404 propio de este controlador.
- **Respuesta 200**: un único `ContentPageResource` (misma forma que cada
  elemento de `pages` de arriba).
- **Errores**:
  - `404` `{"success": false, "message": "Contenido no encontrado"}` si la
    plataforma o el contenido no existen o el contenido no está publicado.
  - `404` `{"success": false, "message": "Pagina no encontrada"}` (sin tilde,
    tal como lo devuelve el controlador) si el contenido existe pero no tiene
    ninguna página con ese `order`.

### `GET /platforms/{platform:slug}/contents/{content:slug}/related` — Contenidos relacionados

- **Auth**: pública.
- **Parámetros de ruta**: igual que `show`.
- **Sin paginación ni query params**: siempre devuelve como máximo **5**
  contenidos (límite fijo en `ContentService::getRelated()`, no configurable
  desde la URL), de la misma plataforma y del mismo tipo (`type_id`) que el
  contenido base, publicados, ordenados por `published_at` descendente,
  excluyendo el propio contenido.
- **Respuesta 200** (`ContentRelatedResource`):

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
      "image": {
        "id": 88,
        "user_id": 1,
        "file_type_id": 4,
        "module": "content",
        "path": "content/88",
        "storage_path": "app/public/content/88/foto.webp",
        "name": "9f8c7b6a5d4e.webp",
        "width": 1200,
        "height": 630,
        "original_name": "foto.jpg",
        "size": 245678,
        "alt": "Sensores de humedad",
        "title": "Sensores de humedad",
        "is_private": false,
        "created_at": "2026-07-01T09:00:00.000000Z",
        "updated_at": "2026-07-01T09:00:00.000000Z",
        "file_type": { "id": 4, "mime": "image/webp" }
      },
      "type": {
        "id": 3,
        "file_id": null,
        "name": "Tutorial",
        "plural_name": "Tutoriales",
        "slug": "tutorial",
        "description": "Guías paso a paso.",
        "icon": "fa fa-graduation-cap",
        "color": "#3788d8",
        "created_at": "2021-05-08T20:21:49.000000Z",
        "updated_at": "2021-05-08T20:21:49.000000Z"
      },
      "published_at": "2026-08-10T10:00:00.000000Z"
    }
  ]
}
```

  Ojo con `image` en este endpoint concreto: a diferencia de todos los demás
  Resources de este archivo, **no** pasa por `SocialImageResource`. Es el
  modelo `File` completo tal cual lo serializa Eloquent (incluye columnas
  internas como `path`, `storage_path`, `name` interno, `size`, `is_private`)
  más la relación `fileType` anidada bajo `file_type` si venía cargada — no
  hay recorte pensado para exponerlo por API. `image` es `null` si el
  contenido no tiene imagen. `type` es el modelo completo (igual que en
  `ContentResource`), precargado (`with(['type', 'seo', 'image.fileType'])`
  en `ContentService::getRelated()`).
- **Errores**: `404` `{"success": false, "message": "Contenido no encontrado"}`
  si la plataforma o el contenido base no existen o no está publicado. Si el
  contenido existe pero no hay relacionados, responde `200` con `"data": []`.

---

## Lo que existió y ya no tiene ruta (no lo reimplementes igual)

| Ruta antigua | Qué pasó |
|---|---|
| `GET /platforms/{slug}/featured` | Es `GET /platforms/{slug}/contents?featured=1` |
| `GET /platforms/{slug}/content/type/{tipo}` | Es `GET /platforms/{slug}/contents?type={tipo}` |

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
