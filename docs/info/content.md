# Módulo: CMS / Contenidos (Content)

Sistema de gestión de contenidos multi-plataforma y multi-tipo. Soporta artículos, tutoriales, proyectos, páginas y reseñas con páginas paginadas, SEO, metadata, categorías, tags, tecnologías, contribuidores, archivos, galerías y contenido relacionado.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Content/Content.php` | `contents` | Contenido principal |
| `app/Models/Content/ContentPage.php` | `content_pages` | Páginas del contenido |
| `app/Models/Content/ContentPageRaw.php` | `content_page_raw` | Contenido raw (HTML/Markdown/JSON) de páginas |
| `app/Models/Content/ContentAvailablePageRaw.php` | `content_available_page_raw` | Tipos de raw disponibles |
| `app/Models/Content/ContentAvailableType.php` | `content_available_types` | Tipos de contenido disponibles |
| `app/Models/Content/ContentAvailableStatus.php` | — | Estados disponibles |
| `app/Models/Content/ContentAvailableCategory.php` | — | Categorías disponibles |
| `app/Models/Content/ContentCategory.php` | `content_categories` | Pivot contenido ↔ categoría |
| `app/Models/Content/ContentTag.php` | `content_tags` | Pivot contenido ↔ tag |
| `app/Models/Content/ContentTechnology.php` | `content_technologies` | Pivot contenido ↔ tecnología |
| `app/Models/Content/ContentContributor.php` | `content_contributors` | Pivot contenido ↔ usuario contribuidor |
| `app/Models/Content/ContentFile.php` | `content_files` | Pivot contenido ↔ archivo |
| `app/Models/Content/ContentGallery.php` | `content_galleries` | Pivot contenido ↔ galería |
| `app/Models/Gallery.php` | `galleries` | Galería reutilizable de imágenes (no exclusiva de Content) |
| `app/Models/GalleryImage.php` | `gallery_images` | Imagen (FK a `files`) perteneciente a una `Gallery` |
| `app/Models/Content/ContentRelated.php` | `content_related` | Relación contenido ↔ contenido |
| `app/Models/Content/ContentSeo.php` | `content_seo` | Datos SEO del contenido |
| `app/Models/Content/ContentMetadata.php` | `content_metadata` | Metadata externa (repos, redes sociales) |
| `app/Models/ContentDailyView.php` | `content_daily_views` | Vistas diarias |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Content/V2/ContentController.php` | API V2 | show, pages, related |
| `app/Http/Controllers/Content/*.php` | Web | Controladores frontend (12 archivos) |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Content/ContentService.php` | Lógica: getBySlug, getRelated, getFeaturedForPlatform |
| `app/Services/Content/ContentSeoService.php` | Lógica SEO del contenido |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/Content/ContentResource.php` | Resource contenido completo |
| `app/Http/Resources/V2/Content/ContentPageResource.php` | Resource páginas |
| `app/Http/Resources/V2/Content/ContentRelatedResource.php` | Resource contenido relacionado (ligero) |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/ContentStatusEnum.php` | Estados: borrador, publicado, archivado, etc. |
| `app/Enums/ContentTypeEnum.php` | Tipos: artículo, tutorial, proyecto, página, reseña |
| `app/Enums/ContentPageRawTypeEnum.php` | Tipos raw: HTML, Markdown, JSON |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/ContentPolicy.php` | Política de autorización |
| `app/Console/Commands/Content/PublishContentCommand.php` | Publicar contenido programado |
| `app/Console/Commands/SitemapGeneratorCommand.php` | Generar sitemap XML |

