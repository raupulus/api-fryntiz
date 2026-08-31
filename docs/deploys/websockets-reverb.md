# Despliegue de los WebSockets (Laravel Reverb)

Cómo se pone en marcha el servidor de WebSockets en el VPS. Cómo funciona por
dentro y qué se emite está en [`docs/info/websockets.md`](../info/websockets.md);
aquí sólo está lo que hay que hacer en la máquina.

> Ficheros de referencia: [`nginx-websocket.conf`](nginx-websocket.conf) ·
> [`deploy-vps.md`](deploy-vps.md)

---

## 0. Lo primero, porque es lo único que puede hacer daño

```env
REVERB_ALLOWED_ORIGINS=raupulus.dev,www.raupulus.dev,otrodominio.tld
```

**Nunca `*` en producción.** Esa lista es lo único que impide que **cualquier
web** abra un socket contra este servidor y se quede escuchando las lecturas.
En local vale `*` porque no hay nada que proteger; en el VPS, no.

Van los dominios **sin esquema y sin barra final**, separados por comas: los que
consumen la API y ninguno más. Al añadir una web nueva a la lista de clientes,
hay que acordarse de meterla aquí, porque el fallo se manifiesta como «no me
llegan los datos en vivo» y no como un error.

---

## 1. Antes de empezar

```bash
cd /var/www/api-fryntiz

composer require laravel/reverb
pnpm add laravel-echo pusher-js
pnpm build
```

`php artisan reverb:install` **no hace falta**: `config/reverb.php` y la conexión
`reverb` de `config/broadcasting.php` ya están en el repositorio.

---

## 2. Variables del `.env`

Generar las credenciales de la aplicación:

```bash
php -r 'printf("REVERB_APP_ID=%d\nREVERB_APP_KEY=%s\nREVERB_APP_SECRET=%s\n", random_int(100000,999999), bin2hex(random_bytes(16)), bin2hex(random_bytes(16)));'
```

Y dejar el bloque así:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=<lo generado>
REVERB_APP_KEY=<lo generado>
REVERB_APP_SECRET=<lo generado>

# Lo que ve el NAVEGADOR: el subdominio público, por TLS.
REVERB_HOST=ws.raupulus.dev
REVERB_PORT=443
REVERB_SCHEME=https

# Lo que ESCUCHA el demonio en la máquina. No se expone: delante va nginx.
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080

REVERB_ALLOWED_ORIGINS=raupulus.dev,www.raupulus.dev

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Las `VITE_*` las lee **Vite al compilar**, no en tiempo de ejecución: si se
cambian hay que volver a lanzar `pnpm build`.

Y la cola, para que la emisión no vaya dentro de la petición de la estación:

```env
QUEUE_CONNECTION=database
```

Con `sync` funciona igual, pero la subida de la estación espera a que Reverb
conteste. Por eso la conexión tiene `timeout => 5`.

---

## 3. DNS y certificado

```
ws.raupulus.dev.   A    <IP del VPS>
```

```bash
sudo certbot --nginx -d ws.raupulus.dev
```

Si el dominio va por Cloudflare, el WebSocket necesita el proxy **activado con
WebSockets habilitados** (Network → WebSockets), o el naranja en gris.

---
## 4. El demonio

### systemd (recomendado)

`/etc/systemd/system/api-fryntiz-reverb.service`:

```ini
[Unit]
Description=Api Fryntiz Reverb WebSocket
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/api-fryntiz
ExecStart=/usr/bin/php artisan reverb:start
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now api-fryntiz-reverb
sudo systemctl status api-fryntiz-reverb
```

Sin `--host` ni `--port`: los coge de `REVERB_SERVER_HOST` y
`REVERB_SERVER_PORT` del `.env`. Pasarlos por línea de órdenes es tener la
misma decisión escrita en dos sitios y que un día no coincidan.

### Supervisor (alternativa)

`/etc/supervisor/conf.d/api-fryntiz-reverb.conf`:

```ini
[program:api-fryntiz-reverb]
process_name=%(program_name)s
command=php /var/www/api-fryntiz/artisan reverb:start
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

### En cada despliegue

```bash
sudo systemctl restart api-fryntiz-reverb
```

Es un proceso de PHP de larga vida: mantiene en memoria el código con el que
arrancó, así que un `git pull` no le llega. Mismo problema que los workers de
cola, y misma solución.

---

## 5. Sitio virtual de nginx

El fichero está en [`nginx-websocket.conf`](nginx-websocket.conf). Se instala
así:

```bash
sudo cp docs/deploys/nginx-websocket.conf /etc/nginx/sites-available/ws.raupulus.dev
sudo ln -s /etc/nginx/sites-available/ws.raupulus.dev /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

Las dos cosas de ese fichero que no se pueden olvidar:

- `proxy_set_header Upgrade` y `Connection "upgrade"`. Sin ellas la conexión
  nunca llega a ser un WebSocket: se queda en una petición HTTP normal.
- `proxy_read_timeout 3600s`. Un WebSocket está abierto horas; con el minuto
  por defecto de nginx se corta solo y el cliente reconecta en bucle.

---

## 6. Comprobar que funciona

```bash
# 1. ¿Escucha el demonio?
ss -lntp | grep 8080

# 2. ¿Pasa nginx el upgrade?
curl -I -H "Connection: Upgrade" -H "Upgrade: websocket" https://ws.raupulus.dev
# Se espera un 101 o un 400 de Reverb; un 200 de HTML significa que el
# `location` no está cogiendo la petición.

# 3. Emitir a mano y ver si llega al navegador
php artisan tinker
>>> event(new \App\Events\WeatherStation\ReadingsReceived(3, ['temperatures' => [['value' => 21.4]]]));
```

Para ver qué se emitiría sin levantar nada: `BROADCAST_CONNECTION=log`, y el
evento entero acaba en el log de la aplicación.

---

## 7. Cortafuegos

El demonio escucha en `127.0.0.1`, así que **el 8080 no se abre**:

```bash
sudo ufw status          # 80 y 443 y nada más
```

Si en `ufw status` aparece el 8080, sobra: quiere decir que en algún momento se
arrancó Reverb con `--host=0.0.0.0` y se expuso el demonio sin TLS y sin la
comprobación de orígenes que hace nginx.

---

## 8. Lista de comprobación

- [ ] `composer require laravel/reverb` y `pnpm add laravel-echo pusher-js`
- [ ] `pnpm build` **después** de poner las `VITE_REVERB_*`
- [ ] `REVERB_ALLOWED_ORIGINS` con los dominios reales, **sin `*`**
- [ ] `BROADCAST_CONNECTION=reverb` y `QUEUE_CONNECTION=database`
- [ ] DNS de `ws.` apuntando al VPS y certificado emitido
- [ ] Sitio virtual instalado y `nginx -t` en verde
- [ ] Demonio habilitado en systemd y arrancado
- [ ] `systemctl restart api-fryntiz-reverb` añadido al guion de despliegue
- [ ] El 8080 **no** abierto en el cortafuegos

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
