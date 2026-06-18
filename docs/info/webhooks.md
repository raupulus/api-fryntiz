# WebHooks

El módulo de Webhooks permite la recepción e integración de eventos provenientes de sistemas externos. Actualmente, la plataforma está diseñada para soportar eventos de GitLab para automatizar el ciclo de integración y despliegue continuo (CI/CD) de la propia API, así como recibir notificaciones.

> [!NOTE]
> Actualmente las rutas en `routes/webhook.php` se encuentran comentadas/deshabilitadas por diseño temporal.

## Archivos Principales

- **Controlador**: `app/Http/Controllers/WebHooks/GitlabWebhookController.php` (Maneja las peticiones entrantes sin pasar por la validación CSRF estándar, ya que usa su propio grupo de rutas).
- **Modelo de Validación**: `app/Models/WebHooks/GitlabWebhook.php` (Hereda de `SimpleWebhookModel`. No almacena en base de datos; actúa como un DTO en memoria para realizar comprobaciones de seguridad comparando el token entrante con el `gitlab_token_deploy_api` del archivo `.env`).
- **Modelo Base**: `app/Models/WebHooks/SimpleWebhookModel.php`
- **Rutas**: `routes/webhook.php`
- **Script Bash**: `scripts/webhooks/api-deploy.sh` (Script ejecutado internamente en el servidor cuando se valida el token de despliegue).

## Lógica y Comportamiento

### Validación de Seguridad (`GitlabWebhook`)
Cuando llega una petición a través de GitLab, se recupera el token de seguridad desde la cabecera HTTP `X-Gitlab-Token`. El modelo `GitlabWebhook` se encarga de llamar a su método `isValidHash()`, el cual valida estrictamente dicho token contra la configuración local. Si el token no es válido o está ausente, el controlador devuelve un error HTTP 500 y finaliza el flujo para evitar ejecuciones no autorizadas.

### Despliegue Automático (`apiDeploy`)
El método `apiDeploy()` en el controlador orquesta la actualización de la aplicación:
1. Recibe el payload completo del evento de GitLab.
2. Comprueba la validez del token de autorización.
3. Si la verificación es exitosa, se invoca de manera asíncrona un proceso de la terminal (mediante `Symfony\Component\Process\Process`) que lanza el script bash `api-deploy.sh` localizado en la raíz del proyecto.
4. Cualquier error durante la ejecución del comando se captura y se graba en los logs de Laravel sin romper el flujo de la respuesta HTTP, para notificar inmediatamente a GitLab sobre la correcta recepción.

### Notificaciones (`apiNotification`)
(Mantenido como estructura) Método concebido para procesar alertas de actividad de repositorios (ej. notificar a un bot de Telegram, enviar un correo, registrar una métrica, etc.) al recibir un hook específico.
