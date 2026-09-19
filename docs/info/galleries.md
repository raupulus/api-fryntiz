# Galerías

Agrupaciones de imágenes reutilizables con relación de aspecto configurable (`aspect_ratio`), asociables polimórficamente a cualquier módulo de la plataforma (`Content`, `ContentPage`, `HardwareDevice`, etc.). Se gestionan íntegramente desde el panel Filament mediante cuadrícula visual interactiva con reordenación drag-and-drop y subidas múltiples por lote.

---

## 1. Archivos principales

| Archivo | Descripción |
|---------|-------------|
| `app/Models/Gallery.php` | Modelo principal de la galería |
| `app/Models/GalleryImage.php` | Imagen individual dentro de una galería con orden y pie de foto |
| `app/Enums/GalleryAspectRatioEnum.php` | Proporciones de aspecto permitidas (`16:9`, `4:3`, `1:1`, `free`) |
| `app/Traits/HasGalleries.php` | Trait reutilizable para modelos que asocian galerías vía tabla polimórfica `galleryables` |
| `app/Filament/Concerns/HandlesBatchImageUploads.php` | Trait para resolución tolerante a fallos y persistencia de subidas en lote |
| `app/Filament/Admin/Resources/Galleries/GalleryResource.php` | CRUD de galerías en el panel Admin (pestañas Detalles y Asociaciones) |
| `app/Filament/Admin/Resources/Galleries/Pages/CreateGallery.php` | Creación con subida múltiple simultánea y asignación automática de portada |
| `app/Filament/Admin/Resources/Galleries/Pages/EditGallery.php` | Edición de metadatos, fotos y asociaciones |
| `app/Filament/Admin/Resources/Galleries/RelationManagers/ImagesRelationManager.php` | Rejilla visual con ordenación drag-and-drop, subida múltiple y selección de portada |
| `app/Filament/Admin/Resources/Content/Contents/RelationManagers/GalleriesRelationManager.php` | Vinculación de galerías a Contenidos del CMS |
| `app/Filament/Admin/Resources/Hardware/HardwareDevices/RelationManagers/GalleriesRelationManager.php` | Vinculación de galerías a proyectos de Hardware con visor modal |
| `app/Policies/GalleryPolicy.php` | Autorización sobre galerías e imágenes (heredan permisos del propietario) |
| `resources/views/filament/components/gallery-images-preview.blade.php` | Vista modal para previsualización rápida de imágenes |

---

## 2. Esquema de Base de Datos y Migraciones

### Migraciones

| Migración | Tabla | Descripción |
|-----------|-------|-------------|
| `2019_07_04_132013_create_galleries_table.php` | `galleries` | Tabla base |
| `2019_07_04_132014_create_gallery_images_table.php` | `gallery_images` | Tabla de imágenes individuales |
| `2026_09_19_000001_enhance_galleries_and_create_galleryables_table.php` | `galleries`, `gallery_images`, `galleryables` | Añade `aspect_ratio`, `order`, `caption`, elimina `content_galleries` y crea `galleryables` |

### Tablas y Campos

#### `galleries`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | Identificador único autoincremental |
| `user_id` | bigint, nullable | Usuario creador de la galería. FK a `users`, `ON DELETE CASCADE` |
| `image_id` | bigint, nullable | Imagen de portada. FK a `files`, `ON DELETE SET NULL` |
| `name` | string(511) | Nombre de la galería |
| `description` | string(1024), nullable | Descripción del contenido de la galería |
| `aspect_ratio` | string(10) | Proporción de aspecto requerida (`16:9`, `4:3`, `1:1`, `free`). Por defecto `16:9` |
| `created_at` / `updated_at` | timestamp | Marcas de tiempo estándar |

#### `gallery_images`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | Identificador único autoincremental |
| `gallery_id` | bigint | FK a `galleries`, `ON DELETE CASCADE` |
| `image_id` | bigint | FK a `files`, `ON DELETE CASCADE` |
| `order` | integer | Orden de visualización secuencial dentro de la galería (indexado) |
| `caption` | string(511), nullable | Pie de foto o descripción corta opcional |
| `created_at` / `updated_at` | timestamp | Marcas de tiempo |

#### `galleryables` (Pivote Polimórfico Universal)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | Identificador único autoincremental |
| `gallery_id` | bigint | FK a `galleries`, `ON DELETE CASCADE` |
| `galleryable_type` | string(255) | Clase del modelo asociado (`App\Models\Content\Content`, `App\Models\Hardware\HardwareDevice`, `App\Models\Hardware\HardwareComponent`, etc.) |
| `galleryable_id` | bigint | ID del modelo asociado |
| `order` | integer | Posición de la galería respecto al elemento asociado (default 0) |
| `created_at` / `updated_at` | timestamp | Marcas de tiempo |

