# Módulo: CMS / Contenidos (Content)

Sistema de gestión de contenidos multi-plataforma y multi-tipo. Soporta artículos, tutoriales, proyectos, páginas y reseñas con páginas paginadas, SEO, metadata, categorías, tags, tecnologías, contribuidores, archivos, galerías y contenido relacionado.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Content/Content.php` | `contents` | Contenido principal |
| `app/Models/Content/ContentPage.php` | `content_pages` | Páginas del contenido |
| `app/Models/Content/ContentPageRaw.php` | `content_page_raw` | Contenido raw (HTML/Markdown/JSON) de páginas |
| `app/Models/Content/ContentPageVersion.php` | `content_page_versions` | Historial: lo que tenía una página antes de cada cambio |
| `app/Models/Content/ContentPageDraft.php` | `content_page_drafts` | Borradores sin guardar, uno por usuario y página |
| `app/Models/Content/ContentAvailablePageRaw.php` | `content_available_page_raw` | Tipos de raw disponibles |
| `app/Models/Content/ContentAvailableType.php` | `content_available_types` | Tipos de contenido disponibles |
| `app/Models/Content/ContentAvailableStatus.php` | — | Estados disponibles |
| `app/Models/Content/ContentAvailableCategory.php` | — | Categorías disponibles |
| `app/Models/Content/ContentCategory.php` | `content_categories` | Pivot contenido ↔ categoría |
| `app/Models/Content/ContentTag.php` | `content_tags` | Pivot contenido ↔ tag |
| `app/Models/Content/ContentTechnology.php` | `content_technologies` | Pivot contenido ↔ tecnología |
| `app/Models/Content/ContentContributor.php` | `content_contributors` | Pivot contenido ↔ usuario contribuidor |
| `app/Models/Content/ContentFile.php` | `content_files` | Pivot contenido ↔ archivo |
| `app/Traits/HasGalleries.php` | `galleryables` | Relación polimórfica contenido ↔ galería (vía trait) |
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
| `app/Services/Content/ContentFormatConverter.php` | Conversión de páginas entre Editor.js, Markdown y HTML, y el HTML que se sirve |
| `app/Services/Content/ContentPageFormatService.php` | Fuente única de cada página: guardar, regenerar derivados; al guardar, historial, bloqueo, fecha de apertura, borrador y ficheros sin usar |
| `app/Services/Content/ContentPageHistoryService.php` | Historial de versiones de las páginas (50 por página, 30 días) |
| `app/Services/Content/ContentPageDraftService.php` | Borradores de las páginas en el servidor, uno por usuario y página |
| `app/Services/Content/ContentPageLockService.php` | Bloqueo de cada página a un usuario mientras la edita (`ContentPageLockState`: cómo está para quien pregunta) |
| `app/Services/Content/ContentFileUsageService.php` | Marca los ficheros de contenido que ya no usa nada y borra los que llevan 30 días así |
| `app/Services/Content/ContentContributorService.php` | Añadir y quitar colaboradores, y el colaborador automático por plataforma |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/Content/ContentResource.php` | Resource contenido completo |
| `app/Http/Resources/V2/Content/ContentPageResource.php` | Resource páginas |
| `app/Http/Resources/V2/Content/ContentRelatedResource.php` | Resource contenido relacionado (ligero) |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/ContentStatusEnum.php` | Estados con el id de la base: 1 borrador, 2 programado, 3 publicado, 4 no publicado, 5 copyright, 6 para eliminar (ver «Estados y publicación») |
| `app/Enums/ContentTypeEnum.php` | Tipos: artículo, tutorial, proyecto, página, reseña |
| `app/Enums/ContentPageRawTypeEnum.php` | Tipos raw: HTML, Markdown, JSON (sin uso) |
| `app/Enums/ContentPageFormatEnum.php` | Formatos de edición de una página: `editorjs`, `markdown`, `html` (el tipo en BD de Editor.js es `json`) |
| `app/Enums/ContentPageVersionReasonEnum.php` | Por qué una versión pasó al historial: `save`, `format_change`, `restore`, `draft_restore`, `emptied` |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/ContentPolicy.php` | Política de autorización (ver «Permisos») |
| `app/Policies/ContentPagePolicy.php` | Las páginas siguen a la política de su contenido |
| `app/Models/PlatformUser.php` | Plataformas de un Editor (`platform_user`) y su interruptor de colaborador automático |
| `app/Console/Commands/ContentPublishCommand.php` + `app/Actions/PublishContentAction.php` | `content:publish`: publica los programados cuya fecha ha llegado (cada 5 minutos) |
| `app/Console/Commands/ContentPruneDraftsAndVersionsCommand.php` | `content:prune-drafts-and-versions`: borradores y versiones de más de 30 días, y versiones que pasan de 50 por página (diaria) |
| `app/Console/Commands/ContentPurgeUnusedFilesCommand.php` | `content:purge-unused-files`: ficheros de contenido que llevan 30 días sin usar (diaria) |
| `app/Console/Commands/SitemapGeneratorCommand.php` | Generar sitemap XML |

## Campos del modelo Content

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `author_id` | int | FK → `users.id` — autor |
| `platform_id` | int | FK → `platforms.id` — plataforma |
| `status_id` | int | FK → `content_available_status.id` (`ContentStatusEnum`). Vacío en los contenidos que vienen de la v1 |
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

## Estados y publicación

**Los ids son los de la base real**, que vienen de la v1: 1 borrador, 2
programado, 3 publicado, 4 no publicado, 5 protegido por copyright, 6 para
eliminar. En mayo de 2026 la v2 cambió el orden en el seeder (2 = publicado) y
el código se escribió encima: la API buscaba los publicados con el id de
«programado» y no servía nada. `ContentStatusEnum`, el seeder y
`tests/Traits/SeedsProductionContentStatuses` tienen el mismo orden, y lo
comprueban `ContentStatusOrderTest` y `project:check-config` (que falla si la
base no casa con el enum).

**Una sola definición de «publicado»:** `Content::scopePublished()` = estado
3 **y** «Activo». La usan la API, `Platform::contentsActive()` (fichas y
estadísticas de plataforma) y `ContentAvailableType::contentsActive()`. Antes
había dos que no coincidían (`status_id = 2` y «activo y con fecha»).

**Reglas** (`Content::applyPublicationRules()`, en el evento `saving`, así
que valen igual desde el panel, la acción masiva o el cron):

| Al pasar a… | Pasa |
|---|---|
| Publicado (desde donde sea) | Fecha de publicación de ese momento si no tenía, y «Activo» marcado |
| Otro estado, estando publicado | **Se rechaza** (`ValidationException`): publicado es definitivo. Se retira de las webs desmarcando «Activo», o se elimina |
| Borrador | Sin fecha de publicación |
| Programado | Fecha programada obligatoria. El formulario exige además que sea futura |
| 4, 5 o 6 | No toca las fechas |

`Content::publish()` publica y deja visible (también uno publicado y oculto).
Lo usan la acción masiva «Publicar» del panel (sólo sobre los que el usuario
puede publicar, ver «Permisos») y el cron.

