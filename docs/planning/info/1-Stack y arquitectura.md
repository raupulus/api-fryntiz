# 1-Stack y arquitectura

Este documento detalla el stack tecnológico y la arquitectura planificada para la actualización del proyecto a Laravel 13 y Filament 5.

## Stack Tecnológico Futuro

### Lenguaje y Framework
- **PHP 8.3**: Versión mínima requerida para Laravel 13.
- **Laravel 13**: Versión core del proyecto.

### Frontend
- **Filament 5**: Panel de administración y backoffice avanzado.
- **Livewire 4**: Integrado directamente con Filament 5 para componentes reactivos.
- **Tailwind CSS 4**: Estilos modernos y optimizados.
- **Alpine.js**: Interactividad ligera en el frontend público.
- **Blade**: Motor de plantillas principal para el frontend público.

### Infraestructura y Servicios
- **PostgreSQL**: Motor de base de datos principal.
- **Storage**: Sistema de archivos local (filesystem local) preparado para migrar a Amazon S3 o sistemas compatibles en el futuro.
- **Caché**: Driver `file` por defecto, con configuración lista para migrar a Redis.
- **Queue**: Driver `sync` por defecto, preparado para migrar a `database` o `Redis`.

## Arquitectura del Proyecto

### Patrón de Diseño
- **MVC (Model-View-Controller)**: Estructura estándar de Laravel.
- **Service Layer**: Implementación de una capa de servicios para centralizar la lógica de negocio compleja, desacoplándola de los controladores y modelos.

### Convenciones
- **PSR-12**: Estándar de estilo de código PHP.
- **Principios SOLID**: Para asegurar un código mantenible y escalable.
- **Naming Conventions**: Seguimiento estricto de las convenciones de Laravel (CamelCase para clases, snake_case para variables/métodos, singular para modelos, plural para tablas).

### Idioma del Código
- **Código (inglés)**: Variables, métodos, clases y comentarios técnicos.
- **Comentarios y Documentación (español)**: Explicaciones de lógica compleja, comentarios en migraciones (columnas/tablas) y documentación de planificación.

## Lógica de Negocio y Helpers
- **Helpers Legados**: El proyecto cuenta con varios Helpers en `app/Helpers/` (AEMETHelper, ContentHelper, JsonHelper, etc.) que deben ser revisados.
- **Transición a Servicios**: Como parte de la nueva arquitectura, la lógica contenida en estos Helpers y en los controladores pesados se migrará a una **Capa de Servicios** (Service Layer) para mejorar la testabilidad y reutilización del código.

## Organización de Paneles (Filament)
Se requiere la implementación de dos paneles distintos:

1. **Panel Admin (`/admin`)**:
    - Acceso exclusivo para superadministradores.
    - Gestión total de recursos, usuarios globales y configuraciones del sistema.
    - Dashboards estadísticos y gestión de logs.

2. **Panel Tenant/Usuario (`/panel`)**:
    - Panel para usuarios registrados (tenants).
    - Gestión de sus propios recursos (Hardware, Smart Plants, etc.).
    - Perfil de usuario y configuraciones de cuenta.
