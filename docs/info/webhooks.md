# WebHooks

Recepción de eventos de sistemas externos. Hoy sólo hay uno previsto: el webhook
de GitLab que dispara el despliegue de esta API.

> [!CAUTION]
> **El módulo está desactivado y, tal como está, no podría funcionar.** Las rutas
> están comentadas y la validación del token **nunca puede dar positivo**. Lo que
> sigue describe lo que hay, no lo que se supone que hace.

## Archivos

| Archivo | Qué es |
|---|---|
| `routes/webhook.php` | Las rutas, **comentadas** |
| `app/Http/Controllers/WebHooks/GitlabWebhookController.php` | `apiDeploy()`. Fuera del grupo `web`, así que sin CSRF |
| `app/Models/WebHooks/GitlabWebhook.php` | DTO en memoria, no toca base de datos. Compara el token entrante con el local |
| `app/Models/WebHooks/SimpleWebhookModel.php` | Base del anterior |
| `scripts/webhooks/api-deploy.sh` | El script que despliega |

## Los tres problemas, verificados el 2026-08-30

### 1. La validación del token no puede dar positivo nunca (N38)

`GitlabWebhook::__construct()` hace:

```php
$this->localToken = config('app.gitlab_token_deploy_api');
```

**Esa clave de configuración no existe.** No está en `config/app.php` ni en
ningún otro fichero de `config/`, y tampoco hay variable de entorno que la
alimente. Así que `localToken` es siempre `null`, e `isValidHash()` sale por su
primera guarda:

```php
if (! $this->token || ! $this->localToken) {
    return false;   // ← siempre por aquí
}
```

Resultado: aunque se descomentaran las rutas, **todo webhook de GitLab
respondería `ko` con un 500** y no desplegaría nada.

Para arreglarlo hacen falta las dos mitades: la clave en `config/` leyendo del
entorno, y la variable en el `.env` de producción. No se hace aquí a propósito:
activar un endpoint que ejecuta un script de despliegue en el servidor es una
decisión, no una errata.

### 2. Los errores del despliegue no se registran

El controlador envuelve `$process->run()` en un `try/catch` y dice capturar
cualquier fallo. No es así, por dos razones:

- `Process::run()` **no lanza excepción cuando el comando falla**: devuelve su
  código de salida. El `catch` sólo salta si el proceso no llega ni a arrancar.
- El comando termina en `&`, o sea que se lanza **en segundo plano**: `run()`
  vuelve inmediatamente y el resultado del script no se conoce nunca.

Un despliegue que falle a medias deja el log limpio y GitLab recibe un `ok`.
Si esto se activa, hay que mirar `$process->getExitCode()` o dejar de decir que
se capturan los errores.

### 3. La segunda ruta comentada apunta al modelo

```php
Route::any('/api-notification', 'App\Http\Controllers\WebHooks\GitlabWebhook@apiNotification')
```

`GitlabWebhook` es el **modelo**, no el controlador, y el namespace que se le
pone es el de los controladores: una clase que no existe. Además
`apiNotification()` tenía el cuerpo vacío y se retiró en la fase 8.

Si algún día hace falta procesar notificaciones de GitLab, se escribe entera.

## Lo que sí está bien

- El webhook va **fuera del grupo `web`**, así que no le afecta CSRF. Correcto:
  GitLab no tiene la sesión.
- El token se lee de la cabecera `X-Gitlab-Token`, que es la que manda GitLab.
- La comparación es de igualdad estricta contra un valor de configuración, no
  contra nada del payload.

> Al activarlo, cambiar `===` por `hash_equals()`: la comparación de un secreto
> con `===` es susceptible de ataque por tiempo. Con un despliegue de por medio
> merece la pena.

---

> Creado: 2026-06-18 · Última revisión: 2026-08-30
