# Contrato API V2 — Mensajes de contacto (Contact)

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelo `Email`, prioridad, umbral de envío,
> decisiones de producto) ver [`docs/info/contact.md`](../../contact.md).

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

  `errors` solo aparece si hay detalle (p. ej. errores de validación 422). Un
  borrado (204) no lleva cuerpo en absoluto.
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405).

---

## Mensajes de contacto (`/contact-messages`)

El recurso es el mensaje, no la acción de enviarlo. La respuesta es **siempre
la misma** tanto si el mensaje se reenvía al dueño de la plataforma como si
se queda solo guardado por parecer dudoso: decirle al remitente que "su
mensaje parece spam" solo le enseña qué cambiar para colarlo la próxima vez,
y a quien escribe de buena fe le da un susto para nada. Lo dudoso se revisa a
mano desde el panel.

### `POST /contact-messages` — Enviar un mensaje de contacto

- **Auth**: no requiere autenticación.
- **Rate limit**: `contact` — 5 peticiones/hora por IP (`RATE_LIMIT_CONTACTO`, config `rate_limits.contact_per_hour`).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `name` | string | `required`, máx. 255 |
| `email` | string | `required`, formato email, máx. 255 |
| `subject` | string | `required`, máx. 255 |
| `message` | string | `required`, mín. 10, máx. 5000 |
| `privacity` | boolean | opcional (`sometimes`) — aceptación de la política de privacidad |
| `contactme` | boolean | opcional (`sometimes`) — consentimiento para que le respondan |
| `attributes` | array | opcional (`sometimes`), máx. 20 claves. Campos libres que añada cada web (teléfono, empresa…) |
| `attributes.*` | string\|null | máx. 255 cada valor |
| `g-recaptcha-response` | string | `required` **solo si** hay clave reCAPTCHA configurada en el servidor (`services.recaptcha.secret_key`); si no hay clave configurada, es `nullable` |

- **Respuesta 201** (siempre el mismo mensaje, se reenvíe o no):

```json
{
  "success": true,
  "message": "Mensaje recibido correctamente",
  "data": null
}
```

- **Qué pasa por dentro (para que no sorprenda al integrar, sin que cambie la respuesta)**:
  - El mensaje **se guarda siempre** en la tabla `emails`, aunque parezca spam.
  - Se calcula una `priority` de 0 a 10 (captcha configurado y su score,
    dominio/IP de confianza, número de enlaces en el texto, palabras de la
    lista de señales de spam, ausencia de `referer` y `User-Agent`…). Si
    `priority >= contact.priority.send_threshold` (por defecto 3), el mensaje
    se reenvía por correo al dueño de la plataforma (`SendContactEmailJob`);
    si no, se queda solo guardado para revisión manual en el panel.
  - Un mensaje duplicado (mismo email en los últimos `contact.deduplication.minutes_per_email`
    minutos, o mismo asunto+mensaje+IP en las últimas `contact.deduplication.hours_per_content`
    horas) se guarda igualmente pero con `priority = 0` (no se reenvía, queda
    el rastro del intento).
  - Si hay clave reCAPTCHA configurada y la verificación falla (`success !== true`
    en la respuesta de Google), la petición se corta ahí: es el único caso que
    da un error distinto de 422 por validación (ver abajo).
  - `subject` y `message` se sanean (se les quita HTML y caracteres de
    control) antes de guardarse.

- **Errores**:
  - `422` validación de los campos del body (formato estándar `errors`).
  - `422` con mensaje `"Verificacion de seguridad fallida"` si hay reCAPTCHA
    configurado y el token no verifica contra Google (esto **no** guarda el
    mensaje: se corta antes).
  - `429` al superar el límite de 5/hora por IP.

---

> Creado: 2026-08-30 · Última revisión: 2026-09-06
