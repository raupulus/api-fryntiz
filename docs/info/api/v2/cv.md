# Contrato API V2 — Currículum (CV)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, tablas, decisiones de producto) ver
> [`docs/info/cv.md`](../../cv.md).

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Éxito paginado
  { "success": true, "message": "...", "data": [ ... ], "meta": { "total": 1, "per_page": 25, "current_page": 1, "last_page": 1, "from": 1, "to": 1 } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

- **Autenticación**: **todas las rutas de este módulo son públicas**. No llevan
  `auth:sanctum` ni ninguna ability — se ha verificado tanto en
  `routes/cv/v2.php` (el grupo `curricula` no tiene middleware de auth, a
  diferencia de `auth/tokens` o `users/me` en `routes/api/v2.php`, que sí lo
  llevan explícitamente) como en `.scribe/endpoints/00.yaml`, donde las cuatro
  rutas de `curricula` están marcadas `authenticated: false`.
- **Ruta inexistente o sección no válida**: `404` con
  `{ "success": false, "message": "API V2 - Endpoint no encontrado" }` (la
  ruta de sección restringe `{section}` con un `where()` literal a las 9
  secciones válidas; cualquier otro valor no hace match de ruta y cae en el
  fallback, no en el controlador).

---

## Diseño: por qué hay `{slug}` en cada ruta

Antes existían diez rutas planas (`/cv/experience`, `/cv/skills`, …) que
asumían que solo hay **un** currículum en toda la plataforma y devolvían
siempre el del superadmin. El módulo tiene dieciocho tablas montadas para
poder tener varios currículums (uno por objetivo/oferta/plataforma), así que
cada sección cuelga ahora del `slug` de un currículum concreto.

Un currículum tiene tres visibilidades (`App\Enums\CurriculumVisibilityEnum`):

| Visibilidad | ¿Sale en `GET /curricula`? | ¿Accesible por `{slug}`? | ¿Accesible por `{shareToken}`? |
|---|---|---|---|
| `public` | Sí | Sí | Sí (aunque no hace falta) |
| `shared` | No | No (404) | Sí, con el token correcto |
| `private` | No | No (404) | No |

Además, si `is_active` es `false` el currículum no es visible por ningún
camino, sea cual sea su visibilidad.

---

## Currículums (`/curricula`)

### `GET /curricula` — Listado público de currículums

- **Auth**: pública.
- **Filtro implícito**: solo devuelve currículums con `is_active = true` y
  `visibility = public` (scope `publicOnly`). Un currículum `shared` o
  `private` nunca aparece aquí.
- **Orden**: por defecto, `title` ascendente.
- **Query params** (`App\Http\Api\CollectionQuery`):

| Parámetro | Tipo | Descripción |
|---|---|---|
| `page` | int | Página, por defecto `1` |
| `per_page` | int | Tamaño de página, por defecto `25`, máximo `100` |
| `sort` | string | `title`, `-title`, `created_at`, `-created_at` (el guion es descendente; por defecto `title` ascendente). Único campo ordenable además de `title` |
| `created_at` | string | Igualdad exacta |
| `created_at[gte]` / `[gt]` / `[lte]` / `[lt]` / `[ne]` | string | Rango sobre `created_at` |
| `from` / `to` | string (fecha) | Alias de `created_at[gte]` / `created_at[lte]` |

  `created_at` es el **único** campo filtrable de este listado (no se puede
  filtrar por `title`, `is_default`, etc.).

- **Respuesta 200** (`CurriculumSummaryResource`, paginada — sin secciones ni
  `share_token`: el token es la llave del enlace privado, no puede salir en un
  listado público):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 3,
      "slug": "backend-laravel-senior",
      "title": "Desarrollador Backend Laravel & PostgreSQL",
      "presentation": "Más de 8 años desarrollando APIs REST y paneles de administración con Laravel.",
      "is_default": true,
      "is_downloadable": true,
      "image": "https://api.fryntiz.es/storage/cv/backend-laravel-senior/foto-large.jpg",
      "created_at": "2025-03-10T09:00:00.000000Z",
      "updated_at": "2026-07-01T18:22:00.000000Z"
    }
  ],
  "meta": {
    "total": 1,
    "per_page": 25,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

  `image` nunca es `null`: si el currículum no tiene imagen propia, sale la
  imagen por defecto de la plataforma (`File::urlDefaultImage('large')`).

### `GET /curricula/{slug}` — Un currículum completo

- **Auth**: pública.
- **Parámetros de ruta**: `slug` (string).
- **Visibilidad**: solo si `isVisibleTo()` es verdadero sin token, es decir
  solo si `visibility = public` y `is_active = true`. Un currículum `shared`
  **no** es accesible por aquí, solo por su enlace (`GET
  /curricula/shared/{shareToken}`).
- **Respuesta 200** (`CurriculumResource`, con las 15 secciones cargadas.
  Cada bloque de sección usa `whenLoaded`, así que si en algún momento se
  sirviera sin cargar una relación, esa clave desaparecería en vez de salir
  vacía):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "id": 3,
    "slug": "backend-laravel-senior",
    "title": "Desarrollador Backend Laravel & PostgreSQL",
    "presentation": "Más de 8 años desarrollando APIs REST y paneles de administración con Laravel.",
    "is_default": true,
    "is_downloadable": true,
    "image": "https://api.fryntiz.es/storage/cv/backend-laravel-senior/foto-large.jpg",
    "experiences": {
      "accredited": [
        {
          "id": 12,
          "curriculum_id": 3,
          "image_id": null,
          "title": "Desarrollador Backend",
          "position": "Backend Developer",
          "company": "Api Raupulus S.L.",
          "description": "Diseño y mantenimiento de la API REST V2 y el panel de administración.",
          "note": null,
          "start_at": "2022-01-01",
          "end_at": null,
          "created_at": "2025-03-10T09:05:00.000000Z",
          "updated_at": "2026-06-01T10:00:00.000000Z"
        }
      ],
      "no_accredited": [],
      "self_employed": [],
      "additional": [],
      "other": []
    },
    "educations": {
      "training": [
        {
          "id": 4,
          "curriculum_id": 3,
          "image_id": null,
          "title": "Desarrollo de Aplicaciones Web",
          "entity": "IES Ejemplo",
          "credential_id": null,
          "credential_url": null,
          "learned": "Backend, frontend, bases de datos",
          "description": null,
          "note": null,
          "hours": 2000,
          "instructor": null,
          "expires": false,
          "expires_at": null,
          "expedition_at": "2015-06-30",
          "start_at": "2013-09-01",
          "end_at": "2015-06-30",
          "created_at": "2025-03-10T09:05:00.000000Z",
          "updated_at": "2025-03-10T09:05:00.000000Z"
        }
      ],
      "complementary": [],
      "complementary_online": []
    },
    "skills": [
      {
        "id": 7,
        "curriculum_id": 3,
        "image_id": null,
        "name": "Laravel",
        "level": 9,
        "description": "Framework principal de backend.",
        "created_at": "2025-03-10T09:05:00.000000Z",
        "updated_at": "2025-03-10T09:05:00.000000Z"
      }
    ],
    "projects": [],
    "repositories": [],
    "services": [],
    "collaborations": [],
    "hobbies": [],
    "jobs": [],
    "created_at": "2025-03-10T09:00:00.000000Z",
    "updated_at": "2026-07-01T18:22:00.000000Z"
  }
}
```

- **Errores**: `404` `{"success": false, "message": "Currículum no encontrado"}`
  tanto si el `slug` no existe como si el currículum no es público — es el
  mismo mensaje a propósito, para que la URL no confirme la existencia de un
  slug privado.

### `GET /curricula/shared/{shareToken}` — Un currículum por enlace privado

- **Auth**: pública (el propio token hace de credencial).
- **Parámetros de ruta**: `shareToken` — string hexadecimal de **exactamente
  64 caracteres** (`[A-Fa-f0-9]{64}`, `bin2hex(random_bytes(32))`). Esta ruta
  va declarada antes de `/{slug}` para que un slug real no se la coma.
- **Visibilidad**: el currículum se busca por `share_token` y luego se
  comprueba `isVisibleTo($token)`: solo pasa si `is_active = true`,
  `visibility = shared` y el token coincide (comparación con `hash_equals`).
  Si el currículum ha vuelto a `private` o a `public`, el token deja de
  valer aunque siga siendo el que se guardó en su día.
- **Respuesta 200**: mismo `CurriculumResource` que `GET /curricula/{slug}`
  (mismas 15 secciones, mismos campos).
- **Cabecera propia**: `X-Robots-Tag: noindex, nofollow` — para que el
  currículum no acabe indexado si alguien pega el enlace en cualquier sitio
  público.
- **Errores**: `404` `{"success": false, "message": "Currículum no encontrado"}`
  tanto si no existe ningún currículum con ese `share_token` como si existe
  pero ya no es `shared`/está inactivo o el formato del token no matchea la
  regex de la ruta (en ese último caso ni siquiera entra al controlador).

### `GET /curricula/{slug}/{section}` — Una sección suelta de un currículum

- **Auth**: pública.
- **Parámetros de ruta**:
  - `slug` — igual que en `GET /curricula/{slug}`.
  - `section` — uno exactamente de estos 9 valores (restringido por
    `where()` en la ruta; cualquier otro valor no hace match y responde el
    404 genérico de "endpoint no encontrado", no llega al controlador):
    `experiences`, `educations`, `skills`, `projects`, `repositories`,
    `services`, `collaborations`, `hobbies`, `jobs`.
- **Visibilidad**: misma regla que `GET /curricula/{slug}` (solo currículums
  públicos y activos; no admite `shareToken` — para una sección de un CV
  compartido hay que pedir el currículum completo por su enlace).
- **Importante — no hay Resource dedicado por sección**: el controlador
  (`CurriculumController::section()`) devuelve las colecciones de modelos
  **tal cual las carga Eloquent**, sin pasar por ningún `JsonResource`. Por
  eso el JSON de cada fila lleva *todas* las columnas de la tabla —
  `curriculum_id`, `image_id`, timestamps— no solo lo "presentable" que sí
  filtra `CurriculumResource`. Tampoco lleva ningún atributo calculado
  (`url_image`, relaciones no cargadas como `type` en repositorios): esos
  accessors existen en el modelo pero no están en `$appends`, así que no
  aparecen si nadie los pide explícitamente.
- **Forma de la respuesta**: dos casos, según si la sección agrupa una o
  varias tablas:
  - `skills`, `projects`, `repositories`, `services`, `collaborations`,
    `hobbies`, `jobs` → una tabla cada una → `data` es directamente el
    **array** de esa tabla (no `{"skills": [...]}`, sino `[...]`).
  - `experiences` (agrupa 5 tablas) y `educations` (agrupa 3 tablas) → `data`
    es un **objeto** con una clave por sub-tipo.

#### `GET /curricula/{slug}/skills`

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 7,
      "curriculum_id": 3,
      "image_id": null,
      "name": "Laravel",
      "level": 9,
      "description": "Framework principal de backend.",
      "created_at": "2025-03-10T09:05:00.000000Z",
      "updated_at": "2025-03-10T09:05:00.000000Z"
    },
    {
      "id": 8,
      "curriculum_id": 3,
      "image_id": null,
      "name": "PostgreSQL",
      "level": 8,
      "description": null,
      "created_at": "2025-03-10T09:05:00.000000Z",
      "updated_at": "2025-03-10T09:05:00.000000Z"
    }
  ]
}
```

