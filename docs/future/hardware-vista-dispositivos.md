# Vista pública de dispositivos hardware

> **Estado:** especificación definida, pendiente de implementar. No bloquea el despliegue.
> **Creado el:** 2026-09-15 · **Última revisión:** 2026-09-17

Diseño de la interfaz pública para el catálogo y monitorización de dispositivos hardware del laboratorio/proyectos (@raupulus). Busca exponer un escaparate tecnológico transparente con telemetría de salud en tiempo real, bajo una estricta política de **privacidad por diseño** (cero exposición de redes, IPs, números de serie o datos privados).

---

## 1. Qué hay hoy (estado actual)

| Pieza | Estado actual | Acción requerida |
|---|---|---|
| `App\Http\Controllers\Hardware\HardwareDeviceController` | Vacío, sin métodos | Implementar `index()` (y opcional `show()`) |
| `resources/views/hardware/index.blade.php` | Esqueleto Blade en blanco | Desarrollar vista de cuadrícula/tarjetas según Design System |
| `resources/views/hardware/show.blade.php` | Esqueleto Blade en blanco | Desarrollar vista de detalle o unificar con modal en index |
| `routes/hardware/web.php` | Sin rutas de dispositivos (sólo `/energy`) | Registrar `GET /hardware` (name: `hardware.index`) |

---

## 2. Política estricta de privacidad (Privacy by Design)

Al tratarse de una vista pública en internet accesible sin autenticación, se establecen reglas estrictas sobre los datos expuestos:

### ⛔ Datos TERMINANTEMENTE PROHIBIDOS (nunca se envían a la vista)
- **Direcciones IP**: Ni `ip_local` ni `ip_public` bajo ninguna circunstancia.
- **Identificadores de fabricación privados**: `serial_number`, direcciones MAC o referencias de pedido/facturación (`ref`, `buy_at`).
- **Datos de usuario / auditoría interna**: `user_id`, `created_at`, `updated_at`, `deleted_at`.
- **Payload crudo del sistema**: Columna `extra` (puede albergar rutas de ficheros locales del sistema operativo, nombres de host o configuraciones de red interna).
- **Tokens o credenciales**: Jamás vincular tokens Sanctum ni credenciales de acceso.
- **Ubicaciones domésticas privadas**: La columna `zone` no debe mostrar ubicaciones íntimas del hogar. Se limitará a `location_type` (Interior / Exterior) o zonas públicas/técnicas (ej: "Azotea", "Taller", "Invernadero").

### ✅ Datos PÚBLICOS permitidos (telemetría genérica y catálogo técnico)
1. **Identidad tecnológica**:
   - Nombre amigable / modelo (`display_name`, `name`, `model`, `brand`).
   - Categoría / Tipo (`HardwareType`: ej. *Servidor SBC*, *Microcontrolador IoT*, *Inversor Solar*, *Estación Meteorológica*).
   - Versión de software/firmware pública (`software_version`, ej. `v2.1.0`).
   - Imagen del dispositivo (`url_image_medium`, optimizada vía `FileThumbnail`).
2. **Disponibilidad y Salud**:
   - Estado online / offline (indicador visual basado en si `last_seen_at` es menor a un umbral, ej. 2 horas).
   - Tiempo desde la última señal (`last_seen_at->diffForHumans()`, ej. "hace 12 minutos").
   - Tiempo de actividad / Uptime (`uptime` formateado en días/horas, ej. "45 días en línea").
3. **Métricas de telemetría de placa / SoC (Stats genéricas)**:
   - `temp`: Temperatura de placa / CPU en ºC (con rango de color seguro/templado/caliente).
   - `cpu`: Porcentaje de carga de procesador (0 - 100%).
   - `ram`: Porcentaje de uso de memoria RAM (0 - 100%).
   - `disk`: Porcentaje de almacenamiento ocupado (0 - 100%).
   - `battery_level` y `battery_voltage`: Batería (para nodos autónomos solares/batería).
4. **Componentes y módulos asociados (`components`)**:
   - Sensores y actuadores acoplados (`HardwareComponent` -> tipo y nombre comercial, ej. *Sensor BME280*, *Módulo LoRa SX1276*, *Display OLED SSD1306*).

---

## 3. Criterio de inclusión de dispositivos

No todos los dispositivos registrados en la base de datos deben ser públicos automáticamente (ej. dispositivos de pruebas o equipos privados de red):
- Se recomienda añadir un campo booleano `is_public` (default `false`) en la tabla `hardware_devices` o un scope `scopePublic($query)` que filtre únicamente dispositivos expresamente autorizados a exponerse en la web pública.
- Solo dispositivos activos y no eliminados (`whereNull('deleted_at')`).

---

## 4. Diseño de la interfaz (UI / UX)

Siguiendo la guía de identidad visual del proyecto (*Obsidian Flux* / *Raupulus Slate*, tokens `@theme`):

- **Cabecera Hero**: Título "Hardware", subtítulo con resumen de dispositivos monitorizados y nodos activos en tiempo real.
- **Filtros rápidos (Alpine.js)**: Filtrado por tipo (`Todos`, `Servidores`, `Nodos IoT`, `Energía`, etc.) o estado (`En línea`, `Todos`).
- **Tarjetas de dispositivo (`grid`)**:
  - Miniatura del dispositivo con badge de estado (punto verde pulsante para online, gris para offline).
  - Título y subtítulo con marca/modelo y tipo.
  - Barra o badges compactos de recursos: Temperatura (`temp`), CPU (`cpu`), RAM (`ram`), Batería (`battery_level`).
  - Chips/pills con los componentes principales acoplados.
- **Ficha / Modal de detalle (`show`)**:
  - Especificaciones técnicas de hardware (SoC, memoria nominal, capacidad de batería, etc.).
  - Lista detallada de componentes y sensores conectados.
  - Descripción pública del proyecto en el que participa el dispositivo (ej: "Nodo sensor meteorológico de radiación solar").

---

## 5. Tareas pendientes de implementación

- [ ] **Migración/Modelo**: Añadir columna `is_public` (boolean, default false) a `hardware_devices` con su migración y scope en el modelo `HardwareDevice`.
- [ ] **Filament Admin**: Añadir el toggle `is_public` en `HardwareDeviceResource` para controlar desde la intranet qué dispositivos son visibles públicamente.
- [ ] **Rutas**: Registrar `GET /hardware` (y opcionalmente `GET /hardware/{device:slug}` o `{device}`) en `routes/hardware/web.php`.
- [ ] **Controlador `HardwareDeviceController`**:
  - Método `index()`: consulta de dispositivos públicos con `with(['type', 'components.type', 'image'])`.
  - Mapeo estricto a DTO o array seguro (garantizando exclusión total de IPs y números de serie).
- [ ] **Vistas Blade**:
  - Maquetar `resources/views/hardware/index.blade.php` con el Design System (tarjetas, tokens `@theme`, modo oscuro).
  - Componente de métricas de telemetría (barras de progreso de CPU/RAM/Disco/Temperatura).
- [ ] **SEO**: Metadatos OpenGraph, Twitter Cards y registro de `/hardware` en el sitemap con prioridad `0.6`.
- [ ] **Tests automatizados**:
  - Test de ruta pública (código 200).
  - Test de exclusión de privacidad: aserción estricta de que la respuesta HTML **nunca** contiene `ip_local`, `ip_public` ni `serial_number`.
  - Test de filtrado: los dispositivos con `is_public = false` no aparecen en el listado.

---

> Creado: 2026-09-15 · Última revisión: 2026-09-17
