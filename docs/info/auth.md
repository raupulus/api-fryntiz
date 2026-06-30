# Módulo: Autenticación y Usuarios (Auth)

Módulo de autenticación con soporte dual: Fortify para web y Sanctum para API. Gestión de usuarios con sistema de roles jerárquicos (SuperAdmin, Admin, User) y dos paneles Filament separados.

## Política de acceso

- **No hay registro público.** Las URLs `/register` y `/panel/register` devuelven 404. Tampoco se llama a `->registration()` en los paneles Filament.
- **Solo dos puntos de entrada**:
  - `/admin/login` para administradores (panel `admin`, requiere rol SuperAdmin).
  - `/panel/login` para usuarios (panel `tenant`).
- **Alta de usuarios**: manual desde el panel admin (Sistema → Users) o por comando `php artisan debug:seed-users` en entorno de desarrollo.
- **Redirección legacy**: `/dashboard` y `/dashboard/*` redirigen a `/panel` (HTTP 301).

### Método `canAccessPanel()`

El modelo `App\Models\User` implementa `Filament\Models\Contracts\FilamentUser` y restringe el acceso por panel:

| Panel | Condición |
|-------|-----------|
| `admin` | Usuario SuperAdmin (`User::isSuperAdmin()`) |
| `tenant` (`/panel`) | Cualquier usuario autenticado |

### Assets de Filament

Las vistas `/admin/login` y `/panel/login` dependen de los assets compilados de Filament que viven en `public/css/filament/`, `public/js/filament/` y `public/fonts/filament/`. Si la página carga sin CSS o el botón "Iniciar sesión" no responde, lo más probable es que estos assets no estén publicados:

```bash
php artisan filament:assets       # publica CSS, JS y fuentes en public/
# o el atajo equivalente:
php artisan filament:upgrade
```

`composer.json` ya está configurado para ejecutar `filament:upgrade` en cada `composer install/update` (sección `post-autoload-dump`), así que en condiciones normales no hace falta lanzarlo a mano — pero conviene saberlo cuando algo va mal en producción.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/User.php` | `users` | Usuario principal |
| `app/Models/UserRole.php` | `user_roles` | Roles de usuario |
| `app/Models/UserSocial.php` | — | Redes sociales del usuario |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Auth/V2/LoginController.php` | API V2 | login, logout |
| `app/Http/Controllers/Api/Auth/V2/RegisterController.php` | API V2 | signup, delete-account |
| `app/Http/Controllers/Api/User/V2/UserController.php` | API V2 | index, show, update, destroy |
| `app/Http/Controllers/Auth/*.php` | Web | Fortify (6 controladores) |
| `app/Http/Controllers/Dashboard/Users/UserController.php` | Dashboard | Admin legacy |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/RecaptchaService.php` | Verificación reCAPTCHA v2/v3 |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/UserResource.php` | Resource JSON usuario |

