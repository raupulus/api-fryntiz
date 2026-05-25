# Módulo: Autenticación y Usuarios (Auth)

Módulo de autenticación con soporte dual: Fortify para web y Sanctum para API. Gestión de usuarios con sistema de roles jerárquicos (SuperAdmin, Admin, User) y dos paneles Filament separados.

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
| `app/Http/Controllers/Api/Auth/V1/LoginController.php` | API V1 | Login V1 legacy |
| `app/Http/Controllers/Api/Auth/V1/RegisterController.php` | API V1 | Register V1 legacy |
| `app/Http/Controllers/Api/User/V1/UserController.php` | API V1 | Users V1 legacy |
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