**Cron:** `content:publish` cada 5 minutos, `withoutOverlapping`. Recorre los
programados vencidos de uno en uno con `publish()` (no un `update` masivo), así
que pasan por las reglas y saltan los eventos del modelo, que regeneran la
caché de la plataforma.

**Panel:** el estado se elige de una lista con los nombres del enum; si el
contenido está publicado, el selector queda bloqueado y explica cómo retirarlo.
Al elegir «Programado» aparece «Publicar el», obligatoria y futura. La fecha de
publicación es de sólo lectura. La tabla enseña «Sin estado» en los contenidos
de la v1 (en producción, todos: no se tocan; se publican a mano cuando toque).

## Campos del modelo ContentPage

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `content_id` | int | FK → `contents.id` |
| `current_page_raw_id` | int | FK → `content_available_page_raw.id`: formato fuente de la página, el que se edita (ver «Páginas: un formato por página») |
| `image_id` | int | FK → `files.id` |
| `title` | string | Título de la página |
| `slug` | string | Slug |
| `content` | text | HTML que se sirve a la web; se regenera desde la fuente al guardar |
| `order` | int | Orden de la página |
| `locked_by_user_id` | int | FK → `users.id` (SET NULL): quién la tiene bloqueada |
| `locked_since` | timestamp | Desde cuándo la tiene bloqueada ese usuario (para el aviso) |
| `locked_at` | timestamp | Última renovación del bloqueo: caduca a los 2 minutos |
| `lock_token` | string(64) | Pestaña que tiene el bloqueo |

`content_page_raw` guarda la página en cada formato: la fuente y las versiones
derivadas en Editor.js y Markdown. Hasta F6 del plan de contenidos, las filas
con `deleted_at` eran las copias de antes de un cambio de formato; ahora lo
anterior va al historial (`content_page_versions`) y ya no se crean.

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
- `Content` → `HasMany` → `ContentFile` (vía `content_id`)
- `Content` → `MorphToMany` → `Gallery` (vía tabla polimórfica `galleryables` con trait `HasGalleries`; inversa `Gallery::contents()`)
- `Content` → `HasMany` → `ContentRelated` (vía `content_id`)
- `Content` → `HasOne` → `ContentSeo` (vía `content_id`)
- `Content` → `HasOne` → `ContentMetadata` (vía `content_id`)
- `ContentPage` → `HasMany` → `ContentPageRaw` (`raws()`, vía `content_page_id`)
- `ContentPage` → `BelongsTo` → `ContentAvailablePageRaw` (`currentRawType()`, vía `current_page_raw_id`)
- `ContentPageRaw` → `BelongsTo` → `ContentAvailablePageRaw` (`availableType()`, vía `available_page_raw_id`)
- `ContentPage` → `HasMany` → `ContentPageVersion` (`versions()`) y `ContentPageDraft` (`drafts()`)
- `ContentPage` → `BelongsTo` → `User` (`lockedBy()`, vía `locked_by_user_id`)
- `Content` → `BelongsToMany` → `User` (`contributors()`, pivote `content_contributors`; inversa `User::contributedContents()`)

Las relaciones con pivote (`contributors()`, `technologies()`, `tagsPlatform()`,
`contentsRelated*()`) y las consultas de categorías y etiquetas **ignoran las
filas borradas** del pivote (`wherePivotNull('deleted_at')`). Todas esas tablas
tienen borrado lógico y antes no se filtraba: un colaborador quitado seguía
contando como colaborador.

## Permisos

Admin y SuperAdmin hacen todo. Un Editor trabaja en los contenidos donde es
**autor** (control completo) o **colaborador** (edita). Sus plataformas sólo
dicen **dónde puede crear**, no qué puede editar (D35).

| Acción | Admin | Editor autor | Editor colaborador | Otro Editor |
|---|---|---|---|---|
| Verlo en la lista, abrirlo, editar datos y páginas, subir ficheros, vincular relacionados y galerías | Sí | Sí | Sí | No (404) |
| Eliminar y restaurar | Sí | Sí | No | No |
| Cambiar autor, plataforma y colaboradores (`manage`) | Sí | Sí | No | No |
| Publicar al momento | Sí | Sí | No | No |
| Programar | Cualquier fecha futura | Cualquier fecha futura | Con al menos 7 días de margen | No |

- **Crear:** un Admin, en cualquier plataforma; un Editor, sólo si tiene alguna
  plataforma asignada, sólo en ésas y siempre como autor (`createIn()`).
- **Borrar definitivamente:** sólo el SuperAdmin.
- Un contenido sin plataforma sólo lo alcanzan los administradores y su autor.
- Las páginas siguen al contenido: `ContentPagePolicy` delega en `update` de
  `ContentPolicy`.

Fijado por `tests/Unit/Policies/ContentPolicyTest.php` (la matriz: cada perfil
por cada acción, incluido el colaborador quitado) y
`tests/Feature/Filament/ContentPermissionsPanelTest.php` (el panel).

### Dónde se aplica

- **Lista:** `ContentResource::getEloquentQuery()` le deja a un Editor sólo los
  contenidos donde es autor o colaborador. Uno ajeno, abierto por URL, da 404.
- **Plataforma:** al crear, sólo las suyas (validado en el servidor con `in`,
  no sólo en el desplegable). Bloqueada para quien no tiene `manage`.
- **Autor:** un Editor que crea es el autor; el campo sale bloqueado y
  `CreateContent` lo fija por si llegara otro valor. Al cambiarlo, sólo vale un
  usuario activo Admin o Editor (un autor antiguo de otro rol se conserva).
- **Estado:** quien no puede publicar no tiene «Publicado» en la lista, y
  «Publicar el» se valida con `schedule()`, con el aviso de los 7 días.
- **Acciones masivas** «Publicar» y «Eliminar»: `authorizeIndividualRecords()`,
  contenido a contenido. Los que no puede se saltan y Filament lo avisa.
- **Relaciones:** Colaboradores (añadir y quitar sólo con `manage`), Relacionados
  y Galerías (vincular y desvincular con `update`). Las tres se ocultan a quien
  no puede ver el contenido.
- **Rutas del editor:** `can:update,content` (ver «Guardar una página»).
- **Secciones de la ficha y vista previa:** cada sección pide `update` (la
  vista previa, `view`); un contenido ajeno da 404, como la ficha, porque la
  consulta del listado ya no lo encuentra. La pantalla propia de páginas (F8)
  usará la misma política.

### Colaboradores

- Quitar un colaborador **borra su fila** de `content_contributors` (borrado
  lógico). Esa fila es la marca de «quitado a mano» y se respeta siempre.
- `App\Services\Content\ContentContributorService`: `add()` (recupera la fila
  borrada en vez de duplicarla; el autor nunca es colaborador de lo suyo),
  `remove()`, `applyToNewContent()` y `applyToExistingContents()`.
