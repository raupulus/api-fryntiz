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

Decide si una imagen se **puede reprocesar** (rotar, escalar, generar miniaturas). No decide si se
**acepta**: para eso está `SAFE_MIMES`.

## Política de subida

`File::addFile()` recibe `bool $validate = true` como último parámetro.

| Valor | Qué hace | Quién lo usa |
|---|---|---|
| `true` (por defecto) | El MIME real tiene que estar en `File::SAFE_MIMES` y el tamaño por debajo de `File::MAX_FILE_SIZE` (20 MB) | Los campos que esperan una imagen o un documento: avatar, portada de contenido, foto de producto |
| `false` | Entra cualquier cosa, sin límite de tipo | El editor de contenido y los archivos adjuntos, donde se sube lo que haga falta |

El parámetro va el último de la firma para que ninguna llamada existente cambie de comportamiento.

⚠️ **`SAFE_MIMES` no sale de la tabla `file_types`, y no debe salir nunca.** `file_types` es un
catálogo de metadatos (icono, extensión, tipo legible) que se rellena desde el panel con toda clase
de formatos — impresión 3D, vectores, proyectos de edición, documentos. Es entrada de usuario:
usarla como lista de tipos seguros sería validar el input contra el propio input. Para aceptar un
tipo nuevo se añade a la constante del modelo, a mano.

El tope de 20 MB es el techo del modelo, no el de la interfaz: cada campo de Filament pone el suyo,
más estricto (`ImageCropperUpload` está en 4 MB). Una foto de alta calidad entra grande y el cropper
la deja en un megabyte o menos.

La validación ocurre **antes de `store()`**: si se comprobara después, el archivo rechazado ya
estaría escrito en el disco.

## Metadatos de las imágenes

Al almacenar una imagen editable, `processStoredImage()` hace tres cosas **en este orden**:

1. **Rota los píxeles de verdad** según la orientación EXIF. Si no, al limpiar los metadatos se iría
   el flag de orientación y las fotos de móvil quedarían tumbadas para siempre.
2. **Acota el ancho** a `File::MAX_IMAGE_WIDTH` (2560 px).
3. **Limpia los metadatos**: `stripMetadata()` vacía el EXIF y quita el perfil ICC, y además se
   guarda con `strip` en el encoder. Son dos capas para lo mismo, a propósito.

Se aplica a **todas** las imágenes, privadas y públicas: una foto pública con las coordenadas de
casa dentro es el mismo problema con más gente mirándola.

La limpieza se hace **aunque la librería ya descarte los metadatos por su cuenta** — hoy lo hace el
driver GD. Esa garantía es un accidente de la implementación, no una decisión del proyecto; el
motivo completo está en [decisiones-tecnicas.md](decisiones-tecnicas.md) D3.

Escribir los metadatos **de plataforma** (autoría, datos de la web) sigue pendiente: ver
[`docs/future/metadatos-imagenes.md`](../future/metadatos-imagenes.md).

## Redimensionado bajo demanda (`/file/resize`)

El ancho no se acepta tal cual: se resuelve contra `File::$thumbnailsSizeWidth`, los tamaños que el
proyecto ya genera. Un ancho fuera de esa lista cae al mayor permitido que no lo supere.

Con la lista cerrada el número de variantes es finito, así que el resultado **se cachea en disco**,
en la misma ruta donde `createThumbnails()` escribe las miniaturas. Para los anchos del catálogo la
imagen suele existir ya y se sirve sin tocar la librería de imagen.

Antes el ancho llegaba libre y sin caché: cada petición reprocesaba la imagen entera, y un ancho
enorme era una forma gratuita de agotar la memoria del servidor. La ruta lleva ahora `throttle:api`.

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

| Método | Ruta | Middleware | Comprobación de propiedad | Qué hace |
|--------|------|-----------|---------------------------|----------|
| GET | `/file/get/{module}/{id}/{slug?}` | — | En el controlador: si `is_private`, sólo el dueño | Sirve el archivo |
| GET | `/file/download/{module}/{id}/{slug?}` | — | Ídem | Descarga con el nombre original |
| GET | `/file/resize/{module}/{id}/{width}/{slug?}` | — | Ídem | Redimensiona y sirve |
| GET | `/file/thumbnail/get/{module}/{id}/{slug?}` | — | Ídem | Sirve la miniatura |
| POST | `/file/delete/{id}` | `auth` | En el controlador (N27) | Borra el archivo, sus miniaturas y la fila |

