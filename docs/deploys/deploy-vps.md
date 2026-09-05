# Despliegue en VPS — Guía paso a paso

Esta guía explica cómo desplegar `api-fryntiz` en un VPS Linux. Cubre dos rutas:

- **Opción A — Docker (recomendada)**: usa los `docker-compose*.yml` del repo.
- **Opción B — Bare-metal**: PHP-FPM + Nginx/Apache + PostgreSQL + Redis instalados directamente en el host.

> Archivos de referencia: [`docker-compose.yml`](../../docker-compose.yml), [`docker-compose.prod.yml`](../../docker-compose.prod.yml), [`docker/app/Dockerfile`](../../docker/app/Dockerfile), [`vhosts/nginx.conf`](nginx.conf), [`vhosts/apache.conf`](apache.conf).

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
| `API_URL` | **Con `https://`** y terminada en `/api`. Ver el aviso de abajo. |
| `SESSION_SECURE_COOKIE` | **`true`.** Sin esto la cookie del panel y el `XSRF-TOKEN` salen sin el flag `Secure`. |
| `FRONTEND_URLS` | Los dominios que consumen la API, **con esquema y separados por comas**. Ver el aviso de abajo. |
| `TRUSTED_PROXIES` | Los rangos del proxy. **Con Cloudflare no basta el valor por defecto** — ver abajo. |
| `RECAPTCHA_SECRET_KEY` | Obligatoria: si está vacía la verificación se desactiva sola y los formularios públicos quedan sin protección. |
| `DB_*` | Credenciales coherentes con `docker-compose.prod.yml` |
| `REDIS_*` | id. |
| `MAIL_*` | SMTP del proveedor (Mailgun/SES/…). |
| `AEMET_API_KEY` | Clave AEMET. Ver [docs/info/apis/aemet.md](../info/apis/aemet.md). |
| `BROADCAST_DRIVER` | `reverb` si se usa WebSockets (ver [websockets.md](../info/websockets.md)). |

#### Las cuatro que fallan en silencio

Estas no dan error ni escriben en el log. La aplicación arranca, responde 200, y
algo no funciona:

| Variable | Qué pasa si está mal |
|---|---|
| `FRONTEND_URLS` vacía | CORS no permite ningún origen. La API responde perfectamente y **el navegador bloquea todas las respuestas**: desde el servidor parece que funciona, desde las webs no funciona nada. El más caro de diagnosticar. |
| `API_URL` con `http://` | Las vistas la meten en el JavaScript del cliente. Sobre una página HTTPS el navegador bloquea esas llamadas por **contenido mixto** y el mapa de vuelos se queda vacío, sin que la petición llegue a salir del navegador. |
| `SESSION_SECURE_COOKIE` sin poner | La cookie de sesión viaja sin `Secure`: basta una petición en claro antes de la redirección para que se vea por la red. |
| `TRUSTED_PROXIES` sin los rangos del proxy real | `request()->ip()` devuelve la IP del proxy **para todo el mundo**, así que los límites por IP (login, contacto, newsletter) pasan a ser un cupo único que se agota en segundos y bloquea a usuarios legítimos. |

> ⚠️ **Con Cloudflare por delante, el valor por defecto de `TRUSTED_PROXIES` no
> vale.** Quien conecta con el servidor es un nodo de Cloudflare, con IP pública,
> así que queda fuera de los rangos privados y deja de ser de confianza. Añade
> los rangos publicados en <https://www.cloudflare.com/ips/> (IPv4 **e** IPv6) y
> revísalos de vez en cuando, porque cambian.

Las cuatro las detecta `php artisan project:check-config` — ver §4.

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

> Opciones completas del comando en [`docs/info/commands.md`](../info/commands.md) §4-ter.

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

Con Nginx + Certbot, ver `docs/deploys/vhosts/nginx.conf` como plantilla y ejecutar:

```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo cp docs/deploys/vhosts/nginx.conf /etc/nginx/sites-available/api-fryntiz
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
DB_NAME=raupulus_api
DB_USER=raupulus_api
DB_PASS='CAMBIA_ESTO'

sudo -u postgres psql -v ON_ERROR_STOP=1 <<SQL
CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASS}';

CREATE DATABASE ${DB_NAME}
    OWNER    ${DB_USER}
    ENCODING 'UTF8'
    LC_COLLATE 'en_US.UTF-8'
    LC_CTYPE   'en_US.UTF-8'
    TEMPLATE template0;
SQL

# El esquema `public` es del rol `pg_database_owner`, no del dueño de la base.
# Se conecta a la base recién creada para cederlo.
sudo -u postgres psql -d ${DB_NAME} -v ON_ERROR_STOP=1 <<SQL
ALTER SCHEMA public OWNER TO ${DB_USER};
GRANT ALL ON SCHEMA public TO ${DB_USER};
SQL
```

Tres detalles que no son adorno:

- **`TEMPLATE template0` con `LC_*` explícitos.** Sin esto la base hereda la
  configuración regional de `template1`, que en un VPS recién instalado suele ser
  `C`. Con esa collation el orden alfabético ignora los acentos y las
  comparaciones de texto no ordenan como espera nadie que escriba en español. Es
  de las cosas que **no se pueden cambiar después** sin volcar y restaurar.
- **`ALTER SCHEMA public OWNER`.** Desde PostgreSQL 15 el esquema `public` ya no
  concede `CREATE` a todo el mundo. Ser dueño de la base **no** implica poder
  crear tablas dentro de su esquema `public`, así que sin esta línea
  `php artisan migrate` falla con `permission denied for schema public`. Es el
  error más común al estrenar servidor con PostgreSQL moderno.
