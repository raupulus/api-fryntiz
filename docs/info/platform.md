# Módulo: Plataformas (Platform)

Módulo de gestión multi-sitio que permite organizar contenidos por plataforma web. Cada plataforma tiene su propio dominio, categorías, tags, redes sociales y contenido destacado.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Platform.php` | `platforms` | Plataforma principal |
| `app/Models/PlatformCategory.php` | `platform_categories` | Pivot plataforma ↔ categoría |
| `app/Models/PlatformTag.php` | `platform_tags` | Pivot plataforma ↔ tag |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Platform/V2/PlatformController.php` | API V2 | index, show, featured, categories, contentByType |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Platform/PlatformService.php` | `getAll()`, `getBySlug(slug)` |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/PlatformResource.php` | Resource JSON plataforma |

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
| `twitter_token` | string | Token Twitter |
| `mastodon` | string | Handler Mastodon |
| `mastodon_token` | string | Token Mastodon |
| `twitch` | string | Handler Twitch |
| `tiktok` | string | Handler TikTok |
| `instagram` | string | Handler Instagram |

## Relaciones

- `Platform` → `BelongsTo` → `User` (vía `user_id`)
- `Platform` → `HasMany` → `Content` (vía `platform_id`)
- `Platform` → `HasMany` → `PlatformCategory` (vía `platform_id`)
- `Platform` → `HasMany` → `PlatformTag` (vía `platform_id`)
- `Platform` → `HasMany` → `Newsletter` (vía `platform_id`)

## Rutas API V2

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/platform` | No | Listar plataformas |
| GET | `/api/v2/platform/{slug}` | No | Ver plataforma por slug |
| GET | `/api/v2/platform/{slug}/featured` | No | Contenido destacado de plataforma |
| GET | `/api/v2/platform/{slug}/categories` | No | Categorías de la plataforma |
| GET | `/api/v2/platform/{slug}/content/type/{contentType}` | No | Contenido de la plataforma filtrado por tipo |

## Caché

El modelo Platform usa caché intensivamente para categorías y contenido destacado, limpiando caché automáticamente en eventos `saved` y `updated`.

## Comando de debug

```bash
php artisan debug:seed-platform --count=3
```

---

> Creado: 2026-05-25 · Última revisión: 2026-08-19
