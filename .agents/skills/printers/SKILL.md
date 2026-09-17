---
name: printers
description: >-
  Mapa del módulo Impresoras de Api Raupulus (gestión de impresoras y su cola de
  impresión, vía API REST V2, WebSockets y Filament). Cárgala cuando trabajes con
  impresoras o la cola de impresión: modelos Printer, PrinterStack; enums
  PrinterTypeEnum, PrinterStatusEnum, PrintJobStatusEnum, PrintJobFormatEnum;
  tablas printers, printer_stack; o el PrinterResource del panel Admin.
  Úsala ante "impresora", "cola de impresión", "print stack", "PrinterResource" o
  "tipos de impresora (2D/3D/térmica/tickets)". Es una skill de orientación: las
  convenciones generales viven en laravel-backend, api-rest-v2 y filament-admin.
---

# Módulo Impresoras — mapa rápido

Doc completa en `docs/info/printers.md` y contrato API en `docs/info/api/v2/printers.md`.
Gestión centralizada de periféricos de impresión física (térmica ESC/POS como EM5820, tickets, 2D y 3D)
y consumo atómico de colas por parte de microcontroladores (ESP32, Raspberry Pi Pico W).

## Modelos (extienden `BaseModel`)

| Modelo | Tabla | Rol |
|--------|-------|-----|
| `Printer` (`app/Models/Printer.php`) | `printers` | La impresora física. FK: `hardware_device_id` (HardwareDevice). Enums: `printer_type` (PrinterTypeEnum), `status` (PrinterStatusEnum), `default_format` (PrintJobFormatEnum). Formatos en JSONB `supported_formats`, odómetro `total_prints_count`. |
| `PrinterStack` (`app/Models/PrinterStack.php`) | `printer_stack` | Cola/registro de impresión. FK: `printer_id` (Printer), `user_id` (User, nullable). Enums: `status` (PrintJobStatusEnum), `format` (PrintJobFormatEnum). Contador de impresiones confirmadas `print_count`, `priority`, `is_favorite`, `attempts`. |

Relaciones clave: `Printer` → `hardwareDevice` (belongsTo), `user` (hasOneThrough vía HardwareDevice), `printStack` (hasMany). `PrinterStack` → `printer` (belongsTo), `user` (belongsTo).

## API REST V2 y WebSockets

- Prefijo: `/api/v2/printers`.
- WebSockets: canal privado `printer.{id}` (`private-printer.{id}` en Pusher/Reverb).
- Eventos broadcast: `job.created` (alias `App\Events\Printers\PrintJobCreated`), `job.updated` (alias `App\Events\Printers\PrintJobStatusUpdated`).
- Abilities Sanctum: `printers:read` (lectura/consulta) y `printers:write` (consumo atómico de cola, estado y heartbeat).
- Microcontrolador: consume de 1 en 1 con `POST /api/v2/printers/{id}/jobs/next` (exclusión mutua vía `lockForUpdate`), confirma con `PATCH /jobs/{id}/status` y reporta presencia con `POST /heartbeat`. Soporta telemetría unificada `hardware_device_info`.

## Convenciones en Filament

- `PrinterResource` vive en el grupo de navegación **"Hardware"**, junto a los dispositivos.
- `ScopesToOwner`: acotado a los periféricos cuyo `HardwareDevice` pertenezca al usuario (o todos para Admin/SuperAdmin).
- La **cola de impresión** se gestiona como **RelationManager** (`PrinterStackRelationManager`) dentro de la edición de la impresora.
- Al crear un trabajo desde Filament, `user_id` se asigna automáticamente al usuario autenticado.
- Acciones en tabla de cola: **Reimprimir**, **Reintentar** (si falló), **Cancelar** (si pendiente).

## Al tocar el módulo

Si añades campos/relaciones: migración con comentarios en PostgreSQL, actualiza el modelo, el contrato en `docs/info/api/v2/printers.md` y `docs/info/printers.md`.