Campos de cada fila (`cv_skills` / `CurriculumSkill`): `id`, `curriculum_id`,
`image_id`, `name` (string), `level` (int|null, 1–10), `description`
(string|null), `created_at`, `updated_at`.

#### `GET /curricula/{slug}/projects`, `/repositories`, `/services`, `/collaborations`, `/jobs`

Misma forma que `skills`: un array plano. Campos por tabla:

- **`projects`** (`cv_projects`) y **`collaborations`** (`cv_collaborations`)
  y **`jobs`** (`cv_jobs`) comparten el mismo esquema: `id`,
  `curriculum_id`, `image_id`, `title`, `description`, `url`, `urlinfo`,
  `repository` (URL del repo, string|null), `role`, `created_at`,
  `updated_at`.
- **`repositories`** (`cv_repositories`): `id`, `curriculum_id`, `image_id`,
  `repository_type_id` (FK cruda a `cv_available_repository_types`; el
  modelo tiene una relación `type()` pero el servicio no la precarga, así
  que aquí **no** sale el nombre del tipo, solo su id), `url`, `title`,
  `description`, `name`, `created_at`, `updated_at`.
- **`services`** (`cv_services`): `id`, `curriculum_id`, `image_id`, `name`,
  `url`, `description`, `created_at`, `updated_at`.

