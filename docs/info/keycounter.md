# Módulo: Contador de Pulsaciones (KeyCounter)

Módulo IoT para registrar pulsaciones de teclado y clicks/movimientos de ratón agrupados por sesiones de trabajo, con estadísticas por usuario y dispositivo hardware.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/KeyCounter/BaseKeyCounter.php` | — | Modelo base abstracto con campos comunes |
| `app/Models/KeyCounter/Keyboard.php` | `keycounter_keyboard` | Registros de pulsaciones de teclado |
| `app/Models/KeyCounter/Mouse.php` | `keycounter_mouse` | Registros de clicks y movimientos de ratón |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/KeyCounter/V2/KeyboardController.php` | API V2 | Store registro de teclado |
| `app/Http/Controllers/Api/KeyCounter/V2/MouseController.php` | API V2 | Store registro de ratón |
| `app/Http/Controllers/Api/KeyCounter/V1/KeyboardController.php` | API V1 | Controlador V1 legacy teclado |
| `app/Http/Controllers/Api/KeyCounter/V1/MouseController.php` | API V1 | Controlador V1 legacy ratón |
| `app/Http/Controllers/Api/KeyCounter/V1/KeyCounterController.php` | API V1 | Controlador V1 legacy general |
| `app/Http/Controllers/KeyCounter/KeyCounterController.php` | Web | Frontend público |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/KeyCounter/KeyCounterService.php` | Lógica: store teclado/ratón, estadísticas |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/KeyCounter/KeyboardResource.php` | Resource JSON teclado |
| `app/Http/Resources/V2/KeyCounter/MouseResource.php` | Resource JSON ratón |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/KeyCounter/V2/StoreKeyboardRequest.php` | Validación store teclado |
| `app/Http/Requests/Api/KeyCounter/V2/StoreMouseRequest.php` | Validación store ratón |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/KeyCounterKeyboardPolicy.php` | Política de autorización teclado |
| `app/Policies/KeyCounterMousePolicy.php` | Política de autorización ratón |
| `app/Console/Commands/KeyCounterGenerateDuration.php` | Comando para recalcular duraciones |
| `app/Console/Commands/KeyCounterRemoveDuplicate.php` | Comando para eliminar duplicados |

## Campos del modelo Keyboard

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_device_id` | int | FK → `hardware_devices.id` — dispositivo |
| `start_at` | datetime | Inicio de la sesión (`Y-m-d H:i:s`) |
| `end_at` | datetime | Fin de la sesión (`Y-m-d H:i:s`) |
| `duration` | int | Duración en segundos (calculado) |
| `pulsations` | int | Total de pulsaciones normales |
| `pulsations_special_keys` | int | Pulsaciones de teclas especiales |
| `pulsation_average` | decimal | Media de pulsaciones por segundo |
| `score` | int | Puntuación calculada |
| `weekday` | int | Día de la semana (0=Lun - 6=Dom) |

## Campos del modelo Mouse

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_device_id` | int | FK → `hardware_devices.id` — dispositivo |
| `start_at` | datetime | Inicio de la sesión |
| `end_at` | datetime | Fin de la sesión |
| `duration` | int | Duración en segundos (calculado) |
| `clicks_left` | int | Clicks botón izquierdo |
| `clicks_right` | int | Clicks botón derecho |
| `clicks_middle` | int | Clicks botón central |
| `total_clicks` | int | Total de todos los clicks |
| `clicks_average` | int | Media de clicks por segundo |
| `weekday` | int | Día de la semana (0-6) |

## Relaciones

- `Keyboard/Mouse` → `BelongsTo` → `User` (vía `user_id`)
- `Keyboard/Mouse` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)

## PrepareForValidation

Ambos FormRequests calculan automáticamente en `prepareForValidation()`:
- `user_id` → se asigna desde `auth()->id()`
- `duration` → se calcula como `start_at.diffInSeconds(end_at)`

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| POST | `/api/v2/keycounter/keyboard` | Sí | api-store | Store registro teclado |
| POST | `/api/v2/keycounter/mouse` | Sí | api-store | Store registro ratón |

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/keycounter` | Dashboard de estadísticas de pulsaciones |
