# Módulo: Formulario de Contacto (Contact)

Módulo para enviar formularios de contacto vía API con verificación reCAPTCHA y envío de email al administrador.

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
| `app/Http/Controllers/Dashboard/EmailController.php` | Dashboard | Admin legacy |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Contact/ContactService.php` | `sendContactForm()` — envía email |
| `app/Services/RecaptchaService.php` | `verify()` — valida reCAPTCHA |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Contact/V2/ContactSendRequest.php` | Validación: name, email, subject, message, g-recaptcha-response |

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
| `client_ip` | string | IP del cliente |
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
| POST | `/api/v2/contact/send` | No | contact (5/hora) | Enviar formulario de contacto |

## Flujo de contacto

1. POST `/api/v2/contact/send` con `name`, `email`, `subject`, `message`, `g-recaptcha-response`
2. `ContactSendRequest` valida campos (message min:10, max:5000)
3. `RecaptchaService::verify()` valida el token reCAPTCHA
4. `ContactService::sendContactForm()` envía el `ContactMail`
5. Respuesta: `{ success: true, message: "Mensaje enviado correctamente" }`

## Comando de debug

```bash
php artisan debug:seed-contact --count=10
```

