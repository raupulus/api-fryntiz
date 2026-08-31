# Plantilla de Módulo: [Nombre del Módulo en Español]

> **Módulo:** [slug-o-nombre]  
> **Estado:** [Operativo / En desarrollo / Planificado]  

---

## 1. Resumen del Módulo

[Descripción concisa en 1 a 3 frases explicando qué hace este módulo, su propósito de negocio dentro de la plataforma y qué usuarios o dispositivos interactúan con él].

---

## 2. Archivos Principales Involucrados

| Tipo de Componente | Ruta del Archivo | Descripción de Responsabilidad |
|---|---|---|
| **Modelo Eloquent** | `app/Models/[Modulo]/[Modelo].php` | Modelo principal con docblock PHPDoc, casts y scopes. |
| **Migración** | `database/migrations/YYYY_MM_DD_XXXXXX_create_[tabla]_table.php` | Esquema relacional con 100% de tablas y columnas comentadas. |
| **Factory** | `database/factories/[Modelo]Factory.php` | Generación de datos sintéticos y estados para testing. |
| **Seeder** | `database/seeders/[Modulo]Seeder.php` | Carga de catálogos o datos corporativos realistas. |
| **Policy** | `app/Policies/[Modelo]Policy.php` | Matriz de autorización por roles para el modelo. |
| **Controlador Web** | `app/Http/Controllers/[Modulo]Controller.php` | Entrega de vistas Blade públicas y renderizado web. |
| **Controlador API V2** | `app/Http/Controllers/Api/[Modulo]/V2/[Modelo]Controller.php` | Endpoints REST bajo `/api/v2` con FormRequests y Resources. |
| **Recurso Filament** | `app/Filament/Admin/Resources/[Modulo]/[Modelo]Resource.php` | Panel de gestión en Filament 5 con subdirectorios y schemas. |
| **Vistas Blade** | `resources/views/[modulo]/*.blade.php` | Plantillas públicas con tokens de Tailwind CSS v4. |
| **Tests de Feature** | `tests/Feature/[Modulo]Test.php` | Suite de pruebas PHPUnit sobre PostgreSQL. |

---

## 3. Esquema de Base de Datos

### Tabla: `[nombre_de_la_tabla]`

| Campo | Tipo | Nullable | Valor por Defecto | Descripción |
|---|---|:---:|---|---|
| `id` | `BIGSERIAL` / `bigIncrements` | No | Auto | Identificador primario único del registro |
| `user_id` | `BIGINT` | Sí | `null` | FK al usuario propietario (`users.id`) con `onDelete('cascade')` |
| `title` | `VARCHAR(255)` | No | — | Título principal en español |
| `slug` | `VARCHAR(255)` | No | — | Identificador amigable único para URLs |
| `content` | `TEXT` / `JSONB` | Sí | `null` | Contenido procesado o estructura de bloques modulares |
| `image_path` | `VARCHAR(255)` | Sí | `null` | Ruta relativa al disco público (`uploads/...`) |
| `is_active` | `BOOLEAN` | No | `true` | Bandera de visibilidad del registro |
| `published_at` | `TIMESTAMP` | Sí | `null` | Fecha y hora en UTC para publicación programada |
| `created_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Marca de tiempo de inserción en UTC |
| `updated_at` | `TIMESTAMP` | Sí | `CURRENT_TIMESTAMP` | Marca de tiempo de última modificación en UTC |
| `deleted_at` | `TIMESTAMP` | Sí | `null` | Marca de borrado lógico (`SoftDeletes`) |

---

## 4. Relaciones, Scopes y Métodos Relevantes

### Relaciones Eloquent
- `user(): BelongsTo<User, $this>`: Relación con el usuario creador o responsable.
- `categories(): BelongsToMany<Category, $this>`: Categorización del registro vía tabla pivote con clave única.

### Scopes de Consulta
- `scopeActive(Builder $query): Builder`: Filtra registros donde `is_active = true`.
- `scopePublished(Builder $query): Builder`: Filtra registros cuya fecha `published_at <= now()`.

### Métodos y Accesores Relevantes
- `getStorageImageUrlAttribute(): string`: Genera dinámicamente la URL absoluta de la imagen vía `Storage::disk('public')->url(...)` con fallback predeterminado.
- `isPublished(): bool`: Comprueba si el registro cumple las condiciones de publicación activa.

---

## 5. Rutas Web y API

### Rutas Web Públicas (`routes/[modulo]/web.php` o `routes/web.php`)

| Verbo | Ruta URI | Nombre de Ruta | Acción / Controlador | Descripción |
|---|---|---|---|---|
| `GET` | `/[modulo]` | `[modulo].index` | `[Modulo]Controller@index` | Listado público paginado |
| `GET` | `/[modulo]/{slug}` | `[modulo].show` | `[Modulo]Controller@show` | Detalle del elemento con SEO |

### Endpoints API V2 (`routes/api/v2.php` o `routes/[modulo]/v2.php`)

| Verbo | Endpoint URI | Nombre de Ruta | Middleware | Descripción |
|---|---|---|---|---|
| `GET` | `/api/v2/[modulo]` | `api.v2.[modulo].index` | `throttle:api-auth` | Listado JSON paginado (Envelope V2) |
| `GET` | `/api/v2/[modulo]/{id}` | `api.v2.[modulo].show` | `throttle:api-auth` | Detalle JSON del registro |
| `POST` | `/api/v2/[modulo]` | `api.v2.[modulo].store` | `auth:sanctum`, `ability:[modulo]:write`, `throttle:api-store` | Creación de registro autenticada |

---

## 6. Configuración en Filament

- **Panel:** `Admin` (`/admin`)
- **Grupo de Navegación (`$navigationGroup`):** `"[Nombre del Grupo en Español]"`
- **Icono (`$navigationIcon`):** `heroicon-o-[nombre-icono]`
- **Etiqueta Singular / Plural:** `[Elemento]` / `[Elementos]`
- **Componentes de Formulario Destacados:**
  - `ImageCropperUpload::makeImage('image_path')->cover16x9()` con soporte de recorte interactivo (`->imageEditor()`).
  - Campos traducibles o pestañas organizadas en `Schemas/`.
  - Feedback exclusivo con `Filament\Notifications\Notification` (sin HTML crudo).
- **Políticas de Acceso:** Respeto estricto a las comprobaciones de `User::canAccessPanel()` y las Policies asociadas al modelo.

---

## 7. Contenido Gestionable

- **Apartado en el Panel:** `/admin/[recurso-slug]`
- **Campos Editables por el Administrador:**
  - Título, slug automático y extracto descriptivo.
  - Imagen destacada con herramienta de recorte y proporciones estándar (`16:9`, `1:1`, `4:3`).
  - Estado de publicación (`Activo`, `Inactivo`, `Borrador`).
  - Metadatos de posicionamiento SEO (título SEO, meta descripción, canonical).
- **Soporte de Idiomas:**
  - Idioma predeterminado: Español (`es`).
  - Fallback corporativo: Inglés (`en`).

## Pie obligatorio

El pie con las fechas es obligatorio en **todo** archivo de `docs/` (AGENTS.md §12.5):

```markdown
---

> Creado: YYYY-MM-DD · Última revisión: YYYY-MM-DD
```

La de creación se pone una vez y no se toca más. La de revisión se actualiza en
el mismo commit en que se cambie el documento.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