- **Colaborador automático** (`platform_user.auto_contributor`, modelo
  `App\Models\PlatformUser`):
  - al crear un contenido en la plataforma entran los Editores activos con el
    interruptor encendido, menos el autor;
  - al encenderlo, entra en los contenidos que ya existen en esa plataforma,
    salvo en los que tenga fila (activa o quitada a mano);
  - al apagarlo no sale de ninguno: sólo deja de entrar en los nuevos.
- Se gestiona en la ficha del usuario (Sistema → Usuarios → «Plataformas»,
  visible con el rol Editor): plataforma + interruptor «Colaborador automático».
- `Content::saveContributors()` sólo toca la relación. Antes, con una lista
  vacía, `contributors()->delete()` **borraba los usuarios**. `saveTags()` y
  `saveCategories()` guardan la etiqueta o categoría de la plataforma, quitan
  (fila borrada) las que sobran y recuperan las que vuelven.

### Selectores de vincular

Autor, colaboradores, relacionados y galerías buscan al escribir, desde dos
letras, 50 resultados como mucho y sin precargar la tabla entera, y enseñan
sólo nombres. Los colaboradores posibles son Editores activos que no sean el
autor ni colaboren ya (un administrador no necesita serlo); en la tabla de
colaboradores, la columna de email es sólo para administradores. Los
relacionados son sólo contenidos de la misma plataforma que el usuario alcanza.

## Rutas API V2

