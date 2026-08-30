---
name: filament-admin
description: >-
  Paneles de administración Filament 5 de Api Raupulus. Cárgala SIEMPRE que
  trabajes bajo app/Filament/: crear o editar Resources, Pages, Widgets o
  Clusters; los dos paneles (Admin para SuperAdmin, Tenant para usuarios);
  campos/componentes personalizados (EditorJsField, ImageCropperUpload,
  YoutubeVideoField); concerns/traits Filament; reglas reutilizables de
  app/Support/FilamentValidationRules; o los PanelProviders en
  app/Providers/Filament/. Incluye el tema/branding del panel
  (resources/css/filament/admin/theme.css). Úsala en cuanto el trabajo mencione
  "panel admin", "Filament", "recurso de administración", "tabla del admin",
  "formulario del back-office", "widget" o "tema del panel", aunque no se nombre
  Filament. Para la API pública usa api-rest-v2; para los modelos usa
  laravel-backend; para el criterio visual (paleta/tipografía) usa design-system.
---

# Filament 5 — Paneles Admin y Tenant

Dos paneles, registrados en `app/Providers/Filament/`:

- **AdminPanelProvider** → panel **Admin**, acceso vía `User::canAccessPanel()`:
  usuario **activo** y con rol **SuperAdmin, Admin o Editor** (`isAdmin() ||
  isEditor()`). No es "solo SuperAdmin": un Editor entra igual, aunque sin el
  bypass de `Gate::before` que sí tiene el SuperAdmin.
- **TenantPanelProvider** → panel **Tenant**, cualquier usuario autenticado y
  activo (es su panel personal).

Estructura bajo `app/Filament/`:

```
Admin/        # Resources (≈16 módulos), Pages, Widgets, Clusters
              # Clusters existentes: AirFlight, Energy, KeyCounter, SmartPlant
Tenant/       # Resources, Pages, Widgets (en construcción)
Components/   # Campos personalizados: EditorJsField, ImageCropperUpload, YoutubeVideoField
Concerns/     # Traits Filament: HasImageFileUpload
```

## Reglas del proyecto

1. **Coloca el recurso en el panel correcto.** Funciones de superadmin → `Admin/`.
   Funciones de usuario final → `Tenant/`. No mezcles.
2. **Agrupa por Cluster** los módulos que ya lo usan (AirFlight, Energy,
   KeyCounter, SmartPlant). Un recurso nuevo de esos dominios va dentro de su
   Cluster, no suelto.
3. **Reutiliza componentes y concerns existentes** antes de crear nuevos:
   - Subida de imágenes → trait `HasImageFileUpload` (`app/Filament/Concerns/`)
     y/o `ImageCropperUpload`.
   - Editor enriquecido de contenido → `EditorJsField`.
   - Vídeo de YouTube → `YoutubeVideoField`.
4. **Validación reutilizable**: usa `app/Support/FilamentValidationRules` en lugar
   de repetir reglas en cada formulario. Los mensajes de validación, en español.
5. **Autorización**: respeta las Policies de `app/Policies/` (el panel Admin ya
   está restringido a SuperAdmin; no reimplementes el control de rol a mano).

## Coherencia con el dominio

- Los modelos que edita Filament son los mismos de `app/Models/<Modulo>/`
  (extienden `BaseModel`). Reutiliza enums de `app/Enums/` en selects/badges en
  lugar de literales (`ContentStatusEnum`, `HardwareTypeEnum`, etc.).
- El tema visual del panel Admin vive en
  `resources/css/filament/admin/theme.css` (entrypoint de Vite). Para criterio
  de color/tipografía, ver la skill `design-system`.

## Al terminar

1. `./vendor/bin/pint`.
2. Si el cambio altera el comportamiento de un módulo, **actualiza
   `docs/info/<modulo>.md`** (sección "Configuración Filament").
3. Verifica que el recurso aparece en el panel correcto y respeta la policy.
