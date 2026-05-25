# Módulo: Gestión de Archivos (Files)

Módulo transversal para subida, almacenamiento, redimensión y gestión de archivos e imágenes con sistema de thumbnails automáticos en múltiples tamaños.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/File.php` | `files` | Archivo principal |
| `app/Models/FileThumbnail.php` | — | Thumbnails generados |
| `app/Models/FileType.php` | — | Tipos de archivo disponibles |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/FileController.php` | Web | Upload, download, get, resize, delete |
| `app/Http/Controllers/FileThumbnailController.php` | Web | Obtener thumbnails |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/FileTypeEnum.php` | Tipos de archivo |

## Campos del modelo File

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `file_type_id` | int | FK → `file_types.id` |
| Otros campos | — | Gestionados vía `$guarded = ['id']` |

## Tamaños de thumbnails

```php
$thumbnailsSizeWidth = [
    'micro'  => 50,
    'small'  => 160,
    'medium' => 320,
    'normal' => 640,
    'large'  => 1280,
];
```

## MIME types editables

```php
$imageMimeCanEdit = [
    'image/jpeg', 'image/pjpeg', 'image/png',
    'image/gif', 'image/webp', 'image/x-windows-bmp',
    'image/x-ms-bmp', 'image/bmp',
];
```

## Imágenes genéricas

Se definen en `File::$genericImages`:
- `error`, `default`, `not_found`, `not_image`, `not_authorized`
- `not_allowed`, `not_allowed_extension`, `not_allowed_size`, `not_allowed_type`, `not_available`

## Relaciones

- `File` → `BelongsTo` → `FileType` (vía `file_type_id`)
- `File` → `HasMany` → `FileThumbnail`

## Traits relacionados

- `ImageTrait` — Usado por modelos que tienen imagen (Platform, Category, Technology, Content, User)
  - Proporciona métodos para acceder a URLs de imágenes en distintos tamaños

## Rutas Web

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/file/get/{module}/{id}/{slug?}` | Obtener archivo |
| POST | `/file/upload` | Subir archivo |
| GET | `/file/download/{module}/{id}/{slug?}` | Descargar archivo |
| GET | `/file/resize/{module}/{id}/{width}/{slug?}` | Redimensionar y obtener |
| POST | `/file/delete/{id}` | Eliminar archivo |
| GET | `/file/thumbnail/get/{module}/{id}/{slug?}` | Obtener thumbnail |

## Uso en la aplicación

El módulo File es referenciado por:
- `Content.image_id` — Imagen principal del contenido
- `ContentPage.image_id` — Imagen de la página
- `ContentSeo.image_id` — Imagen SEO
- `Category.image_id` — Imagen de categoría
- `ContentAvailableType.file_id` — Icono del tipo
- `Technology.image_id` — Icono de la tecnología
- Múltiples modelos vía `ImageTrait`
