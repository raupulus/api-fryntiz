# API del módulo de Impresoras

> **Resuelto e implementado (2026-09-17):** implementado en su totalidad en la API V2 (`/api/v2/printers`), WebSockets en Laravel Reverb (`printer.{id}`) y panel Filament Admin. Documentación técnica viva en [`docs/info/printers.md`](../../info/printers.md) y especificación del contrato en [`docs/info/api/v2/printers.md`](../../info/api/v2/printers.md).

---

## 1. Contexto y Objetivos

El módulo de impresoras centraliza la gestión de periféricos de impresión física (con foco prioritario en **impresoras térmicas de tickets y etiquetas ESC/POS**, además de impresoras 2D estándar y 3D) para exponerlas como servicio automatizado a través de la API REST V2 y administrarlas desde la intranet Filament.

### Casos de Uso Clave
1. **Envío de tickets / avisos**: aplicaciones internas, microservicios o webhooks que mandan a imprimir recibos, notas de recordatorio, resúmenes diarios o tickets de eventos.
2. **Agente de impresión local (Host IoT)**: un dispositivo físico (habitualmente una Raspberry Pi o mini-PC conectado por USB o serie a la impresora térmica) que sondea la cola de impresión mediante un token Sanctum acotado, extrae los trabajos pendientes, los envía al dispositivo físico y reporta el resultado de la impresión.
3. **Control y supervisión en Filament**: visualización en tiempo real del estado de las impresoras (online, sin papel, lista, ocupada) y de los trabajos en cola con posibilidad de cancelar o reintentar.

---

## 2. Diagnóstico del Estado Actual

| Capa / Componente | Estado | Detalle |
|---|---|---|
| Modelos (`Printer`, `PrinterStack`) | ✅ | Existen bajo `app/Models/`, extienden `BaseModel`. |
| `HardwareDevice` | ✅ | Dispositivo anfitrión con `user_id`, identidad física, telemetría y tokens Sanctum (`device:{id}`). |
| Tabla `printer_available_types` | ⚠️ | **A eliminar**: 4 filas estáticas de 2022 que se sustituyen por el Backed Enum `PrinterTypeEnum`. |
| Relación de propiedad (`user_id`) | ⚠️ | **A limpiar**: `printers.user_id` es redundante y se elimina; la propiedad se hereda de `hardware_devices.user_id`. |
| Factories | ✅ | `PrinterFactory`, `PrinterStackFactory`. |
| Panel Filament Admin | ✅ | `PrinterResource` y `PrinterStackRelationManager` bajo el grupo "Hardware". |
| `PrinterPolicy` | ✅ | `app/Policies/PrinterPolicy.php` sobre `OwnedResourcePolicy`, vincula la cola al dueño del `HardwareDevice`. |
| Documentación actual | ✅ | [`docs/info/printers.md`](../info/printers.md). |
| **Esquema de cola de impresión** | ⚠️ | `printer_stack` sólo tiene `note` y `content`: carece de estados (`status`), formato, reintentos, odómetro y fecha de fin. |
| **Esquema de estado de impresora** | ⚠️ | `printers` carece de estado operativo en vivo (`status`, `is_active`, `supported_formats`, `max_payload_kb`, `total_prints_count`, `last_seen_at`). |
| **API REST V2** | ❌ | Sin controladores, Resources, FormRequests ni rutas `/api/v2/printers/...`. |
| **Abilities Sanctum** | ❌ | Faltan `printers:read` y `printers:write` en `TokenAbilities` y `DeviceTokenService`. |
| **Tests automatizados** | ❌ | Sin tests de API ni de servicio de cola. |

---

## 3. Modelo de Datos y Esquema

### 3.1 Backed Enums PHP 8.4

- `App\Enums\PrinterTypeEnum`:
  - `Thermal = 'thermal'` (Impresora térmica de tickets o etiquetas).
  - `Ticket = 'ticket'` (Impresora matricial / impacto de tickets).
  - `TwoD = '2d'` (Impresora 2D estándar de tinta o láser).
  - `ThreeD = '3d'` (Impresora 3D).

- `App\Enums\PrinterStatusEnum`:
  - `Ready = 'ready'` (Lista para recibir trabajos).
  - `Busy = 'busy'` (Imprimiendo actualmente).
  - `OutOfPaper = 'out_of_paper'` (Sin papel detectado por sensor CTS).
  - `CoverOpen = 'cover_open'` (Tapa abierta).
  - `Offline = 'offline'` (Sin conexión del agente/microcontrolador).
  - `Error = 'error'` (Error de comunicación / cabezal térmico).

- `App\Enums\PrintJobStatusEnum`:
  - `Pending = 'pending'` (Encolado, esperando que el agente lo tome).
  - `Processing = 'processing'` (Descargado por el agente, imprimiéndose).
  - `Completed = 'completed'` (Impresión confirmada con éxito).
  - `Failed = 'failed'` (Error durante la impresión física).
  - `Cancelled = 'cancelled'` (Cancelado por el usuario o administrador).

- `App\Enums\PrintJobFormatEnum`:
  - `Text = 'text'` (Texto plano UTF-8 con saltos de línea estándar).
  - `Escpos = 'escpos'` (Comandos binarios ESC/POS codificados en Base64).
  - `Markdown = 'markdown'` (Texto formateado que el agente procesa antes de imprimir).
  - `Json = 'json'` (Estructura de clave/valor para pantallas o plantillas).
  - `Gcode = 'gcode'` (Instrucciones para impresoras 3D).