## Campos del modelo Content

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `author_id` | int | FK → `users.id` — autor |
| `platform_id` | int | FK → `platforms.id` — plataforma |
| `status_id` | int | FK → `content_available_statuses.id` |
| `type_id` | int | FK → `content_available_types.id` |
| `image_id` | int | FK → `files.id` — imagen principal |
| `title` | string | Título del contenido |
| `slug` | string | Slug URL único |
| `excerpt` | text | Extracto/resumen |
| `is_copyright_valid` | boolean | Copyright válido |
| `is_comment_enabled` | boolean | Comentarios habilitados |
| `is_comment_anonymous` | boolean | Comentarios anónimos |
| `is_active` | boolean | Contenido activo |
| `is_featured` | boolean | Contenido destacado |
| `is_visible` | boolean | Visible públicamente |
| `is_visible_on_home` | boolean | Visible en home |
| `is_visible_on_menu` | boolean | Visible en menú |
| `is_visible_on_footer` | boolean | Visible en footer |
| `is_visible_on_sidebar` | boolean | Visible en sidebar |
| `is_visible_on_search` | boolean | Indexable en búsqueda |
| `is_visible_on_archive` | boolean | Visible en archivo |
| `is_visible_on_rss` | boolean | Incluir en RSS |
| `is_visible_on_sitemap` | boolean | Incluir en sitemap |
| `is_visible_on_sitemap_news` | boolean | Incluir en sitemap news |
| `processed_at` | timestamp | Fecha de procesamiento |
| `published_at` | timestamp | Fecha de publicación |
| `scheduled_at` | timestamp | Fecha de publicación programada |

## Campos del modelo ContentPage

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `content_id` | int | FK → `contents.id` |
| `current_page_raw_id` | int | FK → tipo de raw activo |
| `image_id` | int | FK → `files.id` |
| `title` | string | Título de la página |
| `slug` | string | Slug |
| `content` | text | Contenido |
| `order` | int | Orden de la página |

## Campos del modelo ContentSeo

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `content_id` | int | FK → `contents.id` |
| `image_id` | int | FK → `files.id` — imagen SEO |
| `image_alt` | string | Alt de la imagen |
| `distribution` | string | Distribución |
| `keywords` | string | Palabras clave |
| `revisit_after` | string | Revisita |
| `description` | text | Meta description |
| `robots` | string | Robots meta |
| `og_title` | string | Open Graph title |
| `og_type` | string | Open Graph type |
| `twitter_card` | string | Twitter card type |
| `twitter_creator` | string | Twitter creator |

## Campos del modelo ContentMetadata

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `content_id` | int | FK → `contents.id` |
| `web` | string | URL web |
| `telegram_channel` | string | Canal Telegram |
| `youtube_channel` | string | Canal YouTube |
| `youtube_video` | string | Video YouTube |
| `youtube_video_id` | string | ID del video YouTube |
| `gitlab` | string | URL GitLab |
| `github` | string | URL GitHub |
| `mastodon` | string | URL Mastodon |
| `twitter` | string | URL Twitter |

## Relaciones principales (Content)

- `Content` → `BelongsTo` → `User` (vía `author_id`)
- `Content` → `BelongsTo` → `Platform` (vía `platform_id`)
- `Content` → `BelongsTo` → `ContentAvailableType` (vía `type_id`)
- `Content` → `BelongsTo` → `File` (vía `image_id`)
- `Content` → `HasMany` → `ContentPage` (vía `content_id`)
- `Content` → `HasMany` → `ContentCategory` (vía `content_id`)
- `Content` → `HasMany` → `ContentTag` (vía `content_id`)
- `Content` → `HasMany` → `ContentTechnology` (vía `content_id`)
- `Content` → `HasMany` → `ContentContributor` (vía `content_id`)
- `Content` → `HasMany` → `ContentFile` (vía `content_id`)
- `Content` → `BelongsToMany` → `Gallery` (vía pivote `content_galleries`; inversa `Gallery::contents()`)
- `Content` → `HasMany` → `ContentRelated` (vía `content_id`)
- `Content` → `HasOne` → `ContentSeo` (vía `content_id`)
- `Content` → `HasOne` → `ContentMetadata` (vía `content_id`)
- `ContentPage` → `HasMany` → `ContentPageRaw` (vía `content_page_id`)

