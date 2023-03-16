## Api de propósito general

## Secciones

- Estación Meteorológica
- Contador de pulsaciones para teclado/ratón
- SmartPlant para control y monitorización de plantas
- Radar de vuelos para aviones en las proximidades

## Pendiente

- [ ] Crear comando artisan para primera instalación
- [ ] Crear comando artisan para reparar instalaciones (segundas
  instalaciones o corregir migración)
  
## Instalación

git clone https://gitlab.com/fryntiz/api-fryntiz.git

- Editar .env
- Crear Base de datos (postgresql)

cd /var/www/web/api-fryntiz
sudo -u postgres createdb -O web -T template1 api_fryntiz
cp .env.example .env
nano .env

composer install --no-dev
php artisan migrate
php artisan db:seed
php artisan passport:install
php artisan key:generate

#ln -s $PWD/storage/app/public $PWD/public/storage
php artisan storage:link

npm install --production

sudo chown -R www-data:www-data /var/www/web/api-fryntiz
sudo find /var/www/web/api-fryntiz/ -type f -exec chmod 644 {} \;
sudo find /var/www/web/api-fryntiz/ -type d -exec chmod 775 {} \;

sudo mkdir /var/log/apache2/api-fryntiz
sudo cp /var/www/web/api-fryntiz/api-fryntiz.conf /etc/apache2/sites-available/
sudo a2ensite api-fryntiz.conf

echo '127.0.0.1       fryntiz.dev' | sudo tee -a /etc/hosts
echo '127.0.0.1       api.fryntiz.dev' | sudo tee -a /etc/hosts

sudo systemctl reload apache2

sudo certbot --authenticator webroot --installer apache \
-w /var/www/web/api-fryntiz/public \
-d www.api.fryntiz.dev -d api.fryntiz.dev


sudo certbot certonly --webroot -w /var/www/web/api-fryntiz/public \
-d www.api.fryntiz.dev -d api.fryntiz.dev


## API Estación meteorológica

Ruta de acceso: /weatherstation

## API Contador de teclas (Keycounter)

Ruta de acceso: /keycounter

## API Smart Plant

Ruta de acceso: /smartplant

## API Airflight

Ruta de acceso: /airflight

## Cronjobs

Añadir tarea cron para ejecutar cada minuto el comando de laravel para ejecutar los cronjobs:

```bash
## Laravel api-fryntiz
* * * * * fryntiz cd /var/www/public/api-fryntiz && php artisan schedule:run >> /dev/null 2>&1
```

Así laravel podrá gestionar las tareas programadas controladas por código internamente.

## Websockets

Antes de continuar, todo lo que ves a continuación es para instalar websockets en un servidor con Debian Stable, en caso de usar otro sistema operativo o versión de Debian, puede que no funcione o haya que adaptar los comandos.

De cualquier forma, me salto las partes de configuración para la seguridad por ser de ámbito general y esto es un proyecto de código abierto, no un tutorial de seguridad. Lo mínimo que deberías plantear es tener un certificado SSL válido y que la aplicación no se ejecute como root, si no como un nuevo usuario con permisos limitados al menos para la parte de websockets. También deberías tener un firewall activo y configurado para evitar ataques. En el servidor de nginx/apache o el que uses deberías limitar el acceso a la aplicación por IP o rango de IPs (cualquier mecanismo para evitar accesos no autorizados que consuman recursos y/o puedan escalar acceso).

El ejemplo para la configuración de apache lo puedes encontrar en el archivo *api-fryntiz.conf* en la raíz del proyecto.

Para habilitar los websockets se hay que instalar algún gestor de tareas como *supervisor*

```bash
sudo apt install supervisor
```

Y crear un archivo de configuración en */etc/supervisor/conf.d/api-fryntiz.conf* con el siguiente contenido cambiando ruta y usuario:

```
[program:api_fryntiz_websockets]
command=/usr/bin/php /var/www/public/api-fryntiz/artisan websockets:serve --port 6001
numprocs=1
autostart=true
autorestart=true
user=fryntiz
```

Y reiniciar el servicio de supervisor

```bash
sudo supervisorctl update
sudo supervisorctl restart api_fryntiz_websockets
sudo supervisorctl status api_fryntiz_websockets
```

Más información en [https://beyondco.de/docs/laravel-websockets/getting-started/installation](https://beyondco.de/docs/laravel-websockets/getting-started/installation)


Incrementar límites de conexiones al websockets, esto normalmente es un límite del sistema operativo, en este caso Debian Stable:
    
```bash
sudo nano  /etc/security/limits.d/laravel-echo.conf
```

Cambia el nombre del archivo y del usuario por el que corresponda, en este caso es laravel-echo como ejemplo.

Dentro deberá tener el siguiente contenido adaptado a tu caso:

```bash
#<domain>    <type>  <item>    <value>

laravel-echo       soft    nofile        10000
```

Suele estar limitado a 1024.

Para más seguridad y evitar problemas, es mejor haber utilizado un usuario específico para la aplicación y no el usuario root o cualquier otro administrador.
