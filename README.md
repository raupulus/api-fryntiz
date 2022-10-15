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
