# Módulo Impresoras

Gestión de impresoras y su cola de impresión desde el panel de administración Filament.

## Archivos principales

- Modelos: `app/Models/Printer.php`, `app/Models/PrinterStack.php`, `app/Models/PrinterAvailableType.php`
- Resource Filament: `app/Filament/Admin/Resources/Printers/PrinterResource.php`
- Pages: `ListPrinters`, `CreatePrinter`, `EditPrinter`
- RelationManager: `PrinterStackRelationManager`
- Tablas: `printers`, `printer_stack`, `printer_available_types`
- Seeder de tipos: `PrinterAvailableTypesSeeder` (tipos: 2D, 3D, Térmica, Tickets)

## Campos del modelo Printer

| Campo | Tipo | Descripción |
|-------|------|-------------|
| user_id | FK users | Propietario |
| hardware_device_id | FK hardware_devices (nullable) | Dispositivo asociado |
| printer_type_id | FK printer_available_types | Tipo de impresora |
| name | varchar(511) | Nombre |
| code | varchar(255) nullable | Código identificador |
| description | text nullable | Descripción |

## Campos del modelo PrinterStack

| Campo | Tipo | Descripción |
|-------|------|-------------|
| user_id | FK users | Usuario que creó el registro |
| printer_id | FK printers | Impresora asociada |
| note | varchar(511) nullable | Notas |
| content | text nullable | Contenido a imprimir |

## Relaciones

- Printer → User (belongsTo)
- Printer → HardwareDevice (belongsTo)
- Printer → PrinterAvailableType (belongsTo, `printerType`)
- Printer → PrinterStack (hasMany, `printStack`)
- PrinterStack → User (belongsTo)
- PrinterStack → Printer (belongsTo)

## Configuración Filament

- Grupo de navegación: **Hardware** (descubrimiento automático).
- Todos los campos FK usan `Select::make()->relationship()` con `searchable()`/`preload()`.
- La cola de impresión se gestiona como RelationManager dentro de la edición de cada impresora.
- Al crear un registro de cola, `user_id` se asigna automáticamente al usuario autenticado
  (`CreateAction::mutateDataUsing`).