### FormRequests
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Auth/LoginRequest.php` | Validación login |
| `app/Http/Requests/Api/Auth/RegisterRequest.php` | Validación registro |
| `app/Http/Requests/Api/User/V2/UpdateUserRequest.php` | Validación update usuario |

### Filament
| Archivo | Descripción |
|---------|-------------|
| `app/Filament/Admin/Resources/UserResource.php` | Resource Filament para gestión de usuarios |
| `app/Filament/Admin/Resources/UserResource/Pages/*.php` | Páginas CRUD (List, Create, Edit) |
| `app/Filament/Admin/Pages/Dashboard.php` | Dashboard panel Admin |
| `app/Filament/Tenant/Pages/Dashboard.php` | Dashboard panel Tenant |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/UserRoleEnum.php` | Roles: SuperAdmin(1), Admin(2), User(3) |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/UserPolicy.php` | Política de autorización |
| `app/Traits/BelongsToUser.php` | Trait para relación con usuario |
| `config/fortify.php` | Configuración Fortify |
| `config/sanctum.php` | Configuración Sanctum |

## Campos del modelo User

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `name` | string | Nombre |
| `surname` | string | Apellido |
| `email` | string | Email (hidden en serialización) |
| `password` | string | Contraseña hasheada (hidden) |
| `role_id` | int | FK → `user_roles.id` — rol |
| `email_verified_at` | datetime | Fecha verificación email |
| `remember_token` | string | Token recordar sesión |
| `two_factor_secret` | string | Secret 2FA (hidden) |
| `two_factor_recovery_codes` | string | Códigos recuperación 2FA (hidden) |

## Traits usados por User

- `HasApiTokens` — Laravel Sanctum (tokens API)
- `HasFactory` — Factories para tests
- `Notifiable` — Notificaciones
- `TwoFactorAuthenticatable` — 2FA con Fortify
- `ImageTrait` — Gestión de imagen de perfil

## Sistema de roles

| role_id | Nombre | Descripción |
|---------|--------|-------------|
| 1 | SuperAdmin | Acceso total, panel Admin |
| 2 | Admin | Administración parcial |
| 3 | User | Usuario normal, panel Tenant |

### Métodos de rol en User

| Método | Descripción |
|--------|-------------|
| `isSuperAdmin()` | ¿Es SuperAdmin? (role_id = 1) |
| `isAdmin()` | ¿Es Admin? (role_id ≤ 2) |

### Gate global

```php
Gate::before(function ($user, $ability) {
    if ($user->isSuperAdmin()) return true;
});
```

## Paneles Filament

| Panel | Ruta | Acceso | Descripción |
|-------|------|--------|-------------|
| Admin | `/admin` | Solo SuperAdmin | Gestión completa del sistema |
| Tenant | `/panel` | Cualquier autenticado | Panel personal del usuario |

## Rutas API V2 — Auth

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| POST | `/api/v2/auth/login` | No | api-auth | Iniciar sesión (Sanctum token) |
| POST | `/api/v2/auth/signup` | No | api-auth | Registrar usuario (role=3) |
| POST | `/api/v2/auth/logout` | Sí | api-auth | Cerrar sesión (elimina token) |
| POST | `/api/v2/auth/delete-account` | Sí | api-auth | Eliminar cuenta |

## Rutas API V2 — Users

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| GET | `/api/v2/user` | Sí (Admin) | Listar usuarios paginado |
| GET | `/api/v2/user/{id}` | Sí | Ver usuario |
| PUT | `/api/v2/user/{id}` | Sí (Owner) | Actualizar usuario |
| DELETE | `/api/v2/user/{id}` | Sí (Owner/Admin) | Eliminar usuario |

## Autenticación Web

Vía Laravel Fortify:
- Login: `/login`
- Registro: `/register`
- Reset password: `/forgot-password`
- Verificación email
- Two-Factor Authentication (2FA)

## Usuarios de prueba

Los usuarios de prueba se crean con el seeder `UsersTableSeeder` (superadmin,
admin, user). El comando `debug:seed-users` fue **eliminado en fix_11**.

## UserResource en Filament (correcciones fix_11)

`app/Filament/Admin/Resources/UserResource.php`:

- **Email vacío al editar**: el campo `email` está en `$hidden` del modelo `User`,
  por lo que `attributesToArray()` lo omitía. Se inyecta en
  `EditUser::mutateFormDataBeforeFill()`.
- **Mass assignment UserSocial**: `UserSocial` tiene ahora `$fillable`
  (`user_id`, `social_network_id`, `nick`, `url`) y relación `user()`.
- **Verificación de email**: ya no es un `DateTimePicker`. Es un `Toggle`
  (indicador, deshabilitado si ya verificado) + un `Action` con confirmación que
  establece `email_verified_at = now()`. El campo virtual `is_email_verified` se
  descarta en `EditUser::mutateFormDataBeforeSave()`.
- **Imagen de perfil**: usa `ImageCropperUpload` (disco `public`); avatar centrado.
- **Notificaciones**: la sección ocupa el 100% del ancho (`columnSpanFull`).

