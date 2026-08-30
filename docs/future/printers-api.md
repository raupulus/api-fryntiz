# API del módulo de Impresoras

> **Estado:** aplazado a propósito. Se implementa **cuando la v2 esté estable** y todo lo que hoy
> se usa en producción esté migrado.
> **Decidido el:** 2026-08-19

## Contexto

El módulo existe a medias y es intencional: **no está terminado**, no es código muerto.

El objetivo es gestionar **impresoras térmicas** como cola de impresión centralizada sobre varias
de ellas, y ofrecerlo como servicio.

## Qué hay hoy

| Capa | Estado |
|------|--------|
| Modelos `Printer`, `PrinterStack`, `PrinterAvailableType` | ✅ |
| Migraciones (`2022_02_17_*`) | ✅ |
| Seeder `PrinterAvailableTypesSeeder` | ✅ |
| Factories | ✅ |
| `PrinterResource` + `PrinterStackRelationManager` en Filament | ✅ |
| `docs/info/printers.md` | 🟡 49 líneas, el más pobre de `docs/info/` |
| **API V2** | ❌ |
| `PrinterPolicy` | ❌ |
| Tests | ❌ |

## Qué se hace ahora (dentro del roadmap actual)

Sin tocar la API, dejar lo que existe **optimizado, documentado y validando datos**:

- Revisar y optimizar las consultas de `PrinterResource` y `PrinterStackRelationManager`.
- Validación completa en los formularios de Filament (usar `App\Support\FilamentValidationRules`).
- Ampliar `docs/info/printers.md` al estándar del resto de módulos: campos con tipos, relaciones,
  estados de la cola, configuración Filament.
- Comentar modelos y migraciones en español, como el resto del proyecto.
- **No** eliminar nada.

## Qué se hace después (esta fase)

### Endpoints propuestos

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| `GET` | `/api/v2/printers` | `auth:sanctum` | Impresoras del usuario |
| `GET` | `/api/v2/printers/{printer}` | `auth:sanctum` | Detalle de una impresora |
| `GET` | `/api/v2/printers/{printer}/queue` | `ability:printers:read` | Trabajos pendientes de esa impresora |
| `POST` | `/api/v2/printers/{printer}/queue` | `auth:sanctum` | Encolar un trabajo |
| `PATCH` | `/api/v2/printers/queue/{job}` | `ability:printers:write` | Marcar estado: impreso / error / reintento |
| `POST` | `/api/v2/printers/{printer}/status` | `ability:printers:write` | Reportar estado de la impresora (papel, temperatura, online) |

### Requisitos

- Añadir `printers:read` y `printers:write` a `DeviceTokenService::MODULE_ABILITIES`.
- Crear `PrinterPolicy` con filtrado por propietario.
- Aplicar la regla `OwnedHardwareDevice` si la impresora se asocia a un `HardwareDevice`
  (decidir si una impresora ES un hardware device o una entidad aparte).
- Rutas en `routes/printers/v2.php`, registradas desde `routes/api/v2.php`.
- Tests en `tests/Feature/Api/V2/PrintersTest.php`.
- Documentar en `docs/info/printers.md` y regenerar Scribe.

### Cuestiones a resolver antes de implementar

- ¿Una impresora es un `HardwareDevice` más, o una entidad independiente? Afecta al modelo de
  tokens: si es un `HardwareDevice`, hereda el ligado `device:{id}` que ya funciona.
- ¿Cómo se representa el contenido a imprimir? (texto plano, ESC/POS, plantilla, adjunto)
- ¿Reintentos automáticos si la impresora está offline? ¿TTL de los trabajos en cola?
- Si se ofrece como servicio a terceros: cuotas por usuario y aislamiento entre clientes.