---

### 3.2 Migración para `printers` (Vinculada a `HardwareDevice`)

La tabla `printers` deja de duplicar `user_id` y `printer_type_id`: su identidad y pertenencia dependen directamente de `hardware_devices.id`:

```php
Schema::table('printers', function (Blueprint $table) {
    // 1. Limpieza de columnas obsoletas / redundantes:
    // $table->dropForeign(['user_id']);
    // $table->dropColumn('user_id');
    // $table->dropForeign(['printer_type_id']);
    // $table->dropColumn('printer_type_id');

    // 2. Vinculación obligatoria con HardwareDevice:
    $table->foreignId('hardware_device_id')
        ->change()
        ->constrained('hardware_devices')
        ->cascadeOnDelete()
        ->comment('Dispositivo hardware físico al que pertenece esta impresora');

    $table->string('printer_type', 32)
        ->default(PrinterTypeEnum::Thermal->value)
        ->comment('Tipo de tecnología de la impresora (thermal, ticket, 2d, 3d)');

    $table->boolean('is_active')
        ->default(true)
        ->comment('Indica si la impresora está habilitada para recibir nuevos trabajos');

    $table->string('status', 32)
        ->default(PrinterStatusEnum::Offline->value)
        ->comment('Último estado reportado por el microcontrolador');

    $table->jsonb('supported_formats')
        ->default(json_encode([PrintJobFormatEnum::Text->value, PrintJobFormatEnum::Escpos->value]))
        ->comment('Formatos de payload aceptados por esta impresora (text, escpos, json, markdown, gcode)');

    $table->string('default_format', 32)
        ->default(PrintJobFormatEnum::Text->value)
        ->comment('Formato predeterminado si el emisor no especifica uno al encolar');

    $table->unsignedInteger('max_payload_kb')
        ->default(64)
        ->comment('Tamaño máximo de payload en KB para proteger microcontroladores de desbordamiento de RAM');

    $table->unsignedInteger('total_prints_count')
        ->default(0)
        ->comment('Contador global acumulado de impresiones exitosas confirmadas en esta impresora (odómetro)');

    $table->timestamp('last_seen_at')
        ->nullable()
        ->index()
        ->comment('Último momento de contacto o sondeo recibido del microcontrolador');
});
```

---

### 3.3 Migración para `printer_stack` (Cola de Impresión)

Cuelga directamente de `printer_id` (`printers.id`):

```php
Schema::table('printer_stack', function (Blueprint $table) {
    $table->string('status', 32)
        ->default(PrintJobStatusEnum::Pending->value)
        ->index()
        ->comment('Estado del trabajo en cola (pending, processing, completed, failed, cancelled)');

    $table->string('format', 32)
        ->default(PrintJobFormatEnum::Text->value)
        ->comment('Formato del contenido (text, escpos, markdown, json, gcode)');

    $table->integer('priority')
        ->default(0)
        ->comment('Prioridad de ejecución: mayor número se imprime antes');

    $table->unsignedSmallInteger('attempts')
        ->default(0)
        ->comment('Número de intentos de ejecución realizados');

    $table->unsignedInteger('print_count')
        ->default(0)
        ->comment('Número de veces que el microcontrolador ha confirmado la impresión exitosa de este trabajo');

    $table->boolean('is_favorite')
        ->default(false)
        ->index()
        ->comment('Marca el trabajo como plantilla / favorito para reimpresión recurrente');

    $table->text('error_message')
        ->nullable()
        ->comment('Mensaje o traza de error en caso de fallo');

    $table->timestamp('printed_at')
        ->nullable()
        ->comment('Fecha y hora en que se confirmó la última impresión física con éxito');

    // Índice compuesto para consumo atómico ultrarrápido por parte del microcontrolador:
    $table->index(['printer_id', 'status', 'priority', 'created_at'], 'printer_stack_queue_idx');
});
```

---

## 4. Contrato Exhaustivo de la API REST V2

Prefijo base: `/api/v2/printers`.
Todas las respuestas cumplen el envelope estándar de la plataforma implementado mediante `ApiResponseTrait`:

```json
{
  "success": true,
  "message": "Mensaje descriptivo en español",
  "data": { ... }
}
```

Para errores (4xx / 5xx):
```json
{
  "success": false,
  "message": "Error descriptivo",
  "errors": {
    "campo": ["Detalle de la validación"]
  }
}
```

---

### 4.1 Endpoints del Agente IoT / Microcontrolador (ESP32, Pico W, Host)

Estos endpoints son consumidos por el firmware del microcontrolador conectado físicamente a la impresora térmica o de tickets.

#### 1. Reclamar Siguiente Trabajo Atómico
* **Método y Ruta**: `POST /api/v2/printers/{printer}/jobs/next`
* **Autenticación**: `auth:sanctum` con ability `printers:write` y scope `device:{hardware_device_id}`.
* **Headers**:
  * `Authorization: Bearer <token_iot>`
  * `Accept: application/json`
  * `Content-Type: application/json`