Ejemplo (`repositories`):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 5,
      "curriculum_id": 3,
      "image_id": null,
      "repository_type_id": 1,
      "url": "https://github.com/raupulus/api-fryntiz",
      "title": "API Fryntiz",
      "description": "API REST V2 de la plataforma.",
      "name": "api-fryntiz",
      "created_at": "2025-03-10T09:05:00.000000Z",
      "updated_at": "2025-03-10T09:05:00.000000Z"
    }
  ]
}
```

#### `GET /curricula/{slug}/hobbies`

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [
    {
      "id": 2,
      "curriculum_id": 3,
      "image_id": null,
      "title": "Electrónica y domótica",
      "description": "Montaje de estaciones meteorológicas y sensores IoT.",
      "url": null,
      "created_at": "2025-03-10T09:05:00.000000Z",
      "updated_at": "2025-03-10T09:05:00.000000Z"
    }
  ]
}
```

Campos (`cv_hobbies` / `CurriculumHobby`): `id`, `curriculum_id`, `image_id`,
`title` (string|null), `description` (string|null), `url` (string|null),
`created_at`, `updated_at`.

#### `GET /curricula/{slug}/experiences`

Agrupa 5 tablas. Claves fijas, siempre las 5 presentes (vacías como `[]` si
no hay filas):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "accredited": [
      {
        "id": 12,
        "curriculum_id": 3,
        "image_id": null,
        "title": "Desarrollador Backend",
        "position": "Backend Developer",
        "company": "Api Raupulus S.L.",
        "description": "Diseño y mantenimiento de la API REST V2 y el panel de administración.",
        "note": null,
        "start_at": "2022-01-01",
        "end_at": null,
        "created_at": "2025-03-10T09:05:00.000000Z",
        "updated_at": "2026-06-01T10:00:00.000000Z"
      }
    ],
    "no_accredited": [],
    "self_employed": [],
    "additional": [],
    "other": []
  }
}
```

| Clave | Tabla | Modelo |
|---|---|---|
| `accredited` | `cv_experience_accredited` | `CurriculumExperienceAccredited` |
| `no_accredited` | `cv_experience_no_accredited` | `CurriculumExperienceNoAccredited` |
| `self_employed` | `cv_experience_self_employed` | `CurriculumExperienceSelfEmployed` |
| `additional` | `cv_experience_additional` | `CurriculumExperienceAdditional` |
| `other` | `cv_experience_others` | `CurriculumExperienceOther` |

Las 5 tablas comparten el mismo esquema de columnas: `id`, `curriculum_id`,
`image_id`, `title`, `position` (string|null), `company` (string|null),
`description` (string|null), `note` (string|null), `start_at` (string|null),
`end_at` (string|null), `created_at`, `updated_at`.

#### `GET /curricula/{slug}/educations`

Agrupa 3 tablas, mismo patrón (siempre las 3 claves presentes):

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "training": [
      {
        "id": 4,
        "curriculum_id": 3,
        "image_id": null,
        "title": "Desarrollo de Aplicaciones Web",
        "entity": "IES Ejemplo",
        "credential_id": null,
        "credential_url": null,
        "learned": "Backend, frontend, bases de datos",
        "description": null,
        "note": null,
        "hours": 2000,
        "instructor": null,
        "expires": false,
        "expires_at": null,
        "expedition_at": "2015-06-30",
        "start_at": "2013-09-01",
        "end_at": "2015-06-30",
        "created_at": "2025-03-10T09:05:00.000000Z",
        "updated_at": "2025-03-10T09:05:00.000000Z"
      }
    ],
    "complementary": [],
    "complementary_online": []
  }
}
```

