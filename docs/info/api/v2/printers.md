# Contrato API V2 — Impresoras y Cola de Impresión

> Este archivo documenta **el contrato HTTP y de WebSockets** del módulo de impresoras físicas (térmicas ESC/POS como EM5820, tickets, 2D y 3D): rutas, auth Sanctum, parámetros, flujos del microcontrolador y formato exacto de las respuestas.
> Está pensado para copiarse a otro proyecto (o pegarse en el contexto de una IA o firmware en C++/Arduino) para integrar los clientes sin necesidad de leer el código del servidor.
>
> Para la documentación general del módulo en Laravel/Filament, ver [`docs/info/printers.md`](../../printers.md).

---

## 1. Base y convenciones comunes

- **Base URL**: `/api/v2`
- **Envelope uniforme** en todas las respuestas JSON:
  ```json
  // Éxito (200 OK / 201 Created)
  {
    "success": true,
    "message": "Mensaje descriptivo en español",
    "data": { ... }
  }

  // Error (4xx / 5xx)
  {
    "success": false,
    "message": "Mensaje descriptivo de error",
    "errors": {
      "campo": ["Detalle de validación"]
    }
  }
  ```
- **Autenticación**: Laravel Sanctum (`Authorization: Bearer <token>`).
  - **Tokens de sesión humana**: emitidos en `POST /auth/tokens` con scope `session`.
  - **Tokens de dispositivo IoT**: emitidos con scope `device:{hardware_device_id}` y abilities de módulo:
    - `printers:read`: lectura de impresoras y cola de trabajos.
    - `printers:write`: consumo atómico de cola (`jobs/next`), reporte de estado (`status`) y latidos (`heartbeat`).
- **Telemetría Hardware Opcional (`hardware_device_info`)**:
  Cualquier endpoint llamado por el microcontrolador (`/jobs/next`, `/status`, `/heartbeat`) permite adjuntar el objeto `hardware_device_info` (`temp`, `voltage`, `battery_level`, `uptime`, `ip_local`, `software_version`, `extra`) para refrescar la salud del dispositivo físico en el mismo viaje de red.

---

## 2. Contrato de WebSockets (Laravel Reverb / Pusher Protocol v7)

### Conexión WSS
- **URL**: `wss://{REVERB_HOST}:{REVERB_PORT}/app/{REVERB_APP_KEY}?protocol=7&client=js&version=8.4.0&flash=false`
- **Autenticación de Canal Privado**: `POST /broadcasting/auth`
  - Headers: `Authorization: Bearer <token_iot>`, `Content-Type: application/x-www-form-urlencoded`
  - Body: `socket_id={socket_id}&channel_name=private-printer.{printer_id}`
  - Respuesta: `{"auth": "APP_KEY:firma_hmac"}`
- **Suscripción en socket**:
  `{"event": "pusher:subscribe", "data": {"channel": "private-printer.{printer_id}", "auth": "..."}}`

### Eventos del Servidor en `private-printer.{printer_id}`
1. **`job.created`**:
   Aviso instantáneo de trabajo nuevo o reimpresión encolada (no envía el ticket por el socket, sólo despierta al microcontrolador):
   ```json
   {
     "printer_id": 5,
     "job_id": 142,
     "format": "escpos",
     "priority": 10,
     "created_at": "2026-09-17T12:00:00.000000Z"
   }
   ```
2. **`job.updated`**:
   Notificación de cambio de estado (`processing`, `completed`, `failed`, `cancelled`):
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

## 3. Endpoints del Agente IoT / Microcontrolador

### 3.1 Reclamar siguiente trabajo atómico
Extrae de forma atómica (`lockForUpdate`) el siguiente trabajo pendiente por prioridad y antigüedad, marcándolo como `processing`.
- **Método y Ruta**: `POST /api/v2/printers/{printer}/jobs/next`
- **Auth**: `printers:write` (ligado a `device:{printer.hardware_device_id}`).
- **Body** (opcional):
  ```json
  {
    "hardware_device_info": {
      "temp": 42.1,
      "voltage": 5.02,
      "uptime": 3600
    }
  }
  ```
- **Respuesta 200 OK (con trabajo)**:
  ```json
  {
    "success": true,
    "message": "Trabajo de impresión reclamado exitosamente",
    "data": {
      "id": 142,
      "printer_id": 5,
      "user_id": 1,
      "status": "processing",
      "format": "escpos",
      "content": "G1kGExQVFR...==",
      "note": "Ticket de barra",
      "priority": 10,
      "attempts": 1,
      "print_count": 0,
      "is_favorite": false,
      "error_message": null,
      "printed_at": null,
      "created_at": "2026-09-17T12:00:00.000000Z",
      "updated_at": "2026-09-17T12:00:01.000000Z"
    }
  }
  ```