Contrato completo en [`api/v2/content.md`](api/v2/content.md).

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/platforms/{platform:slug}/contents` | No | Contenidos publicados y activos de una plataforma |
| GET | `/api/v2/platforms/{platform:slug}/contents/{content:slug}` | No | Un contenido publicado |
| GET | `/api/v2/platforms/{platform:slug}/contents/{content:slug}/pages` | No | Páginas de un contenido; `?format=editorjs\|markdown\|html` |
| GET | `/api/v2/platforms/{platform:slug}/contents/{content:slug}/pages/{order}` | No | Una página por su orden (numérico); `?format=` igual |
| GET | `/api/v2/platforms/{platform:slug}/contents/{content:slug}/related` | No | Contenido relacionado |

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
- La FK `content_daily_views.content_id` tiene `onDelete('cascade')`: al hacer
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
| `app/Filament/Components/YoutubeVideoField.php` | Componente Filament: `searchEndpoint()`, `channels()`, `platformNames()`, `platformStatePath()` |
| `app/Http/Controllers/Admin/YouTubeSearchController.php` | Endpoint proxy `/admin/youtube/search` con Gate `access-youtube-search` y rate limiting |
| `app/Services/YouTube/YouTubeService.php` | Servicio backend que consulta la API de YouTube v3 y cachea respuestas 30 min |
| `resources/views/filament/components/youtube-video-field.blade.php` | Vista + componente Alpine (`youtubeVideoField`) con inyección de `searchEndpoint` |
| `resources/js/youtube-video-search.js` | Clase `YoutubeVideoSearch` (modal de búsqueda vanilla JS, consume endpoint local) |
| `resources/css/youtube-video-search-tailwind.css` | CSS del modal **y** del layout del campo (ver «Por qué CSS llano» abajo) |
| `resources/css/youtube-video-search.css` | Copia intacta del CSS original de `main` (Bootstrap/AdminLTE), sin usar — se deja como referencia histórica, no la importa nada |

- Integrado en `ContentResource`, pestaña **«Vídeo y enlaces»**, dentro de un
  `Group->relationship('metadata')` (tabla `content_metadata`).
- El estado del campo es `youtube_video_id`. La URL `youtube_video` se deriva
  automáticamente al guardar (hook `saving` en `ContentMetadata`).
- El canal de búsqueda se resuelve según la plataforma seleccionada
  (`Platform.youtube_channel_id`); `platformNames()` mapea `platform_id => title`
  (columna real de `platforms`, no `name`) para la insignia del canal.
- **Seguridad y Quota**: La clave de API de Google (`config('google.api_key')`) **nunca se envía al cliente**. El frontend llama a `/admin/youtube/search`, protegido por sesión de Filament (`auth`), Gate `access-youtube-search` (SuperAdmin, Admin, Editor) y rate limiting (`throttle:30,1`). Las respuestas se cachean 30 minutos en Redis/DB para no quemar la cuota de YouTube (100 unidades por búsqueda). Documentación técnica completa y guía de portabilidad en [`youtube-video-search.md`](youtube-video-search.md).
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
- **Editor.js 2.31.7 empaquetado por Vite** (desde el 2026-09-24): núcleo,
  herramientas, traducción y el componente Alpine `editorJsField` están en
  `resources/js/filament/` (`editorjs.js` es la entrada, `editorjs-i18n.js` los
  textos, `editorjs-code.js` el bloque de código). Antes eran ficheros sueltos
  en `public/vendor/editorjs` (Editor.js 2.29 y herramientas de versiones
  mezcladas), ya borrados. Las versiones están **fijadas** en `package.json`
  (sin `^`): una actualización puede cambiar el formato de los datos, como
  pasó con las listas, y se hace a propósito (ver «Actualizar el editor»).
- `resources/views/filament/components/editorjs-scripts.blade.php` deja los
  endpoints en `window.editorJsEndpoints` y carga el paquete con `@vite`. Se
  inyecta vía `renderHook(PanelsRenderHook::SCRIPTS_AFTER, ..., scopes:
  EditContent::class)` en `AdminPanelProvider`. No usar `@push` desde la vista
  del campo: el modal se monta por Livewire tras la carga de la página y el
  push se descartaría. Si el campo se usa en otra página, añadir esa página a
  los `scopes` del hook.
- **Todo en español**: el diccionario `i18n` cubre la interfaz del núcleo, los
  nombres de bloque, los ajustes y los textos de cada herramienta. El bloque de
  código escribe unos pocos textos directamente en el HTML sin pasar por
  `i18n` («Hide Numbers», «Copied!»…): se cambian en el DOM con un
  `MutationObserver` que no toca nada editable. Comprobado abriendo todos los
  menús en Chrome: 109 textos de interfaz, ninguno en inglés.
- **Títulos del 3 al 6** (3 por defecto): el h1 es el título del contenido y
  el h2 el de la página.
- **Atajos**: resaltar `Cmd/Ctrl+Shift+M`, aviso `Cmd/Ctrl+Shift+W`, cita
  `Cmd/Ctrl+Shift+O` y código en línea `Cmd/Ctrl+Shift+C` (en `main`, código
  en línea compartía atajo con resaltar).
- **Bloque de código**: `@calumk/editorjs-codecup`, sucesor de
  `@calumk/editorjs-codeflask` (el de `main`, retirado de npm). Guarda
  `{code, language, showlinenumbers, showCopyButton}`, compatible con lo que
  ya pintaba `_code.blade.php`. El paquete trae Prism con sólo HTML, CSS y JS
  y un autoloader que **bajaba el resto de lenguajes de cdnjs**: los lenguajes
  del desplegable vienen de npm (`prismjs`) y el autoloader apunta a una ruta
  local, así que el panel no carga scripts de fuera.
- **`data-empty`**: desde la 2.30, Editor.js marca con
  `data-empty="true|false"` los elementos de bloque de cada zona editable y
  las herramientas que guardan `innerHTML` (la alerta) se lo llevaban al JSON
  y al HTML servido. `editorJsField` lo quita al volcar el contenido.
- Modo oscuro: `panel.css` da la paleta del panel a los menús del editor, la
  cabecera de la tabla, los botones de subir y el desplegable de lenguajes
  (quedaban blanco sobre blanco), y tamaño a los títulos.
- Fiabilidad del editor en el modal: Alpine llama `init()`/`destroy()`
  automáticamente (sin `x-init`), el `$watch` del state ignora los cambios
  generados por el propio editor (`lastSaved`) para no re-renderizar mientras
  se escribe, y un listener `focusout` vuelca el último cambio antes de pulsar
  «Guardar».
- Integrado en `PagesRelationManager` como el editor del formato Editor.js
  (ver «Páginas: un formato por página»), con dos pestañas sobre **el mismo
  contenido**: «Editor visual» y «JSON en crudo».

  La primera se llamaba «Editor Visual (JSON)» y, con el `helperText`, daba a
  entender que el editor visual se había sustituido por un pegado de JSON. **El
  JSON es el formato de almacenamiento, no la interfaz.** La pestaña de JSON en
  crudo existe a propósito —pegar el contenido de otra página es cómodo cuando
  se sabe lo que se hace— y comparte estado con el editor visual: lo que se pega
  en una se ve en la otra. Valida que sea un objeto con clave `blocks`.
- La herramienta de alertas no cargaba en la v2 (el editor la buscaba en
  `window` con otro nombre) y las alertas de la v1 salían como «The block can
  not be displayed correctly». Con el paquete de Vite las herramientas se
  importan, no se buscan en `window`.
- Las imágenes que sube el editor de la v2 sólo traen `url`; la vista
  `editor.fields._image` pedía también `url_thumbnail` y `url_large` y
  reventaba al generar el HTML. `TextFormatParseHelper::getImageRaw()` usa
  ahora `url` para las que falten (las de la v1 traen las tres y salen igual).

## Páginas: un formato por página

Cada página se escribe en **un** formato —Editor.js, Markdown o HTML—, su
**fuente**, que marca `content_pages.current_page_raw_id`. En el panel sólo se
ve y se edita el editor de ese formato; los demás se regeneran a partir de él
al guardar. Una página nueva empieza en Editor.js.

### Qué se guarda (`ContentPageFormatService::save()`)

1. La fuente, en `content_page_raw` con su tipo (`json`, `markdown` o `html`).
2. `content_pages.content`: el HTML que se sirve, generado desde la fuente.
   En páginas Editor.js lo genera `TextFormatParseHelper`, el mismo de la v1:
   comprobado con las 19 páginas reales, sale **idéntico** al que ya había.
3. Las versiones derivadas en Editor.js y Markdown (la que no sea la fuente).
   El HTML no se guarda aparte: es `content`. Si una derivada falla, se
   registra en el log y no impide guardar la fuente; la API la convierte al
   vuelo.
4. Si el contenido cambia, lo que había pasa al **historial de versiones**
   (ver «Borradores, bloqueo, historial y ficheros sin usar»), con su motivo:
   cambio de formato, recuperación, página vaciada o guardado normal.
   `latestBackup()` devuelve la última versión.

Un editor vacío no guarda nada (vaciar el editor por error no borra la página).

### Páginas sin `current_page_raw_id`

No se ha migrado nada. `sourceFormat()` decide así, y la próxima vez que se
guarda la página queda marcada:

- tiene JSON de Editor.js → Editor.js (así están las páginas de la v1);
- sólo tiene HTML en `content` → HTML;
- vacía → Editor.js.

### Listas: dos formatos

`@editorjs/list` 2.x (el del editor desde el 2026-09-24) guarda
`{style, meta, items: [{content, meta, items}]}`, con listas anidadas,
`style: checklist` y numeración con letras o romanos (`meta.counterType`,
`meta.start`). Las 24 listas de la v1 están en el formato viejo (`items` como
texto) y pasan al nuevo cuando se vuelve a guardar la página.

`TextFormatParseHelper::listItems()` lee los dos (y los de `checklist` y
NestedList) y las vistas `_list` / `_checkbox` pintan las sublistas dentro del
elemento padre (`_list_box`, `_checkbox_box`). Una lista de casillas se pinta
igual que el bloque `checklist`. Para una lista plana el HTML es **el mismo
byte a byte** que el de la v1.

Lo sujeta `ServedHtmlRegressionTest` con tres juegos de las 19 páginas reales:
tal cual, con las listas pasadas al formato nuevo, y **regrabadas por el
editor del panel** sin tocar nada (`tests/Fixtures/content-pages/editorjs-2.31.7`).
Al regrabar sólo cambian cosas que la web no ve: las listas (formato nuevo y
sin el `<br>` final de cada elemento), el pie de foto vacío (`null` → `""`),
`textVariant` (`null` → `""`) y los datos del separador (`[]` → `{}`). La vista
del párrafo pintaba una clase suelta `r-paragraph-` con `textVariant: ""`; ya
no.

### Actualizar el editor

1. Subir las versiones en `package.json` (exactas) y `pnpm install`.
2. Buscar en cada paquete sus `i18n.t(...)` y textos fijos nuevos y
   añadirlos a `editorjs-i18n.js`.
3. Abrir las páginas reales en el editor, regrabarlas con `editor.save()` sin
   tocar nada y pasar `ServedHtmlRegressionTest` con ese JSON (un juego nuevo
   de fixtures con la versión en el nombre de la carpeta).
4. `pnpm build` y commitear `public/build`.

### Conversiones (`ContentFormatConverter`)

| De → a | Cómo |
|---|---|
| Editor.js → Markdown | Bloque a bloque. Sólo pasa a Markdown «de verdad» lo que, al volver a leerlo, da el mismo bloque con el mismo texto, enlaces (y su `target`), imágenes, clases y estilos (`survivesRoundTrip()`). Lo demás va envuelto (ver abajo) |
| Editor.js → HTML | El HTML de cada bloque (el de la web), cada uno en su envoltorio |
| Markdown → HTML | CommonMark con tablas, listas de tareas, tachado y autoenlaces |
| Markdown / HTML → Editor.js | Se recorre el HTML: párrafos, títulos, listas (anidadas incluidas, en el formato de `@editorjs/list` 2.x), listas de tareas, citas (con pie si el último párrafo empieza por «—»), código, separadores, tablas e imágenes. Los títulos h1 y h2 pasan a h3, con aviso. Lo que no tiene bloque va como bloque `raw` (HTML), también una lista que mezcla numerada y con viñetas en sus niveles (en Editor.js todos los niveles son del mismo tipo) |
| HTML → Markdown | HTML → bloques → Markdown, con las mismas reglas |

**El envoltorio.** Un bloque que Markdown no sabe expresar (imagen del módulo
de ficheros, con pie o con estilos; alerta; vídeo; tarjeta de enlace; adjunto;
párrafo con variante; tabla sin cabecera…) va como HTML:

```html
<div data-editorjs-block="…base64 de {block, hash}…">
…el HTML que genera ese bloque…
</div>
```

La web ve el HTML del bloque (el envoltorio se quita al servir, también en
páginas HTML) y, al volver a Editor.js, el bloque se recupera **tal cual**. Si
se ha cambiado el HTML de dentro, la huella (`hash`) no coincide y lo que
vuelve es un bloque `raw` con esos cambios, nunca el bloque viejo pisando lo
editado. Comprobado con las 21 páginas de la copia local: Editor.js → Markdown →
Editor.js se ve igual en todas, y Editor.js → HTML → Editor.js devuelve los
bloques exactos.

Detalles que costaron:

- `Str::markdown()` usa el conversor de GitHub, que **escapa los `<iframe>`**:
  un vídeo dentro del Markdown salía como texto. Se usa un conversor propio
  con las mismas extensiones menos `DisallowedRawHtmlExtension`, y con
  `allow_unsafe_links: false` para los enlaces `javascript:` de Markdown.
- La v1 quita los saltos de línea del texto al generar el HTML (la web muestra
  «ahorrarmesubir»). El Markdown dice lo que ya ve la web, no lo que se ve en
  el editor.
- Dos listas seguidas son una sola en Markdown aunque las separe una línea en
  blanco: se separan con `<!-- -->`.
- Los bloques que pasan a Markdown de verdad se sirven como HTML estándar
  (`<h2>`, `<ul>`…), sin las clases `r-*` del HTML de Editor.js. El aviso de
  la conversión lo dice.

### Guardar una página

`ContentPageFormatService::savePage()` guarda los datos de la página (título,
slug, orden, imagen) y su contenido **en una sola transacción**: si algo falla,
no se guarda nada, el modal sigue abierto con lo escrito y una notificación dice
por qué. Antes se guardaban por separado y un contenido que fallaba dejaba el
título cambiado y el contenido viejo. El panel lo usa con `->using()`.

Antes de escribir nada:

1. **Validación de bloques** (`ContentBlockValidator`): cada bloque trae su dato
   principal —imagen y adjunto con fichero, vídeo con dirección, tarjeta con
   enlace, tabla con filas, lista con elementos, código con texto, título con
   texto— y es de un tipo conocido. Si no, el campo del formulario enseña
   «Bloque 7 (imagen): no tiene fichero. Quítalo o vuelve a subir la imagen».
   Un párrafo vacío no es error.
2. **Limpieza del HTML** (`ContentHtmlSanitizer`, con `symfony/html-sanitizer`),
   para todo el mundo: en los textos de los bloques sólo quedan negrita,
   cursiva, subrayado, tachado, enlace (`http`, `https`, `mailto`), código en
   línea, resaltado y salto de línea; en el mensaje de la alerta, además `div` y
   `p`; en el título y la descripción de una tarjeta de enlace, sólo texto. Lo
   que ya estaba limpio se guarda **sin cambiar un carácter** (las 19 páginas
   reales no cambian). Los bloques `raw` y `code` no se tocan.
3. **HTML libre, sólo administradores** (Admin y SuperAdmin):
   - un Editor no ve el bloque de HTML ni «JSON en crudo», ni puede pasar una
     página a HTML; si la página ya está en HTML, cambia título, slug, orden e
     imagen, pero no el HTML;
   - en el servidor, a un Editor se le rechaza un bloque `raw` nuevo o cambiado
     (se compara con el HTML de los que ya tiene la página) y cualquier cambio de
     HTML. Los que puso un administrador se conservan: sin la herramienta, el
     editor los enseña como «no se puede mostrar» y los guarda tal cual;
   - el HTML que un Editor escribe dentro de un Markdown se limpia con la misma
     lista (`ContentMarkdownSanitizer`): etiqueta a etiqueta, sin tocar la
     sintaxis de Markdown ni el código. Los envoltorios
     `<div data-editorjs-block>` no se dan por buenos por su huella (no lleva
     secreto): se limpia el bloque que guardan y se vuelve a generar su HTML.

«JSON en crudo» se carga indentado y pasa por las mismas comprobaciones.

**Plantillas que aguantan datos incompletos** (`TextFormatParseHelper` y
`editor/fields/_*`): antes daban por hecho que cada bloque traía todo, y los
adjuntos subidos desde el editor de la v2, las tarjetas de enlace de webs que
no se dejan leer o un JSON pegado a mano reventaban el guardado. Ahora pintan lo
que hay: tarjeta sin datos → enlace normal; adjunto sin miniatura ni icono →
nombre y descarga; cita sin autor → sin línea de autor; alerta sin tipo →
informativa a la izquierda; vídeo sin medidas → 580×320; aviso sin título →
sólo el mensaje; título sin nivel → h3. El código de un bloque de código se
enseña como texto (antes un `<div>` dentro se interpretaba como HTML).

Queda una cosa: al pegar algo en «JSON en crudo», el editor visual lo pinta
antes de guardar, así que un `onerror` pegado se ejecuta en el navegador de
quien lo pega (sólo administradores ven esa pestaña). Al guardar se limpia y ya
no se ejecuta para nadie más.

### El panel (`PagesRelationManager`)

- Un aviso arriba dice el formato de la página y tiene los botones «Pasar a …»
  y, si hay historial, «Recuperar versión anterior». Columna «Formato» en la tabla.
- **Editor.js**: el de siempre, con «Editor visual» y «JSON en crudo».
- **Markdown**: `MarkdownEditor` (sin adjuntos: las imágenes van por URL) y
  una pestaña «Vista previa».
- **HTML**: `CodeEditor` y «Vista previa». **No** el `RichEditor` de antes: el
  `RichEditor` pasa el HTML por TipTap al cargar y al guardar, y en las páginas
  reales se comía entre el 70 y el 80 % del marcado (figuras, pies de foto,
  clases de las tablas) aunque sólo se cambiase el título. Ojo si se guardó
  alguna página desde el panel v2 antes de esto: su `content` pudo quedar
  recortado.
- Las vistas previas pasan por `Str::sanitizeHtml()`: las ve quien administra
  el panel y el contenido lo puede escribir un editor.
- Al guardar una página Markdown o HTML con títulos h1 o h2 sale un aviso (no
  impide guardar): en la web esos niveles son el título del contenido y el de
  la página. Markdown y HTML sí los guardan; sólo Editor.js los limita.

Cambiar de formato, paso a paso:

1. «Pasar a Markdown» convierte lo que hay en pantalla y lo enseña en un modal
   con los avisos de lo que cambia. **No guarda nada.**
2. «Convertir y editar en Markdown» abre la página en Markdown con el
   resultado. El aviso pasa a «(sin guardar)» y aparece «Deshacer y volver a
   Editor.js», que la deja como estaba.
3. Para guardar hay que marcar la casilla «Entiendo que la página pasa de
   Editor.js a Markdown…». Al guardar, Markdown es el formato de la página, se
   regenera todo desde él y el Editor.js anterior pasa al historial.
4. «Recuperar versión anterior» sigue el mismo camino: enseña la última
   versión del historial, la abre sin guardar, se puede deshacer y hay que
   confirmar al guardar. Lo que había pasa a su vez al historial.

Los campos ocultos del formulario (`source_format`, `stored_format`,
`original_format`, `original_content`, `pending_change`, `backup_id`,
`opened_at`) son el estado de ese camino, no columnas. `backup_id` viene del
formulario, así que se comprueba que la versión sea de esa página antes de
usarla. `opened_at` es la fecha de la página al abrir el modal: si alguien la
guarda mientras tanto, no se pisa y lo escrito va al borrador (ver D4 abajo).

El modal no coge el bloqueo (eso llega con la pantalla propia de F8), pero sí
lo respeta: si alguien tiene la página bloqueada, no guarda y dice quién.

### API

`GET …/pages` y `GET …/pages/{order}` devuelven `body` en el formato de cada
página y dicen cuál en `format` (y la fuente en `source_format`); con
`?format=editorjs|markdown|html` se pide otro. Contrato en
[`api/v2/content.md`](api/v2/content.md).

### Las herramientas, y las que se habían perdido

Al migrar el editor de `main` a Filament se quedaron por el camino cinco
herramientas cuyos ficheros JS **seguían en el repositorio**, sin cargarse:
`paragraph`, `image`, `link`, `attaches` y `codebox` (hoy todas cargan, y el
bloque de código es `codecup`, ver arriba). La de imagen es la que más
duele: se sustituyó por `SimpleImage`, que guarda la imagen **incrustada en el
JSON como base64**, lo que hincha la fila de `content_page_raw` y no deja nada
en el módulo de ficheros.

`image`, `attaches` y `linkTool` necesitan endpoints, y ésa es la razón de que
se cayeran. Están en `App\Http\Controllers\Admin\EditorJsController` y
**cuelgan del contenido** (B5 de la auditoría del 2026-09-24):

| Ruta | Para qué |
|---|---|
| `POST /admin/contents/{content}/editor/files` | Sube una imagen o un adjunto (`ContentFileService::store()`) |
| `POST /admin/contents/{content}/editor/files/by-url` | Descarga una imagen pegada por URL y la guarda igual (C5) |
| `GET /admin/contents/{content}/editor/url-metadata` | Título, descripción e imagen de una página externa, para la tarjeta de `linkTool` |

Llevan `auth`, el gate **`access-editorjs`** (Admin, SuperAdmin o Editor, con la
cuenta activa), la política **`update` sobre ese contenido** y el límite
`content-editor`: 30 peticiones por minuto y usuario. El campo del editor recibe
estas rutas de su contenido (`EditorJsField::content()` → `getEndpoints()`); ya
no hay variable global. Devuelven el formato que exige Editor.js —`{success: 1,
file: {…}}` y `{success: 1, meta: {…}}`— y los errores como `{success: 0,
message}`, que el editor enseña (su cargador propio, `createUploader()`).

### Ficheros del editor (`ContentFileService`)

- **Vinculados al contenido:** cada fichero deja su fila en `content_files` y
  va al módulo `content`, como en `main`.
- **La respuesta tiene las claves de `main`**, que son las que ya guardan los
  bloques publicados: `url` (copia de 640 px), `url_thumbnail` (160),
  `url_large` (1280), `path`, `path-thumbnail`, `path-large`, `content_id`,
  `content_file_id`, `file_id`, `module`, `title`, `alt`, `name`, `size`,
  `extension`, `mime` y `file_type_image`. Si la imagen es más pequeña que una
  copia, sale la mayor de las que hay. Para lo que no es imagen, las tres URL
  son el propio fichero.
- **Imágenes → WebP** a calidad 85, sin metadatos (ni GPS ni modelo del móvil),
  giradas según su orientación y a 2560 px como mucho
  (`File::addFile(..., webpOriginal: true)`). Lo mismo para las portadas del
  contenido y de sus páginas. Los GIF se quedan como están (perderían la
  animación). El resto de módulos, más adelante (`docs/future/`).
- **HEIC, HEIF y AVIF** se abren con Imagick (`File::decodeImage()`) y pasan a
  WebP. Sin Imagick con ese formato, 422 con «Este servidor no puede leer fotos
  HEIC: conviértela a JPG». En producción hacen falta `php-imagick` y
  `libheif`; se comprueba con `php -r 'var_dump(Imagick::queryFormats("HEI*"));'`.
- **Límites:** 20 MB las imágenes (D12) y 50 MB el resto, con el motivo y el
  tamaño («La imagen pesa 21 MB y el máximo para imágenes es 20 MB»).
- **PDF y cualquier otro tipo, tal cual** (D13); los PDF conservan sus metadatos
  (D33).
- **Por URL:** mismo filtro que los metadatos (`PublicUrlFetcher`, abajo), tope
  de 20 MB, tiene que ser una imagen, y después el mismo procesado.

**Cómo se sirven** (`FileController`): sólo JPEG, PNG, WebP, GIF y PDF se
enseñan en el navegador (`File::INLINE_MIMES`); el resto, como descarga (D34).
Ficheros y miniaturas llevan `Cache-Control: public, max-age=300,
must-revalidate` (`private` si el fichero es privado) y `Last-Modified`, para
que un recorte se vea en cinco minutos como mucho.

**Pie de foto → texto alternativo** (C3): al guardar la página, el pie de cada
imagen (o el título de cada adjunto) pasa al título y al `alt` del fichero,
**sólo si el pie ha cambiado desde el último guardado**; así se respeta un `alt`
escrito a mano. Sólo para ficheros vinculados a ese contenido.

**Pestaña de imágenes, lado servidor** (`ContentImageService`, H2; la pantalla
llega con la de páginas): qué imágenes usa una página (bloques y portada), dónde
más se usa cada una (otras páginas por su `file_id`, portadas, imagen SEO y
galerías), editar título y `alt` sin tocar el fichero, y recortar o sustituir.
Recortar y sustituir conservan el `file_id` **y los ids de las copias
pequeñas** (`File::replacePixels()` y `regenerateThumbnailsInPlace()`): los
bloques guardan las URLs de esas copias, así que no hay que tocarlos.

### Peticiones a URLs ajenas (`PublicUrlFetcher`)

Los metadatos de un enlace y la imagen por URL hacen una petición saliente a una
dirección que elige quien escribe, o sea SSRF si se deja abierto:
`http://169.254.169.254/` es el servicio de metadatos de media nube y
`http://127.0.0.1:9200` el Elasticsearch de al lado. `App\Services\Http\PublicUrlFetcher`
lleva cinco cierres, y si se toca hay que mantenerlos:

