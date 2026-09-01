# Despliegue en VPS — Guía paso a paso

Esta guía explica cómo desplegar `api-fryntiz` en un VPS Linux. Cubre dos rutas:

- **Opción A — Docker (recomendada)**: usa los `docker-compose*.yml` del repo.
- **Opción B — Bare-metal**: PHP-FPM + Nginx/Apache + PostgreSQL + Redis instalados directamente en el host.

> Archivos de referencia: [`docker-compose.yml`](../../docker-compose.yml), [`docker-compose.prod.yml`](../../docker-compose.prod.yml), [`docker/app/Dockerfile`](../../docker/app/Dockerfile), [`nginx.conf`](nginx.conf), [`apache.conf`](apache.conf).

---

## 1. Requisitos del VPS

| Recurso | Mínimo | Recomendado |
|---------|--------|-------------|
| CPU | 1 vCPU | 2+ vCPU |
| RAM | 1 GB | 2 GB |
| Disco | 20 GB SSD | 40 GB SSD |
| SO | Debian 12 / Ubuntu 22.04 LTS | id. |

Software a instalar antes de empezar:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git ufw fail2ban

# Firewall mínimo
sudo ufw allow OpenSSH
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

---

## 2. Opción A — Despliegue con Docker

### 2.1 Instalar Docker Engine y Compose

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
newgrp docker
docker compose version
```

### 2.2 Clonar el repositorio

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
cd /var/www
git clone https://github.com/raupulus/api-fryntiz.git api-fryntiz
cd api-fryntiz
git checkout main
```

### 2.3 Configurar variables de entorno

```bash
cp .env.example.production .env
nano .env
```

Variables imprescindibles a revisar:

| Variable | Valor en producción |
|----------|---------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública con HTTPS (`https://api.dominio.tld`) |
| `APP_KEY` | Generar con `php artisan key:generate` (o en el contenedor). |
| `DB_*` | Credenciales coherentes con `docker-compose.prod.yml` |
| `REDIS_*` | id. |
| `MAIL_*` | SMTP del proveedor (Mailgun/SES/…). |
| `AEMET_API_KEY` | Clave AEMET. Ver [docs/info/apis/aemet.md](../info/apis/aemet.md). |
| `BROADCAST_DRIVER` | `reverb` si se usa WebSockets (ver [websockets.md](../info/websockets.md)). |

### 2.4 Levantar los contenedores

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

### 2.5 Inicialización dentro del contenedor `app`

```bash
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=ProductionSeeder --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan event:cache
docker compose exec app php artisan filament:optimize
```

Sobre `ProductionSeeder`: es `DatabaseSeeder` **menos** `UsersTableSeeder`, que
crea `superadmin@domain.es` con la contraseña `123123`. Nunca ejecutes
`db:seed` a secas en un servidor por ese motivo.

Los catálogos que sí carga (idiomas, roles, tipos de hardware, tipos y estados
de contenido, categorías, etiquetas, tecnologías, tipos de impresora, sistemas
de energía…) comprueban uno a uno si la fila ya existe antes de insertarla, y
ninguno hace `truncate` ni `delete`. **Se puede ejecutar sobre una base de datos
ya poblada sin tocar los datos existentes**: sólo añade los catálogos nuevos que
traiga la versión que estás desplegando. Es idempotente, así que repetirlo no
tiene efecto.

### 2.6 Crear el primer usuario administrador

```bash
docker compose exec app php artisan user:make-admin --superadmin
```

Pregunta correo, nombre y contraseña por consola. La contraseña se pide con
entrada oculta, así que no queda en el historial del shell ni en la lista de
procesos; para automatizarlo existe `--email`, `--name` y `--password`.

Exige una contraseña de 12 caracteres con letras, mayúsculas y minúsculas,
números y símbolos, y crea el usuario ya activo y con el correo verificado —sin
esas dos cosas el panel de Filament no le deja entrar—. Necesita que los roles
existan, o sea que va **después** del `ProductionSeeder` del paso anterior.

> El recorte de `tinker` que había aquí antes no funcionaba: escribía en
> `user_role_id`, cuando la columna se llama `role_id`, y creaba el rol sin
> `slug`, que es NOT NULL UNIQUE.

### 2.7 Proxy reverso con TLS (host)

El stack Docker expone el servicio Nginx en un puerto interno. En el host, usar Caddy o Nginx para terminar TLS:

```caddy
api.dominio.tld {
    reverse_proxy localhost:8080
    encode gzip zstd
    header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
}
```

Con Nginx + Certbot, ver `docs/deploys/nginx.conf` como plantilla y ejecutar:

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo cp docs/deploys/nginx.conf /etc/nginx/sites-available/api-fryntiz
sudo ln -s /etc/nginx/sites-available/api-fryntiz /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d api.dominio.tld
```

---

## 3. Opción B — Despliegue bare-metal

### 3.1 Instalar dependencias

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y \
    php8.4-fpm php8.4-cli php8.4-pgsql php8.4-redis php8.4-mbstring \
    php8.4-xml php8.4-zip php8.4-curl php8.4-gd php8.4-bcmath php8.4-intl \
    postgresql-16 redis-server nginx supervisor unzip git

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node + pnpm (el proyecto prefiere pnpm — ver README)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs
sudo npm install -g pnpm
```

### 3.2 Configurar PostgreSQL

```bash
sudo -u postgres psql -c "CREATE USER api_fryntiz WITH PASSWORD 'CAMBIA_ESTO';"
sudo -u postgres psql -c "CREATE DATABASE api_fryntiz OWNER api_fryntiz;"
```