* **Cuerpo de la Petición (Request Body)**: Opcional. Permite adjuntar telemetría hardware en la misma llamada (ping periódico):
  ```json
  {
    "hardware_device_info": {
      "temp": 42.5,
      "voltage": 5.02,
      "uptime": 3600,
      "ip_local": "192.168.1.150",
      "ram": 68.4,
      "software_version": "v1.0.4"
    }
  }
  ```
* **Comportamiento Interno**:
  * Ejecuta una transacción atómica con `lockForUpdate`.
  * Busca el siguiente trabajo con `status = 'pending'`, ordenado por `priority DESC` y `created_at ASC`.
  * Si existe: lo marca como `processing`, incrementa `attempts` en 1 y emite `job.updated` por WebSocket.
  * Si viene `hardware_device_info`: invoca `storeDeviceInfoIfPresent()` vía `HandlesHardwareDeviceInfo` para refrescar el dispositivo.
* **Respuesta Exitosa (200 OK - Trabajo disponible)**:
  ```json
  {
    "success": true,
    "message": "Trabajo de impresión reclamado exitosamente",
    "data": {
      "id": 142,
      "printer_id": 5,
      "status": "processing",
      "format": "escpos",
      "content": "G1kGExQVFR...==",
      "priority": 10,
      "attempts": 1,
      "print_count": 0,
      "is_favorite": false,
      "note": "Comanda mesa 4",
      "created_at": "2026-09-17T12:00:00.000000Z"
    }
  }
  ```
* **Respuesta Exitosa (200 OK / 204 - Cola vacía)**:
  ```json
  {
    "success": true,
    "message": "No hay trabajos pendientes en la cola",
    "data": null
  }
  ```

---

#### 2. Notificar Resultado de Impresión
* **Método y Ruta**: `PATCH /api/v2/printers/jobs/{job}/status`
* **Autenticación**: `auth:sanctum` con ability `printers:write`.
* **Headers**:
  * `Authorization: Bearer <token_iot>`
  * `Accept: application/json`
  * `Content-Type: application/json`
* **Cuerpo de la Petición (Request Body)**:
  ```json
  {
    "status": "completed",
    "error_message": null,
    "hardware_device_info": {
      "temp": 43.1,
      "uptime": 3615
    }
  }
  ```
* **Parámetros**:
  * `status` (string, requerido): `completed`, `failed` o `out_of_paper`.
  * `error_message` (string, opcional): obligatorio si `status === 'failed'`.
  * `hardware_device_info` (objeto, opcional): telemetría hardware.
* **Comportamiento Interno**:
  * Si `status === 'completed'`: incrementa `printer_stack.print_count` (+1), incrementa `printers.total_prints_count` (+1) y fija `printed_at = now()`.
  * Si `status === 'failed'`: registra `error_message` y mantiene el trabajo en `failed`.
  * Si `status === 'out_of_paper'`: devuelve el trabajo a `pending` para que no se pierda, y actualiza el estado de la impresora a `out_of_paper`.
  * Emite el evento `job.updated` por WebSocket.
* **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Estado del trabajo de impresión actualizado exitosamente",
    "data": {
      "id": 142,
      "status": "completed",
      "print_count": 1,
      "printed_at": "2026-09-17T12:00:15.000000Z"
    }
  }
  ```

---

#### 3. Heartbeat de Impresora y Ping de Hardware
* **Método y Ruta**: `POST /api/v2/printers/{printer}/heartbeat`
* **Autenticación**: `auth:sanctum` con ability `printers:write`.
* **Cuerpo de la Petición (Request Body)**:
  ```json
  {
    "status": "ready",
    "hardware_device_info": {
      "temp": 41.2,
      "voltage": 5.01,
      "battery_level": 100,
      "ip_local": "192.168.1.150",
      "uptime": 7200,
      "extra": {
        "cts_pin": 0,
        "head_temp_c": 32,
        "paper_status": "ok"
      }
    }
  }
  ```
* **Parámetros**:
  * `status` (string, requerido): `ready`, `busy`, `out_of_paper`, `cover_open`, `offline`, `error`.
  * `hardware_device_info` (objeto, opcional): bloque estándar de estado de hardware.
* **Comportamiento Interno**:
  * Fija `printers.status = $status` y `printers.last_seen_at = now()`.
  * Actualiza la tabla `hardware_devices` mediante `storeDeviceInfoIfPresent()`.
* **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Heartbeat registrado exitosamente",
    "data": {
      "printer_id": 5,
      "status": "ready",
      "last_seen_at": "2026-09-17T12:05:00.000000Z"
    }
  }
  ```

---

#### 4. Descarga de Trabajo Concreto por ID
* **Método y Ruta**: `GET /api/v2/printers/jobs/{job}`
* **Autenticación**: `auth:sanctum` (`printers:write` o `printers:read`).
* **Uso**: Permite al microcontrolador recuperar el contenido de un trabajo si tiene memoria para plantillas favoritas o si el usuario pulsa un botón físico de reimpresión en el dispositivo.

---

### 4.2 Endpoints de Aplicaciones Emisoras / Intranet (Creación y Gestión)