1. sólo `http` y `https` —nada de `file://`, `gopher://` ni `dict://`;
2. el host se **resuelve** y ninguna de sus IPs puede ser privada, de bucle ni
   de enlace local (`interna.midominio.com` puede apuntar a 10.0.0.5);
3. la conexión va **a la IP comprobada** (`CURLOPT_RESOLVE`), no a lo que
   resuelva el DNS un instante después;
4. sin seguir redirecciones: una redirección es otra URL sin comprobar;
5. tiempo de espera corto y tope de bytes (128 KB para los metadatos, 20 MB
   para una imagen).

Ante la duda no se pide nada: los metadatos responden `success: 0` (la tarjeta
se queda en enlace, que es válido) y la imagen, 422 con el motivo. Fijado por
`tests/Feature/Filament/EditorJsTest.php`, que prueba las ocho URL que no debe
tocar, en las dos rutas, y comprueba que **no sale ninguna petición**.

## La ficha en el panel, por secciones

Fase F7 del plan de contenidos del 2026-09-24 (E2–E7, G3 y C7 de la
auditoría). Antes era un formulario largo con pestañas y, al final, otro bloque
de pestañas con las relaciones: las páginas, lo más usado, quedaban abajo del
todo, y no había dónde editar el SEO ni las categorías y etiquetas.

