#!/usr/bin/env bash
##
## @author Raúl Caro Pastorino
## @copyright Copyright © 2018 Raúl Caro Pastorino
## @license https://wwww.gnu.org/licenses/gpl.txt
##

VERSION="0.0.2"
WORKSCRIPT="$PWD"
ADMIN='web'  ## Nombre del usuario administrador

DIR_APACHECONF='/etc/apache2/sites-available'  ## Donde guarda conf de apache
DIR_WEB=''  ## Directorio publico dentro de la raíz del repositorio

URL1='api.fryntiz.es'      ## Primera url sin www
URL2='www.api.fryntiz.es'  ## Segunda url con www
URL_LOCAL='api.fryntiz.local'  ## Url para entorno de desarrollo


RUTA_DEV='/var/www/Proyect'
RUTA_PROD='/var/www/Public'

PROYECTO='api-fryntiz'  ## Nombre de apacheconf y directorio web
REPOSITORIO='https://gitlab.com/fryntiz/api-fryntiz.git'

DIR_LOG="/var/log/apache2/${PROYECTO}"

##
## Comprueba si se ha declarado entorno para desplegar y si no es así pregunta.
##
setEnv() {
    if [[ "$ENV" = '' ]]; then
        while [[ "$input" != 'dev' ]] || [[ "$input" != 'prod' ]]; do
            echo 'Introduce el entorno sobre el que desplegar'
            read -p 'dev o prod → ' $ENV
        done
    fi

    if [[ "$ENV" = 'dev' ]]; then
        ruta=$RUTA_DEV
        apacheConf="dev-${PROYECTO}.conf"
    elif [[ "$ENV" = 'prod' ]]; then
        ruta=$RUTA_PROD
        apacheConf="${PROYECTO}.conf"
    else
        echo 'Saliendo sin poder detectar el entorno dev|prod'
        exit 1
    fi

    if [[ -d "$ruta" ]]; then
        mkdir -p "$ruta"
    fi
}

##
## Establece permisos para el sitio virtual.
##
permisos() {
    echo 'Aplicando permisos y propietario www-data'
    if [[ "$ENV" = 'prod' ]]; then
        sudo chown -R ${USER}:www-data "${ruta}/${PROYECTO}"
        sudo chmod ug+rw -R "${ruta}/${PROYECTO}"
    elif [[ "$ENV" = 'dev' ]]; then
        sudo chown -R ${ADMIN}:www-data "${ruta}/${PROYECTO}"
    fi
}

##
## Resuelve dependencias para funcionar.
##
dependencias() {
    echo 'Instalando dependencias'
}

##
## Configura el sitio virtual y/o el entorno.
##
configuraciones() {
    echo 'Aplicando configuraciones'

    if [[ ! -d "${ruta}/${PROYECTO}" ]]; then
        sudo ln -s "${ruta}/${PROYECTO}" "$PWD"
    fi
}

##
## Agrega configuración para Virtual Host de apache y resuelve dependencias a él
##
apache() {
    echo 'Agregando configuración de Apache'
    ## Copio la configuración
    sudo cp "${ruta}/${PROYECTO}/${apacheConf}" "$DIR_APACHECONF"

    ## Creo directorio para guardar logs
    if [[ ! -d "$DIR_LOG" ]]; then
        sudo mkdir -p "$DIR_LOG"
    fi

    ## Habilito el sitio
    read -p '¿Habilitar sitio?' $input
    if [[ "$input" = 'y' ]] || [[ "$input" = 'Y' ]]; then
        sudo a2ensite "${apacheConf}.conf"
    fi

    read -p '¿Añadir a hosts? → ' $input
    if [[ "$input" = 'y' ]] || [[ "$input" = 'Y' ]]; then
        echo "127.0.0.1 ${URL_LOCAL}.local | sudo tee -a /etc/hosts"
    fi
}

##
## Resuelve dependencias para funcionar.
##
dependencias() {
    echo 'Instalando dependencias'
    composer install
    composer run-script post-create-project-cmd
}

##
## Recarga servicios configurados para aplicar los cambios
##
recargarServicios() {
    echo 'Reiniciando servicios'
    sudo systemctl reload apache2
}

##
## Configura un certificado para https con ssl mediante certbot
## Cuando la llamada al script recibe el parámetro "-y" se ejecuta sin preguntas
##
certificado() {
    if [[ -f '/usr/bin/certbot' ]]; then
        local SN=''

        if [[ "$1" = '-y' ]]; then
            SN='S'
        else
            read -p "¿Generar certificado ssl para https con certbot? s/N → " SN
        fi

        if [[ "$SN" = 's' ]] || [[ "$SN" = 'S' ]]; then
            sudo certbot --authenticator webroot --installer apache \
                -w "$ruta/$DIR_WEB" \
                -d "$URL1" -d "$URL2"
        fi
    else
        echo "No se ha configurado SSL porque cerbot no se encuentra instalado"
    fi
}

update() {
    cd "$ruta" || exit 1
    echo 'Actualizando Repositorio'

    if [[ "$ENV" = 'prod' ]]; then
        sudo -u www-data git pull
    elif [[ "$ENV" = 'dev' ]]; then
        git pull
    fi

    cd "$WORKSCRIPT" || exit 1
}

setEnv

if [[ "$1" = '-p' ]]; then
    permisos
elif [[ "$1" = '-d' ]]; then
    dependencias
    permisos
elif [[ "$1" = '-c' ]]; then
    configuraciones
    permisos
elif [[ "$1" = '-a' ]]; then
    apache
    recargarServicios
elif [[ "$1" = '-s' ]]; then
    certificado "$2"
    recargarServicios
elif [[ "$1" = '-u' ]]; then
    update
    permisos
elif [[ "$1" = '-f' ]]; then
    permisos
    update
    dependencias
    configuraciones
    apache
    certificado "-y"
    permisos
    recargarServicios
else
    echo "Versión del script: $VERSION"
    echo ""
    echo "-d    Dependencias"
    echo "-p    Permisos"
    echo "-c    Configuraciones"
    echo "-a    Apache"
    echo "-s    Certificado SSL con Cerboot"
    echo "-u    Update Repo and rebuild"
    echo "-f    Despliegue completo sin preguntas"
fi

exit 0
