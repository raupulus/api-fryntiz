#!/usr/bin/env bash
##
## @author Raúl Caro Pastorino
## @copyright Copyright © 2018 Raúl Caro Pastorino
## @license https://wwww.gnu.org/licenses/gpl.txt
##

proyecto='api-fryntiz'
repositorio='https://gitlab.com/fryntiz/api-fryntiz.git'

if [[ $ENV = '' ]]; then
    echo 'Introduce el entorno sobre el que desplegar'
    read -p 'dev o prod' $ENV
fi

if [[ $ENV = 'dev' ]]; then
    ruta='/var/www/html/Proyectos'
    apacheConf="dev-${proyecto}"
    if [[ ! -d "${ruta}/${proyecto}" ]]; then
        sudo git clone "$repositorio" "${ruta}/${proyecto}"
    elif [[ -d "${HOME}/git/${proyecto}" ]]; then
        sudo ln -s "${ruta}/${proyecto}" "$PWD"
    fi
    sudo chown -R ${USER}:www-data "${ruta}/${proyecto}"
    sudo chmod ug+rw -R "${ruta}/${proyecto}"
elif [[ $ENV = 'prod' ]]; then
    ruta='/var/www/html/Publico'
    apacheConf="$proyecto"
    sudo -u www-data git clone "$repositorio" "${ruta}/${proyecto}"
    sudo chown -R web:www-data "${ruta}/${proyecto}"
else
    exit 0
fi

sudo cp "${ruta}/${proyecto}/${apacheConf}" '/etc/apache/sites-available'
sudo chmod ug+rwx "${ruta}/${proyecto}/desplegar.sh"
sudo chmod ug+rwx "${ruta}/${proyecto}/Makefile"
cd ${ruta}/${proyecto}
make install

exit 0


## TODO → Añadir a archivo hosts?
## Habilitar sitio??
