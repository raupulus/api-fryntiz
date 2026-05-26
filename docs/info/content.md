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
| `app/Models/Content/ContentGallery.php` | — | Galerías de contenido |
| `app/Models/Content/ContentRelated.php` | `content_related` | Relación contenido ↔ contenido |
| `app/Models/Content/ContentSeo.php` | `content_seo` | Datos SEO del contenido |
| `app/Models/Content/ContentMetadata.php` | `content_metadata` | Metadata externa (repos, redes sociales) |
| `app/Models/ContentDailyView.php` | `content_daily_views` | Vistas diarias |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Content/V2/ContentController.php` | API V2 | show, pages, related |
| `app/Http/Controllers/Api/Content/ContentController.php` | API V1 | Controlador V1 legacy |
| `app/Http/Controllers/Content/*.php` | Web | Controladores frontend (12 archivos) |
| `app/Http/Controllers/Dashboard/Content/*.php` | Dashboard | Controladores admin legacy (17 archivos) |

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
- `Content` → `HasMany` → `ContentRelated` (vía `content_id`)
- `Content` → `HasOne` → `ContentSeo` (vía `content_id`)
- `Content` → `HasOne` → `ContentMetadata` (vía `content_id`)
- `ContentPage` → `HasMany` → `ContentPageRaw` (vía `content_page_id`)

## Rutas API V2

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/content/{platform:slug}/{content:slug}` | No | Ver contenido por plataforma y slug |
| GET | `/api/v2/content/{content:slug}/pages` | No | Páginas de un contenido |
| GET | `/api/v2/content/{content:slug}/related` | No | Contenido relacionado |

## Comando de debug

```bash
php artisan debug:seed-content --count=10
```