#### 1. Encolar Nuevo Trabajo de Impresión
* **Método y Ruta**: `POST /api/v2/printers/{printer}/jobs`
* **Autenticación**: `auth:sanctum` (`PrinterPolicy@update`).
* **Headers**: `Authorization: Bearer <token>`, `Content-Type: application/json`
* **Cuerpo de la Petición (Request Body)**:
  ```json
  {
    "content": "Texto a imprimir o Base64 ESC/POS",
    "format": "escpos",
    "note": "Ticket de caja #4921",
    "priority": 5,
    "is_favorite": false
  }
  ```
* **Validación Estricta**:
  * `content`: `required|string`. La longitud en bytes no puede superar `printer.max_payload_kb * 1024`.
  * `format`: `nullable|string`. Debe pertenecer a `printer.supported_formats`. Si no se envía, se asigna automáticamente `printer.default_format`. Si no es compatible, devuelve `422`.
  * `note`: `nullable|string|max:255`.
  * `priority`: `nullable|integer|between:-100,100`.
  * `is_favorite`: `nullable|boolean`.
* **Respuesta Exitosa (201 Created)**:
  ```json
  {
    "success": true,
    "message": "Trabajo de impresión encolado exitosamente",
    "data": {
      "id": 143,
      "printer_id": 5,
      "status": "pending",
      "format": "escpos",
      "priority": 5,
      "is_favorite": false,
      "created_at": "2026-09-17T12:10:00.000000Z"
    }
  }
  ```
* **Efecto Colateral**: Emite inmediatamente el evento `job.created` en el canal privado de WebSockets `private-printer.5`.

---

#### 2. Reimprimir un Trabajo Existente
* **Método y Ruta**: `POST /api/v2/printers/jobs/{job}/reprint`
* **Autenticación**: `auth:sanctum`.
* **Cuerpo (opcional)**: `{ "priority": 10 }`
* **Comportamiento**: Encola un nuevo registro en `printer_stack` duplicando `content`, `format` y `note` del trabajo original, listo para ejecutarse de inmediato. Emite `job.created`.

---

#### 3. Alternar Favorito / Plantilla
* **Método y Ruta**: `PATCH /api/v2/printers/jobs/{job}/favorite`
* **Cuerpo (opcional)**: `{ "is_favorite": true }` (si se omite, alterna el valor actual).

---

#### 4. Listar Favoritos de una Impresora
* **Método y Ruta**: `GET /api/v2/printers/{printer}/favorites`
* **Respuesta**: Colección paginada de trabajos marcados con `is_favorite = true`.

---

#### 5. Listado e Historial de Trabajos
* **Listado de Impresoras**: `GET /api/v2/printers` (filtros: `status`, `printer_type`, `is_active`).
* **Ficha de Impresora**: `GET /api/v2/printers/{printer}` (incluye `printer_type`, `supported_formats`, `default_format`, `max_payload_kb`, `total_prints_count` y contador de pendientes).
* **Historial de Trabajos**: `GET /api/v2/printers/{printer}/jobs` (filtros: `status`, `is_favorite`, fechas).
* **Cancelar Trabajo**: `DELETE /api/v2/printers/jobs/{job}` (sólo permitido si `status === 'pending'`).

---

## 5. El Bloque Común de Telemetría Hardware (`hardware_device_info`)

Cumpliendo con el estándar global de la plataforma para todos los módulos IoT (estaciones meteorológicas, energía solar, plantas inteligentes, KeyCounter y AirFlight), cualquier petición enviada por un dispositivo puede incluir el objeto `hardware_device_info`.

### 5.1 Reglas de Validación (`DeviceStatusPayload`)

| Clave | Tipo / Restricción | Descripción |
|---|---|---|
| `temp` | `nullable, numeric` | Temperatura de la placa o CPU en ºC |
| `voltage` | `nullable, numeric` | Voltaje de alimentación del microcontrolador (V) |
| `battery_level` | `nullable, integer, 0-100` | Nivel de batería en porcentaje |
| `battery_voltage` | `nullable, numeric` | Tensión de la celda de batería (V) |
| `cpu` | `nullable, numeric, 0-100` | Carga de CPU estimada (%) |
| `disk` | `nullable, numeric, 0-100` | Uso del sistema de archivos flash (%) |
| `ram` | `nullable, numeric, 0-100` | Porcentaje de memoria RAM ocupada |
| `uptime` | `nullable, integer, min:0` | Segundos transcurridos desde el último reinicio |
| `ip_local` | `nullable, ip` | Dirección IP local asignada por DHCP (ej. `192.168.1.150`) |
| `software_version` | `nullable, string, max:50` | Versión del firmware del microcontrolador (ej. `"v1.2.0"`) |
| `extra` | `nullable, array, max:30` | Pares clave-valor adicionales (longitud máxima por valor: 255 caracteres) |

> 🔒 **Nota de Seguridad**: La clave `ip_public` se resuelve y sobreescribe de forma obligatoria en el servidor mediante `ClientIp::public($request)` a través del trait `HandlesHardwareDeviceInfo`. El microcontrolador no necesita reportarla.

---

## 6. Contrato Exhaustivo de WebSockets (Laravel Reverb)

Para que el microcontrolador o el panel web reciban avisos instantáneos con latencia cero:

### 6.1 Parámetros de Conexión WSS
* **Protocolo**: Pusher Protocol v7 (compatible con librerías Pusher y Reverb).
* **URL de Conexión**: `wss://{REVERB_HOST}:{REVERB_PORT}/app/{REVERB_APP_KEY}` (ej. `wss://ws.raupulus.dev:443/app/api_key_xxx`).
* **Path**: `/app/{key}`
* **Parámetros Query**: `?protocol=7&client=js&version=8.4.0&flash=false`