### Secciones (`ContentResource::getRecordSubNavigation()`, pestañas arriba)

| Sección | Página | Ruta |
|---|---|---|
| Datos | `EditContent` | `/admin/content/contents/{id}/edit` |
| Páginas | `ManageContentPages` (la tabla de `PagesRelationManager`) | `…/{id}/pages` |
| SEO | `EditContentSeo` | `…/{id}/seo` |
| Categorías y etiquetas | `EditContentTaxonomies` | `…/{id}/taxonomies` |
| Relacionados | `ManageContentRelations` (galerías, colaboradores y contenidos relacionados) | `…/{id}/relations` |
| Visibilidad | `EditContentVisibility` | `…/{id}/visibility` |
| Vista previa | `PreviewContent` (desde la cabecera de cada sección y desde el listado) | `…/{id}/preview` |

- Todas son páginas del mismo registro; lo común está en el rasgo
  `Pages/Concerns/ContentSectionPage` (título con la sección, «Vista previa»
  en la cabecera, cada sección sólo con sus relaciones).
- Las de formulario llevan «Guardar cambios» arriba y abajo fijo al
  desplazarse (`$formActionsAreSticky`).
- «Páginas» y «Relacionados» reutilizan los gestores de relación de F5 tal
  cual, con su autorización. «Páginas» se sustituye por la pantalla propia en
  F8.
