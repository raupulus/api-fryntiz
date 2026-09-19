# Módulo Impresoras

Gestión de periféricos de impresión física (con foco en impresoras térmicas ESC/POS como EM5820, tickets, 2D y 3D) y colas de trabajo, administradas desde Filament e integradas mediante API REST V2 y WebSockets (Laravel Reverb).

## Archivos principales

- **Modelos**: `app/Models/Printer.php`, `app/Models/PrinterStack.php` (extienden `BaseModel`).
- **Enums PHP 8.4**: `App\Enums\PrinterTypeEnum`, `App\Enums\PrinterStatusEnum`, `App\Enums\PrintJobStatusEnum`, `App\Enums\PrintJobFormatEnum`.
- **Servicio de Negocio**: `App\Services\Printers\PrinterService.php`.
- **Eventos WebSockets**: `App\Events\Printers\PrintJobCreated.php` (`job.created`), `App\Events\Printers\PrintJobStatusUpdated.php` (`job.updated`).
- **Canal de Broadcast**: `routes/channels.php` (`private-printer.{printerId}`).
- **Controladores API V2**: `app/Http/Controllers/Api/Printers/V2/PrinterController.php`, `app/Http/Controllers/Api/Printers/V2/PrintJobController.php`.
- **FormRequests API V2**: `app/Http/Requests/Api/Printers/V2/` (`EnqueuePrintJobRequest`, `ClaimNextJobRequest`, `UpdatePrintJobStatusRequest`, `PrinterHeartbeatRequest`, `ReprintJobRequest`, `ToggleFavoriteJobRequest`).
- **Resources API V2**: `app/Http/Resources/V2/Printers/PrinterResource.php`, `app/Http/Resources/V2/Printers/PrintJobResource.php`.
- **Rutas API V2**: `routes/printers/v2.php` (requerido desde `routes/api/v2.php`).
- **Comando de Mantenimiento**: `app/Console/Commands/Printers/CleanupStaleJobsCommand.php` (`printers:cleanup-stale-jobs`).
- **Resource Filament Admin**: `app/Filament/Admin/Resources/Printers/PrinterResource.php`.
- **RelationManager Filament**: `app/Filament/Admin/Resources/Printers/RelationManagers/PrinterStackRelationManager.php`.
- **Tablas**: `printers`, `printer_stack`. (La tabla legacy `printer_available_types` fue eliminada y reemplazada por `PrinterTypeEnum`).
- **Policy**: `app/Policies/PrinterPolicy.php`.
- **Contrato de API V2**: [`docs/info/api/v2/printers.md`](api/v2/printers.md).

---

## Campos del modelo Printer

La tabla `printers` no duplica `user_id`: la propiedad se hereda de `hardware_devices.user_id`.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigIncrements | Identificador único |
| hardware_device_id | FK hardware_devices | Dispositivo físico anfitrión (NOT NULL, cascade) |
| name | varchar(511) | Nombre descriptivo de la impresora |
| code | varchar(255) nullable | Código identificador |
| printer_type | varchar(32) | Enum `PrinterTypeEnum` (`thermal`, `ticket`, `2d`, `3d`) |
| is_active | boolean | Habilitada para recibir trabajos (default true) |
| status | varchar(32) | Enum `PrinterStatusEnum` (`ready`, `busy`, `out_of_paper`, `cover_open`, `offline`, `error`) |
| supported_formats | jsonb | Formatos soportados (`text`, `escpos`, `markdown`, `json`, `gcode`) |
| default_format | varchar(32) | Formato predeterminado (`PrintJobFormatEnum`) |
| max_payload_kb | unsignedInteger | Tamaño máximo de carga útil en KB (default 64) |
| total_prints_count | unsignedInteger | Odómetro acumulado de impresiones confirmadas (default 0) |
| last_seen_at | timestamp nullable | Último contacto recibido del microcontrolador |
| description | text nullable | Descripción |

---

## Campos del modelo PrinterStack (Cola de Impresión)