### 6.2 Handshake de Autenticación de Canal Privado (`POST /broadcasting/auth`)
Para unirse al canal `private-printer.{id}`:
1. El microcontrolador abre el socket y recibe el evento inicial `pusher:connection_established` con un `socket_id` (ej. `"49102.39410"`).
2. Realiza una petición HTTPS POST al backend Laravel:
   * **URL**: `https://api.raupulus.dev/broadcasting/auth`
   * **Headers**:
     * `Authorization: Bearer <sanctum_device_token>`
     * `Content-Type: application/x-www-form-urlencoded` (o `application/json`)
   * **Body**:
     `socket_id=49102.39410&channel_name=private-printer.5`
   * **Autorización en `routes/channels.php`**:
     ```php
     Broadcast::channel('printer.{printerId}', function (User $user, int $printerId) {
         $printer = Printer::with('hardwareDevice')->find($printerId);
         if (! $printer || ! $printer->hardwareDevice) {
             return false;
         }

         if ($user->isSuperAdmin() || $user->isAdmin()) {
             return true;
         }

         // La propiedad se verifica a través del hardwareDevice asociado:
         if ((int) $user->id !== (int) $printer->hardwareDevice->user_id) {
             return false;
         }

         $token = $user->currentAccessToken();
         if ($token && ! in_array('*', $token->abilities, true)) {
             return $token->can('printers:write')
                 && $token->can("device:{$printer->hardware_device_id}");
         }

         return true;
     }, ['guards' => ['sanctum', 'web']]);
     ```
   * **Respuesta 200 OK**:
     ```json
     {
       "auth": "REVERB_APP_KEY:a1b2c3d4e5f6...firma_hmac..."
     }
     ```
   * Si el token no tiene permiso sobre esa impresora, responde `403 Forbidden`.

3. El microcontrolador envía por el WebSocket el frame de suscripción:
   ```json
   {
     "event": "pusher:subscribe",
     "data": {
       "channel": "private-printer.5",
       "auth": "REVERB_APP_KEY:a1b2c3d4e5f6...firma_hmac..."
     }
   }
   ```

### 6.3 Eventos Emitidos por el Servidor

#### Evento: `job.created`
* **Canal**: `private-printer.{printer_id}`
* **Nombre de Evento Pusher**: `job.created` (alias de `App\Events\Printers\PrintJobCreated`).
* **Cuándo se emite**: Cuando un usuario o app encola un nuevo trabajo (`POST /printers/{printer}/jobs` o `/reprint`).
* **Carga Útil (Payload)**:
  ```json
  {
    "printer_id": 5,
    "job_id": 142,
    "format": "escpos",
    "priority": 10,
    "created_at": "2026-09-17T12:00:00.000000Z"
  }
  ```
  *(Nota: El contenido del ticket NUNCA se envía por aquí; solo el aviso de trabajo pendiente).*

#### Evento: `job.updated`
* **Canal**: `private-printer.{printer_id}`
* **Nombre de Evento Pusher**: `job.updated` (alias de `App\Events\Printers\PrintJobStatusUpdated`).
* **Cuándo se emite**: Cuando el estado cambia a `processing`, `completed`, `failed` o `cancelled`.
* **Carga Útil (Payload)**:
  ```json
  {
    "printer_id": 5,
    "job_id": 142,
    "status": "completed",
    "print_count": 1,
    "updated_at": "2026-09-17T12:00:15.000000Z"
  }
  ```

---

## 7. Guía de Implementación del Firmware (ESP32 / Raspberry Pi Pico W)

El siguiente flujo resume la máquina de estados ideal para que cualquier desarrollador o IA implemente el cliente embebido en C++ / Arduino / MicroPython:

```c
// === SETUP INICIAL ===
void setup() {
    init_uart_printer(9600); // Conexión a EM5820 (TX/RX/CTS)
    pinMode(CTS_PIN, INPUT_PULLDOWN); // Detección de falta de papel
    connect_wifi();

    // 1. Enviar Heartbeat inicial con estado hardware
    send_heartbeat("ready");

    // 2. Conectar a WebSockets (Laravel Reverb)
    connect_reverb_websocket();

    // 3. Drenaje inicial de cola (por si había tareas acumuladas al estar apagada)
    drain_queue();
}

// === BUCLE PRINCIPAL ===
void loop() {
    websocket_client.poll();

    // Si ha saltado aviso WebSocket o toca sondeo de seguridad:
    if (has_pending_work) {
        drain_queue();
        has_pending_work = false;
    }

    // Cada 60 segundos: enviar heartbeat con ping de hardware
    if (millis() - last_heartbeat > 60000) {
        send_heartbeat(digitalRead(CTS_PIN) ? "out_of_paper" : "ready");
        last_heartbeat = millis();
    }
}

// === VACIADO DE COLA (ESTRICTAMENTE DE 1 EN 1) ===
void drain_queue() {
    while (true) {
        // Verificar si la impresora física tiene papel antes de pedir trabajo
        if (digitalRead(CTS_PIN) == HIGH) {
            send_heartbeat("out_of_paper");
            break; // No pedir trabajos hasta que el usuario reponga papel
        }

        // POST /api/v2/printers/{id}/jobs/next
        Job job = api_claim_next_job();
        if (!job.has_job) {
            break; // Cola vacía
        }

        // Imprimir físicamente por el puerto serie
        bool printed_ok = print_to_thermal(job.content, job.format);

        if (printed_ok) {
            // PATCH /api/v2/printers/jobs/{id}/status -> completed
            // (Sube print_count en BD y total_prints_count en impresora)
            api_report_status(job.id, "completed", NULL);
        } else {
            // Si falló por papel a mitad:
            const char* reason = digitalRead(CTS_PIN) ? "out_of_paper" : "uart_error";
            api_report_status(job.id, "failed", reason);
        }
    }
}
```

