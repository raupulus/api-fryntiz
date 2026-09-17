# GDACS — cómo la usamos

Cómo consume esta plataforma la API de **GDACS** (Global Disaster Alert and
Coordination System): qué pedimos, cada cuánto, qué guardamos y qué hay que
vigilar.

> **Esto no es la documentación de GDACS.** Lo que devuelve cada endpoint, sus
> parámetros, el descubrimiento de que `alertlevel` excluye el nivel verde por
> defecto si no se pide explícito, y la valoración frente a otras APIs
> gratuitas (USGS, NASA EONET, ReliefWeb) está en
> [`docs/future/archived/gdacs-api.md`](../../future/archived/gdacs-api.md) — es donde hay que
> mirar antes de tocar nada aquí.

---

## 1. Las piezas

```
Schedule::command('gdacs:sync')->cron('7,17,27,37,47,57 * * * *')
        │
        ▼
  GdacsSyncCommand
        │
        ▼
  GdacsService::sync()
        │  · GET a config('gdacs.base_url'), filtrado por país/tipos/alertlevel
        │  · descarta lo que no tenga tipo/nivel/id reconocido (falla cerrado)
        │  · calcula distancia (haversine) al punto de referencia
        │  · sólo guarda lo que cae dentro del radio configurado
        ▼
  gdacs_events (upsert por event_type + event_id)
        │
        ▼
  Panel Admin → GDACS (bajo "Módulos", debajo de AEMET)
        · listado de solo lectura + modal de detalle (ViewAction)
        · tarjeta visual arriba de la tabla si hay algo activo (is_current)
```

| Pieza | Qué hace |
|---|---|
| `App\Services\Gdacs\GdacsService` | Toda la lógica: petición HTTP, filtro geográfico en cliente, upsert |
| `App\Console\Commands\Gdacs\GdacsSyncCommand` | `gdacs:sync`. Delgado: sólo llama al servicio y resume el resultado |
| `App\Models\Gdacs\GdacsEvent` | Una fila por `(event_type, event_id)`. Se sobrescribe in-place en cada sondeo, no se acumula histórico de episodios |
| `App\Enums\GdacsEventTypeEnum` | Los seis códigos de desastre (`EQ`, `TC`, `FL`, `VO`, `DR`, `WF`) con etiqueta e icono |
| `App\Enums\GdacsAlertLevelEnum` | `Green`/`Orange`/`Red`, con etiqueta y color de Filament |
| `App\Policies\GdacsEventPolicy` | Sólo lectura, ni un administrador crea/edita/borra un evento a mano |
| `App\Filament\Admin\Resources\Gdacs\GdacsEvents\GdacsEventResource` | El recurso del panel: listado, filtros, `ViewAction` (modal) |
| `App\Filament\Admin\Widgets\GdacsActiveEventsWidget` | La tarjeta de "activo ahora" — no se monta si no hay ninguno |

---

## 2. Config (`config/gdacs.php`)

| Clave | Por defecto | Qué es |
|---|---|---|
| `base_url` | `https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH` | Sin API key, es pública |
| `alert_levels` | `green;orange;red` | **Fijo, no configurable a propósito.** Sin esto, GDACS excluye el verde por defecto — ver `docs/future/archived/gdacs-api.md` |
| `event_types` | Los seis códigos | Qué tipos de desastre se piden |
| `country` | `Spain` (`GDACS_COUNTRY`) | Filtro de la propia API de GDACS (nombre en inglés, no ISO-3). Sin esto, una ventana de días ya toca el límite de 100 registros/página |
| `lookback_days` | `60` (`GDACS_LOOKBACK_DAYS`) | Ventana de `fromdate`. GDACS reescribe el mismo evento in-place mientras sigue activo, así que no hace falta más |
| `reference.lat` / `reference.lon` | Chipiona (`GDACS_REFERENCE_LAT`/`LON`) | Punto contra el que se mide la distancia |
| `reference.radius_km` | `150` (`GDACS_RADIUS_KM`) | Sólo se guarda lo que cae dentro |
| `attribution` | — | GDACS sólo pide citar la fuente; usar este texto donde se muestren los datos |

