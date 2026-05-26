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
| `app/Http/Controllers/Api/Newsletter/V2/NewsletterController.php` | API V2 | subscribe, verify, unsubscribe |
| `app/Http/Controllers/Api/NewsletterController.php` | API V1 | Controlador V1 legacy |

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
| `ip_address` | string | IP del suscriptor |
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
| POST | `/api/v2/newsletter/subscribe` | No | api-auth | Suscribir email |
| GET | `/api/v2/newsletter/verify/{token}` | No | api-auth | Verificar email |
| GET | `/api/v2/newsletter/unsubscribe/{token}` | No | api-auth | Cancelar suscripción |

## Flujo de suscripción

1. POST `/subscribe` con `email` (y opcionalmente `name`)
2. Se crea registro con `is_verified=false` y tokens generados
3. Se envía `NewsletterVerification` mail con enlace de verificación
4. Usuario hace click en enlace → GET `/verify/{token}`
5. Se marca `is_verified=true` y `verified_at=now()`
6. Para darse de baja → GET `/unsubscribe/{token}`
7. Se marca `status=unsubscribed` y `unsubscribed_at=now()`

## Comando de debug

```bash
php artisan debug:seed-newsletter --count=10
```

