# Módulo: Contador de Pulsaciones (KeyCounter)

Módulo IoT para registrar pulsaciones de teclado y clicks/movimientos de ratón agrupados por sesiones de trabajo, con estadísticas por usuario y dispositivo hardware.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/KeyCounter/BaseKeyCounter.php` | — | Modelo base abstracto con campos comunes |
| `app/Models/KeyCounter/Keyboard.php` | `keycounter_keyboard` | Registros de pulsaciones de teclado |
| `app/Models/KeyCounter/Mouse.php` | `keycounter_mouse` | Registros de clicks y movimientos de ratón |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/KeyCounter/V2/KeyboardController.php` | API V2 | Store registro de teclado |
| `app/Http/Controllers/Api/KeyCounter/V2/MouseController.php` | API V2 | Store registro de ratón |
| `app/Http/Controllers/KeyCounter/KeyCounterController.php` | Web | Frontend público |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/KeyCounter/KeyCounterService.php` | Lógica: store teclado/ratón, estadísticas |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/KeyCounter/KeyboardResource.php` | Resource JSON teclado |
| `app/Http/Resources/V2/KeyCounter/MouseResource.php` | Resource JSON ratón |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/KeyCounter/V2/StoreKeyboardRequest.php` | Validación store teclado |
| `app/Http/Requests/Api/KeyCounter/V2/StoreMouseRequest.php` | Validación store ratón |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/KeyCounterKeyboardPolicy.php` | Política de autorización teclado |
| `app/Policies/KeyCounterMousePolicy.php` | Política de autorización ratón |
| `app/Filament/Concerns/ScopesToOwner.php` | Usado por `KeyboardResource` y `MouseResource`: la tabla del panel sólo muestra las sesiones propias. Sin él, un `Editor` veía las pulsaciones y los horarios de actividad de todos (AR-SEC-02) |
| `app/Console/Commands/KeyCounterGenerateDuration.php` | Comando para recalcular duraciones |
| `app/Console/Commands/KeyCounterRemoveDuplicate.php` | Comando para eliminar duplicados |

## Campos del modelo Keyboard

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_device_id` | int | FK → `hardware_devices.id` — dispositivo |
| `start_at` | datetime | Inicio de la sesión (`Y-m-d H:i:s`) |
| `end_at` | datetime | Fin de la sesión (`Y-m-d H:i:s`) |
| `duration` | int | Duración en segundos (calculado) |
| `pulsations` | int | Total de pulsaciones normales |
| `pulsations_special_keys` | int | Pulsaciones de teclas especiales |
| `pulsation_average` | decimal | Media de pulsaciones por segundo |
| `score` | int | Puntuación calculada |
| `weekday` | int | Día de la semana. **0 = lunes**, 6 = domingo — ver más abajo |

## Campos del modelo Mouse

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_device_id` | int | FK → `hardware_devices.id` — dispositivo |
| `start_at` | datetime | Inicio de la sesión |
| `end_at` | datetime | Fin de la sesión |
| `duration` | int | Duración en segundos (calculado) |
| `clicks_left` | int | Clicks botón izquierdo |
| `clicks_right` | int | Clicks botón derecho |
| `clicks_middle` | int | Clicks botón central |
| `total_clicks` | int | Total de todos los clicks |
| `clicks_average` | int | Media de clicks por segundo |
| `weekday` | int | Día de la semana. **0 = lunes**, 6 = domingo |

## Relaciones

