# Mapa de rutas Web (frontend, auth y paneles)

> Mapa completo de las rutas que **no** son la API V2 REST: frontend público, autenticación
> web (Fortify) y paneles de administración (Filament). El mapa de la API V2 (`/api/v2/...`)
> vive aparte en [api/v2/README.md](api/v2/README.md) y no se duplica aquí.

## 1. Frontend Web Público (`routes/web.php` y submódulos)
- `GET  /`: Portada corporativa con presentación de servicios y métricas (`home`).
- `GET  /about`: Página propia con la información del proyecto (`about`). **Ya no
  redirige** — hasta el 2026-09-14 era un `302` a la portada; ahora es contenido real
  en `resources/views/about.blade.php`, enlazado desde el footer.
- `GET  /docs`: Documentación interactiva de la API con Swagger/OpenAPI (requiere sesión).
- `GET  /languages/ajax/get/languages`: Consulta asíncrona de idiomas soportados.
- `GET  /file/get/{module}/{id}/{slug?}`: Streaming dinámico de ficheros públicos y privados.
- `GET  /file/thumbnail/get/{module}/{id}/{slug?}`: Generación y entrega de miniaturas WebP.
- `POST /file/upload`: Subida autenticada de archivos al disco configurado.
- `POST /file/delete/{id}`: Eliminación segura y autenticada de archivos y sus miniaturas asociadas.
- `GET  /weatherstation`: Interfaz pública de la estación meteorológica (`weather_station.index`).
- `GET  /weatherstation/sensor/{type}`: Consulta visual de historial y gráficas por sensor meteorológico.
- `GET  /smartplant`: Listado de plantas inteligentes monitorizadas.
- `GET  /smartplant/{smartplant}`: Detalle de sensores de suelo, luz y riego de una planta.
- `GET  /hardware`: Catálogo público de dispositivos hardware y telemetría de salud (`hardware.index`).
- `GET  /hardware/{device:slug}`: Ficha técnica y telemetría en tiempo real de un dispositivo público por slug (`hardware.show`).
- `GET  /hardware/energy`: Monitorización en tiempo real de balance fotovoltaico y consumos (`hardware.energy.index`).
- `GET  /keycounter`: Estadísticas agregadas de pulsaciones y actividad de periféricos.
- `GET  /airflight`: Mapa y radar visual de tráfico aéreo ADS-B.
- `GET  /cv`: Listado de currículums públicos, en tarjetas horizontales (`cv.index`).
  Enlazado desde el home en la tarjeta que antes llevaba a `/panel`.
- `GET  /cv/{slug}`: Vista pública de un currículum, con botón de descarga del PDF
  (`cv.show`).
- `GET  /cv/pdf`: Descarga del currículum predeterminado en PDF (`cv.pdf.default`).
- `GET  /cv/{slug}/pdf`: Descarga de un currículum público concreto en PDF (`cv.pdf`).
  **La ruta antigua `/cv/get/pdf/raupulus/default` ya no existe** — ver `docs/info/cv.md`.
- `ANY  /register*`, `/panel/register*`: **Bloqueo explícito** con respuesta `404 Not Found`.
- `ANY  /dashboard*`: Redirección `301 Moved Permanently` a `/panel`.

## 2. Autenticación Web (Laravel Fortify)
- `GET|POST /login`: Formulario y autenticación web para sesiones de usuario (`login`).
- `POST     /logout`: Cierre de sesión web.
- `GET|POST /two-factor-challenge`: Desafío de autenticación de doble factor.
- `GET|POST /user/two-factor-*`: Gestión de claves de recuperación y códigos QR 2FA.

El registro público está bloqueado: `/register`, `/register/*`, `/panel/register` y
`/panel/register/*` devuelven 404 desde `routes/web.php`. El alta de usuarios es manual
desde el panel admin. `/dashboard` y `/dashboard/*` redirigen a `/panel` (301).

## 3. Paneles de Administración (Filament 5)
- **Panel Admin (`/admin`):** SuperAdmin, Admin y Editor (`AdminPanelProvider`,
  `User::canAccessPanel()`).
  - Login dedicado en `/admin/login` y perfil en `/admin/profile`.
  - 24 Recursos administrativos: Usuarios, Tokens API, Plataformas, Contenidos, Categorías, Tags,
    Tecnologías, Dispositivos Hardware, Componentes, Tipos de Hardware, Tipos de archivo,
    Dispositivos de Energía + Registros de Energía (dos Resources distintos, uno por
    `HardwareDevice` filtrado a energía y otro por `HardwareEnergy`), Plantas Inteligentes
    (Plants + Registers), Vuelos ADS-B (Aviones + Rutas), Currículum, Tipos de repositorio de CV,
    Emails de contacto, Impresoras, Galerías, Teclado y Ratón (KeyCounter). **No hay Resource de
    Estación Meteorológica** — pese a lo que decía una versión anterior de este fichero, ese
    módulo no tiene panel de administración propio hoy.
- **Panel Tenant / Usuario (`/panel`):** Panel para usuarios autenticados (`TenantPanelProvider`).
  - Dashboard de cliente y visualización de recursos propios.

---

> Fuente original: `AGENTS.md` §6-bis (movido aquí el 2026-09-15 para no cargarlo en
> consultas que no tocan el frontend). Mantener actualizado en el mismo commit que
> cualquier cambio en `routes/web.php`, Fortify o los paneles Filament.

---

> Creado: 2026-09-15 · Última revisión: 2026-09-17
