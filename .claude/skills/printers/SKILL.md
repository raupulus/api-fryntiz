---
name: printers
description: >-
  Mapa del módulo Impresoras de Api Raupulus (gestión de impresoras y su cola de
  impresión, vía Filament). Cárgala cuando trabajes con impresoras o la cola de
  impresión: modelos Printer, PrinterStack, PrinterAvailableType; tablas
  printers, printer_stack, printer_available_types; o el PrinterResource del panel
  Admin. Úsala ante "impresora", "cola de impresión", "print stack",
  "PrinterResource" o "tipos de impresora (2D/3D/térmica/tickets)". Es una skill
  de orientación: las convenciones generales viven en laravel-backend (modelos) y
  filament-admin (panel); aquí están solo los hechos propios del módulo.
---

# Módulo Impresoras — mapa rápido

Doc completa en `docs/info/printers.md`. CRUD estándar gestionado desde el panel
Admin de Filament; sigue las convenciones generales de las skills
`laravel-backend` y `filament-admin`. Aquí solo lo específico del módulo.

## Modelos (extienden `BaseModel`)

| Modelo | Tabla | Rol |
|--------|-------|-----|
| `Printer` (`app/Models/Printer.php`) | `printers` | La impresora. FKs: `user_id`, `hardware_device_id` (nullable → `HardwareDevice`), `printer_type_id` |
| `PrinterStack` (`app/Models/PrinterStack.php`) | `printer_stack` | Cola/registro de impresión. FKs: `user_id`, `printer_id`; campos `note`, `content` |
| `PrinterAvailableType` (`app/Models/PrinterAvailableType.php`) | `printer_available_types` | Catálogo de tipos (`name`, `slug`, `description`) |

Relaciones clave: `Printer` → `printerType` (belongsTo), `printStack` (hasMany),
`hardwareDevice` (belongsTo, opcional). Tipos sembrados por
`PrinterAvailableTypesSeeder`: **2D, 3D, Térmica, Tickets**.

## Convenciones propias en Filament

- `PrinterResource` vive en el **grupo de navegación "Hardware"**, junto a los
  dispositivos. Mantén ahí cualquier recurso relacionado.
- La **cola de impresión** se gestiona como **RelationManager**
  (`PrinterStackRelationManager`) dentro de la edición de la impresora, no como
  recurso suelto.
- Al crear un registro de cola, `user_id` se asigna automáticamente al usuario
  autenticado (`CreateAction::mutateDataUsing`). No pidas ese campo en el form.
- Las FKs se editan con `Select::make()->relationship()` + `searchable()`/`preload()`.

## Al tocar el módulo

Si añades campos/relaciones: migración con comentarios (skill
`postgresql-migrations`), actualiza el modelo y **`docs/info/printers.md`**.
