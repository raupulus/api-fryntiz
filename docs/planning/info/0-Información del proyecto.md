# 0-Información del proyecto

Este documento resume la situación actual del proyecto y los objetivos de la gran actualización planificada.

## Resumen del Proyecto
El proyecto es un backend robusto basado en Laravel que gestiona múltiples servicios (Plataformas, Contenidos, Hardware, Estaciones Meteorológicas, etc.). Actualmente utiliza un stack basado en Laravel 8 y AdminLTE.

## Objetivos de la Actualización
- **Modernización**: Migrar a Laravel 13 y PHP 8.3.
- **Interfaz Administrativa**: Sustituir AdminLTE por Filament 5.
- **Frontend**: Mantener vistas Blade pero actualizadas a Tailwind CSS 4 y Alpine.js.
- **Arquitectura**: Adoptar un patrón MVC con Service Layer para lógica compleja.
- **Compatibilidad**: Mantener las APIs "V1" para asegurar el funcionamiento de aplicaciones existentes.

## Estado Actual (Legado)
- **Framework**: Laravel 8.x
- **Panel**: AdminLTE 3
- **Base de Datos**: PostgreSQL
- **APIs**: Múltiples grupos de rutas (Airflight, CV, Hardware, KeyCounter, Smart Plant, Weather Station).

## Estructura de Documentación
- [1-Stack y arquitectura.md](1-Stack%20y%20arquitectura.md): Detalles técnicos del nuevo stack.
- [2-APIs V1.md](2-APIs%20V1.md): Listado de endpoints actuales que deben mantenerse.
- [3-Modelos y Base de Datos.md](3-Modelos%20y%20Base%20de%20Datos.md): Estructura de datos actual y futura.
- [4-Paneles Filament.md](4-Paneles%20Filament.md): Planificación de los paneles Admin y Tenant.
- [5-Frontend Público.md](5-Frontend%20Público.md): Planificación de la parte pública.