---

## 8. Autenticación y Sistema de Tokens (Sanctum)

Siguiendo la arquitectura de `TokenAbilities` y `DeviceTokenService`:

1. **Catálogo de Abilities**:
   - `TokenAbilities::PRINTERS_READ = 'printers:read'` -> 'Impresoras lectura' (consulta de cola y estados para dashboards).
   - `TokenAbilities::PRINTERS_WRITE = 'printers:write'` -> 'Impresoras escritura' (para el agente/microcontrolador que saca trabajos y reporta estado).
   - Registrar ambas en `TokenAbilities::MODULE_ABILITIES` y en el array descriptivo de aperturas.
2. **Ligado a Dispositivo (`device:{id}`)**:
   - Toda impresora física conectada por TTL, USB o red a un ESP32, Raspberry Pi Pico o SBC se vincula mediante `printers.hardware_device_id`.
   - Cuando el token del agente porta `device:{id}`, la autorización valida que `printer.hardware_device_id === id`, impidiendo que un microcontrolador reclame trabajos de impresoras de otros dispositivos.

---

## 9. Panel de Administración (Filament Admin)

Mejoras a incorporar en `PrinterResource` y `PrinterStackRelationManager`:

1. **Configuración de Formatos de la Impresora**:
   - Selector múltiple de `supported_formats` (`text`, `escpos`, `markdown`, `json`, `gcode`).
   - Selector de `default_format`.
   - Campo numérico de `max_payload_kb` para ajustar el límite según la memoria del dispositivo.
2. **Indicador de Salud de la Impresora**:
   - Badge en cabecera y listado con `status`: verde (`Ready`), azul (`Busy`), amarillo (`OutOfPaper`), rojo (`Error`/`Offline`).
   - Diferencia de tiempo relativa desde el último `last_seen_at`.
3. **Gestión de la Cola de Impresión**:
   - Tabla de trabajos con badges de estado (`pending`: gris, `processing`: azul pulsante, `completed`: verde, `failed`: rojo, `cancelled`: marrón).
   - Acciones de fila:
     - **Reintentar**: devuelve un trabajo `failed` a estado `pending`.
     - **Cancelar**: pasa un trabajo `pending` a `cancelled`.
     - **Ver contenido**: modal con visualización del texto o vista previa del ticket.
   - Filtros en tabla por formato (`format`), estado (`status`) y fecha de creación.

---

## 10. Cuestiones Arquitectónicas Resueltas

1. **Gestión de Memoria en Microcontroladores (Poca RAM ante Ráfagas de Tareas)**:
   - Si se encolan varias tareas seguidas mientras el microcontrolador está ocupado o apagado, este **NO almacena la lista de trabajos en su memoria RAM**.
   - Al recibir cualquier aviso `job.created` por WebSocket, el firmware simplemente activa una bandera interna `has_work = true`.
   - El bucle de impresión del microcontrolador procesa **estrictamente de 1 en 1**:
     1. Llama a `POST /api/v2/printers/{id}/jobs/next`.
     2. Si devuelve un trabajo, lo imprime por UART (TTL).
     3. Notifica `PATCH /jobs/{id}/status` con `status: completed`.
     4. Vuelve al paso 1 de inmediato hasta que la API responda que no hay más trabajos pendientes (`null`).
   - La cola completa reside de forma segura en PostgreSQL; la RAM del ESP32/Pico sólo retiene el ticket activo en curso. Es imposible que se sature la memoria o se pierdan trabajos.

2. **Arranque en Frío / Encendido en la Oficina (Cold Start)**:
   - La impresora puede estar apagada y encenderse horas o días después. En ese periodo, no habrá recibido ningún evento WebSocket.
   - **Protocolo de inicio del microcontrolador**:
     1. Conexión Wi-Fi y comprobación hardware de pines (CTS).
     2. Reporte de presencia: `POST /heartbeat` (`status: ready`).
     3. Conexión WebSocket a `private-printer.{id}` en Reverb.
     4. **Drenaje inicial de cola (*Queue Draining*)**: invoca inmediatamente `POST /jobs/next` en bucle para despachar todo lo que quedó pendiente mientras estuvo apagada. Una vez vacía la cola, pasa a reposo a la espera de nuevos eventos.

