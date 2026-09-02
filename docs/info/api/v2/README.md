# API V2 — Índice de rutas y contratos

> Esta es la referencia única del mapa de rutas de **nuestra** API V2
> (`/api/v2/...`). Al trabajar con cualquiera de estos endpoints, **consulta
> el contrato del módulo antes de tocar código** y **actualízalo en el mismo
> commit** si cambia una ruta, un parámetro o la forma de la respuesta — es
> la misma obligación que para `docs/info/<modulo>.md` (ver
> [AGENTS.md](../../../../AGENTS.md) §13).
>
> Cada archivo de este directorio documenta **solo el contrato HTTP** (rutas,
> auth, parámetros, forma exacta de la respuesta) y está pensado para
> copiarse a otro proyecto sin tener que leer el código fuente. Para el
> diseño interno de cada módulo (modelos, servicios, decisiones de producto)
> ver su `docs/info/<modulo>.md` correspondiente, enlazado desde cada
> contrato.

Verificado con `php artisan route:list --path=api` el 2026-09-02. Full REST:
recursos en plural, sub-recursos anidados bajo su padre. El fallback
`ANY /api/v2/{any}` devuelve siempre JSON 404 con envelope
`{success, message}`, para cualquier método y ruta no reconocida.

⚠️ El renombrado a este esquema (recursos en plural, `/auth/tokens` en vez de
`/auth/login`, `/hardware/energy-readings` en vez de `/hardware/energy/store`,
etc.) sustituyó por completo las rutas de escritura IoT anteriores **sin
alias de compatibilidad**. Antes de desplegar a un entorno con dispositivos
físicos reales enviando datos, confirma que su firmware ya apunta a las
rutas nuevas.

---

## Forma de la respuesta

**Todas** las respuestas de `/api/v2/**` llevan el mismo sobre, con una única
excepción: el `204` de un borrado, que por definición del protocolo no lleva
cuerpo.

```jsonc
// Correcta
{ "success": true,  "message": "Operación exitosa", "data": { } }

// Colección: añade "meta"
{ "success": true,  "message": "Operación exitosa", "data": [ ], "meta": {
    "total": 40, "per_page": 25, "current_page": 1, "last_page": 2, "from": 1, "to": 25 } }

// Se guardó, pero hay algo que mirar: añade "warnings"
{ "success": true,  "message": "Lecturas de energia almacenadas", "data": [ ],
  "warnings": ["El canal 3 no tiene ningún elemento activo dado de alta; su lectura no se ha guardado."] }

// Error
{ "success": false, "message": "Recurso no encontrado" }

// Error de validación: añade "errors"
{ "success": false, "message": "Los datos proporcionados no son válidos.",
  "errors": { "email": ["El campo correo electrónico es obligatorio."] } }
```

Reglas del sobre:

- `success` es siempre booleano y siempre está.
- `message` es siempre texto y siempre está. Va **traducido**: sale de
  `lang/{es,en}/api.php` y respeta la cabecera `Accept-Language` (o `?lang=`),
  igual que los mensajes de validación. Ver `App\Http\Middleware\SetLocale`.
- `data` sólo aparece en las respuestas correctas. En un error no hay recurso
  que devolver, así que la clave no existe —no es `null`—.
- `meta`, `warnings`, `errors` y `debug` son opcionales y van siempre detrás.
- **No hay ninguna respuesta con la forma de Laravel.** Un `429`, un `500`, un
  `413` o cualquier otro error que genere el framework se envuelven igual, y
  siempre en JSON aunque el cliente mande `Accept: */*` o no mande `Accept`
  —que es lo que hacen los microcontroladores—. Las cabeceras del error se
  conservan: un `429` sigue trayendo su `Retry-After`.

### El bloque `debug`

Sólo existe con `APP_DEBUG=true`, o sea **nunca en producción**. Es el contexto
que la V1 devolvía en `JsonHelper::siteData()`, recuperado para desarrollo:

```jsonc
"debug": {
  "method": "GET",
  "domain": "api.raupulus.dev",
  "path": "api/v2/platforms",
  "full_url": "https://api.raupulus.dev/api/v2/platforms?created_at=abc",
  "locale": "es",
  "parameters": { "created_at": "abc", "password": "[oculto]" },
  "headers": { "accept": "application/json", "user-agent": "..." },
  "exception": { "class": "...", "message": "...", "file": "...", "line": 42 }
}
```

Dos cosas que **no** se copiaron de la V1:

- Las cabeceras pasan por **lista blanca** (`ApiEnvelope::SAFE_HEADERS`), no por
  lista negra. `Authorization` y `Cookie` no salen nunca. Con lista negra, la
  cabecera que se invente mañana entraría sola.
- Los campos sensibles del cuerpo se tapan (`ApiEnvelope::REDACTED_INPUT`). Sin
  eso, el `debug` de `POST /auth/tokens` enseñaría la contraseña en claro.

Es en desarrollo donde se pegan respuestas en capturas y en tickets, así que el
filtrado también aplica ahí.

### Dónde se construye

| Pieza | Papel |
|---|---|
| `App\Support\Http\ApiEnvelope` | La **forma**. Único sitio donde está escrita, y donde vive el bloque `debug`. |
| `App\Traits\ApiResponseTrait` | Puerta para los **controladores** (`successResponse()`, `notFoundResponse()`…). |
| `JsonHelper` (`support/helpers/`) | Puerta **estática**, para donde un trait no llega: los handlers de `bootstrap/app.php`, la ruta de cierre de `routes/api/v2.php` y los `render()` de `app/Exceptions/`. |

