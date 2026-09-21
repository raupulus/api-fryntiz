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
| `app/Http/Controllers/Cv/CurriculumController.php` | Web | Vistas públicas (`index`, `show`) y descarga de PDF: `defaultPdf`, `pdf`, `sharedPdf` |

### Servicios

| Archivo | Descripción |
|---------|-------------|
| `app/Services/Cv/CurriculumService.php` | `publicOnly()`, `bySlug()`, `byShareToken()`, `defaultCurriculum()` — todas cargan las 15 secciones (`CurriculumService::SECTIONS`) de una vez |
| `app/Services/Cv/CurriculumPdfService.php` | Genera el PDF con DomPDF (`barryvdh/laravel-dompdf`) y lo guarda; `absolutePath()` da la ruta del que ya existe |
| `app/Services/Cv/CurriculumDocument.php` | Prepara un CV para pintarse como documento (web y PDF): ordena y agrupa secciones, formatea periodos, parte descripciones en viñetas, decide qué va en la barra lateral y genera el QR. Ver «Maquetación» |

### Enums

| Archivo | Descripción |
|---------|-------------|
| `app/Enums/CurriculumVisibilityEnum.php` | `Private` / `Shared` / `Public`, con `label()`/`description()` |

### Otros

| Archivo | Descripción |
|---------|-------------|
| `app/Policies/CurriculumPolicy.php` | `view`/`update`/`delete`: admin o el propio dueño. `create`: cualquier usuario que no sea un token de dispositivo IoT |
| `app/Filament/Concerns/ScopesToOwner.php` | Usado por `CurriculumResource`. `viewAny()` devuelve `true` —cada quien tiene que ver su propio índice— y eso, sin scoping, hacía que un `Editor` viese en `/admin/c-v/curriculums` **el listado completo de los CV de todos los usuarios**, publicados o no (AR-SEC-02). La tabla se filtra por `user_id`; el administrador la ve entera |
| `app/Console/Commands/CV/RegenerateCurriculumPdfsCommand.php` | `cv:regenerate-pdfs`, programado (ver `routes/console.php`) — regenera los PDF marcados con `pdf_needs_regeneration` |
| `config/cv.php` | Contacto público del propietario (email, ubicación, web, LinkedIn, GitHub) y datos breves (teletrabajo, carné). Igual para todos los CV, por eso no es columna |
| `resources/views/cv/pdf.blade.php` + `cv/partials/pdf-*.blade.php` | Plantilla del PDF (CSS compatible con DomPDF: tablas, nada de flex/grid) |
| `resources/views/cv/show.blade.php` + `cv/partials/web-*.blade.php` | Vista web: la misma maquetación que el PDF, con Tailwind |
| `resources/fonts/lato/` | Lato (SIL OFL 1.1) para el PDF. DomPDF guarda sus métricas en `storage/fonts/` (ignorado en git; `CurriculumPdfService` crea el directorio si falta) |

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

## Maquetación: la del CV impreso de 2024 (2026-09-21)

La vista `cv.show` y el PDF reproducen el CV de 2024: columna principal a la
izquierda (nombre, titular, contacto y secciones) y barra lateral azul a la
derecha (logo, perfil, habilidades, intereses, repositorios, QR del enlace
público y botones de LinkedIn/GitHub). Las dos vistas reciben los datos ya
preparados de `CurriculumDocument`, así que lo que se previsualiza en la web es
lo que se descarga.

- **Cabecera:** el nombre es el `full_name` del usuario dueño; el titular, el
  `title` del CV. El contacto sale de `config/cv.php`, **nunca** de
  `users.email` (el de acceso al panel puede no ser público).
- **Secciones de la columna principal, en este orden:** perfil (si no cabe en la
  barra), experiencia (acreditada + autónomo + no acreditada + adicional,
  mezcladas), habilidades (si no caben en la barra), educación (formación
  reglada), formación complementaria, certificaciones y cursos (online, a dos
  columnas), proyectos, trabajos, servicios, colaboraciones, otra experiencia
  (`experienceOther`), y al final intereses y código abierto si no caben en la
  barra. `experienceAdditional` (prácticas) antes no se pintaba en ningún sitio.
- **Orden dentro de cada sección con fechas:** lo que sigue en curso (sin
  `end_at`) primero, luego por `end_at` y `start_at` descendentes; lo que no
  tiene ninguna fecha, al final. Proyectos, habilidades, etc. por `position`.
- **Periodos** (no hay campo de precisión): un periodo del 1 de enero al 31 de
  diciembre se muestra en años (`2007 – 2009`); cualquier otro, en mes/año
  (`12/2018 – Actualidad`). Una fecha suelta (la `expedition_at` de un curso) en
  31 de diciembre se muestra como año. Para meter un dato del que sólo se sabe el
  año: inicio `YYYY-01-01`, fin `YYYY-12-31`.