3. **Gestión de Falta de Papel (Pin CTS) y Pausa de Cola**:
   - Cuando la EM5820 se queda sin papel (o se abre la tapa), el pin CTS conmuta a nivel alto (`HIGH`), o el comando de estado ESC/POS reporta error.
   - Si ocurre durante o antes de un trabajo:
     - El microcontrolador reporta `POST /heartbeat` con `status: out_of_paper`.
     - Si había tomado un trabajo que no pudo imprimir, reporta `status: out_of_paper` (o error de papel), liberando el trabajo de vuelta a `pending` para no destruirlo.
     - La cola se detiene. Cuando el usuario repone el rollo de papel en la oficina, CTS vuelve a `LOW`.
     - El microcontrolador emite `heartbeat` con `status: ready` y reanuda el drenaje de los trabajos pendientes automáticamente.

4. **Reimpresión y Trabajos Favoritos (`is_favorite`)**:
   - Para tickets que se imprimen habitualmente (plantillas de prueba, vales, etiquetas de inventario recurrentes), el campo `is_favorite` permite marcarlos desde el panel Filament o vía API (`PATCH /jobs/{job}/favorite`).
   - El endpoint `POST /api/v2/printers/jobs/{job}/reprint` permite reencolar una copia idéntica del contenido para ejecutarla al momento.
   - Asimismo, el microcontrolador puede descargar directamente un trabajo específico mediante `GET /api/v2/printers/jobs/{job}` (útil si el dispositivo físico cuenta con pantalla o botones para seleccionar plantillas favoritas).

5. **Contador de Impresiones Confirmadas (Odómetro de Periférico)**:
   - **Por trabajo (`printer_stack.print_count`)**: Se inicializa en 0. **Únicamente se incrementa (+1) cuando el microcontrolador reporta con éxito `status: completed`**. Si el trabajo falla, se cancela o la impresora se queda sin papel a mitad, el contador NO sube. Si un trabajo favorito se imprime 10 veces, reflejará exactamente `10`.
   - **Por impresora (`printers.total_prints_count`)**: Acumulador global que suma cada confirmación exitosa de impresión física en ese hardware. Funciona como un odómetro para controlar el desgaste del cabezal térmico y el consumo de metros de papel.

6. **Tolerancia a Cortes de Red (Reconexión Resiliente)**:
   - Si el microcontrolador termina de imprimir físicamente el ticket por el puerto serie pero la conexión Wi-Fi se interrumpe justo antes de confirmar el estado por HTTP:
   - El firmware retiene el `last_completed_job_id` en una variable de memoria. En cuanto la red Wi-Fi se reestablece, envía el `PATCH /jobs/{job}/status` pendiente **antes** de solicitar el siguiente trabajo, garantizando la consistencia de los contadores.

7. **Aislamiento de Formatos y Enfoque Genérico**:
   - Cada entidad `Printer` define en `supported_formats` los formatos concretos que su hardware o firmware puede procesar (ej. `['escpos', 'text']` para una térmica EM5820; `['gcode']` para una impresora 3D; o `['text', 'json', 'markdown']` para una pantalla/dispositivo de visualización).
   - **Validación Estricta en Entrada**: `EnqueuePrintJobRequest` rechaza con `422 Unprocessable Entity` cualquier trabajo cuyo formato no esté en los permitidos por esa impresora concreta.
   - Si el cliente no especifica formato, se asume automáticamente el `default_format` configurado en la impresora.

8. **Recuperación de Trabajos Bloqueados (Stale Jobs)**:
   - Un trabajo en estado `processing` durante más de 15 minutos sin confirmación se considera colgado. El comando `printers:cleanup-stale-jobs` lo reencola como `pending` si `attempts < 3`, o lo marca como `failed`.

9. **Unificación con `HardwareDevice` y Eliminación de Tablas Redundantes**:
   - `HardwareDevice` centraliza la identidad física, red, telemetría y propiedad del usuario (`user_id`), además de la emisión de tokens Sanctum con scope `device:{id}`.
   - La tabla `printers` se simplifica eliminando `user_id` (la pertenencia se resuelve siempre a través de `hardware_devices.user_id`) y eliminando la FK `printer_type_id`.
   - La tabla `printer_available_types` se retira por completo de la base de datos, sustituida por el Backed Enum `App\Enums\PrinterTypeEnum` de PHP 8.4 (`thermal`, `ticket`, `2d`, `3d`).
   - La cola `printer_stack` se vincula directamente a `printers.id` (`printer_id`).

---

## 11. Checklist de Implementación Paso a Paso

### Fase 1: Base de Datos, Enums y Modelos
- [x] Crear enum `App\Enums\PrinterTypeEnum` (thermal, ticket, 2d, 3d).
- [x] Crear enum `App\Enums\PrinterStatusEnum` (ready, busy, out_of_paper, cover_open, offline, error).
- [x] Crear enum `App\Enums\PrintJobStatusEnum` (pending, processing, completed, failed, cancelled).
- [x] Crear enum `App\Enums\PrintJobFormatEnum` (text, escpos, markdown, json, gcode).
- [x] Crear migración para eliminar tabla obsoleta `printer_available_types` y limpiar su seeder/factory.
- [x] Crear migración para `printers`: eliminar columnas redundantes `user_id` y `printer_type_id`, vincular `hardware_device_id` como clave foránea NOT NULL (`cascadeOnDelete`), y añadir `printer_type`, `is_active`, `status`, `supported_formats`, `default_format`, `max_payload_kb`, `total_prints_count`, `last_seen_at`.
- [x] Crear migración para ampliar `printer_stack` (`status`, `format`, `priority`, `attempts`, `print_count`, `is_favorite`, `error_message`, `printed_at` e índice compuesto).
- [x] Actualizar modelos `Printer` (relación `belongsTo(HardwareDevice::class)`, eliminación de `BelongsToUser` directo, casts de Enums y JSON) y `PrinterStack` (relación `belongsTo(Printer::class)`).
- [x] Actualizar factories `PrinterFactory` y `PrinterStackFactory`.

