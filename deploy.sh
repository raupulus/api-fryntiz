#!/usr/bin/env bash
##
## @author Raúl Caro Pastorino
## @copyright Copyright © 2018 Raúl Caro Pastorino
## @license https://wwww.gnu.org/licenses/gpl.txt
##

proyecto='api-fryntiz'
repositorio='https://gitlab.com/fryntiz/api-fryntiz.git'

if [[ "$ENV" = '' ]]; then
    echo 'Introduce el entorno sobre el que desplegar'
    read -p 'dev o prod' $ENV
fi

if [[ "$ENV" = 'dev' ]]; then
    ruta='/var/www/html/Proyectos'
    apacheConf="dev-${proyecto}.conf"

    if [[ -d "$ruta" ]]; then
        mkdir -p "$ruta"
    fi

    if [[ ! -d "${ruta}/${proyecto}" ]]; then
        sudo git clone "$repositorio" "${ruta}/${proyecto}"
    elif [[ -d "${HOME}/git/${proyecto}" ]]; then
        sudo ln -s "${ruta}/${proyecto}" "$PWD"
    fi

    sudo chown -R ${USER}:www-data "${ruta}/${proyecto}"
    sudo chmod ug+rw -R "${ruta}/${proyecto}"
elif [[ "$ENV" = 'prod' ]]; then
    ruta='/var/www/html/Publico'
    apacheConf="${proyecto}.conf"

    if [[ -d "$ruta" ]]; then
        mkdir -p "$ruta"
    fi

    sudo -u www-data git clone "$repositorio" "${ruta}/${proyecto}"
    sudo chown -R web:www-data "${ruta}/${proyecto}"
else
    exit 1
fi

sudo cp "${ruta}/${proyecto}/${apacheConf}" '/etc/apache/sites-available'
sudo chmod ug+rw "${ruta}/${proyecto}/desplegar.sh"
sudo chmod ug+rw "${ruta}/${proyecto}/Makefile"

cd ${ruta}/${proyecto}

composer install
composer run-script post-create-project-cmd

read -p '¿Habilitar sitio?' $input
if [[ "$input" = 'y' ]] || [[ "$input" = 'Y' ]]; then
    sudo a2ensite "${apacheConf}.conf"
fi

##read -p '¿Añadir a hosts?' $input
##if [[ "$input" = 'y' ]] || [[ "$input" = 'Y' ]]; then
##    echo "127.0.0.1 ${proyecto}.local"
##fi

exit 0