Clave única: `['gallery_id', 'galleryable_type', 'galleryable_id']`.

---

## 3. Relaciones Eloquent

### `Gallery`
- `user()`: `BelongsTo<User>`
- `image()`: `BelongsTo<File>` (portada)
- `images()`: `HasMany<GalleryImage>` (ordenadas por `order ASC, id ASC`)
- `contents()`: `MorphToMany<Content>` (vía `galleryables`)
- `pages()`: `MorphToMany<ContentPage>` (vía `galleryables`)
- `hardwareDevices()`: `MorphToMany<HardwareDevice>` (vía `galleryables`)
- `hardwareComponents()`: `MorphToMany<HardwareComponent>` (vía `galleryables`)

### `GalleryImage`
- `gallery()`: `BelongsTo<Gallery>`
- `image()`: `BelongsTo<File>`

### Modelos consumidores (`Content`, `ContentPage`, `HardwareDevice`, `HardwareComponent`)
- Utilizan el trait `App\Traits\HasGalleries`:
  - `galleries()`: `MorphToMany<Gallery>` (ordenadas por pivote `order ASC`)

---

## 4. Funcionamiento en Filament Admin

### Creación Rápida (`/admin/galleries/create`)
- Selector de relación de aspecto (`GalleryAspectRatioEnum`): `16:9` (Recomendado), `4:3`, `1:1`, o Libre.
- **Validación preventiva:** Campo `name` con autofoco y validación en vivo (`live(onBlur: true)`).
- **Límites reales y control de subida:**
  - Límite de tamaño: **15 MB por foto** (garantiza compatibilidad con fotos de alta resolución y margen frente al límite global de 20 MB).
  - Límite de cantidad: **Hasta 20 fotos por tanda** (alineado con `max_file_uploads` de PHP y para evitar saturar memoria en canvas del navegador).
  - Formatos admitidos: JPG, PNG, WEBP, GIF.
  - Procesamiento tolerante a fallos: Si algún archivo sufre un error puntual, el sistema guarda todas las imágenes válidas y muestra una notificación de aviso con el desglose exacto, sin perder el trabajo del usuario.
- Si no se define una portada explícita, el sistema asigna automáticamente la primera imagen subida como portada.
- Al guardar, redirige de forma fluida a la pantalla de edición.