Cuelga directamente de `printer_id` (`printers.id`).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigIncrements | Identificador único del trabajo |
| printer_id | FK printers | Impresora física de destino (NOT NULL, cascade) |
| user_id | FK users nullable | Usuario que encoló el trabajo (opcional para sistema/IoT) |
| content | text | Contenido o payload a imprimir (texto plano, Base64 ESC/POS, etc.) |
| note | varchar(511) nullable | Nota descriptiva |
| status | varchar(32) | Enum `PrintJobStatusEnum` (`pending`, `processing`, `completed`, `failed`, `cancelled`) |
| format | varchar(32) | Enum `PrintJobFormatEnum` (`text`, `escpos`, `markdown`, `json`, `gcode`) |
| priority | integer | Prioridad de ejecución (mayor número se procesa antes) |
| attempts | unsignedSmallInteger | Número de intentos de ejecución realizados |
| print_count | unsignedInteger | Veces que se ha confirmado físicamente la impresión con éxito |
| is_favorite | boolean | Marcado como plantilla o favorito para reimpresión |
| error_message | text nullable | Mensaje o traza en caso de fallo |
| printed_at | timestamp nullable | Momento de la última impresión exitosa |

Índice compuesto para extracción atómica: `(printer_id, status, priority, created_at)`.

---

## Relaciones

- `Printer` → `HardwareDevice` (belongsTo, obligatorio)
- `Printer` → `User` (hasOneThrough vía `HardwareDevice`)
- `Printer` → `PrinterStack` (hasMany, `printStack`)
- `PrinterStack` → `Printer` (belongsTo)
- `PrinterStack` → `User` (belongsTo, nullable)

---

## Integración WebSockets (Laravel Reverb)

- **Canal Privado**: `printer.{printerId}` (`private-printer.{printerId}` en Pusher/Reverb).
- **Autorización dual**:
  - Usuarios autenticados (`session`): si son dueños del dispositivo o administradores.
  - Microcontroladores / Tokens IoT: si el token tiene `printers:write` o `printers:read` y su scope alcanza el dispositivo (`device:{hardware_device_id}`).
- **Eventos**:
  - `job.created`: emite id de trabajo, formato y prioridad cuando se encola una tarea.
  - `job.updated`: emite id, estado y contador de impresiones.
- **Principio Metadata Only**: El payload del ticket nunca viaja por WebSockets por seguridad y memoria del microcontrolador; el firmware recibe el aviso y descarga el trabajo vía `POST /jobs/next`.

---

## Panel Filament Admin

- Ubicado en el grupo de navegación **Hardware**.
- `PrinterResource` implementa `ScopesToOwner`: los usuarios sólo ven sus impresoras, los administradores todas.
- Gestión de formatos soportados, formato por defecto y cota `max_payload_kb`.
- Odómetro en tabla y formulario.
- `PrinterStackRelationManager`: badges de estado y formato, columna de impresiones, acciones de fila para **Reimprimir**, **Reintentar** (si falló) y **Cancelar** (si está pendiente).

---

## Estado del módulo

| Capa | Estado | Detalle |
|------|--------|---------|
| Modelos (`Printer`, `PrinterStack`) | ✅ | `HasFactory`, enums PHP 8.4, scopes y casts |
| Migraciones | ✅ | `2026_09_17_000004_update_printers_and_printer_stack_tables.php` |
| Panel Filament | ✅ | `PrinterResource` y `PrinterStackRelationManager` completos |
| API REST V2 | ✅ | Controladores, FormRequests y Resources bajo `/api/v2/printers` |
| WebSockets Reverb | ✅ | Canal privado `printer.{id}` y eventos `job.created` / `job.updated` |
| Service Layer | ✅ | `PrinterService` con extracción atómica `lockForUpdate` y odómetro |
| Mantenimiento | ✅ | Comando `printers:cleanup-stale-jobs` |
| Tests Automatizados | ✅ | `PrintersTest` (Feature API V2), `PrinterServiceTest` (Unit), `CleanupStaleJobsCommandTest` (Feature), `PanelAuthorizationTest` |

---

> Creado: 2026-06-17 · Última revisión: 2026-09-19