| Clave | Tabla | Modelo |
|---|---|---|
| `training` | `cv_academic_training` | `CurriculumAcademicTraining` |
| `complementary` | `cv_academic_complementary` | `CurriculumAcademicComplementary` |
| `complementary_online` | `cv_academic_complementary_online` | `CurriculumAcademicComplementaryOnline` |

Las 3 tablas comparten esquema: `id`, `curriculum_id`, `image_id`, `title`,
`entity` (string|null), `credential_id` (string|null), `credential_url`
(string|null), `learned` (string|null), `description` (string|null), `note`
(string|null), `hours` (int|null), `instructor` (string|null), `expires`
(bool), `expires_at` (string|null), `expedition_at` (string|null), `start_at`
(string|null), `end_at` (string|null), `created_at`, `updated_at`.

- **Errores de `GET /curricula/{slug}/{section}`**:
  - `404` `{"success": false, "message": "Currículum no encontrado"}` si el
    `slug` no existe o el currículum no es público/activo.
  - `404` `{"success": false, "message": "API V2 - Endpoint no encontrado"}`
    si `{section}` no es una de las 9 palabras válidas (lo filtra el
    `where()` de la ruta antes de llegar al controlador; el `notFoundResponse('Sección no reconocida')`
    que hay dentro de `CurriculumController::section()` es una comprobación
    de cinturón y tirantes que en la práctica nunca se dispara por esta vía).

---

## Lo que existió y ya no tiene ruta (no lo reimplementes igual)

| Ruta antigua | Qué pasó |
|---|---|
| `GET /cv` | Es `GET /curricula/{slug}` — antes no existía slug, se devolvía siempre el CV del superadmin |
| `GET /cv/experience` | Es `GET /curricula/{slug}/experiences` |
| `GET /cv/education` | Es `GET /curricula/{slug}/educations` |
| `GET /cv/skills`, `/cv/projects`, `/cv/repositories`, `/cv/services`, `/cv/collaborations`, `/cv/hobbies`, `/cv/jobs` | Son `GET /curricula/{slug}/{section}` con la sección correspondiente |

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
