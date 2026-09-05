# Módulo: Currículum Vitae (CV)

Módulo multi-currículum: la plataforma puede tener **varios** currículums (uno
por objetivo, oferta o idioma), cada uno con su `slug`, su visibilidad propia
(`public`/`shared`/`private`) y 15 secciones (experiencia en 5 variantes,
formación en 3, habilidades, proyectos, repositorios, servicios,
colaboraciones, hobbies y trabajos). Incluye descarga de un PDF generado desde
la base de datos.

> Contrato HTTP completo (rutas, parámetros, forma exacta de cada respuesta)
> en [`docs/info/api/v2/cv.md`](api/v2/cv.md).

## Diseño: por qué hay varios currículums

Antes solo existía un CV (el del superadmin) y las rutas lo daban por hecho.
El módulo tiene 18 tablas montadas justo para poder tener varios currículums a
la vez, cada uno pensado para una oferta/plataforma concreta. La visibilidad
(`App\Enums\CurriculumVisibilityEnum`) decide quién lo ve:

| Visibilidad | ¿Sale en el listado público? | ¿Accesible por `{slug}`? | ¿Accesible por `{shareToken}`? |
|---|---|---|---|
| `public` | Sí | Sí | Sí (aunque no hace falta) |
| `shared` | No | No | Sí, con el token correcto (64 hex, `noindex`) |
| `private` | No | No | No |

Si `is_active` es `false`, el currículum no es visible por ningún camino, sea
cual sea su visibilidad. El campo legado `is_public` se mantiene sincronizado
automáticamente (`Curriculum::booted()`) con `visibility === Public`, para no
romper consultas antiguas que aún lo miren.

## Archivos principales

### Modelos (`app/Models/CV/`)

| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `Curriculum.php` | `cv` | El currículum: `slug`, `visibility`, `share_token`, `is_default`, `is_downloadable`, datos del PDF |
| `CurriculumBaseSection.php` | — | Modelo base abstracto para secciones |
| `CurriculumExperienceAccredited.php` | `cv_experience_accredited` | Experiencia acreditada |
| `CurriculumExperienceNoAccredited.php` | `cv_experience_no_accredited` | Experiencia no acreditada |
| `CurriculumExperienceSelfEmployed.php` | `cv_experience_self_employed` | Experiencia autónomo |
| `CurriculumExperienceAdditional.php` | `cv_experience_additional` | Experiencia adicional |
| `CurriculumExperienceOther.php` | `cv_experience_others` | Otra experiencia |
| `CurriculumAcademicTraining.php` | `cv_academic_training` | Formación reglada |
| `CurriculumAcademicComplementary.php` | `cv_academic_complementary` | Formación complementaria |
| `CurriculumAcademicComplementaryOnline.php` | `cv_academic_complementary_online` | Formación online |
| `CurriculumSkill.php` | `cv_skills` | Habilidades |
| `CurriculumProject.php` | `cv_projects` | Proyectos |
| `CurriculumRepository.php` | `cv_repositories` | Repositorios (con `type()` → `CurriculumAvailableRepositoryType`, sin precargar hoy en la API) |
| `CurriculumService.php` | `cv_services` | Servicios ofrecidos |
| `CurriculumCollaboration.php` | `cv_collaborations` | Colaboraciones |
| `CurriculumHobby.php` | `cv_hobbies` | Hobbies |
| `CurriculumJob.php` | `cv_jobs` | Trabajos |
| `CurriculumAvailableRepositoryType.php` | `cv_available_repository_types` | Catálogo de tipos de repositorio |

> ⚠️ **Directorio duplicado detectado (2026-08-30):** existe también
> `app/Models/Cv/` (minúscula) con una copia byte a byte de estas mismas 17
> clases, en un namespace distinto (`App\Models\Cv\*`). No lo usa nada — todo
> el proyecto importa `App\Models\CV\*` (mayúscula). Es basura de un renombrado
> a medias; no se ha tocado en este pase porque no formaba parte del encargo,
> pero conviene borrarla en un cambio aparte antes de que alguien importe la
> copia equivocada por error.

### Controladores

| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Cv/V2/CurriculumController.php` | API V2 | `index`, `shared`, `show`, `section` — ver [contrato de API](api/v2/cv.md) |
| `app/Http/Controllers/Cv/CurriculumController.php` | Web | Descarga de PDF: `defaultPdf`, `pdf`, `sharedPdf` |

### Servicios

| Archivo | Descripción |
|---------|-------------|
| `app/Services/Cv/CurriculumService.php` | `publicOnly()`, `bySlug()`, `byShareToken()`, `defaultCurriculum()` — todas cargan las 15 secciones (`CurriculumService::SECTIONS`) de una vez |
| `app/Services/Cv/CurriculumPdfService.php` | Genera el PDF con DomPDF (`barryvdh/laravel-dompdf`) y lo guarda; `absolutePath()` da la ruta del que ya existe |

### Enums

| Archivo | Descripción |
|---------|-------------|
| `app/Enums/CurriculumVisibilityEnum.php` | `Private` / `Shared` / `Public`, con `label()`/`description()` |

### Otros

| Archivo | Descripción |
|---------|-------------|
| `app/Policies/CurriculumPolicy.php` | `view`/`update`/`delete`: admin o el propio dueño. `create`: cualquier usuario que no sea un token de dispositivo IoT |
| `app/Console/Commands/CV/RegenerateCurriculumPdfsCommand.php` | `cv:regenerate-pdfs`, programado (ver `routes/console.php`) — regenera los PDF marcados con `pdf_needs_regeneration` |

## Relaciones del modelo Curriculum (las 15 secciones, cargadas juntas por `CurriculumService::SECTIONS`)

| Relación | Modelo destino | Tipo |
|----------|---------------|------|
| `repositories` | `CurriculumRepository` | HasMany |
| `services` | `CurriculumService` | HasMany |
| `collaborations` | `CurriculumCollaboration` | HasMany |
| `hobbies` | `CurriculumHobby` | HasMany |
| `jobs` | `CurriculumJob` | HasMany |
| `projects` | `CurriculumProject` | HasMany |
| `academicTraining` | `CurriculumAcademicTraining` | HasMany |
| `academicComplementary` | `CurriculumAcademicComplementary` | HasMany |
| `academicComplementaryOnline` | `CurriculumAcademicComplementaryOnline` | HasMany |
| `experienceAccredited` | `CurriculumExperienceAccredited` | HasMany |
| `experienceNoAccredited` | `CurriculumExperienceNoAccredited` | HasMany |
| `experienceSelfEmployed` | `CurriculumExperienceSelfEmployed` | HasMany |
| `experienceAdditional` | `CurriculumExperienceAdditional` | HasMany |
| `experienceOther` | `CurriculumExperienceOther` | HasMany |
| `skills` | `CurriculumSkill` | HasMany |

El controlador de API (`CurriculumController::section()`) devuelve estas
colecciones **tal cual las carga Eloquent**, sin pasar por ningún
`JsonResource`: el JSON incluye todas las columnas de la tabla. Detalle
completo en el [contrato de API](api/v2/cv.md).

## Rutas API V2

Base `/api/v2/curriculum`, todas públicas y de solo lectura. Ver
[`docs/info/api/v2/cv.md`](api/v2/cv.md) para el contrato exacto (parámetros,
JSON de respuesta, errores).

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/curriculum` | Listado paginado, solo currículums públicos y activos |
| GET | `/curriculum/shared/{shareToken}` | Un currículum por enlace privado |
| GET | `/curriculum/{slug}` | Un currículum completo, con sus 15 secciones |
| GET | `/curriculum/{slug}/{section}` | Una sección suelta (9 valores válidos de `{section}`) |

## Rutas Web (`routes/cv/web.php`)

| Ruta | Nombre | Descripción |
|------|--------|-------------|
| `GET /cv/pdf` | `cv.pdf.default` | PDF del currículum predeterminado |
| `GET /cv/{slug}/pdf` | `cv.pdf` | PDF de un currículum público, por slug |
| `GET /cv/s/{shareToken}` | `cv.shared.pdf` | PDF de un currículum compartido por enlace (cabecera `X-Robots-Tag: noindex, nofollow`) |

> ⚠️➡️✅ **Bug corregido el 2026-08-30**: las tres rutas apuntaban a métodos
> que no existían en el controlador (`pdfPorDefecto`, `pdfCompartido` en vez
> de `defaultPdf`, `sharedPdf`), así que **las tres devolvían 500** en
> cualquier petición (`BadMethodCallException`). Arreglado en
> `routes/cv/web.php`; test de regresión en
> `tests/Feature/Cv/CurriculumWebRoutesTest.php`.

## PDF: generación real, no un fichero estático

- Se genera con **DomPDF** (`barryvdh/laravel-dompdf`, sí está instalado) vía
  `CurriculumPdfService`, a partir de los datos reales del currículum — no es
  un fichero fijo en `public/`.
- Solo se sirve si `Curriculum::is_downloadable` es `true`; si no, 404.
- Cada currículum guarda `pdf_path`, `pdf_generated_at` y
  `pdf_needs_regeneration`. Cualquier cambio en el currículum o sus secciones
  marca `pdf_needs_regeneration = true` (`Curriculum::booted()` →
  `markPdfForRegeneration()`).
- Si al pedir el PDF no existe o está marcado para regenerar, el controlador
  web lo genera al vuelo antes de servirlo (mejor una descarga lenta que un
  PDF caducado); si la generación falla y hay uno viejo, se sirve el viejo.
- El comando programado `cv:regenerate-pdfs` (`app/Console/Commands/CV/RegenerateCurriculumPdfsCommand.php`)
  regenera en batch los que están marcados, para no depender solo de la
  primera visita.

## Comando de debug

```bash
php artisan debug:seed-cv
```

---

> Creado: 2026-05-25 · Última revisión: 2026-08-30