- **Respuesta 200 OK (cola vacía)**:
  ```json
  {
    "success": true,
    "message": "No hay trabajos pendientes en la cola",
    "data": null
  }
  ```

### 3.2 Notificar resultado de impresión física
- **Método y Ruta**: `PATCH /api/v2/printers/jobs/{job}/status`
- **Auth**: `printers:write`.
- **Body**:
  ```json
  {
    "status": "completed",
    "error_message": null,
    "hardware_device_info": {
      "temp": 42.5
    }
  }
  ```
  - `status` (`required|in:completed,failed,out_of_paper`):
    - `completed`: incrementa `job.print_count` (+1), `printer.total_prints_count` (+1) y fija `printed_at = now()`.
    - `failed`: guarda `error_message` (requerido).
    - `out_of_paper`: restablece el trabajo a `pending` y pasa la impresora a `out_of_paper`.
- **Respuesta 200 OK**:
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

### 3.3 Heartbeat y presencia de hardware
- **Método y Ruta**: `POST /api/v2/printers/{printer}/heartbeat`
- **Auth**: `printers:write`.
- **Body**:
  ```json
  {
    "status": "ready",
    "hardware_device_info": {
      "temp": 41.2,
      "uptime": 7200
    }
  }
  ```
  - `status` (`required|enum:ready,busy,out_of_paper,cover_open,offline,error`).
- **Respuesta 200 OK**:
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

### 3.4 Descarga de trabajo concreto por ID
- **Método y Ruta**: `GET /api/v2/printers/jobs/{job}`
- **Auth**: `printers:read`, `printers:write` o `session`.

---

## 4. Endpoints de Aplicaciones Emisoras e Intranet

### 4.1 Encolar nuevo trabajo de impresión
- **Método y Ruta**: `POST /api/v2/printers/{printer}/jobs`
- **Auth**: `printers:write` o `session`.
- **Body**:
  ```json
  {
    "content": "Comanda #9821\n1x Café con leche\n1x Tostada aceite",
    "format": "text",
    "note": "Mesa 8",
    "priority": 5,
    "is_favorite": false
  }
  ```
  - `content` (`required|string`): longitud acotada por `max_payload_kb` de la impresora.
  - `format` (`nullable|string`): debe pertenecer a los `supported_formats` de la impresora (por defecto toma `default_format`).
  - `priority` (`nullable|integer|-100..100`): por defecto `0`.
- **Respuesta 201 Created**: Emite `job.created` por WebSockets y devuelve el recurso encolado.

### 4.2 Reimprimir un trabajo existente
- **Método y Ruta**: `POST /api/v2/printers/jobs/{job}/reprint`
- **Body** (opcional): `{"priority": 10}`
- **Respuesta 201 Created**: Encola una copia lista para ejecutarse y emite `job.created`.

### 4.3 Alternar favorito / plantilla
- **Método y Ruta**: `PATCH /api/v2/printers/jobs/{job}/favorite`
- **Body** (opcional): `{"is_favorite": true}`

### 4.4 Listar plantillas favoritas de una impresora
- **Método y Ruta**: `GET /api/v2/printers/{printer}/favorites`

### 4.5 Listar impresoras
- **Método y Ruta**: `GET /api/v2/printers`
- **Parámetros Query**: `status`, `printer_type`, `is_active`, `page`.

### 4.6 Ficha de una impresora
- **Método y Ruta**: `GET /api/v2/printers/{printer}`

### 4.7 Listar historial de trabajos de una impresora
- **Método y Ruta**: `GET /api/v2/printers/{printer}/jobs`
- **Parámetros Query**: `status`, `format`, `is_favorite`, `page`.

### 4.8 Cancelar trabajo de impresión
- **Método y Ruta**: `DELETE /api/v2/printers/jobs/{job}`

---

## 5. Máquina de Estados del Firmware (C++ / Arduino / Pico W)

```c
void loop() {
    websocket_client.poll();

    if (has_pending_job_signal) {
        drain_queue();
        has_pending_job_signal = false;
    }

    if (millis() - last_heartbeat > 60000) {
        bool no_paper = digitalRead(CTS_PIN) == HIGH;
        api_send_heartbeat(no_paper ? "out_of_paper" : "ready");
        last_heartbeat = millis();
    }
}

void drain_queue() {
    while (true) {
        if (digitalRead(CTS_PIN) == HIGH) {
            api_send_heartbeat("out_of_paper");
            break;
        }

        Job job = api_claim_next_job();
        if (!job.exists) break;

        bool ok = print_to_serial(job.content, job.format);
        if (ok) {
            api_patch_status(job.id, "completed", NULL);
        } else if (digitalRead(CTS_PIN)) {
            api_patch_status(job.id, "out_of_paper", "Agotado papel en mitad de impresion");
        } else {
            api_patch_status(job.id, "failed", "uart_error");
        }
    }
}
```

---

> Creado: 2026-09-17 · Última revisión: 2026-09-19
