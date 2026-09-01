# Ideas y notas

Este archivo es para editarlo yo manualmente, aquí anoto ideas para futuras implementaciones/cambios/mejoras pero no
debe intervenir en el archivo ninguna ia.

- Los contenidos deben poder asociarse con recursos por tipos: pdf, esquemas, galerías, web principal, enlaces a webs desde las que he obtenido datos, vídeo en youtube, lista de reproducción en youtube,
- En "/airflight" los "Aviones detectados (última hora)" no se actualizan dinámicamente (hay que recargar la página)
- Para las rutas web crear un md en "docs/info/routes-web.md"? así se quita del AGENTS.md para que no se lean en las consultas que no usen esa parte del proyecto.



## Json para datos al subir info de microcontroladores

Los JSON de referencia listos para copiar a tus clientes (Raspberry, NAS, portátiles, Pico…). Recuerda que en todos los casos la petición va con la cabecera de autenticación del dispositivo:

```
Authorization: Bearer <TOKEN_SANCTUM_DEL_DISPOSITIVO>
Content-Type: application/json
Accept: application/json
```

Y el token debe tener la ability `hardware:write`.

**1) Endpoint dedicado "solo estado" (NAS, Raspberry, portátil)**

`POST /api/v2/hardware/device-status`

Envía el estado directamente en la raíz del cuerpo. `hardware_device_id` es **obligatorio**; el resto son opcionales (solo se guarda lo que envíes):

```json
{
    "hardware_device_id": 12,
    "temp": 33.5,
    "voltage": 3.7,
    "battery_level": 48,
    "cpu": 33,
    "disk": 80,
    "uptime": 123456,
    "ip_local": "192.168.1.100",
    "ip_public": "203.0.113.1",
    "extra": {
        "ram": "62%",
        "processes": 148,
        "temp_disk": 41
    }
}
```

Ejemplo mínimo (lo más habitual en un cron periódico):

```json
{
    "hardware_device_id": 12,
    "temp": 45.2,
    "cpu": 27,
    "disk": 63,
    "uptime": 987654
}
```

**2) Estado adjunto a cualquier otra subida (p. ej. tu Pico enviando datos de sensor + estado)**

Cuando el cuerpo lleva otros datos (energía, sensores, etc.), agrupa el estado del dispositivo dentro de `hardware_device_info`. El sistema lo aplana y lo procesa igual:

```json
{
    "hardware_device_id": 12,
    "hardware_device_info": {
        "temp": 33,
        "voltage": 3.7,
        "battery_level": 48,
        "cpu": 33,
        "uptime": 123456
    },
    "data": {
        "...": "aquí van los datos propios del endpoint (energía, lectura de sensor, etc.)"
    }
}
```

**Reglas / validaciones a tener en cuenta**

- `hardware_device_id` → **requerido**, entero, debe existir y pertenecer al dispositivo del token (regla `OwnedHardwareDevice`). Si no es válido, se rechaza y **no se guarda nada**.
- `temp` → numérico (°C).
- `voltage` → numérico (V).
- `battery_level` → entero `0–100`.
- `cpu` → numérico `0–100` (%).
- `disk` → numérico `0–100` (%).
- `uptime` → entero `>= 0` (segundos).
- `ip_local` / `ip_public` → dirección IP válida.
- `extra` → objeto/array libre para métricas futuras (ram, procesos, temperatura de disco…).

**Notas importantes**

- El nombre canónico del identificador es **`hardware_device_id`** en entrada y salida (ya no existen alias legacy `hardware_device` / `device_id`).
- Solo se guarda el **último estado conocido** (no hay histórico); cada envío sobrescribe los valores anteriores.
- Tras recibir los datos, `last_seen_at` se actualiza automáticamente al timestamp actual.
- Todos los campos de estado son opcionales: envía solo los que tu dispositivo pueda medir.

> Creado: 2026-08-30 · Última revisión: 2026-09-01
