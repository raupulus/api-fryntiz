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

## Filtros, orden y paginación

Todas las colecciones aceptan el mismo juego de parámetros, resuelto en
`App\Http\Api\CollectionQuery`:

```
?page=1&per_page=25            paginación (máximo 100 por página)
?campo=valor                   igualdad
?campo=a,b,c                   WHERE campo IN (a, b, c)
?campo[gte]=x&campo[lte]=y     rango (gte, gt, lte, lt, ne)
?from=&to=                     alias de created_at[gte] / created_at[lte]
?sort=-created_at              orden; el guion es descendente
```

**Sólo se aceptan los campos que declara cada endpoint.** Un campo, un operador
o un orden que no esté en su lista blanca **se ignora**: ni filtra ni da error.
Es deliberado, para no romper clientes antiguos.

**El valor sí se comprueba**, y un valor que no case con el tipo de su columna
responde **422** con el envelope y el detalle en `errors`:

```jsonc
GET /api/v2/platforms?created_at=abc

{ "success": false, "message": "Los datos proporcionados no son válidos.",
  "errors": { "created_at": ["El campo created at no es una fecha válida."] } }
```

El tipo se deduce del nombre de la columna, que en este esquema es fiable:

| Nombre | Tipo | Ejemplos |
|---|---|---|
| `*_at` | fecha | `created_at`, `published_at`, `last_seen_at` |
| `id`, `*_id` | entero | `type_id`, `hardware_device_id` |
| `is_*`, `has_*` | booleano | `is_featured` |
| el resto | texto | `name`, `slug`, `domain`, `icao` |

Hasta la revisión de 2026-09-02 el valor llegaba tal cual a `where()`, y como
PostgreSQL es estricto con los tipos, `?created_at=abc` no devolvía cero filas:
lanzaba `SQLSTATE 22007`, o sea **un 500 que provocaba cualquiera sin
autenticar** en todas las colecciones públicas (AR-E01). `?campo[gte][]=1` era
aún más directo: metía un array como tercer argumento de `where()`.

---

## Límites de peticiones

**Toda** ruta de `api/*` tiene límite. El grupo `api` lleva un techo aplicado
con `throttleApi('api-global')` en `bootstrap/app.php`, y encima de él cada ruta
declara el suyo cuando necesita algo más estricto. El middleware de ruta corre
después del de grupo, así que se aplican los dos y **manda el más estricto**.

| Limitador | Por defecto | Reparto | Dónde |
|---|---|---|---|
| `api-global` | 300/min | token, o IP si no hay token | techo de todo el grupo `api` |
| `api` | 60/min | token, o IP | lecturas autenticadas y gestión de tokens |
| `api-store` | 60/min | token | escrituras IoT por sensor |
| `api-store-batch` | 20/min | token | lotes (AirFlight y multi-sensor) |
| `api-auth` | 10/min | IP **y** email | login y newsletter |
| `contact` | 5/hora | IP | formulario de contacto |
| `api-fallback` | 30/min | IP | ruta de cierre `ANY /api/v2/{any}` |

Los números salen de `config/rate_limits.php`, que explica de dónde sale cada
uno, y se pueden ajustar por `.env` sin tocar código.

⚠️ **Todo lo que reparte por IP depende de `TRUSTED_PROXIES`.** Mal puesto,
`$request->ip()` devuelve la IP de nginx y el límite pasa a ser un cupo global
compartido por todos los visitantes: ni frena a nadie ni deja pasar el tráfico
bueno.

Hasta la revisión de 2026-09-02 esto **no era así**: desde Laravel 11 el grupo
`api` no trae throttle de fábrica y nadie lo había pedido, así que catorce rutas
públicas de lectura y siete autenticadas iban sin ningún límite (AR-S01,
AR-A01). `tests/Feature/Api/V2/RouteContractTest.php` recorre el enrutador y no
deja que vuelva a pasar con una ruta nueva.

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

- `GET /api/v2/platforms`: catálogo de plataformas. **No filtra por estado**: devuelve todas las que hay.
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

⚠️ **Leer y escribir son abilities distintas.** A un dispositivo se le emite
sólo la de escritura de su módulo (`weatherstation:write`, `keycounter:write`…);
la de lectura (`:read`) es para un panel o un cliente que consulte datos. Hasta
el 2026-09-02 sólo Hardware tenía `:read` y las GET de KeyCounter y SmartPlant
se protegían con la ability de **escritura**, así que el token de un teclado
podía listar todas las sesiones y plantas de su dueño (AR-S02).

| Módulo | Contrato | Rutas |
|---|---|---|
| Estación meteorológica | [`weather-station.md`](weather-station.md) | `GET /api/v2/weather-stations` (público), `GET /api/v2/weather-stations/{station}` (público), `POST /api/v2/weather-stations/{station}/readings` (`weatherstation:write`), `GET /api/v2/weather-stations/{station}/{sensor}` (público) y `POST` del mismo (`weatherstation:write`) |
| Hardware / energía | [`hardware.md`](hardware.md) | `GET /api/v2/hardware/devices` y `GET .../{device}` (`hardware:read`; el `serial_number` sale **sólo en el detalle**), `PUT .../{device}/status`, `POST /api/v2/hardware/energy-readings` y `POST .../solar-readings` (`hardware:write`) |
| Contador de pulsaciones | [`keycounter.md`](keycounter.md) | `GET /api/v2/keycounter/{keyboard,mouse}-sessions` (`keycounter:read`), `POST` de las mismas (`keycounter:write`) |
| Plantas inteligentes | [`smart-plant.md`](smart-plant.md) | `GET /api/v2/smartplant/plants` y `GET .../{plant}/readings` (`smartplant:read`), `POST .../{plant}/readings` (`smartplant:write`) |
| Registro de vuelos | [`airflight.md`](airflight.md) | `GET /api/v2/airflight/aircrafts` y `GET /api/v2/airflight/receiver` (**públicos**), `POST /api/v2/airflight/aircrafts` y `.../batch` (`airflight:write`) |

## Model Context Protocol (MCP)

No es un módulo de `api/v2` ni usa su envelope: vive en `/mcp/api-raupulus`
(`GET|POST|DELETE`), aparte del prefijo REST. Documentado en
[`docs/info/mcp.md`](../../mcp.md).

---

> Creado: 2026-08-30 · Última revisión: 2026-09-02