- En el móvil la subnavegación es un desplegable; el listado enseña título y
  estado, y las acciones de cada fila van en un menú «⋮» (tocar la fila abre la
  ficha). En la tabla de páginas, «Editar» queda a la vista y lo demás en el
  menú.

### Datos

Título, slug, plataforma, autor, estado (con la programación de F2), tipo,
extracto, imagen principal y, plegado, «Vídeo y enlaces» (`metadata`). Es
también el formulario de crear.

**Slug único dentro de su plataforma**, como el índice de la base
(`contents_platform_id_slug_unique`). El formulario lo pedía único entre todas
las plataformas. Un contenido de la papelera también lo ocupa, y el mensaje lo
dice: «Ese slug lo tiene «X», que está en la papelera: restáuralo, elimínalo
definitivamente o elige otro slug».

### SEO (`content_seo`)

Descripción, palabras clave, indexación (`robots`), «volver a pasar»
(`revisit_after`), alcance (`distribution`), título al compartir (`og_title`),
tipo (`og_type`), tarjeta de X (`twitter_card`), usuario del autor en X
(`twitter_creator`), imagen para redes y su texto alternativo.

- Contadores de la descripción (160) y del título al compartir (60): se ponen
  en naranja al pasarse, pero no impiden guardar.
- La imagen se recorta a 1200 × 630 (`ImageCropperUpload::socialCard()`) y se
  guarda en WebP, como las demás de los contenidos.
- Un contenido sin SEO empieza con los valores de la base, salvo el tipo, que
  es «artículo». Se guarda con `ContentSeoService::upsert()`; las columnas del
  contenido no se tocan.

### Categorías y etiquetas

Las de la plataforma del contenido (`platform_categories`,
`platform_tags`): categorías, categoría principal (`is_main`), subcategorías
de las categorías elegidas, etiquetas y tecnologías. Se puede crear una
categoría, subcategoría o etiqueta sin salir: si ya existe una con ese nombre
o slug (son únicos en toda la base) se reutiliza, y se añade a la plataforma.
Se guarda con `saveCategories()` y `saveTags()` (F5); sin categoría principal
se pasa `0` para que ninguna quede marcada.

### Visibilidad

«Activo», «Destacado» y la fecha de publicación (sólo lectura); dónde se
enseña (portada, menú, pie, barra lateral, buscador, archivo, RSS, sitemap y
sitemap de noticias); comentarios, comentarios anónimos y derechos de autor.
Cada uno con una línea de ayuda. «Derechos de autor» tiene tres estados
(comprobados, con material ajeno, o sin marcar = sin comprobar): un
interruptor convertía el «sin comprobar» de la base en «no» al guardar. La API
los enviará todos (F9).

### Papelera

- Listado: filtro «Papelera» (sin la papelera, todos o sólo la papelera),
  «Restaurar» y «Eliminar definitivamente», también en masa, cada uno
  autorizado contenido a contenido. Eliminar definitivamente es sólo del
  SuperAdmin (`ContentPolicy::forceDelete()`), y marca sus ficheros para la
  limpieza de F6.
- Las fichas abren también un contenido de la papelera
  (`getRecordRouteBindingEloquentQuery()` sin el filtro de borrados), para
  restaurarlo desde su cabecera.
- Páginas: el mismo filtro en su tabla. «Eliminar» usa `safeDelete()` (las de
  detrás suben un puesto); «Restaurar» la devuelve al final para no chocar con
  el orden de las demás; «Eliminar definitivamente» es sólo del SuperAdmin
  (`ContentPagePolicy::forceDelete()`).

### Vista previa (`PreviewContent`)

Todas las páginas seguidas, en orden, con el HTML que sirve la API
(`content_pages.content`), también de un borrador. Estilos básicos en
`panel.css` (sección «Contenidos · vista previa», clases `cp-preview*`),
partiendo de la vista previa de `main`: valen para las clases `r-*` de
Editor.js y para el HTML de Markdown y HTML, en claro y en oscuro. No es el
diseño de ninguna web. Enseña el HTML guardado **tal cual**, sin volver a
limpiarlo (D37).

## Borradores, bloqueo, historial y ficheros sin usar

Fase F6 del plan de contenidos del 2026-09-24 (D1, P4, D4, G5 y C2 de la
auditoría). Es el servidor: la pantalla que lo usa llega en F8.

### Historial (`ContentPageHistoryService`, tabla `content_page_versions`)

- Una versión es lo que tenía la página **antes** de un cambio, con su
  formato, título, contenido, huella, motivo (`ContentPageVersionReasonEnum`) y
  quién guardó el cambio.
- Sólo se crea si el contenido cambia. La huella (`hash()`) no cuenta el
  `time` de Editor.js (cambia en cada guardado del editor sin que cambie nada)
  ni la indentación del JSON, ni los saltos de línea de Windows. Cambiar sólo
  el título no crea versión.