Las dos puertas son gemelas y devuelven exactamente lo mismo:
`tests/Feature/Api/V2/ApiResponseParityTest.php` las compara método a método, y
`tests/Feature/Api/V2/ErrorEnvelopeTest.php` fija que ningún error se salga del
sobre. **Si tocas un método de una, toca su gemelo en la otra.**

---

## Autenticación (`Api\Auth\V2\TokenController`)

Contrato completo: [`auth.md`](auth.md).

- `POST   /api/v2/auth/tokens`: emisión de token Bearer Sanctum de sesión (ability `session`). Rate limit `api-auth`.
- `GET    /api/v2/auth/tokens`: tokens activos del usuario autenticado.
- `DELETE /api/v2/auth/tokens/current`: revoca el token con el que se hace la petición.
- `DELETE /api/v2/auth/tokens/{token}`: revoca un token concreto por id.
- `POST   /api/v2/auth/tokens/devices`: emisión de token de dispositivo IoT (uso administrativo/CLI).
- `GET    /api/v2/users/me`: datos del perfil del usuario autenticado.

## Contacto (`Api\ContactMessage\V2\ContactMessageController`)

Contrato completo: [`contact.md`](contact.md).

- `POST /api/v2/contact-messages`: envío de formulario de contacto con reCAPTCHA v3 y rate limit `contact`.

## Newsletter (`Api\Newsletter\V2\NewsletterSubscriptionController`)

Contrato completo: [`newsletter.md`](newsletter.md).

- `POST   /api/v2/newsletter/subscriptions`: alta de suscriptor con verificación doble opt-in.
- `POST   /api/v2/newsletter/subscriptions/verification`: reenvío de token de confirmación.
- `POST   /api/v2/newsletter/subscriptions/{token}/confirmation`: activación de la suscripción.
- `DELETE /api/v2/newsletter/subscriptions/{token}`: baja inmediata.
- `POST   /api/v2/newsletter/subscriptions/{token}/unsubscription`: baja vía enlace de email.
- `GET    /api/v2/newsletter/subscriptions/stats`: métricas (protegido con gate `view-statistics`).

## Plataformas y CMS (`Api\Platform\V2\PlatformController`, `Api\Content\V2\ContentController`)

Contrato completo: [`content.md`](content.md).

- `GET /api/v2/platforms`: catálogo de plataformas activas.
- `GET /api/v2/platforms/{platform:slug}`: detalle de plataforma.
- `GET /api/v2/platforms/{platform:slug}/categories`: categorías de la plataforma.
- `GET /api/v2/platforms/{platform:slug}/contents`: listado de contenidos.
- `GET /api/v2/platforms/{platform:slug}/contents/{content:slug}`: contenido completo con SEO y autor.
- `GET /api/v2/platforms/{platform:slug}/contents/{content:slug}/pages`: páginas del contenido.
- `GET /api/v2/platforms/{platform:slug}/contents/{content:slug}/pages/{order}`: página concreta.
- `GET /api/v2/platforms/{platform:slug}/contents/{content:slug}/related`: contenidos relacionados.

## Currículum (`Api\Cv\V2\CurriculumController`)

Contrato completo: [`cv.md`](cv.md).

- `GET /api/v2/curricula`: listado público de CVs.
- `GET /api/v2/curricula/shared/{shareToken}`: CV por enlace privado (`X-Robots-Tag: noindex`).
- `GET /api/v2/curricula/{slug}`: CV completo por slug.
- `GET /api/v2/curricula/{slug}/{section}`: una sección suelta del CV.

## Ingesta y consulta IoT

Protegidas con tokens de dispositivo Sanctum + `ability:<scope>` +
`throttle:api-store`. Catálogo de abilities en
[`auth.md`](auth.md) y `App\Support\Auth\TokenAbilities`.

| Módulo | Contrato | Rutas |
|---|---|---|
| Estación meteorológica | [`weather-station.md`](weather-station.md) | `GET\|POST /api/v2/weather-stations`, `GET /api/v2/weather-stations/{station}`, `POST /api/v2/weather-stations/{station}/readings` (`weatherstation:write`), `GET\|POST /api/v2/weather-stations/{station}/{sensor}` |
| Hardware / energía | [`hardware.md`](hardware.md) | `GET /api/v2/hardware/devices`, `GET /api/v2/hardware/devices/{device}` (`hardware:read`), `PUT /api/v2/hardware/devices/{device}/status` (`hardware:write`), `POST /api/v2/hardware/energy-readings`, `POST /api/v2/hardware/solar-readings` (`hardware:write`) |
| Contador de pulsaciones | [`keycounter.md`](keycounter.md) | `GET\|POST /api/v2/keycounter/keyboard-sessions`, `GET\|POST /api/v2/keycounter/mouse-sessions` (`keycounter:write`) |
| Plantas inteligentes | [`smart-plant.md`](smart-plant.md) | `GET /api/v2/smartplant/plants`, `GET\|POST /api/v2/smartplant/plants/{plant}/readings` (`smartplant:write`) |
| Registro de vuelos | [`airflight.md`](airflight.md) | `GET\|POST /api/v2/airflight/aircrafts` (`?minutes=`, `?from=&to=` para el histórico), `POST /api/v2/airflight/aircrafts/batch`, `GET /api/v2/airflight/receiver` (`airflight:write`) |

## Model Context Protocol (MCP)

No es un módulo de `api/v2` ni usa su envelope: vive en `/mcp/api-raupulus`
(`GET|POST|DELETE`), aparte del prefijo REST. Documentado en
[`docs/info/mcp.md`](../../mcp.md).

---

> Creado: 2026-08-30 · Última revisión: 2026-09-02
