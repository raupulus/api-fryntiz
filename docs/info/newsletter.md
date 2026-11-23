# Módulo: Newsletter

Módulo de suscripción a newsletter con flujo de verificación por email, gestión de baja por token, soporte multi-plataforma y tracking de origen de suscripción.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Newsletter.php` | `newsletter` | Suscriptor de newsletter |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Newsletter/V2/NewsletterController.php` | API V2 | subscribe, resendVerification, verify, unsubscribe, stats |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Newsletter/NewsletterService.php` | `subscribe()`, `verify()`, `unsubscribe()` |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Newsletter/V2/NewsletterSubscribeRequest.php` | Validación suscripción |

### Mailables
| Archivo | Descripción |
|---------|-------------|
| `app/Mail/NewsletterVerification.php` | Email de verificación |
| `app/Mail/NewsletterUnsubscribe.php` | Email de confirmación de baja |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/NewsletterStatusEnum.php` | Estados: active, inactive, unsubscribed, bounced |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/NewsletterPolicy.php` | Política de autorización |

## Campos del modelo Newsletter

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `platform_id` | int | FK → `platforms.id` (nullable) |
| `email` | string | Email del suscriptor |
| `name` | string | Nombre (nullable) |
| `is_verified` | boolean | Verificación completada |
| `verification_token` | string | Token de verificación (auto-generado) |
| `verified_at` | datetime | Fecha de verificación |
| `unsubscribe_token` | string | Token de baja (auto-generado) |
| `status` | string | Estado: `active`, `inactive`, `unsubscribed`, `bounced` |
| `unsubscribed_at` | datetime | Fecha de baja |
| `subscription_source` | string | Origen: `web`, `api`, `import`, `admin` |
| `language` | string | Idioma preferido |
| `preferences` | json | Preferencias (cast a array) |
| `ip_address` | string | IP de origen del suscriptor. Se resuelve con `App\Support\Http\ClientIp`, que lee la cabecera del proxy (`CF-Connecting-IP`, `X-Forwarded-For`…) en vez de la IP de la conexión |
| `user_agent` | string | User agent |
| `metadata` | json | Metadata adicional (cast a array) |

## Relaciones

- `Newsletter` → `BelongsTo` → `Platform` (vía `platform_id`)

## Constantes

```php
STATUS_ACTIVE = 'active'
STATUS_INACTIVE = 'inactive'
STATUS_UNSUBSCRIBED = 'unsubscribed'
STATUS_BOUNCED = 'bounced'

SOURCE_WEB = 'web'
SOURCE_API = 'api'
SOURCE_IMPORT = 'import'
SOURCE_ADMIN = 'admin'
```

## Boot automático

Al crear un suscriptor, se generan automáticamente:
- `verification_token` — token hashed para verificar el email
- `unsubscribe_token` — token hashed para cancelar suscripción

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| POST | `/api/v2/newsletter/subscriptions` | No | api-auth | Suscribir email (no degrada suscriptores ya verificados y activos) |
| POST | `/api/v2/newsletter/subscriptions/verification` | No | api-auth | Reenviar email de verificación |
| POST | `/api/v2/newsletter/subscriptions/{token}/confirmation` | No | api-auth | Verificar email |
| POST | `/api/v2/newsletter/subscriptions/{token}/unsubscription` | No | api-auth | Baja de un clic (RFC 8058) |
| DELETE | `/api/v2/newsletter/subscriptions/{token}` | No | api-auth | Cancelar suscripción |
| GET | `/api/v2/newsletter/subscriptions/stats` | Sí (`session`, `view-statistics`) | api-auth | Estadísticas de suscriptores |

## Flujo de suscripción

1. `POST /api/v2/newsletter/subscriptions` con `email`, `platform_id` y `privacy_accepted`. Si la suscripción ya está verificada y activa, se conserva intacta sin reenviar correo.
2. Si es nueva o sigue pendiente, se guarda `is_verified=false` y tokens criptográficos únicos.
3. Se envía `NewsletterVerification` mail con enlace a la vista web `GET /newsletter/{token}` (sin mutar en GET).
4. Usuario confirma vía `POST /newsletter/{token}/confirmation` → se marca `is_verified=true`, `verified_at=now()` y se limpia `verification_token`.
5. Para darse de baja → `POST` de confirmación desde `/newsletter/{token}` o `List-Unsubscribe-Post` (RFC 8058).
6. En envíos con bloque `$content`, la plantilla `mail/newsletter.blade.php` sanea el HTML mediante `HtmlHelper::safeBasic()`.

## Comando de debug

```bash
php artisan debug:seed-newsletter --count=10
```

---

## Estado del módulo (2026-08-19)

| Capa | Estado |
|------|--------|
| Modelo, servicio y API V2 | ✅ |
| Tests (6 métodos) | ✅ |
| Mailables (verificación y baja) | ✅ |
| **Panel Filament** | ❌ **Sin Resource** — no hay forma de ver ni gestionar suscriptores desde `/admin` (fase 05, tarea T4) |

---

> Creado: 2026-05-25 · Última revisión: 2026-11-23