- **Descripciones:** texto plano (el panel usa `Textarea`). Cada línea que
  empieza por `- ` es una viñeta; el resto, párrafos.
- **Habilidades:** cada fila es un grupo (`name` = «Backend», `description` =
  «PHP · Laravel · …»). `level` sigue pintando la barra si se rellena.
- **Barra lateral:** en el PDF sólo existe en la primera página (DomPDF no parte
  una columna entre páginas; con tablas deja páginas en blanco). Por eso
  `CurriculumDocument::inSidebar()` estima por caracteres lo que ocupa cada bloque
  (perfil, habilidades, intereses, repositorios, en ese orden) y lo pone en la
  barra sólo si cabe entero; si no, va a la columna principal. La estimación es
  conservadora a propósito: con una más ajustada las habilidades quedaban
  tapadas por el bloque del QR. En la web la barra ocupa todo el alto y su
  contenido es `sticky`.
- **Saltos de página:** las entradas de hasta 450 caracteres no se parten entre
  páginas; las más largas sí (si no, dejaban media página en blanco).
- **Imagen:** la del CV si tiene; si no, el logotipo del sitio
  (`public/images/logo/logo320x320.png` en el PDF, `.webp` en la web y en la API).
- **Colores:** en la web, tokens `cv-sidebar`, `on-cv-sidebar`,
  `cv-sidebar-footer`, `cv-accent`, `cv-linkedin` y `cv-github` en
  `resources/css/app.css` (con variante oscura). En el PDF van en la propia
  plantilla (DomPDF no lee el CSS de Vite).

## Rutas Web (`routes/cv/web.php`)

| Ruta | Nombre | Descripción |
|------|--------|-------------|
| `GET /cv` | `cv.index` | Listado de currículums públicos: una tarjeta horizontal por cada uno (`Curriculum::scopePublicOnly()`), enlazada desde el home en la tarjeta que antes llevaba al panel de gestión |
| `GET /cv/{slug}` | `cv.show` | Vista pública de un currículum con la maquetación del documento, y botones «Ver PDF» y «Descargar PDF» arriba a la derecha (sólo si `is_downloadable`) |
| `GET /cv/pdf` | `cv.pdf.default` | PDF del currículum predeterminado |
| `GET /cv/{slug}/pdf` | `cv.pdf` | PDF de un currículum público, por slug. Se abre en el navegador (`inline`); con `?download=1` se descarga (`attachment`). Nombre: `<nombre-completo>-<slug>.pdf` |
| `GET /cv/s/{shareToken}` | `cv.shared.pdf` | PDF de un currículum compartido por enlace (cabecera `X-Robots-Tag: noindex, nofollow`) |

> ⚠️ **Orden de las rutas**: `/{slug}` va registrada la última del grupo, después
> de `/s/{shareToken}` y `/pdf`. Los tres son literales de un solo segmento;
> si `/{slug}` se registrara antes, se comería «s» y «pdf» como si fueran un
> slug real y esas dos rutas nunca se alcanzarían.

> ⚠️ **`route('cv.show', ...)` y `route('cv.pdf', ...)` necesitan `['slug' =>
> ...]` explícito.** `Curriculum` no sobreescribe `getRouteKeyName()`, así que
> pasar el modelo tal cual (`route('cv.show', $cv)`) generaría la URL con el
> `id`, no con el `slug`. Se usa así en las vistas (`cv/index.blade.php`,
> `cv/show.blade.php`) y en `SitemapGeneratorCommand`.

> ⚠️➡️✅ **Bug corregido el 2026-08-30**: las tres rutas de PDF apuntaban a
> métodos que no existían en el controlador (`pdfPorDefecto`, `pdfCompartido`
> en vez de `defaultPdf`, `sharedPdf`), así que **las tres devolvían 500** en
> cualquier petición (`BadMethodCallException`). Arreglado en
> `routes/cv/web.php`; test de regresión en
> `tests/Feature/Cv/CurriculumWebRoutesTest.php`.

## Sitemap (2026-09-14)

`cv.index` entra siempre en `sitemap:generate`, tenga o no currículums
públicos hoy —igual que `smartplant.index` o `weather_station.index`—. Cada
currículum con `visibility = public` (y `is_active`) añade además su propia
`cv.show` (`SitemapGeneratorCommand::addCurriculumUrls()`): si no hay ninguno
público, no hay ninguna `cv.show` en el sitemap; si hay varios, entran todos.
Los compartidos por token y los privados nunca aparecen. Ver skill `seo`.

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

> Creado: 2026-05-25 · Última revisión: 2026-09-21