- `Keyboard/Mouse` → `BelongsTo` → `User` (vía `user_id`)
- `Keyboard/Mouse` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`)

## PrepareForValidation

Ambos FormRequests calculan automáticamente en `prepareForValidation()`:
- `user_id` → se asigna desde `auth()->id()`
- `duration` → se calcula como `start_at.diffInSeconds(end_at)`

## Rutas API V2

| Método | Ruta | Auth | Throttle | Qué hace |
|--------|------|------|----------|----------|
| GET | `/api/v2/keycounter/keyboard-sessions` | `ability:keycounter:read` | `api` | Listar sesiones de teclado |
| GET | `/api/v2/keycounter/mouse-sessions` | `ability:keycounter:read` | `api` | Listar sesiones de ratón |
| GET | `/api/v2/keycounter/summary` | `ability:keycounter:read` | `keycounter-summary` (20/min) | Acumulado de un periodo, de un dispositivo |
| POST | `/api/v2/keycounter/keyboard-sessions` | `ability:keycounter:write` | `api-store` | Registrar una sesión de teclado |
| POST | `/api/v2/keycounter/mouse-sessions` | `ability:keycounter:write` | `api-store` | Registrar una sesión de ratón |

> **Cómo leer la columna «Auth».** Un `ability:` **no** es «hace falta estar
> autenticado»: es «hace falta un token **con esa ability concreta**». Un token
> de otro cacharro está autenticado y aquí no entra. Poner «Sí» a secas —que es
> lo que ponía antes esta tabla— borra justo esa diferencia, que es toda la que
> queda si alguien roba el token de un sensor (**N263**).

Leer y escribir son abilities distintas desde el 2026-09-02: el token que se
graba en un teclado sólo tiene que hacer `POST`, y con `keycounter:write` podía
además listar todas las sesiones de su dueño (**AR-S02**).

Contrato completo —cuerpos, respuestas y errores— en
[`docs/info/api/v2/keycounter.md`](api/v2/keycounter.md).

### Sesiones (`keyboard-sessions`, `mouse-sessions`)

Las rutas eran `/keycounter/keyboard` y `/keycounter/mouse` (sólo POST). El
recurso es la **sesión**, así que pasan a `keyboard-sessions` y `mouse-sessions`,
y listar sale gratis con el `GET` de la misma URL (fase 5).

Los dos `POST` admiten, además, una clave opcional `hardware_device_info` con
el último estado del propio dispositivo (batería, temperatura, uptime...). Se
aplica sobre `hardware_device_id` en la misma petición mediante el trait
`App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo`
(mismo mecanismo que `/energy/readings` y `/energy/solar-readings`). Contrato de
campos en [`docs/info/hardware.md`](hardware.md).

### `GET /keycounter/summary` — para reanudar tras un reinicio

**Nueva el 2026-09-07.** Un contador que se apaga, se reinicia o cierra el script
pierde el acumulado del día. Al arrancar pide este resumen y sigue sumando desde
donde estaba, en vez de empezar de cero y enseñar un total falso hasta
medianoche.

```
GET /api/v2/keycounter/summary?device_id=9&date=today
```

| Parámetro | Obligatorio | Valores |
|---|---|---|
| `device_id` | **Sí** | El dispositivo que pregunta. Tiene que ser del usuario del token, y si el token está ligado a un `device:{id}`, ése |
| `date` | No | `today` (por defecto), `month`, `AAAA-MM-DD` o `AAAA-MM` |

**El resumen es de un dispositivo**, el que pregunta. No hay agregado de varios
ni forma de pedirlo: lo que cuente el teclado de al lado no es asunto suyo.

Devuelve **sumas** —`pulsations_total`, `pulsations_total_special_keys`,
`sessions`, `duration_seconds`— para continuar la cuenta, y **máximos**
—`combo_score`, `pulsation_high`— para no perder el récord del periodo, más un
bloque `mouse` con lo equivalente del ratón.

El corte del periodo es por `created_at`, la misma columna que usa la web de
`/keycounter`: así el «total de hoy» del cacharro y el de la web son el mismo
número. Las fechas se guardan y se cortan en UTC.

Límite propio de **20 peticiones por minuto** por token: es una consulta agregada
sobre una tabla de millones de filas y está pensada para una petición por
arranque, no para sondear.

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/keycounter` | Dashboard de estadísticas de pulsaciones |