### Fase 2: Autenticación, TokenAbilities y Canales WebSockets
- [x] Añadir `PRINTERS_READ` y `PRINTERS_WRITE` a `App\Support\Auth\TokenAbilities`.
- [x] Registrar las nuevas abilities en `TokenAbilities::MODULE_ABILITIES` y en sus descripciones.
- [x] Registrar canal privado `private-printer.{printerId}` en `routes/channels.php` con autorización dual (usuario web o token IoT con scope `device:{id}`).
- [x] Revisar `PrinterPolicy` para cubrir las nuevas acciones (`print`, `requeue`, `reprint`, `favorite`, `cancel`).

### Fase 3: Capa de Negocio (Service Layer) y Eventos Broadcast
- [x] Crear evento `App\Events\Printers\PrintJobCreated` (`ShouldBroadcast`, canal `private-printer.{id}`).
- [x] Crear evento `App\Events\Printers\PrintJobStatusUpdated` (`ShouldBroadcast`, canal `private-printer.{id}`).
- [x] Crear `App\Services\Printers\PrinterService`:
  - `enqueueJob(Printer $printer, array $data, ?User $user = null): PrinterStack` (emite `PrintJobCreated`).
  - `claimNextJob(Printer $printer): ?PrinterStack` (transacción atómica con `lockForUpdate`, emite `PrintJobStatusUpdated`).
  - `reprintJob(PrinterStack $job): PrinterStack` (reencola copia y emite `PrintJobCreated`).
  - `toggleFavorite(PrinterStack $job, ?bool $isFavorite = null): PrinterStack`.
  - `updateJobStatus(PrinterStack $job, PrintJobStatusEnum $status, ?string $errorMessage = null): PrinterStack` (incrementa `print_count` y `total_prints_count` en `completed`, emite `PrintJobStatusUpdated`).
  - `recordHeartbeat(Printer $printer, PrinterStatusEnum $status, ?array $hardwareDeviceInfo = null): void`.
- [x] Crear comando de consola para mantenimiento `printers:cleanup-stale-jobs`.

### Fase 4: Controladores API V2, Requests y Resources
- [x] Crear `App\Http\Requests\Api\Printers\V2\EnqueuePrintJobRequest` (validación de formatos permitidos por impresora y cota `max_payload_kb`).
- [x] Crear `App\Http\Requests\Api\Printers\V2\UpdatePrintJobStatusRequest`.
- [x] Crear `App\Http\Requests\Api\Printers\V2\PrinterHeartbeatRequest`.
- [x] Crear `App\Http\Resources\V2\Printers\PrinterResource` (incluye `supported_formats`, `default_format`, `max_payload_kb`, `total_prints_count`).
- [x] Crear `App\Http\Resources\V2\Printers\PrintJobResource` (incluye `print_count`, `is_favorite`, `printed_at`).
- [x] Crear `App\Http\Controllers\Api\Printers\V2\PrinterController`.
- [x] Crear `App\Http\Controllers\Api\Printers\V2\PrintJobController` (incluye `claimNext`, `reprint`, `favorite`).
- [x] Crear rutas en `routes/printers/v2.php` y registrarlas en `routes/api/v2.php`.

### Fase 5: Panel de Administración Filament
- [x] Actualizar `PrinterResource`: campos de formatos soportados, formato por defecto, tamaño máximo de payload, odómetro de impresiones, badges de estado y último contacto.
- [x] Actualizar `PrinterStackRelationManager`: columnas con badges de estado y formato, columna con contador de impresiones (`print_count`), badge de favorito, acciones para reimprimir, reintentar y cancelar trabajos, y visualizador de payload.

### Fase 6: Pruebas Automatizadas y Documentación
- [x] Tests de Feature para clientes: listar impresoras, encolar trabajos, validación de rechazo de formatos no soportados (`422`), reimpresión de trabajos y alternancia de favoritos.
- [x] Tests de Feature para agentes: pop de siguiente trabajo con exclusión mutua (`lockForUpdate`), incremento verificado de `print_count` y `total_prints_count` tras `completed`, y reporte de heartbeat.
- [x] Tests de Broadcast: comprobar emisión de `PrintJobCreated` y `PrintJobStatusUpdated` en el canal privado adecuado.
- [x] Tests de Policy: aislamiento estricto entre usuarios y ligado `device:{id}`.
- [x] Actualizar [`docs/info/printers.md`](../../info/printers.md) con el contrato de endpoints, enums, WebSockets y flujos.
- [x] Crear [`docs/info/api/v2/printers.md`](../../info/api/v2/printers.md) y enlazarlo en el índice general de la API V2.
- [x] Ejecutar `php artisan scribe:generate`.
- [x] Pasar pipeline obligatorio (`pint`, `composer phpstan`, `php artisan test`).

---

> Creado: 2026-08-30 · Última revisión: 2026-09-19
