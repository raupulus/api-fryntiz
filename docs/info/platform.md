# Módulo: Plataformas (Platform)

Módulo de gestión multi-sitio que permite organizar contenidos por plataforma web. Cada plataforma tiene su propio dominio, categorías, tags, redes sociales y contenido destacado.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Platform.php` | `platforms` | Plataforma principal |
| `app/Models/PlatformCategory.php` | `platform_categories` | Pivot plataforma ↔ categoría |
| `app/Models/PlatformTag.php` | `platform_tags` | Pivot plataforma ↔ tag |
| `app/Models/PlatformUser.php` | `platform_user` | Plataformas asignadas a un Editor: dónde puede crear contenidos. `auto_contributor` (bool, por defecto `false`): entra como colaborador en todos los contenidos de la plataforma (ver [content.md → Permisos](content.md#permisos)) |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Platform/V2/PlatformController.php` | API V2 | index, show (ficha completa), categories, tags |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Platform/PlatformService.php` | `getAll()`, `getBySlug(slug)` (sólo la fila: cada ruta carga lo suyo) |
| `app/Services/Platform/PlatformApiService.php` | Ficha completa de la API (`detail()`, en caché) y etiquetas con su recuento (`tags()`) |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/PlatformResource.php` | Resource JSON plataforma (listado) |
| `app/Http/Resources/V2/TechnologyResource.php` | Tecnología compacta (`id`, `slug`, `name`, `color`, miniatura) |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/PlatformStatusEnum.php` | Estados de plataforma |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/PlatformPolicy.php` | Política de autorización |

## Campos del modelo Platform

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `title` | string | Nombre de la plataforma |
| `slug` | string | Slug URL |
| `description` | text | Descripción |
| `domain` | string | Dominio web |
| `url_about` | string | URL about |
| `youtube_channel_id` | string | ID canal YouTube |
| `youtube_presentation_video_id` | string | ID video presentación |
| `twitter` | string | Handler Twitter |
| `twitter_token` | string | Token Twitter. **Nunca sale por la API** |
| `mastodon` | string | Handler Mastodon |
| `mastodon_token` | string | Token Mastodon. **Nunca sale por la API** |
| `twitch` | string | Handler Twitch |
| `tiktok` | string | Handler TikTok |
| `instagram` | string | Handler Instagram |

## Relaciones

- `Platform` → `BelongsTo` → `User` (vía `user_id`)
- `Platform` → `HasMany` → `Content` (vía `platform_id`)
- `Platform` → `HasMany` → `PlatformCategory` (vía `platform_id`)
- `Platform` → `HasMany` → `PlatformTag` (vía `platform_id`)
- `Platform` → `HasMany` → `Newsletter` (vía `platform_id`)
- `User` → `BelongsToMany` → `Platform` (`User::platforms()`, pivote `platform_user` con `auto_contributor`; `User::platformAssignments()` da las filas como `PlatformUser`)

## Rutas API V2

Contrato completo, con ejemplos, en [`api/v2/content.md`](api/v2/content.md).

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/platforms` | No | Listar plataformas (paginado) |
| GET | `/api/v2/platforms/{slug}` | No | Ficha completa: redes (sin `*_token`), autor con sus datos de `user_details`, tecnologías de sus proyectos publicados, recuento de contenidos publicados por tipo y páginas de tipo `page` |
| GET | `/api/v2/platforms/{slug}/categories` | No | Categorías raíz con sus subcategorías |
| GET | `/api/v2/platforms/{slug}/tags` | No | Etiquetas con cuántos contenidos publicados las usan |
| GET | `/api/v2/platforms/{slug}/contents…` | No | Contenidos: ver [content.md → Rutas API V2](content.md#rutas-api-v2) |

Los destacados, últimos y tendencia ya no son rutas ni métodos de `Platform`
(`featured()`, `latest()`, `trend()` y sus `clean*Cache()` se han quitado:
recalculaban una caché que nadie leía). Los sirve
`GET /api/v2/platforms/{slug}/contents/highlights`.

## Caché

Todas las rutas de plataformas y contenidos llevan `ETag`, responden `304`
con `If-None-Match` y `Cache-Control: max-age=60, public`. Las respuestas
montadas (ficha, categorías, listados, destacados, detalle) van a la caché del
servidor con la clave ligada a `App\Support\ApiCacheVersion`: un contador que
sube con cualquier cambio en lo que enseñan. Plataformas, categorías,
etiquetas, tecnologías, sus pivotes, los datos y redes del autor y el nombre o
la foto de un usuario lo suben al guardarse (`App\Models\Concerns\BumpsApiCache`
y `User::booted()`). Detalle en [content.md → API](content.md#api).

## Comando de debug

```bash
php artisan debug:seed-platform --count=3
```

---

> Creado: 2026-05-25 · Última revisión: 2026-09-27
