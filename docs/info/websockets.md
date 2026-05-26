# WebSockets — Laravel Reverb

> Estado actual: WebSockets **no están habilitados por defecto**. Esta documentación es la referencia para activarlos cuando el módulo correspondiente lo requiera (notificaciones en tiempo real, sincronización de KeyCounter, alertas AEMET, etc.).

## 1. Resumen

El proyecto está preparado para usar **[Laravel Reverb](https://reverb.laravel.com/)** como servidor WebSocket nativo, con **Laravel Echo** en el frontend.

| Componente | Responsabilidad |
|------------|-----------------|
| `laravel/reverb` (backend) | Servidor WebSocket que mantiene las conexiones. |
| `pusher-js` + `laravel-echo` (frontend) | Cliente JS que se conecta a Reverb. |
| `config/broadcasting.php` | Driver de broadcasting (`reverb`). |
| Eventos `ShouldBroadcast` | Eventos PHP que se emiten al canal. |
| `routes/channels.php` | Autorización de canales privados/presence. |

---

## 2. Instalación

### 2.1 Backend (Reverb)

```bash
composer require laravel/reverb
php artisan reverb:install
```

El install:

- Añade `BROADCAST_DRIVER=reverb` y vars `REVERB_*` al `.env`.
- Publica `config/reverb.php`.
- Cambia `config/broadcasting.php` a `'default' => env('BROADCAST_DRIVER', 'reverb')`.

### 2.2 Frontend (Echo)

```bash
pnpm add laravel-echo pusher-js
```

Crear/editar `resources/js/echo.js`:

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

Importarlo desde `resources/js/app.js`:

```js
import './echo';
```

---

## 3. Variables de entorno

```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=local
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend (Vite expone solo las VITE_*)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

En producción cambiar `REVERB_SCHEME=https` y `REVERB_HOST=ws.dominio.tld`.

---

## 4. Levantar el servidor Reverb

### Local

```bash
php artisan reverb:start
# Por defecto escucha en 0.0.0.0:8080
```

### Producción (Supervisor)

Crear `/etc/supervisor/conf.d/api-fryntiz-reverb.conf`:

```ini
[program:api-fryntiz-reverb]
process_name=%(program_name)s
command=php /var/www/api-fryntiz/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/api-fryntiz-reverb.log
stopwaitsecs=10
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start api-fryntiz-reverb
```

### Producción (systemd, alternativa)

`/etc/systemd/system/api-fryntiz-reverb.service`:

```ini
[Unit]
Description=Api Fryntiz Reverb WebSocket
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/api-fryntiz
ExecStart=/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now api-fryntiz-reverb
```

---

## 5. Proxy reverso para WebSockets (Nginx)

Añadir un bloque `location` específico para WSS:

```nginx
server {
    listen 443 ssl http2;
    server_name ws.dominio.tld;

    ssl_certificate     /etc/letsencrypt/live/ws.dominio.tld/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ws.dominio.tld/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket upgrade
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Long-lived connections
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }
}
```

Generar certificado con Certbot:

```bash
sudo certbot --nginx -d ws.dominio.tld
```

---

## 6. Emitir un evento desde Laravel

```php
// app/Events/SensorReadingReceived.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class SensorReadingReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $deviceId, public array $payload) {}

    public function broadcastOn(): Channel
    {
        return new Channel("sensor.{$this->deviceId}");
    }
}
```

Disparar:

```php
event(new SensorReadingReceived($device->id, ['temp' => 21.3]));
```

Escuchar en el frontend:

```js
window.Echo.channel(`sensor.${deviceId}`)
    .listen('SensorReadingReceived', (e) => {
        console.log('Lectura recibida', e);
    });
```

---

## 7. Verificación

```bash
# 1. Servidor levantado
curl -I http://localhost:8080
# Debe responder con headers de Reverb.

# 2. Test handshake WSS desde el navegador
# DevTools → Network → WS → ws://localhost:8080/app/<key>?protocol=7

# 3. Disparar evento manualmente
php artisan tinker --execute='event(new \App\Events\SensorReadingReceived(1, ["temp" => 20]));'
```

---

## 8. Troubleshooting

| Síntoma | Causa | Solución |
|---------|-------|----------|
| Frontend no conecta | `VITE_REVERB_*` no recompilado | `pnpm run build` y limpiar cache del navegador. |
| 502 Bad Gateway al WSS | Nginx sin upgrade headers | revisar `proxy_set_header Upgrade $http_upgrade;` |
| Eventos no llegan | `ShouldBroadcast` falta o cola sin worker | añadir `implements ShouldBroadcast` y `queue:work`. |
| Canal privado falla con 403 | `routes/channels.php` no autoriza | añadir `Broadcast::channel('canal.{id}', fn () => ...)`. |
| Reverb se cae con muchas conexiones | Límite de `ulimit -n` bajo | subir a 65536 en `/etc/security/limits.conf`. |

---

## 9. Referencias

- Documentación oficial: [reverb.laravel.com](https://reverb.laravel.com/)
- Echo: [github.com/laravel/echo](https://github.com/laravel/echo)
- Guía de deploy VPS: [../deploys/deploy-vps.md](../deploys/deploy-vps.md)