### 3.3 Clonar y configurar

```bash
sudo mkdir -p /var/www/api-fryntiz
sudo chown -R www-data:www-data /var/www/api-fryntiz
sudo -u www-data git clone https://github.com/raupulus/api-fryntiz.git /var/www/api-fryntiz
cd /var/www/api-fryntiz
sudo -u www-data git checkout main
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data pnpm install --frozen-lockfile
sudo -u www-data pnpm run build

sudo -u www-data cp .env.example.production .env
sudo -u www-data nano .env
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --class=ProductionSeeder --force
sudo -u www-data php artisan storage:link

# Un comando por línea: `artisan` sólo ejecuta el primero que recibe, así que
# `artisan config:cache route:cache view:cache event:cache` cacheaba únicamente
# la configuración y trataba el resto como argumentos.
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache
sudo -u www-data php artisan filament:optimize

# Primer administrador (ver la nota del apartado 2.6).
sudo -u www-data php artisan user:make-admin --superadmin
```

Vale aquí lo mismo que en Docker: `db:seed` **a secas nunca** en un servidor
—crearía `superadmin@domain.es` con la contraseña `123123`—; `ProductionSeeder`
sí, que es idempotente y no toca los datos existentes.

### 3.4 Configurar Nginx

```bash
sudo cp docs/deploys/nginx.conf /etc/nginx/sites-available/api-fryntiz
sudo ln -s /etc/nginx/sites-available/api-fryntiz /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d api.dominio.tld
```

### 3.5 Queue worker con Supervisor

Crear `/etc/supervisor/conf.d/api-fryntiz-worker.conf`:

```ini
[program:api-fryntiz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/api-fryntiz/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/api-fryntiz-worker.log
stopwaitsecs=3600
```

Activar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start api-fryntiz-worker:*
```

### 3.6 Scheduler con cron

```bash
sudo crontab -e -u www-data
# Añadir:
* * * * * cd /var/www/api-fryntiz && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Verificación post-deploy

```bash
# 1. Página principal
curl -I https://api.dominio.tld          # debe ser 200

# 2. Login admin
curl -I https://api.dominio.tld/admin/login   # 200

# 3. Tests
docker compose exec app php artisan test --testsuite=Feature   # opción Docker
# o:
sudo -u www-data php artisan test --testsuite=Feature           # opción bare-metal
```

### Errores comunes

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| HTTP 500 sin logs | `storage/` sin permisos | `chown -R www-data:www-data storage bootstrap/cache` |
| HTTP 500 con "key not set" | falta `APP_KEY` | `php artisan key:generate --force` |
| HTTP 502 Bad Gateway | PHP-FPM caído | `systemctl status php8.4-fpm` |
| Login OK pero panel vacío | `php artisan config:cache` antes de editar `.env` | `php artisan config:clear` |

---

## 4-bis. WebSockets (Laravel Reverb)

El servidor de WebSockets es un **demonio aparte**: no basta con desplegar la
aplicación. Está apagado por defecto (`BROADCAST_CONNECTION=null`) y encenderlo
son cuatro cosas —instalar el paquete, poner las variables, levantar el demonio
y montar su sitio virtual—, todas en
[`websockets-reverb.md`](websockets-reverb.md).

> ⚠️ **`REVERB_ALLOWED_ORIGINS` no puede quedarse en `*`.** Es lo único que
> impide que cualquier web abra un socket contra el servidor. Van los dominios
> que consumen la API, separados por comas.

Y en cada despliegue posterior, `sudo systemctl restart api-fryntiz-reverb`: es
un proceso de PHP de larga vida y un `git pull` no le llega.

---

## 5. Backups

### Base de datos

```bash
# Docker
docker compose exec postgres pg_dump -U postgres api_fryntiz | gzip > backup-$(date +%F).sql.gz

# Bare-metal
sudo -u postgres pg_dump api_fryntiz | gzip > backup-$(date +%F).sql.gz
```

Recomendado: cron diario que mande el `.sql.gz` a almacenamiento externo (S3, Backblaze B2, etc).

### Storage

```bash
tar czf storage-$(date +%F).tgz storage/app/public
```

---

## 6. Rollback

Estrategia mínima:

1. Mantener el branch anterior etiquetado: `git tag -a v0.x.y -m "..." && git push --tags`.
2. Ante un fallo en producción:
   ```bash
   cd /var/www/api-fryntiz
   git fetch --tags
   git checkout v0.x.y
   composer install --no-dev --optimize-autoloader
   php artisan migrate:rollback --step=1   # solo si la migración la rompió
   php artisan config:cache
   php artisan route:cache
   sudo systemctl reload php8.4-fpm
   ```

---

## 7. Seguridad y hardening

- **`.env` con permisos restringidos**: `chmod 640 .env` y propietario `www-data`.
- **Firewall**: solo abrir 80/443 al público. SSH solo con keys.
- **Fail2ban**: activar al menos las jaulas `sshd` y `nginx-http-auth`.
- **Headers HTTP**: incluir HSTS, CSP, X-Frame-Options en el reverse proxy (ver `nginx.conf`).
- **Actualizaciones**: `unattended-upgrades` para parches críticos del SO.
- **Logs**: rotación con `logrotate` para `storage/logs/laravel.log`.

---

## 8. Referencias

- [README del proyecto](../../README.md)
- [Catálogo de comandos](../info/commands.md)
- [Documentación AEMET](../info/apis/aemet.md)
- [WebSockets en VPS](../info/websockets.md)
- [Configuración de autenticación](../info/auth.md)
