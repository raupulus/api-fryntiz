# Módulo: Autenticación y Usuarios (Auth)

Módulo de autenticación con soporte dual: Fortify para web y Sanctum para API. Gestión de usuarios con sistema de roles jerárquicos (SuperAdmin, Admin, User, Editor) y dos paneles Filament separados.

## Política de acceso

- **No hay registro público.** Las URLs `/register` y `/panel/register` devuelven 404. Tampoco se llama a `->registration()` en los paneles Filament.
- **Solo dos puntos de entrada**:
  - `/admin/login` para el panel `admin` (SuperAdmin, Admin o Editor).
  - `/panel/login` para usuarios (panel `tenant`).
- **Alta de usuarios**: manual desde el panel admin (Sistema → Users) o con el seeder `UsersTableSeeder` en entorno de desarrollo (el comando `debug:seed-users` fue eliminado en fix_11).
- **Redirección legacy**: `/dashboard` y `/dashboard/*` redirigen a `/panel` (HTTP 301).

### Método `canAccessPanel()`

El modelo `App\Models\User` implementa `Filament\Models\Contracts\FilamentUser` y restringe el acceso por panel:

| Panel | Condición |
|-------|-----------|
| `admin` | `is_active` y (`isAdmin()` o `isEditor()`) — es decir, SuperAdmin, Admin o Editor |
| `tenant` (`/panel`) | `is_active` (cualquier usuario autenticado activo) |

### reCAPTCHA v3 en el login

Igual que el formulario de contacto (`RecaptchaService`): sin
`RECAPTCHA_SECRET_KEY`/`RECAPTCHA_SITE_KEY` en el entorno no se aplica nada, ni
siquiera se carga el script de Google (desarrollo sin claves configuradas
sigue funcionando igual). Con las claves puestas (producción):

1. `resources/views/filament/components/recaptcha-login-script.blade.php` (vía
   render hook `AUTH_LOGIN_FORM_BEFORE` en ambos `PanelProvider`) carga
   `recaptcha/api.js` y refresca un token cada 100 s (caduca a los 2 min),
   escribiéndolo en un input oculto ligado por `wire:model` a la propiedad
   `recaptchaToken` — no hace falta interceptar el submit del formulario.
2. `App\Filament\Concerns\HasRecaptchaLogin::verifyRecaptcha()` se llama al
   principio de `authenticate()` en ambos Login (`Admin\Pages\Login` y
   `Tenant\Pages\Login`) y corta el intento con el mismo mensaje genérico de
   credenciales incorrectas si el token no es válido — no se distingue
   «bloqueado por captcha» de «contraseña mala».

Política del proyecto: **todo formulario abierto a quien no tiene sesión**
lleva este mismo patrón (activo solo si hay claves). Cubre hoy el contacto y
los dos logins; cualquier formulario público nuevo debe seguirlo también.


#### Si Google no responde, se deja pasar

`RecaptchaService::verify()` devuelve `valid: true` cuando la petición a Google falla —excepción de
red o un status que no sea 2xx— y el envío se acepta.

**Es una decisión, no un descuido.** Si Google no responde no se puede afirmar que quien envía sea un
bot, y no se va a cerrar el acceso al sitio porque un tercero se caiga. En principio no debería
ocurrir; si ocurriera de verdad, la salida sería buscar otro proveedor, no dejar a la gente fuera
mientras tanto.

**Qué vigilar.** Los dos `Log::warning` de `RecaptchaService` («no se ha podido verificar» y
«respuesta no satisfactoria») son la señal de alerta: si aparecen a ráfagas, alguien está provocando
el fallo para saltarse la comprobación. Ahí sí toca mirar.

Hay dos tests en `RecaptchaServiceTest` que fijan este comportamiento, precisamente para que no se
"arregle" sin querer. Ver [decisiones-tecnicas.md](decisiones-tecnicas.md) D10.

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
| `app/Http/Controllers/Api/Auth/V2/TokenController.php` | API V2 | `store` (crea token de sesión), `index` (lista tokens), `storeDeviceToken`, `destroyCurrent`, `destroy` — ver "Rutas API V2 — Tokens" abajo |
| `app/Http/Controllers/Api/Auth/V2/RegisterController.php` | API V2 | Código de alta/baja de cuenta, **sin ruta activa** (ver "Lo que ya no existe") |
| `app/Http/Controllers/Api/User/V2/UserController.php` | API V2 | Solo `me()` — los datos del propio usuario. No hay `index`/`store`/`show`/`update`/`destroy` |
| `app/Http/Controllers/Auth/*.php` | Web | Fortify (6 controladores) |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/RecaptchaService.php` | Verificación reCAPTCHA v2/v3 |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/UserResource.php` | Resource JSON usuario (el email solo se ve si eres su dueño o admin) |
| `app/Http/Resources/V2/ApiTokenResource.php` | Resource JSON de un token (nunca expone el token en claro) |