- Como mucho 50 por página: al crear la 51 se borra la más antigua. Las de más
  de 30 días las borra `content:prune-drafts-and-versions`.
- Sustituye a las copias como filas borradas de `content_page_raw`.

### Borradores (`ContentPageDraftService`, tabla `content_page_drafts`)

- Uno por usuario y página (y uno por usuario y contenido para una página
  nueva, con un índice único parcial). Cada borrador es sólo de quien lo
  escribió: `find()` nunca da el de otro, y `restore()` y `discard()` de otro
  dan `AuthorizationException`.
- `save()` sólo escribe si lo que llega es distinto del último borrador y de
  lo guardado (huella de formato, título, slug, imagen y contenido). Si es
  igual a lo guardado, borra el borrador que hubiera.
- `base_page_updated_at` es la fecha de la página al abrirla: `isOutdated()`
  dice si se ha guardado después («La página ha cambiado desde tu borrador»).
- `restore()` guarda el borrador en su página (o crea la página, si era nueva)
  con motivo `draft_restore`: lo que había queda en el historial.
- Al guardar una página se borra el borrador de quien guarda, no el de los
  demás. Los de más de 30 días sin tocar los borra la tarea diaria.

### Bloqueo (`ContentPageLockService`)

- `acquire()` con un `lock_token` por pestaña: la coge si está libre, caducada
  o ya era de esa pestaña. Si no, devuelve el estado sin tocar nada:
  «Ana la está editando desde hace 5 minutos…» u «Ya la tienes abierta en otra
  pestaña…».
- `renew()` con cada autoguardado; `false` es «bloqueo perdido» (caducó y la
  cogió otro, o un administrador forzó el desbloqueo). `release()` al salir.
- Caduca a los 2 minutos (`TTL_SECONDS`) sin renovar.
- `forceUnlock()`: sólo administradores. El borrador del desplazado sigue ahí.
- Todo se escribe con el constructor de consultas: el bloqueo **no toca
  `updated_at`**, que es la fecha que usa D4. `acquire()` lee la fila con
  `FOR UPDATE`, así que dos pestañas a la vez no se la quedan las dos.

### Al guardar (`ContentPageFormatService::savePage()`)

Dentro de la misma transacción, con la fila de la página leída con
`FOR UPDATE`:

1. Si otro tiene el bloqueo (otro usuario u otra pestaña), no guarda:
   `ContentPageLockedException` con el aviso de quién.
2. **D4, la última red:** si llega `openedAt` y la página se ha guardado
   después, no guarda: `ContentPageConflictException` («Esta página se ha
   modificado mientras la editabas. Tu versión está a salvo en el borrador;
   recarga para ver la otra»). Quien llama guarda lo escrito en el borrador;
   el modal de páginas ya lo hace. Con el bloqueo, sólo pasa tras un
   desbloqueo forzado. La fecha va al segundo, como `updated_at`.
3. Guarda, deja lo anterior en el historial y borra el borrador de quien
   guarda.
4. Marca y desmarca los ficheros del contenido (abajo).

### Ficheros sin usar (`ContentFileUsageService`, `content_files.unused_since`)

- **En uso** = aparece en alguna página del contenido (también las de la
  papelera, que se pueden restaurar, y sus formatos guardados), en una versión,
  en un borrador, o es portada del contenido, de una página, de su SEO o de un
  borrador. Se reconoce por su `file_id` en el JSON de Editor.js y por sus URLs
  (`/file/get|download|resize/{módulo}/{id}` y las de sus miniaturas,
  `/file/thumbnail/get/{módulo}/{id de la miniatura}`), también con las barras
  escapadas del JSON. Con la copia de producción, los 71 ficheros de contenido
  salen en uso.
- Cada guardado de página marca con `unused_since` los que no están en uso y
  desmarca los que vuelven. También la tarea de podar historial y borradores,
  en los contenidos donde ha borrado algo.
- A la papelera no se marca nada. **Eliminar definitivamente** una página
  vuelve a mirar los de su contenido; un contenido marca todos los suyos, y sus
  filas se quedan con `content_id` nulo (la clave pasó de CASCADE a SET NULL)
  para que la tarea los encuentre.
- `content:purge-unused-files` borra los marcados hace más de 30 días
  —fichero, miniaturas del disco y sus filas— después de comprobar en **toda**
  la base que nada los usa: otro contenido que lo tenga en uso, el texto de
  cualquier página, versión o borrador, o cualquier columna con clave foránea a
  `files` (portadas, galerías, usuarios…). Si algo lo usa, lo desmarca y no lo
  borra.
- `Content::safeDelete()` y `ContentPage::safeDelete()` ya no borran ficheros:
  borraban del disco las imágenes de un contenido o página que se quedaba en la
  papelera. (Ninguna pantalla los usaba.)

Tareas diarias (hora de Madrid): `content:prune-drafts-and-versions` a las
04:00 y `content:purge-unused-files` a las 04:15.

Fijado por `ContentPageDraftTest`, `ContentPageLockTest`,
`ContentPageHistoryTest`, `ContentFileUsageTest` (con ficheros de verdad en el
disco) y `ContentPageSavingTest` (conflicto en el modal).

## Galerías

Las tablas `galleries` y `gallery_images` existían desde 2019 sin modelo
Eloquent ni recurso Filament; `Content::galleries()` apuntaba a un `HasMany`
sobre `ContentGallery` con un formulario placeholder (un `TextInput` numérico
para escribir a mano el `gallery_id`). Implementado por completo:

- `App\Models\Gallery` (tabla `galleries`, top-level, no bajo `Content/`
  porque es un recurso de imágenes reutilizable, igual que `File`):
  `user()`, `image()` (portada, FK a `files`), `images()` (`HasMany` →
  `GalleryImage`), `contents()` (`MorphToMany` → `Content`, inversa de
  `Content::galleries()`). `safeDelete()` sobrescrito: borra primero cada
  `GalleryImage` (y su `File`) antes de borrarse a sí misma.
- `App\Models\GalleryImage` (tabla `gallery_images`): `gallery()`, `image()`
  (FK a `files`), `order`, `caption`.
- `Content::galleries()` utiliza el trait polimórfico `App\Traits\HasGalleries`
  (pivote universal `galleryables` con orden): una galería puede reutilizarse
  en varios contenidos, páginas, dispositivos hardware o componentes.
- Recurso Filament `app/Filament/Admin/Resources/Galleries/`: CRUD de galerías
  con selector de relación de aspecto, portada automática, subidas en lote y
  `ImagesRelationManager` con tarjeta visual responsiva, arrastrar y soltar,
  y borrado seguro.
- `GalleriesRelationManager` de `ContentResource` reescrito al patrón
  Attach/Detach de `RelatedRelationManager`: `AttachAction` +
  `->inverseRelationship('contents')` explícito (evita que Filament adivine
  mal el nombre del método inverso; ver el bug real que motivó esto en
  `RelatedRelationManager` y `ContributorsRelationManager`).
- `galleries.description` era `NOT NULL` sin default desde el origen de la
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

> Creado: 2026-05-25 · Última revisión: 2026-09-27
