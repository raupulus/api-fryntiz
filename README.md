# Proyecto: api_fryntiz

Estructura básica para una API php

En este momento se encuentra en un desarrollo muy temprano y es posible que 
contenga algunos errores o incoherencias que iré corrigiendo a medida se vayan
reportando y me sea posible.

Ruta del paquete en packagist:

https://packagist.org/packages/fryntiz/api-slim-template

Ruta del repositorio para el desarrollo:

https://github.com/fryntiz/slim-template.git

## Instalación

Para instalar esta api_fryntiz desde packagist será necesario tener  composer.

Crearemos un proyecto con el cuerpo de la api_fryntiz usando la siguiente orden:

### Última versión estable

La última versión estable es la más fiable y probada pero puede ir por detrás
en cantidad de implementaciones respecto a la rama principal, si no sabes cual
elegir probablemente esta sea la mejor opción.

```bash
    composer create-project fryntiz/api-slim-template nombreProyecto
```

### Última incorporación a la rama principal estable

Utilizará el último commit de la rama master con todas las implementaciones y
correcciones agregadas hasta el momento, puede ser igual que la última estable
o contener pocos cambios respecto a ella. 

Si quieres usar las últimas incorporaciones o testear esta puede ser la mejor
opción.

```bash
    composer create-project fryntiz/api-slim-template:dev-master nombreProyecto
```

### Última incorporación en desarrollo

De la siguiente forma puedes generar un proyecto utilizando el último commit
introducido en la rama desarrollo sobre la que trabajamos.

Esto puede ser una buena opción si quieres colaborar con el desarrollo pero no
lo será si tienes pensado utilizarlo para algo que requiera estabilidad.

```bash
    composer create-project fryntiz/api-slim-template:dev-dev nombreProyecto
```

Esto creara un nuevo directorio llamado **nombreProyecto** con la estructura
necesaria para comenzar a desarrollar en este microframework **Slim**.

## Información sobre Slim

Para más información sobre **Slim** disponemos de sus sitios oficiales:
- Paquete en Packagist → https://packagist.org/packages/slim/slim
- Documentación oficial → https://www.slimframework.com/docs
- Repositorio Oficial → https://github.com/slimphp/Slim