## Rutas API V2

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/content/{platform:slug}/{content:slug}` | No | Ver contenido por plataforma y slug |
| GET | `/api/v2/content/{content:slug}/pages` | No | Páginas de un contenido |
| GET | `/api/v2/content/{content:slug}/pages/{order}` | No | Una página concreta del contenido por su orden (numérico) |
| GET | `/api/v2/content/{content:slug}/related` | No | Contenido relacionado |

## Comando de debug

```bash
php artisan debug:seed-content --count=10
```

> El comando ya **no crea categorías** (deben existir vía `CategoriesSeeder`) y
> ahora genera registros en `content_daily_views` (últimos 7 días + hoy).

## Estadísticas de vistas (fix_11)

- Modelo `ContentDailyView` (`content_daily_views`): vistas diarias por contenido.
- Relaciones: `Content::dailyViews()` (hasMany) y `ContentDailyView::content()` (belongsTo).
- Al consultar un contenido por API v2 (`ContentController::show`) se despacha
  `ProcessContentViewJob` que hace upsert de la vista del día. No se registran
  vistas en `pages()` ni `related()`.
- La FK `content_daily_views.content_id` tiene `onDelete('cascade')` (migración
  `2026_05_28_000001_add_cascade_delete_to_content_daily_views`): al hacer
  `forceDelete` de un contenido se eliminan sus vistas; el soft delete las conserva.

## Buscador de vídeos de YouTube

Recupera el plugin JS original (`resources/js/dashboard/youtube_video_search.js`
en `main`) para el panel Filament v2, y a lo largo de la migración se fueron
encontrando y arreglando varios problemas de raíz distinta. Queda documentado
aquí porque ninguno era obvio y todos volvieron a aparecer en revisiones
posteriores.

### Archivos

| Archivo | Descripción |
|---------|-------------|
| `app/Filament/Components/YoutubeVideoField.php` | Componente Filament: `apiKey()`, `channels()`, `platformNames()`, `platformStatePath()` |
| `resources/views/filament/components/youtube-video-field.blade.php` | Vista + componente Alpine (`youtubeVideoField`) |
| `resources/js/youtube-video-search.js` | Clase `YoutubeVideoSearch` (modal de búsqueda vanilla JS, sin Alpine) |
| `resources/css/youtube-video-search-tailwind.css` | CSS del modal **y** del layout del campo (ver «Por qué CSS llano» abajo) |
| `resources/css/youtube-video-search.css` | Copia intacta del CSS original de `main` (Bootstrap/AdminLTE), sin usar — se deja como referencia histórica, no la importa nada |

- Integrado en `ContentResource`, pestaña **«Vídeo y enlaces»**, dentro de un
  `Group->relationship('metadata')` (tabla `content_metadata`).
- El estado del campo es `youtube_video_id`. La URL `youtube_video` se deriva
  automáticamente al guardar (hook `saving` en `ContentMetadata`).
- El canal de búsqueda se resuelve según la plataforma seleccionada
  (`Platform.youtube_channel_id`); `platformNames()` mapea `platform_id => title`
  (columna real de `platforms`, no `name`) para la insignia del canal.
- La API key se toma de `config('google.api_key')` — en `local`/`testing` cae a
  `GOOGLE_DEV_API_KEY` si existe (ver `config/google.php`).
- El JS/CSS se inyectan por `@vite('resources/js/youtube-video-search.js')`
  dentro de un `@push('scripts')` en la propia vista del campo, no por un
  render hook de Filament.

### Bugs de la migración a Filament (todos verificados con Alpine.js/Chrome
### headless real antes de darlos por corregidos, no solo leyendo el código)

- **Input de búsqueda invisible**: el CSS nunca declaraba `border`/
  `background-color` propios para el `<input>` del modal —confiaba en el
  estilo por defecto del navegador—, y el Preflight de Tailwind 4 los deja
  transparentes para cualquier `<input>` sin excepción. Se podía escribir a
  ciegas sin ver nada. Corregido añadiéndolos explícitos.
- **El modal se abría solo**: en `main` el contenedor del modal llevaba
  `class="modal-youtube-video-search-hidden"` directamente en el HTML; se
  perdió al migrar. El modal quedaba visible en cuanto se instanciaba
  `YoutubeVideoSearch` (al montar el campo), y como la pestaña «Vídeo y
  enlaces» está oculta hasta seleccionarla, el efecto era que el buscador se
  abría solo al entrar en la pestaña.
- **Enter enviaba el formulario**: en `main` el input nunca vivía dentro de
  un `<form>`; en Filament todo el recurso es un único
  `<form wire:submit="save">`. `preventDefault()` solo frena la acción nativa
  del navegador —Filament escucha Enter a nivel de formulario para saltar de
  campo (evita envíos accidentales) y ese listener reacciona al evento
  burbujeado igualmente—, hace falta también `stopPropagation()`.
- **Doble montaje / búsqueda muda**: Livewire puede volver a llamar a
  `x-init` sobre el mismo nodo (`wire:ignore` protege a sus hijos de un
  morph, pero no siempre evita esto). Sin guard, la segunda instancia de
  `YoutubeVideoSearch` resolvía su input con `document.querySelector` por id
  global, que siempre devuelve el PRIMER elemento con ese id —el de la
  instancia vieja, invisible debajo—, así que la instancia nueva (la
  visible) enganchaba sus eventos al input equivocado: se podía escribir sin
  que nada reaccionara, con la consola y la pestaña Red completamente
  limpias. Arreglado con un guard de idempotencia
  (`$el.dataset.ytInitialized`) y resolviendo el botón/contenedor dentro de
  `this.$el` en vez de por id global.
- **`domModalGenerate()` llamaba dos veces a `domModalFooterGenerate()`**
  (código muerto ya presente en `main`, inofensivo allí). Al añadir la
  paginación por número, la segunda llamada sobreescribía la referencia real
  al contenedor de números de página con uno huérfano nunca insertado en el
  DOM: los números quedaban invisibles pese a que la lógica interna
  (`currentPage`, `maxKnownPage`, tokens) funcionaba bien. Se quitó la
  llamada duplicada.
- **Layout del campo (no del modal) con `display: block` en vez de
  `flex`**: los botones "Buscar"/"Quitar" y la vista previa usaban
  utilidades de Tailwind (`grid grid-cols-5`, `md:col-span-2`, `flex
  flex-col`) directamente en el blade. En el navegador real con el que se
  probó, `getComputedStyle` confirmaba `display: block` para el contenedor
  pese a que el HTML servido era exactamente el esperado (mismas clases,
  verificado carácter a carácter). Tailwind 4 compila los prefijos
  responsive (`md:`) con sintaxis de rango de Media Queries nivel 4
  (`@media (width >= 48rem)`) en vez del `@media (min-width: 48rem)`
  clásico; no se pudo aislar si era exactamente eso lo que fallaba. En vez
  de seguir dependiendo del pipeline de utilidades de Tailwind para este
  layout concreto, se reescribió con **CSS llano** en
  `youtube-video-search-tailwind.css` (clases `field-*-youtube-video-search`):
  flexbox con `flex-wrap` en vez de grid, `@media (min-width)` y
  `prefers-color-scheme` clásicos — nada que no soporte cualquier navegador
  desde hace más de una década. El resto del panel (Filament/Tailwind del
  vendor) no se toca; esto es solo para el layout propio de este campo.
- **Miniatura pixelada al recargar la página**: el `<iframe
  src="https://www.youtube.com/embed/{id}">` se montaba directamente. Recién
  elegido el vídeo se veía nítido, pero al recargar la página el embed
  arrancaba a intentar reproducir (autoplay del navegador) a la calidad más
  baja mientras bufferizaba, estirada a toda la caja del `aspect-video` —de
  ahí el pixelado, que no aparecía nada más elegir el vídeo porque en ese
  momento no había recarga de por medio. Se sustituyó por el patrón
  "lite embed": una `<img>` estática de `i.ytimg.com/vi/{id}/hqdefault.jpg`
  (resolución fija, siempre disponible) con botón de play superpuesto; el
  `<iframe>` real (con `?autoplay=1`) solo se monta al pulsar.

### Funciones añadidas sobre el original de `main`

- Insignia con el nombre del canal/plataforma (arriba a la izquierda del
  modal), enlaza al canal real de YouTube en pestaña nueva
  (`target="_blank" rel="noopener noreferrer"`).
- Botón de limpiar búsqueda (cuadrado rojo, icono, a la izquierda del
  input) que también vacía la lista de resultados — antes, borrar el texto
  dejaba la lista de la búsqueda anterior a la vista.
- Paginación por número encima de "Página Anterior"/"Página Siguiente",
  reutilizando los `pageToken` ya descubiertos (la API de YouTube solo da
  "siguiente"/"anterior", no salto directo a página N, así que solo se
  listan páginas ya alcanzadas).
- Botón "Quitar vídeo asociado" (rojo, debajo de "Buscar") con modal de
  confirmación propio — antes no había forma de desasociar un vídeo sin
  elegir otro.
- Layout en dos columnas: controles (botones + ID) a la izquierda, vista
  previa más grande a la derecha (antes vídeo debajo, a todo lo ancho).

## Editor.js en Filament (fix_11)

- Componente reutilizable `app/Filament/Components/EditorJsField.php` + vista
  `resources/views/filament/components/editorjs-field.blade.php`.
- Carga Editor.js desde `public/vendor/editorjs/`. Los scripts y el componente
  Alpine `editorJsField` viven en
  `resources/views/filament/components/editorjs-scripts.blade.php` y se
  inyectan vía `renderHook(PanelsRenderHook::SCRIPTS_AFTER, ..., scopes:
  EditContent::class)` en `AdminPanelProvider`. No usar `@push` desde la vista
  del campo: el modal se monta por Livewire tras la carga de la página y el
  push se descartaría. Si el campo se usa en otra página, añadir esa página a
  los `scopes` del hook.
- Fiabilidad del editor en el modal: Alpine llama `init()`/`destroy()`
  automáticamente (sin `x-init`), el `$watch` del state ignora los cambios
  generados por el propio editor (`lastSaved`) para no re-renderizar mientras
  se escribe, y un listener `focusout` vuelca el último cambio antes de pulsar
  «Guardar».
- Integrado en `PagesRelationManager`, con tres pestañas sobre **el mismo
  contenido**: «Editor visual», «JSON en crudo» y «HTML». El JSON se persiste en
  la relación `raw()` (`content_page_raw`, tipo `json`); al editar se carga solo
  el raw de tipo `json` (no el más reciente de cualquier tipo).

  La primera se llamaba «Editor Visual (JSON)» y, con el `helperText`, daba a
  entender que el editor visual se había sustituido por un pegado de JSON. **El
  JSON es el formato de almacenamiento, no la interfaz.** La pestaña de JSON en
  crudo existe a propósito —pegar el contenido de otra página es cómodo cuando
  se sabe lo que se hace— y comparte estado con el editor visual: lo que se pega
  en una se ve en la otra. Valida que sea un objeto con clave `blocks`.

### Las herramientas, y las que se habían perdido

Al migrar el editor de `main` a Filament se quedaron por el camino cinco
herramientas cuyos ficheros JS **seguían en el repositorio**, sin cargarse:
`paragraph`, `image`, `link`, `attaches` y `codebox`. La de imagen es la que más
duele: se sustituyó por `SimpleImage`, que guarda la imagen **incrustada en el
JSON como base64**, lo que hincha la fila de `content_page_raw` y no deja nada
en el módulo de ficheros.

`image`, `attaches` y `linkTool` necesitan endpoints, y ésa es la razón de que
se cayeran. Están en `App\Http\Controllers\Admin\EditorJsController`:

| Ruta | Para qué |
|---|---|
| `POST /admin/editorjs/upload` | Sube el fichero con `File::addFile()` al módulo `content-pages`, así que queda como una fila de `files` más: se ve en el panel, se sirve por `route('file.get', …)` y tiene miniaturas |
| `GET /admin/editorjs/url-metadata` | Título, descripción e imagen de una página externa, para la tarjeta de `linkTool` |

Las dos van detrás de `auth` y del gate **`access-editorjs`** (mismo criterio que
abre el panel: `Admin`, `SuperAdmin` o `Editor`, y la cuenta activa). Devuelven
el formato que exige Editor.js —`{success: 1, file: {…}}` y
`{success: 1, meta: {…}}`—, **no** el `{success, message, data}` de la API v2:
son endpoints del panel, no de la API pública.

⚠️ **`url-metadata` hace una petición saliente a una URL que elige quien
escribe**, o sea SSRF si se deja abierto: `http://169.254.169.254/` es el
servicio de metadatos de media nube y `http://127.0.0.1:9200` es el
Elasticsearch de al lado. Lleva cuatro cierres, y si se toca hay que mantener
los cuatro:

