# Contrato API V2 — Newsletter

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelo `Newsletter`, estados, decisiones de
> producto) ver [`docs/info/newsletter.md`](../../newsletter.md).

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
- **Autenticación**: Laravel Sanctum, cabecera `Authorization: Bearer <token>`,
  solo hace falta en `GET /newsletter/subscriptions/stats` (token de sesión,
  ability `session`). El resto de rutas de este módulo son públicas.
- **Ruta inexistente**: cualquier método/URL que no esté documentado responde
  `404` con `{ "success": false, "message": "API V2 - Endpoint no encontrado" }`
  (incluso si el método HTTP es el que no cuadra: el contrato es la pareja
  método+ruta, no hay 405).

Todas las rutas de este módulo cuelgan de `/newsletter/subscriptions` y
comparten el limitador `api-auth`: **10 peticiones/min por IP y 10/min por
email** (`RATE_LIMIT_AUTH`, config `rate_limits.auth_per_minute`), ambos
límites cuentan a la vez. La clave de email sale de `input('email')`: en las
rutas que llevan `{token}` en la URL el body no tiene campo `email`, así que
esa mitad del límite queda con la clave vacía (`email:`) y ese cupo de 10/min
se comparte **globalmente entre todas las peticiones sin email** a esas rutas
durante ese minuto, no es un límite por persona.

---

## Suscripciones (`/newsletter/subscriptions`)

### `POST /newsletter/subscriptions` — Suscribirse a la newsletter

- **Auth**: no requiere autenticación.
- **Rate limit**: `api-auth` (ver arriba; aquí sí hay campo `email` en el body, así que el límite por email es real, por dirección).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | `required`, formato email, máx. 255 |
| `name` | string\|null | opcional |
| `platform_id` | int | `required`, debe existir en `platforms`. Si no se manda, se intenta resolver por el dominio (`Host`) de la petición; si tampoco así se encuentra una plataforma, falla la validación |

- **Respuesta 201** (mismo mensaje tanto si es alta nueva como si reactiva una baja anterior):

```json
{
  "success": true,
  "message": "Suscripcion creada. Revisa tu email para verificar.",
  "data": null
}
```

- **Comportamiento**: si ya existía una suscripción con ese `email` +
  `platform_id` y estaba dada de baja, se reactiva (vuelve a quedar sin
  verificar) en lugar de crear una fila duplicada. En ambos casos se envía (o
  reenvía) el correo de verificación.
- **Errores**:
  - `422` validación (incluye `platform_id.required` con el mensaje
    `"No se ha podido determinar la plataforma de la suscripción."` cuando no
    se manda y tampoco se resuelve por dominio).
  - `429` al superar el límite `api-auth`.

### `GET /newsletter/subscriptions/stats` — Estadísticas de suscripciones

- **Auth**: `auth:sanctum` + `ability:session` + gate `view-statistics`
  (sesión humana de administrador; no es una ruta para dispositivos ni para
  el público).
- **Query params**: `platform_id` (int, opcional) — si se manda, filtra las
  estadísticas a esa plataforma; si no, son globales.