### FormRequests
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Auth/V2/LoginRequest.php` | Validación de `POST /auth/tokens` |
| `app/Http/Requests/Api/Auth/V2/RegisterRequest.php` | Validación de alta (código sin ruta activa) |
| `app/Http/Requests/Api/Auth/V2/DeleteAccountRequest.php` | Validación de baja (código sin ruta activa) |
| `app/Http/Requests/Api/Auth/V2/IssueDeviceTokenRequest.php` | Validación de `POST /auth/tokens/devices` |

### Filament
| Archivo | Descripción |
|---------|-------------|
| `app/Filament/Admin/Resources/UserResource.php` | Resource Filament para gestión de usuarios |
| `app/Filament/Admin/Resources/UserResource/Pages/*.php` | Páginas CRUD (List, Create, Edit) |
| `app/Filament/Admin/Pages/Dashboard.php` | Dashboard panel Admin |
| `app/Filament/Tenant/Pages/Dashboard.php` | Dashboard panel Tenant |
| `app/Filament/Admin/Pages/Login.php` | Login custom del panel Admin: logging + reCAPTCHA v3 |
| `app/Filament/Tenant/Pages/Login.php` | Login custom del panel Tenant: reCAPTCHA v3 (antes usaba el genérico de Filament) |
| `app/Filament/Concerns/HasRecaptchaLogin.php` | Trait compartido: propiedad `recaptchaToken` + `verifyRecaptcha()` |
| `resources/views/filament/components/recaptcha-login-script.blade.php` | Script de reCAPTCHA v3 + input oculto, inyectado por render hook `AUTH_LOGIN_FORM_BEFORE` |

### Enums
| Archivo | Descripción |
|---------|-------------|
| `app/Enums/UserRoleEnum.php` | Roles: SuperAdmin(1), Admin(2), User(3), Editor(4) |

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
| 1 | SuperAdmin | Acceso total (bypass de `Gate::before`), panel Admin |
| 2 | Admin | Administración operativa, panel Admin. Alcanza los recursos de todos los usuarios **menos los de un SuperAdmin**, y sin bypass: cada policy lo contempla |
| 3 | User | Usuario normal, panel Tenant. Sólo lo suyo |
| 4 | Editor | Panel Admin sin el bypass de SuperAdmin; sus permisos los deciden las Policies. Sólo lo suyo, salvo el contenido de sus plataformas |

### Métodos de rol en User

| Método | Descripción |
|--------|-------------|
| `isSuperAdmin()` | ¿Es SuperAdmin? (role_id = 1) |
| `isAdmin()` | ¿Es SuperAdmin o Admin? (role_id ∈ {1, 2}) |
| `isEditor()` | ¿Es Editor? (role_id = 4) |

### La jerarquía, en una línea

`SuperAdmin` → todo · `Admin` → todo menos lo de un `SuperAdmin` · el resto →
sólo lo suyo.

### Gate global

```php
Gate::before(function ($user, $ability) {
    // Si la petición viene de un token de dispositivo IoT, el atajo NO se
    // aplica: devolver null deja que decida la policy correspondiente.
    if (TokenAbilities::deviceRequest($user)) {
        return null;
    }

    if ($user->isSuperAdmin()) {
        return true;
    }

    return null;
});
```

⚠️ **La excepción de los tokens de dispositivo no es un detalle.** El dueño de
los cacharros es `SuperAdmin`, así que sin ella el token de una estación
heredaría «acceso a todo» y las policies quedarían anuladas justo para el
principal del que hay que defenderse.

De ahí se siguen dos reglas al escribir cualquier policy:

1. **`Gate::before` sólo cubre a `SuperAdmin`.** Un `Admin` no recibe nada de ese
   atajo, así que si la policy no lo contempla explícitamente se queda fuera:
   ve el listado del panel y se lleva un 403 al abrir cualquier ficha ajena.
2. **El bypass de administrador es para administradores con sesión.** Un
   `|| $user->isAdmin()` sin comprobar el token reintroduce por otra puerta el
   agujero que `Gate::before` evita. El patrón correcto está en
   `app/Policies/OwnedResourcePolicy.php`.

## Paneles Filament

| Panel | Ruta | Acceso | Descripción |
|-------|------|--------|-------------|
| Admin | `/admin` | SuperAdmin, Admin o Editor (activos) | Gestión del sistema; solo SuperAdmin tiene bypass total |
| Tenant | `/panel` | Cualquier usuario autenticado y activo | Panel personal del usuario |

## Rutas API V2 — Tokens

**El token es un recurso, no un verbo** (D90). `POST /auth/login` pasó a ser
`POST /auth/tokens`, y con eso listar y revocar salen de los métodos HTTP de
siempre, que es justo lo que necesita el panel de usuario.

| Método | Ruta | Auth | Throttle | Qué hace |
|--------|------|------|----------|----------|
| POST | `/api/v2/auth/tokens` | No | `api-auth` | Crea un token de sesión a partir de email y contraseña. Responde **201** |
| GET | `/api/v2/auth/tokens` | `ability:session` | — | Lista los tokens del usuario |
| POST | `/api/v2/auth/tokens/devices` | `ability:session` | — | Emite un token de dispositivo IoT, acotado por abilities |
| DELETE | `/api/v2/auth/tokens/current` | `ability:session` | — | Revoca el token con el que se llama (el «logout» de siempre) |
| DELETE | `/api/v2/auth/tokens/{token}` | `ability:session` | — | Revoca **otro** token del propio usuario |

> ⚠️ **`ability:session` no es lo mismo que «estar autenticado».** Un token de
> dispositivo IoT está autenticado y **no** puede tocar estas rutas: no lleva esa
> ability. Es lo que impide que, robando el token de un sensor, se listen y
> revoquen los tokens de la persona.

### Lo que ya no existe, y por qué

| Ruta que había | Qué pasó |
|---|---|
| `POST /api/v2/auth/login` | Es `POST /auth/tokens` |
| `POST /api/v2/auth/logout` | Es `DELETE /auth/tokens/current` |
| `POST /api/v2/auth/signup` | **Retirada.** El alta de usuarios se hace desde Filament |
| `POST /api/v2/auth/delete-account` | **Retirada** (auditoría A1): borraba la cuenta y **todos** los tokens sin pedir la contraseña, así que el token de cualquier cacharro dejaba al dueño fuera. El código sigue escrito y securizado en `RegisterController`, sin ruta |

## Rutas API V2 — Usuarios

| Método | Ruta | Auth | Qué hace |
|--------|------|------|----------|
| GET | `/api/v2/users/me` | `ability:session` | Los datos del **propio** usuario |

Y no hay más. `GET /user/{id}`, `PUT /user/{id}` y `DELETE /user/{id}` **se
retiraron**: el `GET` no comprobaba nada (auditoría **A4**), así que cualquier
token —el de un sensor incluido— podía enumerar usuarios recorriendo ids. La
gestión de usuarios se hace desde el panel de administración, que sí pasa por
`UserPolicy`.

### Lo que decía esta página y era mentira (N258)

Documentaba las tres rutas `/user/{id}` como si existieran, y en el `DELETE`
ponía «Sí (Owner/Admin)». `UserPolicy::delete()` exige **superadmin**, y además
prohíbe borrarse a uno mismo. Es decir: **la documentación era más permisiva que
el código**, que es la peor dirección posible en la que puede equivocarse. Y el
`GET` ponía «Sí» a secas, igual que el resto de la columna, cuando ahí no se
comprobaba absolutamente nada: el «Sí» ocultaba el agujero haciéndolo pasar por
una decisión.

### `UserPolicy`, para lo que se usa desde el panel

| Método | Quién puede |
|---|---|
| `viewAny` / `create` | Admin |
| `view` / `update` | Admin, o el propio usuario. Y a un superadmin sólo lo toca otro superadmin |
| `delete` / `forceDelete` | **Sólo superadmin**, y nunca a sí mismo |
| `restore` | Admin |

### `ApiTokenPolicy`, el mismo criterio aplicado a los tokens

Emitir un token es repartir una identidad, así que aquí la regla de «Admin llega
a todo menos a lo de un SuperAdmin» no es cortesía: es la diferencia entre
administrar y escalar.

| Método | Quién puede |
|---|---|
| `viewAny` / `create` | Admin, con la lista de usuarios acotada a los que puede administrar |
| `view` / `update` / `delete` | Admin, **salvo si el token es de un SuperAdmin** |
| Cualquiera, desde un token de dispositivo | Nadie. Un cacharro no administra tokens, ni el suyo |

`ApiTokenResource` acompaña a la policy en tres sitios, porque la policy sola no
llega: `getEloquentQuery()` oculta los tokens de `SuperAdmin` de la tabla, el
`Select` de usuario los excluye del formulario, y la acción en lote
`revoke_user` se acota a los usuarios alcanzables. Sin lo último bastaba
seleccionar un token cualquiera para dejar sin tokens a su dueño entero.

## Autenticación Web

Vía Laravel Fortify (sin registro público — `/register` y `/panel/register` devuelven 404):
- Login: `/login` (además `/admin/login` y `/panel/login`)
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

---

## Deuda de seguridad: estado tras la fase 3

Lo que había abierto el 2026-08-19 y qué pasó con cada cosa. Verificado contra el
código el 2026-08-30.

| Lo que había | Estado |
|---|---|
| El login emitía tokens **con ability `*`** | ✅ `TokenController::store()` los emite con `ability:session`, y los de dispositivo sólo con las de su módulo |
| `UserController::show()` **sin `authorize()`** | ✅ La ruta se retiró entera (A4). El controlador sólo tiene `me()` |
| `StoreUserRequest` aceptaba `role_id` de cualquier admin | ✅ Retirado con las rutas de gestión de usuarios |
| Política de contraseñas débil (`min:8`) | ✅ `Password::defaults()` en `AppServiceProvider`: mínimo 12, mayúsculas y minúsculas, números, y `uncompromised()` en producción |
| `UserResource` accedía a `role->name` sin protección | ✅ `whenLoaded('role', …)` |
| `canAccessPanel()` dejaba entrar a cualquiera al panel **admin** | ✅ Exige `is_active` **y** (admin o editor). El panel tenant (`/panel`) sí es para cualquier usuario activo: es su panel |

### Lo que sigue así, y es a propósito

| Qué | Por qué |
|---|---|
| `config('sanctum.expiration') === null` | **Los tokens de dispositivo IoT no caducan a propósito.** Un sensor en un tejado no puede renovar un token cada 30 días. Su seguridad son las abilities, no el tiempo. Los tokens de **persona** sí caducan: `TokenController` les pone `now()->addDays(config('auth.api_session_days'))` al crearlos |

### Lo único que queda por vigilar

`User::$fillable` sigue incluyendo `role_id` y `email_verified_at`. Hoy no es
explotable —no hay ningún endpoint que haga `User::create($request->all())`, y
`RegisterController` fija el rol a mano— pero es una mina para el día en que
alguien escriba ese endpoint sin pensarlo. Anotado en
`docs/planning/` (local, fuera de git).

## 🔒 Decisión de diseño: no hay recuperación de contraseña

**Decidido el 2026-08-19. Es intencional, no es un olvido ni una funcionalidad pendiente.**

El sistema **no expone ningún flujo de "he olvidado mi contraseña"**. No hay `/forgot-password`
funcional, ni enlace de recuperación en los logins de los paneles.

### Motivo

El usuario principal de la plataforma es el propio administrador. Un flujo público de recuperación
es superficie de ataque —enumeración de usuarios, envío masivo de correos, tokens de reset
interceptables— que **aquí no aporta ninguna utilidad**.

### Cómo se recupera una contraseña

**Solo el administrador**, desde el panel Filament (`/admin` → Sistema → Users):

1. Restablecer la contraseña de un usuario.
2. Opcionalmente, enviarle la nueva contraseña por email.

Si se pierde la contraseña del administrador, se restablece por consola en el servidor
(`php artisan tinker`).

### Implicaciones técnicas

| Elemento | Estado que debe mantenerse |
|----------|---------------------------|
| `Features::resetPasswords()` en `config/fortify.php` | **Comentado / desactivado** |
| `->passwordReset()` en los paneles Filament | **No activar en ninguno** |
| `resources/views/auth/forgot-password.blade.php` | Eliminar (huérfana) |
| `resources/views/auth/reset-password.blade.php` | Eliminar (huérfana) |
| `Fortify::resetUserPasswordsUsing()` | Revisar: registrado sobre una feature desactivada |

**No reactivar estas rutas** sin una decisión explícita que revierta esta.

---

> Creado: 2026-05-25 · Última revisión: 2026-09-05