1. sólo `http` y `https` —nada de `file://`, `gopher://` ni `dict://`;
2. el host se **resuelve** y ninguna de sus IPs puede ser privada, de bucle ni
   de enlace local (`interna.midominio.com` puede apuntar a 10.0.0.5);
3. sin seguir redirecciones: una redirección es otra URL que no ha pasado por
   los dos puntos anteriores;
4. tiempo de espera corto, `throttle:30,1` y sólo se leen los primeros 128 KB.

Ante la duda responde `success: 0` y `linkTool` enseña el enlace pelado, que es
un resultado perfectamente válido. Fijado por
`tests/Feature/Filament/EditorJsTest.php`, que prueba las ocho URL que no debe
tocar y comprueba que **no sale ninguna petición**.

## Galerías

Las tablas `galleries` y `gallery_images` existían desde 2019 sin modelo
Eloquent ni recurso Filament; `Content::galleries()` apuntaba a un `HasMany`
sobre `ContentGallery` con un formulario placeholder (un `TextInput` numérico
para escribir a mano el `gallery_id`). Implementado por completo:

- `App\Models\Gallery` (tabla `galleries`, top-level, no bajo `Content/`
  porque es un recurso de imágenes reutilizable, igual que `File`):
  `user()`, `image()` (portada, FK a `files`), `images()` (`HasMany` →
  `GalleryImage`), `contents()` (`BelongsToMany` → `Content`, inversa de
  `Content::galleries()`). `safeDelete()` sobrescrito: borra primero cada
  `GalleryImage` (y su `File`) antes de borrarse a sí misma.