### Frontend (Fix 5)

- **Aviso de privacidad:** Se muestra al inicio de la vista, advirtiendo que los datos pueden tener variaciones por privacidad.
- **Tarjetas resumen (caché 1h):** Resumen de Keyboard y Mouse con estadísticas de los últimos 100 registros. Claves de caché: `keycounter:keyboard:summary`, `keycounter:mouse:summary`.
- **Widgets estadísticos (caché 24h):** Total global de pulsaciones, mejor año, mejor mes, mejor día, mejor hora, totales por año y **totales por dispositivo**. Cada widget tiene un icono Material Symbols distintivo y una paleta de color propia (amber, yellow, orange, lime, cyan, blue, purple, teal). Clave de caché: `keycounter:widgets`.
- **Dispositivo top:** el equipo con más pulsaciones **no lleva tarjeta propia**. Se destaca su tarjeta dentro de «totales por dispositivo» (morada, icono `devices`) con el distintivo «Dispositivo top» y se muestra sólo el nombre del equipo. Hasta el 2026-09-06 se pintaba además una tarjeta aparte, así que el mismo equipo salía dos veces y descuadraba la rejilla de cinco columnas. `$widgets['top_device']` sigue existiendo —es el primer elemento de `totals_by_device`, que ya viene ordenado— pero la vista sólo lo usa para saber a qué tarjeta ponerle el distintivo.
- **Tablas detalladas eliminadas:** Se eliminaron las tablas con registros individuales por motivos de privacidad.
- **Meses futuros deshabilitados:** En el selector de fecha, los meses futuros se deshabilitan dinámicamente al cambiar el año (JavaScript).

### Formato de las cifras

Todas las cifras de la vista usan `App\Support\Format\Cifra` (importado en la
plantilla con `@use`), no `number_format()` directo:

| Método | Uso | Ejemplo |
|--------|-----|---------|
| `Cifra::miles()` | Tarjetas de «Estadísticas Globales» | `75884812` → `75.885` |
| `Cifra::entera()` | Resumen del mes y tarjetas de Keyboard/Mouse | `1234.56` → `1.235` |

Dos decisiones detrás:

- **Punto de millar español y cero decimales.** Antes salía el separador inglés
  (`75,884,812`) y las medias con dos decimales (`2.0` pulsaciones/min), que a
  esta escala es precisión sin valor.
- **Las cifras acumuladas se recortan a millares.** Los tres últimos dígitos de
  un contador de decenas de millones cambian a cada subida y no dicen nada. Se
  muestran **sin sufijo de escala**, por decisión explícita del 2026-09-06: una
  tarjeta de `75.885` son 75,9 millones de pulsaciones, no setenta y cinco mil.
  Por debajo del millar `Cifra::miles()` devuelve la cifra íntegra, porque
  redondear 812 pulsaciones dejaría un «0» en la tarjeta.

### Paleta de iconos por widget

| Widget | Icono | Color |
|--------|-------|-------|
| Total global pulsaciones | `functions` | amber |
| Mejor año | `military_tech` | yellow |
| Mejor mes | `event` | orange |
| Totales por año | `bar_chart` | blue |
| Mejor día | `calendar_month` | lime |
| Mejor hora | `schedule` | cyan |
| Totales por dispositivo | `dns` | teal |
| Dispositivo top (dentro de los totales por dispositivo) | `devices` + distintivo `emoji_events` | purple |
| Tarjeta resumen Keyboard | `keyboard` | indigo (gradient) |
| Tarjeta resumen Mouse | `mouse` | emerald (gradient) |

### Comando de debug

```bash
php artisan debug:seed-keycounter --count=50
```


## Gráfica de pulsaciones por día