**Compromiso consciente**: el filtro `country=Spain` es del lado de GDACS, no
sólo el radio en cliente. Un suceso real a menos de 150 km pero cuyo centroide
cayera en Portugal o Marruecos no se vería. Si eso llega a importar algún día,
la solución es sondear también ese país (la API no admite varios países en una
consulta, se comprobó).

---

## 3. La tabla `gdacs_events`

Ver la migración para el detalle completo (todas las columnas están
comentadas). Lo que no está: nada de país/ISO — el filtro de la consulta ya
acota a lo que interesa, así que sería el mismo valor en cada fila.

Un dato que se pidió guardar y **no se rellena todavía**:
`affected_population`. GDACS no lo trae en el listado (`SEARCH`); hace falta
una llamada aparte por evento (`geteventdata`) que hoy no está implementada —
el campo existe para cuando se añada.

---

## 4. Planificador

`gdacs:sync` corre cada 10 minutos (`routes/console.php`), desfasado a los
minutos `7, 17, 27, 37, 47, 57` para evitar el minuto `:00` de solapamiento
con tareas horarias y el mantenimiento del servidor externo. Se ejecuta con
`withoutOverlapping()` y `runInBackground()`. GDACS no documenta ningún límite
de tasa; con el filtro por país la respuesta son unas decenas de sucesos como
mucho, nunca los cientos que obligarían a paginar.

Un fallo de red no tumba el comando: `GdacsService::fetch()` atrapa la
excepción, deja un `Log::error`/`Log::warning` y el comando sale con código 0
(igual que `guardedSave()` de AEMET) — un 204 de GDACS ("sin resultados para
este filtro") se trata como caso normal, no como fallo.

---

## 5. Panel de administración

Recurso de **solo lectura** bajo "Módulos" → GDACS (debajo de AEMET en el
menú). `GdacsEventPolicy` deniega crear/editar/borrar a todo el mundo,
administrador incluido — editar un evento a mano desincroniza la copia local
de lo que GDACS dice de verdad, que es lo único que le da valor a la tabla.

- **Listado**: badges de nivel de alerta (color por `GdacsAlertLevelEnum`),
  tipo, distancia, si sigue activo, fechas. Filtros por nivel, tipo y activo.
- **`ViewAction`** abre un modal de sólo lectura con el detalle completo
  (severidad, coordenadas, enlace al informe de GDACS).
- **Tarjeta de sucesos activos** (`GdacsActiveEventsWidget`), arriba de la
  tabla, sólo cuando hay algún evento con `is_current = true`. CSS propio en
  `resources/css/filament/admin/panel.css` (prefijo `gd-`), nada de utilidades
  de Tailwind en la vista — ver la skill `filament-admin`.

---

## 5bis. Sitio público (2026-09-16)

Hasta esta fecha GDACS sólo se veía en el panel admin. Ahora también en el
frontal público:

- **Banner en `/weatherstation`**, justo encima de "Datos de los sensores":
  la última alerta vigente (`GdacsEvent::active()->orderByDesc('last_modified_at')->first()`),
  coloreada por `alert_level`, o un enlace "no hay alertas GDACS vigentes
  actualmente, ver anteriores" si no hay ninguna activa. Ambos casos enlazan a
  la página de listado.
- **`/weatherstation/gdacs`** (`App\Http\Controllers\Gdacs\GdacsController`):
  listado paginado (50/página) de **todas** las alertas guardadas —activas o
  no—, ordenadas por `from_date` desc, en tarjetas horizontales a ancho
  completo coloreadas por nivel. Fechas en español y hora de Madrid
  (`config('app.display_timezone')`). En el sitemap con prioridad `0.3`
  (menor que el índice del módulo, `0.6`): es un histórico, no la página
  principal.

Lleva la atribución obligatoria (`config('gdacs.attribution')`) tanto en el
banner como en el listado — ver el punto 2.

---

## 6. Comandos
 
| Comando | Qué hace | Cadencia |
|---|---|---|
| `gdacs:sync` | Sondea GDACS y guarda los sucesos dentro del radio | Cada 10 min (desfasado :07, :17...) |

---

> Creado: 2026-09-15 · Última revisión: 2026-09-17