> **Esta tabla no tenía columna de permisos** (**N260**), justo en el módulo cuyo
> problema era la falta de permisos. Sin esa columna no se ve lo único que
> importa aquí: que las cuatro rutas de lectura son **públicas a propósito** —un
> archivo público se sirve a cualquiera— y que lo que separa un archivo privado
> de uno público **no es un middleware, es una comprobación dentro del
> controlador**. Si alguien añade una ruta nueva y se le olvida esa
> comprobación, no hay nada más que lo pare.

Cuando un archivo no se puede servir se devuelve una imagen genérica en su
lugar, nunca un error: `not_found`, `not_authorized` o `not_image` según el caso.

## Uso en la aplicación

El módulo File es referenciado por:
- `Content.image_id` — Imagen principal del contenido
- `ContentPage.image_id` — Imagen de la página
- `ContentSeo.image_id` — Imagen SEO
- `Category.image_id` — Imagen de categoría
- `ContentAvailableType.file_id` — Icono del tipo
- `Technology.image_id` — Icono de la tecnología
- Múltiples modelos vía `ImageTrait`

## Subida de imágenes en Filament (fix_11)

- Componente centralizado `app/Filament/Components/ImageCropperUpload.php` (extiende
  `FileUpload`). Métodos: `makeImage()` (disco `public`, cropper, jpeg/png/webp) y
  presets `avatar()`, `cover16x9()`, `logo()`, `icon($size)`.
- Todos los Resources de imágenes usan este componente en lugar de `FileUpload`
  directo (User, Profile, Content, Category, Platform, Technology, HardwareDevice,
  FileType, páginas de contenido, CV).
- Para campos `*_id` que son FK a `files` (HardwareDevice, CV), las Pages usan el
  trait `app/Filament/Concerns/HasImageFileUpload.php` (`resolveImageUpload`), que
  convierte el upload temporal en un registro `File` vía `File::addFile()` y guarda
  su id. El campo usa `->storeFiles(false)` para conservar el `UploadedFile`.

---

## Estado de las rutas de ficheros

| Ruta | Estado |
|------|--------|
| `GET /file/get/...`, `/thumbnail/get/...`, `/resize/...` | ✅ Públicas por diseño. Las privadas devuelven la imagen de «no autorizado» |
| `GET /file/download/{module}/{id}/{slug?}` | ✅ Implementada en la fase 8. Misma comprobación de privacidad que `get()`, y descarga con el nombre original |
| `POST /file/delete/{id}` | ✅ Con `auth` y comprobación de propiedad dentro del controlador (N27, fase 3) |
| `POST /file/upload` | 🗑️ **Retirada** en la fase 8 |

### Por qué se retira la subida por HTTP

`FileController::upload()` tenía **el cuerpo vacío** y la ruta estaba viva: la
petición respondía **200 sin subir nada**. Un endpoint que no hace nada es peor
que uno que no existe, porque el cliente cree que funcionó.

Lo mismo le pasaba a `download()`, pero ese sí se ha implementado: bajaba un
fichero de **cero bytes** y ahora devuelve el fichero de verdad, con la misma
comprobación de privacidad que `get()` y con el nombre con el que se subió.

La subida no se implementa porque **ya se hace bien en otro sitio**: el panel,
con `ImageCropperUpload` y `HasImageFileUpload`, que valida tipo y tamaño,
recorta, genera las miniaturas y deja la propiedad asignada. Un `POST /file/upload`
genérico tendría que replicar todo eso o quedarse corto. Cuando haga falta subir
por HTTP se escribirá entera, con su contrato y sus límites.

### Lo que queda pendiente

Está en `docs/planning/todo.md`: `resizeAndGet()` no cachea
el resultado —reprocesa la imagen en cada petición— y `File` no borra los
metadatos EXIF de los ficheros privados, que es un asunto de privacidad: una foto
privada puede llevar dentro la geolocalización.

---

> Creado: 2026-05-25 · Última revisión: 2026-08-30
