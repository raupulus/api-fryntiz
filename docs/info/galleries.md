# Galerías

Agrupaciones de imágenes reutilizables, asociables a uno o varios contenidos del CMS. Se
gestionan íntegramente desde el panel Filament; **no tienen API pública** a fecha de esta
revisión.

## Archivos principales

| Archivo | Descripción |
|---------|-------------|
| `app/Models/Gallery.php` | Modelo de la galería |
| `app/Models/GalleryImage.php` | Imagen individual dentro de una galería |
| `app/Models/Content/ContentGallery.php` | Tabla pivote entre `Content` y `Gallery` |
| `app/Filament/Admin/Resources/Galleries/GalleryResource.php` | CRUD en el panel admin |
| `app/Filament/Admin/Resources/Galleries/RelationManagers/ImagesRelationManager.php` | Gestión de las imágenes de la galería |
| `app/Filament/Admin/Resources/Content/Contents/RelationManagers/GalleriesRelationManager.php` | Asociación de galerías a un contenido |
| `app/Filament/Components/ImageCropperUpload.php` | Campo de subida con recorte |
| `app/Filament/Concerns/HasImageFileUpload.php` | Trait de subida de imágenes |
| `app/Policies/GalleryPolicy.php` | Autorización sobre `Gallery` y `GalleryImage`, sobre `OwnedResourcePolicy`. La imagen no tiene dueño propio: lo hereda de su galería |
| `app/Filament/Concerns/ScopesToOwner.php` | Usado por `GalleryResource`: la tabla del panel sólo muestra las galerías propias; el administrador las ve todas |

## Migraciones

| Migración | Tabla |
|-----------|-------|
| `2019_07_04_132013_create_galleries_table.php` | `galleries` |
| `2019_07_04_132014_create_gallery_images_table.php` | `gallery_images` |
| `2021_05_08_202335_create_content_galleries_table.php` | `content_galleries` (pivote) |
| `2026_07_02_153015_make_galleries_description_nullable.php` | `description` pasa a nullable |

## Campos

### `galleries`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | Identificador |
| `user_id` | bigint, nullable | Usuario que crea la galería. FK a `users`, `ON DELETE CASCADE` |
| `image_id` | bigint, nullable | Imagen de portada. FK a `files`, `ON DELETE SET NULL` |
| `name` | string(511) | Nombre de la galería |
| `description` | string(1024), nullable | Descripción |
| `created_at` / `updated_at` | timestamp | — |
| `deleted_at` | timestamp, nullable | Borrado lógico (soft delete) |

### `gallery_images`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | Identificador |
| `gallery_id` | bigint, nullable | FK a `galleries` |
| `image_id` | bigint, nullable | FK a `files` |
| `created_at` / `updated_at` | timestamp | — |
| `deleted_at` | timestamp, nullable | Borrado lógico |

> Ambos modelos usan `protected $guarded = ['id']`, no `$fillable`.

## Relaciones

### `Gallery`

| Relación | Tipo | Destino |
|----------|------|---------|
| `user()` | belongsTo | `User` |
| `image()` | belongsTo | `File` (portada, vía `image_id`) |
| `images()` | hasMany | `GalleryImage` |
| `contents()` | belongsToMany | `Content` (vía `content_galleries`) |

### `GalleryImage`

| Relación | Tipo | Destino |
|----------|------|---------|
| `gallery()` | belongsTo | `Gallery` |
| `image()` | belongsTo | `File` |

## Rutas

- **API:** ninguna. Está previsto evaluar endpoints de lectura pública en la
  fase 07 del roadmap.
- **Web:** las imágenes se sirven por las rutas genéricas de ficheros:
  `GET /file/get/{module}/{id}/{slug?}`, `GET /file/thumbnail/get/...` y
  `GET /file/resize/{module}/{id}/{width}/{slug?}`. Ver [`files.md`](files.md).

## Panel Filament

`GalleryResource` (panel `admin`) con:

- CRUD completo de galerías.
- `ImagesRelationManager` para añadir, ordenar y eliminar imágenes.
- Subida con recorte mediante `ImageCropperUpload`.

Desde `ContentResource` → `GalleriesRelationManager` se asocian galerías existentes a un
contenido.

## Comandos y tests

- **Comandos:** ninguno específico.
- **Tests:** ninguno a fecha de esta revisión. Pendiente en la
  fase 09 del roadmap.

## Pendiente

- API de lectura pública (fase 07).
- Tests (fase 09).
- El commit `ce976d8` corrigió errores de vinculación y subida en Filament; conviene revisar
  que no queden casos límite sin cubrir.

---

> Creado: 2026-08-30 · Última revisión: 2026-09-05
