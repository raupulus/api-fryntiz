---
name: laravel-backend
description: >-
  Convenciones de backend de Api Raupulus (Laravel 13 / PHP 8.4). Cárgala
  SIEMPRE que trabajes en la capa de servidor de este proyecto: crear o editar
  Models, Services, Actions, Jobs, Events/Listeners, Enums, Helpers, Mail,
  Notifications, Policies o Traits; añadir lógica de negocio; refactorizar dentro
  de app/; o cuando dudes dónde colocar una pieza de código de dominio. Úsala
  aunque el usuario no diga "Laravel": si el cambio vive bajo app/ y no es API,
  Filament, migración ni frontend, esta es la skill base. No la uses para diseño
  de endpoints REST (usa api-rest-v2), Filament (filament-admin), esquema de BD
  (postgresql-migrations) ni frontend (vue-tailwind-frontend).
---

# Backend Laravel — Api Raupulus

Stack: **Laravel 13, PHP 8.4**, arquitectura **MVC + Service Layer**. Código en
**inglés**, PHPDoc/comentarios/mensajes de validación en **español**. Formateo
**PSR-12** con `./vendor/bin/pint` (ejecútalo siempre antes de dar por cerrado un
cambio).

## Dónde va cada cosa

| Pieza | Ubicación | Regla |
|-------|-----------|-------|
| Lógica de negocio | `app/Services/<Modulo>/` | Un servicio orquesta el caso de uso; los controladores son finos |
| Operación atómica reutilizable | `app/Actions/` (Fortify en `Actions/Fortify/`) | P. ej. `PublishContentAction`, `StoreSensorDataAction` |
| Modelos | `app/Models/<Modulo>/` | Organizados por módulo (WeatherStation, Hardware, Content, CV, KeyCounter, SmartPlant, AirFlight, WebHooks) |
| Enums | `app/Enums/` | PHP 8.4 backed enums, sufijo `Enum` |
| Eventos / Listeners | `app/Events/` y `app/Listeners/` | Broadcast preparado para Reverb; sub-eventos granulares en `Events/WeatherStation/` |
| Jobs | `app/Jobs/` | Trabajo asíncrono (`ProcessContentViewJob`) |
| Helpers globales sin namespace | `support/helpers/` | Autocargados vía `composer.json → autoload.files`, **fuera de PSR-4** a propósito |
| Helpers con namespace | `app/Helpers/` | `ContentHelper`, `TextFormatParseHelper`, etc. |
| Traits compartidos | `app/Traits/` | Reutilizar antes de duplicar |

## Modelos

Extiende siempre un modelo base de `app/Models/BaseModels/`:
`BaseModel` o `BaseAbstractModelWithTableCrud`. No extiendas
`Illuminate\Database\Eloquent\Model` directamente.

Antes de escribir lógica repetida, comprueba estos traits ya existentes en
`app/Traits/` y reúsalos:

`HasSlug`, `HasStatus`, `Filterable`, `HasTimestampScopes`, `ApiResponseTrait`,
`BelongsToUser`, `BelongsToHardwareDevice`.

Las relaciones se declaran con **return type tipado** (`BelongsTo`, `HasMany`,
…) y PHPDoc en español, como en el resto del código:

```php
/**
 * Relación con el contenido al que pertenece.
 */
public function content(): BelongsTo
{
    return $this->belongsTo(Content::class, 'content_id', 'id');
}
```

## Service Layer

El servicio concentra el caso de uso y se inyecta por constructor en el
controlador. Mantén los servicios sin estado de request y sin formatear
respuestas HTTP (eso es del controlador / Resource). Ejemplo real:

```php
class ContactService
{
    public function sendContactForm(array $data): void
    {
        Mail::to(config('mail.admin_address', config('mail.from.address')))
            ->send(new ContactMail($data));
    }
}
```

Patrón de uso desde el controlador (constructor promotion):

```php
public function __construct(private ContentService $service) {}
```

## Enums (PHP 8.4)

Backed enums en `app/Enums/`, sufijo `Enum`. Ejemplos existentes:
`UserRoleEnum`, `ContentStatusEnum`, `ContentTypeEnum`, `FileTypeEnum`,
`HardwareTypeEnum`, `NewsletterStatusEnum`. Úsalos en casts de modelo y en
validación en lugar de strings mágicos.

Roles: `1=SuperAdmin, 2=Admin, 3=User` (ver `UserRoleEnum` y `RoleHelper`).

## Autorización

Policies por módulo en `app/Policies/` (16 policies). Registra/usa la policy del
módulo en lugar de comprobar roles a mano dentro del controlador.

## Comandos Artisan del proyecto

```bash
php artisan project:install     # Inicializa proyecto (keys, BD, storage)
php artisan project:clear       # Limpia cachés
php artisan debug:seed-all      # Datos de prueba
php artisan content:publish     # Publica contenidos programados (hourly)
php artisan sitemap:generate    # Genera sitemap (daily)
php artisan aemet:*             # Integración AEMET (ver app/Console/Commands/AEMET/)
```

Las tareas programadas se registran en `routes/console.php`.

## Antes de cerrar un cambio

1. `./vendor/bin/pint` para formatear (PSR-12).
2. `php artisan test` (BD de test: `api_raupulus_testing`, PostgreSQL).
3. **Actualizar la doc del módulo en `docs/info/<modulo>.md`** — es obligatorio
   en este proyecto al modificar o crear un módulo (ver AGENTS.md, sección de
   documentación). Si creas un módulo nuevo, añade su `.md` y referénciálo en
   `docs/info/README.md`.

## Detalle clave

Las excepciones de API se transforman a JSON globalmente en `bootstrap/app.php`
(`JsonValidationException`, `JsonAuthorizationException` en `app/Exceptions/`).
No captures ni formatees errores de validación dentro del servicio: deja que
suban. Para el formato de respuesta de API, consulta la skill `api-rest-v2`.