Los colores salen de `BaseKeyCounter::getStatisticsForChart()` y se asignan **por
posición del dispositivo**, no al azar. Antes se elegían con
`rand(0, count($colors) - 1)` en cada carga, con tres efectos a la vez: dos
dispositivos podían salir del mismo color, el color cambiaba al recargar la
página, y la paleta incluía `#000000` —invisible sobre el fondo oscuro— y
`#e8c3b9`, casi blanco, invisible sobre el claro.

La paleta actual son ocho tonos con la luminancia en la franja media, que
contrastan tanto sobre `surface` claro (#f8f9ff) como sobre el oscuro (#0f131d):
es lo que hace falta cuando el mismo `<canvas>` se pinta en los dos temas. Los
dos primeros son los acentos del sistema (`on-tertiary-container` y el turquesa
de Obsidian Flux). La serie «Total» va aparte, en rojo profundo (`#c1121f`, no
`#ff0000` puro) y con trazo más grueso.

Al añadir un color nuevo, comprobar el contraste en **los dos temas** antes de
darlo por bueno.

## Herramientas cliente

Ambas en <https://gitlab.com/raupulus/python-keycounter>:

- **python-keycounter** — el recolector, en Python 3, para **GNU/Linux y macOS**.
- **MacOs KeyCounter** — aplicación complementaria de macOS que muestra las
  estadísticas en la barra superior del sistema.

---

> Creado: 2026-05-25 · Última revisión: 2026-09-07


## El día de la semana: `0` es lunes, y los datos viejos no

`weekday` sigue la convención de `datetime.weekday()` de Python —**0 = lunes …
6 = domingo**—, que es la del cliente que sube las rachas. No es la de Carbon ni
la de JavaScript, donde el 0 es el domingo.

El mapa vive en `App\Enums\KeyCounterWeekdayEnum`, y de ahí salen las etiquetas
de las tablas del panel, las opciones de sus filtros y el `Select` del
formulario. Para calcularlo desde PHP, `KeyCounterWeekdayEnum::deLaFecha()`:
usa `dayOfWeekIso - 1`. **No usar `Carbon::dayOfWeek`**, que da la convención
contraria; con él se sembraban los datos de depuración, que por eso no se
parecían a los de producción.

### El cliente cambió de convención en 2020 y nadie se enteró

Comprobado sobre 1,3 millones de filas reales de `keycounter_keyboard`:

| Periodo | `weekday` coincide con | Proporción |
|---|---|---|
| 2013-01 … 2019-12 | `EXTRACT(DOW …)` → Carbon, **0 = domingo** | 100 % |
| 2020-02 … hoy | `EXTRACT(ISODOW …) - 1` → Python, **0 = lunes** | ~95 % |

El corte es limpio: diciembre de 2019 está al 100 % en la convención vieja y
febrero de 2020 al 95 % en la nueva (en enero de 2020 no hay datos). El 5 % que
no cuadra en el tramo moderno son rachas que cruzan la medianoche: `start_at`
está en UTC y el cliente calcula el día en hora local, así que una racha de
madrugada aparece en UTC como del día anterior. **Ese dato no está mal**, y por
eso no se toca.

Mientras las dos poblaciones convivan, cualquier gráfica o filtro por día de la
semana que abarque las dos épocas mezcla peras con manzanas.

**Normalización:** `php artisan keycounter:fix_weekday`

    php artisan keycounter:fix_weekday                  # sólo cuenta y enseña una muestra
    php artisan keycounter:fix_weekday --write          # escribe
    php artisan keycounter:fix_weekday --until=2020-01-01

Sale **en seco por defecto** porque reescribe datos históricos que no se pueden
reconstruir si se hace mal. Sólo toca las filas que hoy cuadran con la
convención vieja **y no** con la nueva: una fila anterior a 2020 que ya esté
bien, o que no cuadre con ninguna de las dos, se queda como está. Convertir a
ciegas todo lo anterior a la fecha estropearía justamente esas.

Al 2026-09-07, en el volcado de producción: **749 991 rachas de teclado y
219 410 de ratón** en la convención vieja.
