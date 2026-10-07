# Módulo: Formulario de Contacto (Contact)

Módulo para enviar formularios de contacto vía API con verificación de captcha (Cloudflare Turnstile o Google reCAPTCHA v3) y envío de email al administrador.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Email.php` | `emails` | Registro de emails enviados/recibidos |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Contact/V2/ContactController.php` | API V2 | Enviar formulario de contacto |
| `app/Http/Controllers/EmailController.php` | Web | Controlador web legacy |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Contact/ContactService.php` | `sendContactForm()` — envía email |
| `app/Services/CaptchaVerifierService.php` | `verify()` — mira qué token ha llegado (Turnstile o reCAPTCHA) y lo valida contra su proveedor |
| `app/Services/TurnstileService.php` | `verify()` — valida un token contra Cloudflare Turnstile |
| `app/Services/RecaptchaService.php` | `verify()` — valida un token contra Google reCAPTCHA (también lo usa el login de los paneles) |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Contact/V2/ContactSendRequest.php` | Validación: name, email, subject, message y los tokens de captcha (`g-recaptcha-response`, `recaptcha_token`, `cf-turnstile-response`, `turnstile_token`) |

### Mailables
| Archivo | Descripción |
|---------|-------------|
| `app/Mail/ContactMail.php` | Mailable de contacto |
| `app/Mail/GenericMail.php` | Mailable genérico reutilizable |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/EmailPolicy.php` | Política de autorización |

## Campos del modelo Email

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` (nullable) |
| `language_id` | int | FK → `languages.id` (nullable) |
| `email` | string | Dirección de email |
| `attributes` | text | Atributos adicionales |
| `subject` | string | Asunto |
| `message` | text | Mensaje |
| `privacity` | boolean | Aceptación de privacidad |
| `contactme` | boolean | Contactar de vuelta |
| `server_ip` | string | IP del servidor |
| `client_ip` | string | IP de origen del visitante. Se resuelve con `App\Support\Http\ClientIp`, que lee la cabecera del proxy (`CF-Connecting-IP`, `X-Forwarded-For`…). Es también la que se manda a reCAPTCHA, que valora el riesgo del visitante, no el del proxy |
| `app_name` | string | Nombre de la aplicación |
| `app_domain` | string | Dominio de la aplicación |
| `client_user_agent` | string | User agent del cliente |
| `client_referer` | string | Referer del cliente |
| `client_accept_language` | string | Idiomas aceptados |

## Relaciones

- `Email` → `BelongsTo` → `User` (vía `user_id`)
- `Email` → `BelongsTo` → `Language` (vía `language_id`)

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| POST | `/api/v2/contact-messages` | No | contact (5/hora) | Enviar formulario de contacto |

## Flujo de contacto

1. POST `/api/v2/contact-messages` con `name`, `email`, `subject`, `message` y un token de captcha
2. `ContactSendRequest` valida campos (message min:10, max:5000). Si hay algún proveedor de captcha configurado, exige al menos un token (error 422 sobre `g-recaptcha-response`)
3. `CaptchaVerifierService::verify()` elige el proveedor según el token recibido:
   - `cf-turnstile-response` o `turnstile_token` → Cloudflare Turnstile (`TURNSTILE_SECRET_KEY`, `services.turnstile.secret_key`), `POST https://challenges.cloudflare.com/turnstile/v0/siteverify` con `secret`, `response` y `remoteip`.
   - `g-recaptcha-response` o `recaptcha_token` → Google reCAPTCHA v3 (`RECAPTCHA_SECRET_KEY`, `google.recaptcha.secret_key`), con el umbral de `RECAPTCHA_MIN_SCORE`.
   - Si llegan los dos, manda Turnstile. Si el token es de un proveedor sin clave pero hay otro configurado, se rechaza (si no, bastaría inventarse el token del proveedor que no está para saltarse la comprobación).
   - **Sin ninguna clave configurada** (desarrollo y tests) se omite la comprobación externa para no bloquear sin credenciales.
   - Token inválido → 422 «Verificacion de seguridad fallida» y el mensaje no se guarda.
4. `ContactService::sendContactForm()` envía el `ContactMail`
5. Respuesta: `{ success: true, message: "Mensaje enviado correctamente" }`

> Turnstile no devuelve puntuación: el mensaje se guarda con `captcha_score` nulo y
> sin el bonus de prioridad que da una puntuación alta de reCAPTCHA. El proveedor utilizado
> (`turnstile` o `recaptcha`) se guarda en `attributes.captcha_provider` y se muestra en la ficha de Filament
> (o «No aplica» si es nulo). El patrón de reCAPTCHA (activo solo con claves en `.env`) protege también
> el login de los dos paneles Filament — ver [auth.md](auth.md).

## Comando de debug

```bash
php artisan debug:seed-contact --count=10
```

---

> Creado: 2026-05-25 · Última revisión: 2026-10-07
