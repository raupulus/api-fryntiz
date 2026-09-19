# Selector y Buscador de Vídeos de YouTube

Sistema desacoplado y seguro para buscar y asociar vídeos de YouTube a contenidos (`Content`) desde el panel administrativo de Filament (`YoutubeVideoField`).

Toda la comunicación con la API de Google Cloud (YouTube Data API v3) se realiza **exclusivamente desde el backend**, protegiendo la clave de API privada (`GOOGLE_API_KEY`), aplicando control de acceso por roles, limitación de tasa (rate limiting) y una capa de caché en servidor para no agotar la cuota diaria de Google.

---

## 1. Archivos principales

| Archivo | Tipo | Descripción |
|---|---|---|
| `app/Services/YouTube/YouTubeService.php` | Servicio | Interacción con YouTube Data API v3, gestión de errores y caché inteligente (30 min) |
| `app/Http/Controllers/Admin/YouTubeSearchController.php` | Controlador | Endpoint administrativo `/admin/youtube/search` protegido por autenticación, gate y throttle |
| `app/Filament/Components/YoutubeVideoField.php` | Componente Filament | Campo personalizado de formulario para seleccionar vídeos por canal o ID directo |
| `resources/views/filament/components/youtube-video-field.blade.php` | Vista Blade | Maquetación del campo, integración con Alpine.js y modal de búsqueda |
| `resources/js/youtube-video-search.js` | Script JS | Lógica del modal de búsqueda: debounce, paginación por tokens y selección |
| `resources/css/youtube-video-search-tailwind.css` | CSS | Estilos del modal y vista previa del reproductor |
| `tests/Feature/YouTube/YouTubeSearchTest.php` | Tests | Suite de pruebas para el servicio, controlador, caché y permisos |

---

## 2. Flujo y Arquitectura de Seguridad

Anteriormente, la clave de API de Google viajaba al navegador en el HTML (`apiKey: @js(...)`) y el cliente invocaba directamente `https://www.googleapis.com`. Con la nueva arquitectura:

```
[Navegador / Modal JS]
       │
       ▼ (1) GET /admin/youtube/search?q=...&channel_id=...&page_token=... (cookie de sesión)
[Backend / YouTubeSearchController]
       │
       ├─► (2) Middleware 'auth': exige usuario activo autenticado
       ├─► (3) Gate 'access-youtube-search': SuperAdmin, Admin o Editor
       ├─► (4) Middleware 'throttle:30,1': máx. 30 peticiones/minuto por usuario
       ├─► (5) YouTubeService comprueba Cache (clave única: channel + query + token + limit)
       │
       ▼ (6) Si no está en caché: llamada HTTP a Google con GOOGLE_API_KEY
[Google Cloud YouTube Data API v3]
       │
       ▼ (7) Respuesta JSON retornada al backend y guardada en caché (30 min)
[Navegador / Modal JS]
```

### Ventajas clave:
1. **Cero exposición de credenciales:** Ningún usuario (ni siquiera inspeccionando el código fuente o la pestaña de red) puede obtener la `GOOGLE_API_KEY`.
2. **Ahorro masivo de cuota:** Cada búsqueda (`search.list`) cuesta **100 unidades** de cuota de Google (el tier gratuito incluye 10.000 unidades diarias). La caché en backend (30 minutos por término y canal) evita llamadas duplicadas al paginar, corregir términos o repetir consultas.
3. **Auditoría y control de abusos:** El endpoint solo acepta consultas de editores y administradores activos y cuenta con limitación de frecuencia.

---

## 3. Funcionamiento en Filament Admin

### 3.1. Uso en formularios (`ContentResource`)
En la pestaña **Vídeo y enlaces** de cualquier contenido:

```php
YoutubeVideoField::make('youtube_video_id')
    ->label('Vídeo de YouTube')
    ->helperText('Busca un vídeo en el canal de la plataforma seleccionada y selecciónalo. La URL se genera automáticamente al guardar.')
    ->channels(fn () => Platform::query()
        ->whereNotNull('youtube_channel_id')
        ->pluck('youtube_channel_id', 'id')
        ->toArray())
    ->platformNames(fn () => Platform::query()
        ->whereNotNull('youtube_channel_id')
        ->pluck('title', 'id')
        ->toArray())
    ->columnSpanFull(),
```

### 3.2. Características del selector:
- **Resolución dinámica de canal:** Al cambiar la plataforma del contenido (`data.platform_id`), el buscador filtra automáticamente los vídeos del canal de YouTube asociado a esa plataforma (`platforms.youtube_channel_id`).
- **Introducción manual directa:** Permite escribir o pegar directamente el ID del vídeo (11 caracteres) sin necesidad de abrir el buscador.
- **Vista previa nítida sin autoplay forzado:** Muestra la miniatura fija en alta resolución de YouTube (`i.ytimg.com/vi/{id}/hqdefault.jpg`) con botón de reproducción; el reproductor real en `iframe` solo se monta cuando el usuario pulsa reproducir.
- **Eliminación con confirmación:** Diálogo modal para desvincular el vídeo con un solo clic.

---

## 4. Guía de Portabilidad a Otros Proyectos (Copy / Paste)

Este módulo fue diseñado para ser copiado y reutilizado fácilmente en cualquier otra aplicación Laravel (v11/v12/v13) con Filament:

1. **Copiar archivos del Backend:**
   - `app/Services/YouTube/YouTubeService.php`
   - `app/Http/Controllers/Admin/YouTubeSearchController.php`
2. **Copiar archivos del Frontend:**
   - `app/Filament/Components/YoutubeVideoField.php`
   - `resources/views/filament/components/youtube-video-field.blade.php`
   - `resources/js/youtube-video-search.js` y sus estilos CSS.
3. **Registrar la ruta en `routes/web.php`:**
   ```php
   Route::middleware(['auth', 'can:access-youtube-search'])
       ->prefix('admin/youtube')
       ->name('admin.youtube.')
       ->group(function () {
           Route::get('/search', [YouTubeSearchController::class, 'search'])
               ->middleware('throttle:30,1')
               ->name('search');
       });
   ```
4. **Definir el Gate en `AppServiceProvider`:**
   ```php
   Gate::define('access-youtube-search', fn ($user) => $user->isAdmin() || $user->isEditor());
   ```
5. **Configurar la variable de entorno:**
   ```dotenv
   GOOGLE_API_KEY=AIzaSy...
   ```

---

## 5. Pruebas y Validación

La funcionalidad está cubierta por la suite de pruebas automatizadas [`tests/Feature/YouTube/YouTubeSearchTest.php`](file:///Users/fryntiz/git/3-Fryntiz/api-fryntiz/tests/Feature/YouTube/YouTubeSearchTest.php) (7 tests, 19 aserciones):
- Bloqueo de usuarios no autenticados (401).
- Bloqueo de usuarios estándar sin permisos de edición (403).
- Búsqueda exitosa con administrador mediante simulación `Http::fake()`.
- Verificación de la caché (segunda petición idéntica no ejecuta llamadas HTTP a Google).
- Respuesta amigable si la clave de API no está configurada o si Google devuelve error de cuota (403).
- Verificación estricta de que el HTML generado no expone la clave de Google ni la propiedad `apiKey`.

---

> Creado: 2026-09-19 · Última revisión: 2026-09-19