- **Respuesta 200**:

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": {
    "total": 120,
    "active": 95,
    "verified": 100,
    "subscribed": 95,
    "unsubscribed": 15,
    "bounced": 2
  }
}
```

  Es un array plano (no un Resource): `total` son todas las filas que cumplen
  el filtro de plataforma; el resto son recuentos por estado/condición sobre
  ese mismo conjunto.
- **Errores**: `401` sin token válido, `403` si el token no tiene el gate
  `view-statistics`.

### `POST /newsletter/subscriptions/verification` — Reenviar el correo de verificación

- **Auth**: no requiere autenticación.
- **Rate limit**: `api-auth` (por IP y por `email`, real aquí).
- **Body**:

| Campo | Tipo | Reglas |
|---|---|---|
| `email` | string | `required`, formato email |
| `platform_id` | int | `required`, debe existir en `platforms` |

- **Respuesta 200** (siempre el mismo mensaje exista o no la suscripción, y
  también si ya estaba verificada — evita que la ruta sirva para comprobar
  qué direcciones están suscritas):

```json
{
  "success": true,
  "message": "Si la dirección está suscrita y pendiente de verificar, se ha enviado el correo.",
  "data": null
}
```

- **Errores**: `422` validación, `429` al superar el límite.

### `POST /newsletter/subscriptions/{token}/confirmation` — Confirmar la suscripción

Antes era `GET /newsletter/verify/{token}` (ver sección de abajo).

- **Auth**: no requiere autenticación. El propio `{token}` (token de
  verificación) es la credencial.
- **Rate limit**: `api-auth`, con la salvedad de la clave de email compartida explicada arriba (no hay campo `email` en esta ruta).
- **Body**: ninguno.
- **Respuesta 200**:

```json
{
  "success": true,
  "message": "Email verificado correctamente",
  "data": null
}
```

- **Errores**: `404` con mensaje `"Token inválido"` si el token no existe o
  no corresponde a ninguna suscripción pendiente.

### `POST /newsletter/subscriptions/{token}/unsubscription` — Baja de un clic (RFC 8058)

Pensada para la cabecera de correo `List-Unsubscribe` + `List-Unsubscribe-Post:
List-Unsubscribe=One-Click`: el cliente de correo hace este `POST` sin que la
persona confirme nada más, así que responde `200` con cuerpo (un `204` sin
cuerpo confunde a algunos clientes de correo procesando esa cabecera).

- **Auth**: no requiere autenticación. El `{token}` (token de baja) es la credencial.
- **Rate limit**: `api-auth`, con la misma salvedad de clave de email compartida.
- **Body**: ninguno.
- **Respuesta 200**:

```json
{
  "success": true,
  "message": "Suscripcion cancelada correctamente",
  "data": null
}
```

- **Errores**: `404` con mensaje `"Token inválido"`.

### `DELETE /newsletter/subscriptions/{token}` — Baja de la suscripción

Misma operación de negocio que la baja de un clic, pero pensada para un
cliente que sí quiere el semántico `DELETE` + `204`. Antes era
`GET /newsletter/unsubscribe/{token}` (ver sección de abajo).

- **Auth**: no requiere autenticación. El `{token}` (token de baja) es la credencial.
- **Rate limit**: `api-auth`, con la misma salvedad de clave de email compartida.
- **Respuesta**: `204` sin cuerpo.
- **Errores**: `404` con mensaje `"Token inválido"` (con este endpoint el 404
  sí viene en el envelope de error estándar, ya que no es un borrado
  exitoso).

---

## Lo que ya no existe, y por qué

| Ruta antigua | Qué pasó |
|---|---|
| `GET /newsletter/verify/{token}` | Es `POST /newsletter/subscriptions/{token}/confirmation`. Se cambió de `GET` a `POST` porque el enlace viaja dentro de un correo: Gmail, Outlook y los antivirus corporativos hacen *prefetch* de las URLs de un mensaje para comprobar si son maliciosas, y ese prefetch confirmaba suscripciones que nadie había pedido confirmar. |
| `GET /newsletter/unsubscribe/{token}` | Es `POST /newsletter/subscriptions/{token}/unsubscription` (o `DELETE /newsletter/subscriptions/{token}`). Mismo motivo: el prefetch daba de baja a gente que no lo había pedido. Además, RFC 8058 (baja de un clic) exige que la baja sea `POST`, no `GET`. |

El enlace que de verdad viaja en el correo apunta a una página web que **no
muta nada**, `GET /newsletter/{token}` (en `routes/web.php`, fuera de esta
API): esa página es la que, con una acción explícita de la persona, dispara
el `POST`/`DELETE` real contra estos endpoints.

---

> Creado: 2026-08-30 · Última revisión: 2026-09-06