- `App\Models\GalleryImage` (tabla `gallery_images`): `gallery()`, `image()`
  (FK a `files`).
- `Content::galleries()` pasó de `HasMany` a `BelongsToMany` (pivote
  `content_galleries`, sin columnas propias): una galería puede reutilizarse
  en varios contenidos y un contenido puede tener varias galerías.
  `ContentGallery` ahora expone `content()`/`gallery()` para quien consulte
  el pivote directamente, aunque el `belongsToMany` no pasa por `->using()`
  (mismo criterio que `Content::contentsRelated()`).
- Recurso Filament nuevo `app/Filament/Admin/Resources/Galleries/`
  (grupo «Gestión»): CRUD de galerías con portada
  (`ImageCropperUpload` + `HasImageFileUpload`, igual que `CurriculumResource`)
  y un `ImagesRelationManager` para subir/borrar las imágenes de la galería
  (`DeleteAction` sobrescrita para llamar a `$record->safeDelete()` y no dejar
  `files` huérfanos).
- `GalleriesRelationManager` de `ContentResource` reescrito al patrón
  Attach/Detach de `RelatedRelationManager`: `AttachAction` +
  `->inverseRelationship('contents')` explícito (evita que Filament adivine
  mal el nombre del método inverso; ver el bug real que motivó esto en
  `RelatedRelationManager` y `ContributorsRelationManager`).
- Migración `2026_07_02_153015_make_galleries_description_nullable.php`:
  `galleries.description` era `NOT NULL` sin default desde el origen de la
  tabla (2019), lo que impedía crear una galería sin descripción. Como el
  módulo se usa por primera vez ahora, se corrige antes de que produzca datos.
- **Estilos en línea a propósito** en `gallery-images-preview.blade.php` y en
  el HTML del selector de `GalleriesRelationManager` (Attach): el panel Admin
  **no** tiene registrado un tema Tailwind custom (`->viteTheme()` en
  `AdminPanelProvider` se probó y se revirtió — el `resources/css/filament/admin/theme.css`
  del repo estaba huérfano, nunca cargado, y sus reglas sin probar rompían el
  dropzone de FilePond en Hardware/Platforms/Technologies/Categories en cuanto
  se activaban). Mientras no exista un tema custom verificado, cualquier vista
  Blade propia del panel debe usar CSS en línea (`<style>`/`style=""`), no
  clases Tailwind: no hay ningún build que las genere.

---

> Creado: 2026-05-25 · Última revisión: 2026-08-19