### Edición y Gestión Visual (`/admin/galleries/{id}/edit`)
- **Rejilla visual de tarjetas (`Stack` + `contentGrid`):** Las fotos se presentan como tarjetas en una cuadrícula responsive (`sm:1`, `md:2`, `lg:3`) con miniatura en proporción nativa CSS (`aspect-ratio: 16/9`, `4/3`, etc.), altura generosa y clara (`min-height: 220px` sin franjas estrechas aplastadas), distintivo de posición y pie de foto.
- **Bloqueo estricto del recortador:** Configurado mediante `imageEditorViewportWidth` e `imageEditorViewportHeight` para que el cropper de Filament fuerce de manera estricta la proporción elegida (16:9, 4:3, 1:1) sin permitir deformaciones libres.
- **Edición in-situ al pulsar la foto:** Al pulsar sobre cualquier tarjeta de foto (o en el botón «Editar»), se abre inmediatamente un modal in-situ con el cropper para re-recortar o sustituir la imagen y editar el pie de foto, sin abandonar la pantalla.
- **Regla unificada de portada (Posición #1 = Portada):** La primera foto de la lista es siempre la portada de la galería.
  - Al reordenar con arrastrar y soltar, la nueva foto en posición #1 se sincroniza automáticamente como `image_id` de la galería vía hook `afterReordering`.
  - Acción «Portada» en cada tarjeta para promover cualquier imagen al primer puesto con un solo clic.
  - Vista previa de la portada actual en la parte superior con indicación explicativa clara.
- **Acción «Eliminar»:** Permite suprimir fotos directamente con confirmación en modal, purgando el fichero asociado y renumerando las posiciones restantes.
- **Subida adicional en lote:** Acción en cabecera para incorporar nuevas tandas de hasta 20 fotos recortadas con la proporción de la galería.
- **Pestaña «Asociaciones»:** Permite vincular o desvincular contenidos del CMS, dispositivos de hardware y componentes directamente desde la propia galería sin recargas pesadas.
- **Protección contra navegación accidental:** Panel con `unsavedChangesAlerts()` para evitar perder recortes por cerrar la pestaña por error.

---

## 5. Vinculación y Asociación con Hardware y Componentes

El módulo de galerías utiliza una arquitectura polimórfica universal mediante la tabla `galleryables`, lo que permite asociar una galería tanto al **dispositivo físico completo** (`HardwareDevice`) como a **componentes individuales instalados** (`HardwareComponent`), de forma bidireccional y sin duplicar archivos ni datos.

### 5.1. Desde la propia Galería (`/admin/galleries/{id}/edit` → Pestaña «Asociaciones»)
En la ficha de edición de cualquier galería, la pestaña **Asociaciones** expone selectores múltiples con búsqueda en vivo:
- **Dispositivos Hardware (`hardwareDevices`):** Asocia la galería a aparatos completos (ej. *Raspberry Pi 5*, *Estación Meteorológica Chipiona*).
- **Componentes de Hardware (`hardwareComponents`):** Asocia la galería a componentes específicos instalados (ej. *Sensor BME280*, *Módulo LoRa SX1276*). Para evitar confusiones si existen componentes con nombres similares, el desplegable indica el nombre del componente acompañado entre paréntesis del dispositivo al que pertenece (`Sensor BME280 (Estación Chipiona)`).

### 5.2. Desde el Dispositivo Hardware (`/admin/hardware-devices/{id}/edit` → Pestaña «Galerías de fotos»)
En la ficha técnica de cada dispositivo en `HardwareDeviceResource`, la pestaña **Galerías de fotos** gestionada por `GalleriesRelationManager` permite:
- **Vincular Galería (`AttachAction`):** Buscador interactivo que muestra la portada en miniatura y el conteo de fotos de cada galería disponible para enlazarla de un clic.
- **Ver fotos en modal (`viewImages`):** Abre un visualizador modal in-situ con todas las fotos de la galería y sus pies de foto sin salir de la ficha del dispositivo.
- **Editar Galería (`editGallery`):** Botón con enlace directo que abre la galería en una nueva pestaña para modificar imágenes, recortar o reordenar fotos.
- **Desvincular (`DetachAction`):** Desvincula la galería del dispositivo sin borrar la galería ni sus imágenes físicas del almacenamiento.

### 5.3. Tabla polimórfica `galleryables`
La vinculación se almacena en la tabla pivote `galleryables` con:
- `gallery_id`: ID de la galería.
- `galleryable_type`: Clase Eloquent (`App\Models\Hardware\HardwareDevice` o `App\Models\Hardware\HardwareComponent`).
- `galleryable_id`: ID del registro asociado.
- `order`: Posición de la galería para ordenar múltiples galerías asociadas al mismo hardware.
- Clave única compuesta: `['gallery_id', 'galleryable_type', 'galleryable_id']`.

---

## 6. Rutas y APIs

- **API V2:** No dispone de endpoints públicos propios a fecha de esta revisión; las entidades se consumen anidadas en los modelos padres o vía Filament.
- **Ficheros:** Servidos por el módulo central de archivos (`/file/get/...`, `/file/thumbnail/...`).

---

## 7. Comandos y Tests

- **Tests de Feature:** `tests/Feature/Galleries/GalleryManagementTest.php` (13 tests, 75 aserciones):
  - Carga inmediata sin lazy loading diferido (`$isLazy = false`) para evitar cajas vacías o parpadeos.
  - Creación múltiple en lote y auto-asignación de portada.
  - Promoción de imagen a posición #1 y actualización de portada (`test_set_as_cover_action_in_relation_manager_promotes_image_to_first_position`).
  - Subidas parciales tolerantes a fallos (`test_batch_upload_handles_partial_invalid_files_gracefully`).
  - Ordenación secuencial drag-and-drop.
  - Cambio de portada mediante acción directa y confirmación.
  - Validaciones de relación de aspecto y enum con métodos de viewport.
  - Asociaciones polimórficas con `Content`, `ContentPage`, `HardwareDevice` y `HardwareComponent`.
  - Compatibilidad de columnas `jsonb` en dispositivos asociados.
  - Borrado en cascada `safeDelete()` para imágenes y ficheros físicos.
  - Políticas de acceso `GalleryPolicy`.
  - Renderizado de RelationManagers en Filament con layout de tarjetas.

---

> Creado: 2026-08-30 · Última revisión: 2026-09-19