- **`ON_ERROR_STOP=1`.** Sin él `psql` sigue adelante tras un error y termina con
  código 0: el script parece haber funcionado y la base queda a medias.

Comprobar que quedó bien antes de seguir:

```bash
sudo -u postgres psql -d ${DB_NAME} -c "\l ${DB_NAME}"    # encoding y collation
PGPASSWORD="${DB_PASS}" psql -U ${DB_USER} -h 127.0.0.1 -d ${DB_NAME} \
    -c "CREATE TABLE _prueba (id int); DROP TABLE _prueba;"
```

Si lo segundo pasa, `migrate` va a funcionar.

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
sudo cp docs/deploys/vhosts/nginx.conf /etc/nginx/sites-available/api-fryntiz
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

> Antes esta línea acababa en `>> /dev/null 2>&1`. `routes/console.php` se
> molesta en poner `onFailure()` en las 16 tareas «porque antes un fallo no se
> veía en ningún sitio», y el cron que las lanzaba tiraba a la basura todo lo
> que escribieran. Si `schedule:run` no arrancaba —una dependencia rota, un
> permiso, PHP actualizado— no quedaba ni rastro (auditoría AR-D04).
>
> El fichero lo rota `logrotate` como cualquier otro de `storage/logs`.

```bash
sudo crontab -e -u www-data
# Añadir:
* * * * * cd /var/www/api-fryntiz && php artisan schedule:run >> /var/www/api-fryntiz/storage/logs/schedule.log 2>&1
```

---

## 4. Verificación post-deploy

### 4.0 Lo primero: `project:check-config`

```bash
php artisan project:check-config --strict
```

**Ejecútalo DESPUÉS de `config:cache`, no antes.** Es precisamente la caché lo
que hace silenciosos estos fallos: con la configuración cacheada Laravel deja de
cargar el `.env`, así que `env()` devuelve `null` y las variables mal puestas se
ignoran sin un solo aviso.

Devuelve código 1 si algo falla, así que encadénalo con `&&` en el script de
despliegue y no abras al público hasta que pase.

Comprueba:

| Qué | Por qué importa |
|---|---|
| `APP_KEY`, `APP_DEBUG` | Lo evidente. |
| `FRONTEND_URLS` | Vacía o con comodín + credenciales. |
| `TRUSTED_PROXIES` | Vacía o en `*`. |
| `RECAPTCHA_SECRET_KEY` y su umbral | Vacía en producción, o umbral a 0 (que deja pasar a los bots). |
| `SESSION_SECURE_COOKIE`, `APP_URL`, `API_URL` | Cookies y URL sin HTTPS. |
| Colas y broadcast | `QUEUE_CONNECTION=sync`, Reverb mal configurado. |
| **Cobertura de policies del panel** | Que ningún recurso de Filament administre un modelo sin policy. |

La última merece una explicación: **en Filament, un modelo sin policy no queda
cerrado, queda abierto.** `Gate::getPolicyFor()` devuelve `null`, y entonces el
recurso autoriza ver, crear, editar y borrar a cualquiera que llegue al panel —
y a `/admin` llega también el rol `Editor`. Un recurso nuevo mal registrado no da
error: da acceso. Ver [filament-panels.md](../info/filament-panels.md#autorización).

### 4.1 Comprobaciones HTTP

```bash
# 1. Página principal
curl -I https://api.dominio.tld          # debe ser 200

# 2. Login admin
curl -I https://api.dominio.tld/admin/login   # 200

# 3. CORS de verdad, desde uno de los orígenes declarados
curl -I -H "Origin: https://raupulus.dev" https://api.dominio.tld/api/v2/airflight/aircrafts
# debe devolver Access-Control-Allow-Origin con ese mismo valor

# 4. Tests
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
| La API responde 200 pero las webs no ven nada | `FRONTEND_URLS` vacía: lo bloquea el navegador, no el servidor | Rellenarla y `config:cache`. Se ve en la consola del navegador, no en el log |
| El mapa de vuelos sale vacío y sin errores de servidor | `API_URL` con `http://` sobre una página HTTPS (contenido mixto) | Ponerla con `https://` y `config:cache` |
| Rate limit que salta con poquísimo tráfico | `TRUSTED_PROXIES` sin los rangos del proxy: todos comparten la IP | Añadir los rangos reales (Cloudflare incluidos) |
| No se puede iniciar sesión en el panel | `SESSION_SECURE_COOKIE=true` sobre HTTP | Terminar el TLS antes, o dejarla vacía **sólo** en local |

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
docker compose exec postgres pg_dump -U postgres raupulus_api | gzip > backup-$(date +%F).sql.gz

# Bare-metal
sudo -u postgres pg_dump raupulus_api | gzip > backup-$(date +%F).sql.gz
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
- **Headers HTTP**: incluir HSTS, CSP, X-Frame-Options en el reverse proxy (ver `vhosts/nginx.conf`).
- **Actualizaciones**: `unattended-upgrades` para parches críticos del SO.
- **Logs**: rotación con `logrotate` para `storage/logs/laravel.log`.

---

## 8. Referencias

- [README del proyecto](../../README.md)
- [Catálogo de comandos](../info/commands.md)
- [Documentación AEMET](../info/apis/aemet.md)
- [WebSockets en VPS](../info/websockets.md)
- [Configuración de autenticación](../info/auth.md)

> Creado: 2026-05-26 · Última revisión: 2026-09-05
