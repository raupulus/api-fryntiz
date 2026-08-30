---
name: module-development
description: >-
  Flujo de trabajo estricto paso a paso para la creación e integración de nuevos
  módulos completos en Api Raupulus. Cárgala SIEMPRE que vayas a construir un
  módulo desde cero o ampliar un dominio existente abarcando migración, modelo,
  factory, panel Filament, policy, controlador, vistas Blade, seeders, tests y
  documentación técnica.
---

# Desarrollo de Módulos en Api Raupulus

Este documento define el protocolo secuencial obligatorio de 10 pasos para crear e integrar nuevos módulos en la plataforma, garantizando que el código resultante sea 100% homogéneo, comprobable y documentado.

---

## Flujo de Trabajo Secuencial (10 Pasos)

### Paso 1: Migración de Base de Datos (`database/migrations/`)
- Este proyecto usa **PostgreSQL exclusivamente** (ver skill `postgresql-migrations`): no escribas artefactos pensados para MySQL/MariaDB (`engine`, `charset`, `collation` de tabla) ni los copies de migraciones legacy.
- **Obligatorio al 100%**: Comentarios explicativos en la tabla y en **cada una de las columnas**:
  ```php
  $table->comment('Almacena los registros del módulo...');
  $table->string('title')->comment('Título del registro');
  $table->string('slug')->unique()->comment('Slug amigable para URLs');
  ```
- Definir claves foráneas explícitas con cascadas coherentes e índices en tablas pivote.

### Paso 2: Modelo Eloquent (`app/Models/[Modulo]/[Modelo].php`)
- `declare(strict_types=1);` en cabecera.
- **Docblock PHPDoc exhaustivo** a nivel de clase (`@property`, `@property-read`, `@method static Builder query()`, `@mixin \Eloquent`).
- Configuración de `$fillable` y `$casts` (o método nativo `casts(): array`).
- Métodos de relación Eloquent estrictamente tipados (`BelongsTo`, `HasMany`, etc.) con genéricos PHPDoc.
- Accesores dinámicos para imágenes y multimedia (`Storage::disk('public')->url(...)` o `asset(...)`), prohibiendo URLs absolutas rígidas.

### Paso 3: Factory (`database/factories/[Modelo]Factory.php`)
- Definición de datos sintéticos representativos y realistas.
- Soporte para campos traducibles y estados útiles para pruebas:
  ```php
  public function published(): static
  {
      return $this->state(fn () => [
          'is_active' => true,
          'published_at' => now()->subDay(),
      ]);
  }
  ```
- Estados como `draft()`, `featured()`, `inactive()`.

### Paso 4: Recurso Filament (`app/Filament/Admin/Resources/[Modulo]/`)
- Modularizar componentes complejos en subdirectorios si procede (`Schemas/`, `Tables/`, `Pages/`, `Infolists/`).
- En formularios, todas las subidas de imágenes deben usar editor/cropper con proporciones estándar:
  ```php
  ImageCropperUpload::makeImage('image_path')->cover16x9();
  ```
- Etiquetas, descripciones y placeholders **100% en español con tildes**.
- Notificaciones exclusivas con `Filament\Notifications\Notification` sin HTML crudo.

### Paso 5: Policy de Autorización (`app/Policies/[Modelo]Policy.php`)
- Matriz de permisos vinculada a roles (`SuperAdmin`, `Admin`, `User`, `Editor`):
  - SuperAdmin tiene bypass irrestricto vía `Gate::before()`.
  - Admin gestiona operativamente el módulo.
  - Editor sólo gestiona si tiene asignada la plataforma (`platform_user`).
- Registro de la Policy en `AuthServiceProvider.php`.

### Paso 6: Controlador y Vistas Blade Públicas
- Controlador web bajo `app/Http/Controllers/[Modulo]Controller.php` o API bajo `app/Http/Controllers/Api/[Modulo]/V2/`.
- Vistas Blade en `resources/views/[modulo]/` aplicando tokens de diseño Tailwind CSS v4 (`--color-surface`, `--color-primary`, etc.) de `docs/info/DESIGN.md`.
- Reutilización obligatoria de componentes Blade UI (`<x-button>`, `<x-input>`, `<x-alert>`) de `docs/info/COMPONENTS.md`.
- SEO técnico on-page completo (metas dinámicas, OpenGraph, canonical y atributo `alt` en imágenes).
- Textos de interfaz traducibles en `lang/es/` y `lang/en/`.

### Paso 7: Seeder de Demostración
- Crear seeder en `database/seeders/[Modulo]Seeder.php` con datos realistas en español para demostración.
- Integrar la llamada en `DatabaseSeeder.php` y en `ProjectDummyCommand.php`.

### Paso 8: Test de Feature PHPUnit (`tests/Feature/[Modulo]Test.php`)
- Crear pruebas automatizadas con PHPUnit sobre la base de datos real PostgreSQL (`api_raupulus_testing`).
- Probar listados, detalle, validación de formularios, permisos de acceso y persistencia.

### Paso 9: Documentación Técnica Obligatoria
- Crear el documento técnico `docs/info/[modulo].md` siguiendo la plantilla oficial `docs/info/_MODULE_TEMPLATE.md`.
- Incluir obligatoriamente la fecha de revisión al pie: `> Última revisión: YYYY-MM-DD`.
- Registrar el módulo en la tabla de módulos de `docs/info/README.md` y en `AGENTS.md`.

### Paso 10: Verificación y Calidad de Código
- Ejecutar las puertas de calidad obligatorias antes de dar por terminada la tarea:
  ```bash
  vendor/bin/pint --format agent
  php artisan test --compact
  ./vendor/bin/phpstan analyse
  ```

---

> Última revisión: 2026-08-30
