# Módulo: Entidades Comunes (Common)

Entidades compartidas transversales usadas por múltiples módulos: categorías jerárquicas, tags, tecnologías, idiomas y redes sociales.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Category.php` | `categories` | Categorías jerárquicas (con parent_id) |
| `app/Models/Tag.php` | `tags` | Etiquetas |
| `app/Models/Technology.php` | `technologies` | Tecnologías/frameworks/lenguajes |
| `app/Models/Language.php` | `languages` | Idiomas del sistema |
| `app/Models/SocialNetwork.php` | `social_networks` | Redes sociales disponibles |
| `app/Models/PlatformCategory.php` | `platform_categories` | Pivot plataforma ↔ categoría |
| `app/Models/PlatformTag.php` | `platform_tags` | Pivot plataforma ↔ tag |

### Controladores Dashboard (Admin legacy)
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Controllers/Dashboard/CategoryController.php` | CRUD categorías |
| `app/Http/Controllers/Dashboard/TagController.php` | CRUD tags |
| `app/Http/Controllers/Dashboard/TechnologyController.php` | CRUD tecnologías |
| `app/Http/Controllers/Dashboard/LanguageController.php` | CRUD idiomas |

### Controladores Web
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Controllers/LanguageController.php` | Ajax obtener idiomas |

### Políticas
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/CategoryPolicy.php` | Política categorías |
| `app/Policies/TagPolicy.php` | Política tags |
| `app/Policies/TechnologyPolicy.php` | Política tecnologías |

### Traits compartidos
| Archivo | Descripción |
|---------|-------------|
| `app/Traits/HasSlug.php` | Generación automática de slug |
| `app/Traits/HasStatus.php` | Gestión de estados |
| `app/Traits/Filterable.php` | Filtrado dinámico de queries |
| `app/Traits/HasTimestampScopes.php` | Scopes por rango de fechas |
| `app/Traits/BelongsToUser.php` | Relación con User |
| `app/Traits/BelongsToHardwareDevice.php` | Relación con HardwareDevice |
| `app/Traits/ApiResponseTrait.php` | Respuestas JSON estandarizadas (API V2) |

## Modelo Category

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `name` | string | Nombre |
| `slug` | string | Slug URL |
| `description` | text | Descripción |
| `parent_id` | int | FK self-referencial → `categories.id` (nullable) |
| `image_id` | int | FK → `files.id` (nullable) |
| `icon` | string | Icono CSS |
| `color` | string | Color hex |
| `priority` | int | Prioridad/orden |

### Relaciones

- `Category` → `BelongsTo` → `Category` como `parentCategory` (vía `parent_id`)
- `Category` → `HasMany` → `Category` como `subcategories` (vía `parent_id`)
- `Category` → `BelongsTo` → `File` como `image` (vía `image_id`)

## Modelo Tag

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `name` | string | Nombre |
| `slug` | string | Slug URL (unique) |
| `description` | string | Descripción |

## Modelo Technology

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `name` | string | Nombre |
| `slug` | string | Slug URL |
| `description` | text | Descripción |
| `color` | string | Color hex |
| `image_id` | int | FK → `files.id` (nullable) |

### Relaciones

- `Technology` → `BelongsTo` → `File` como `image` (vía `image_id`)

## Modelo Language

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `locale` | string | Locale (es, en, etc.) |
| `iso_locale` | string | ISO locale |
| `iso2` | string | Código ISO 2 letras |
| `iso3` | string | Código ISO 3 letras |
| `icon64` | string | Ruta icono 64x64 |

## Modelo base heredado (BaseAbstractModelWithTableCrud)

Muchos modelos del proyecto (Category, Tag, Technology, Platform, BaseWheaterStation) extienden `BaseAbstractModelWithTableCrud` que provee:

| Método estático | Descripción |
|-----------------|-------------|
| `getModuleName()` | Nombre del módulo |
| `getModelTitles()` | Títulos CRUD (singular, plural, add, edit, delete) |
| `getFieldsValidation()` | Reglas de validación |
| `getTableHeads()` | Cabeceras de tabla |
| `getTableCellsInfo()` | Info de celdas (tipo, wrapper, class) |
| `getTableActionsInfo()` | Acciones disponibles |
| `getTableRowsByPage()` | Paginación manual |
| `getPolicy()` | Clase de política asociada |

## Uso en otros módulos

- **Content** → usa `ContentCategory`, `ContentTag`, `ContentTechnology` como pivots
- **Platform** → usa `PlatformCategory`, `PlatformTag` como pivots
- **Email** → usa `Language` (vía `language_id`)
- **Múltiples modelos** → usan `ImageTrait` con relación a `File`

## Seeders (fix_11)

| Seeder | Contenido |
|--------|-----------|
| `SocialNetworkSeeder` | ~20 redes sociales (slug, type, color, url, icono). Requiere `$fillable` en `SocialNetwork`. |
| `CategoriesSeeder` | 17 categorías con descripciones. |
| `TagsSeeder` | 35 tags con descripciones. |
| `TechnologiesSeeder` | Actualizado con descripciones completas y colores reales; corregido el slug de Nuxt; añadidos MicroPython y Swift. |

Todos son idempotentes (`firstOrCreate` por `slug`) y están registrados en
`DatabaseSeeder` en el orden correcto.

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/languages/ajax/get/languages` | AJAX obtener idiomas |
