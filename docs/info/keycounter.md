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
| `app/Services/KeyCounter/KeyCounterService.php` | Ingesta: store de teclado/ratón y resumen para reanudar |
| `app/Services/KeyCounter/KeyCounterStatisticsService.php` | Todo lo que enseña la web: gráfica, resúmenes, widgets y totales anuales. Lo comparten el controlador (que sólo lee caché) y `keycounter:warm_cache` (que la escribe) |

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
| `app/Console/Commands/KeyCounterFixWeekdayCommand.php` | Normaliza `weekday` a 0=lunes en las rachas anteriores a 2020 |
| `app/Console/Commands/KeyCounterWarmCacheCommand.php` | Precalienta la caché: `--live` cada hora, sin opciones cada día |
| `app/Support/KeyCounter/KeyCounterCache.php` | Claves y ventanas de caché, en un único sitio |

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
| `weekday` | int | Día de la semana. **0 = domingo**, 6 = sábado — ver más abajo |

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
| `weekday` | int | Día de la semana. **0 = domingo**, 6 = sábado |

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

- **Aviso de privacidad:** Se muestra al inicio de la vista: los datos del día actual son aproximados y pueden mostrar ligeras diferencias con la realidad. **A propósito no dice cada cuánto se actualiza** ni por qué: contar la cadencia es dar la pista que el retardo viene a esconder. El detalle técnico va en «Caché de las estadísticas», no en la página.
- **Tarjetas resumen (caché 1 h):** Resumen de Keyboard y Mouse con estadísticas de los últimos 100 registros. Claves de caché: `keycounter:keyboard:summary`, `keycounter:mouse:summary`.
- **Widgets estadísticos (caché 1 h):** Total global de pulsaciones, mejor año, mejor mes, mejor día, mejor hora, totales por año y **totales por dispositivo**. Cada widget tiene un icono Material Symbols distintivo y una paleta de color propia (amber, yellow, orange, lime, cyan, blue, purple, teal). Clave de caché: `keycounter:widgets`.
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


## El día de la semana: `0` es domingo (2026-09-11)

`weekday` sigue la convención de `Carbon::dayOfWeek` —**0 = domingo …
6 = sábado**—, la misma que JavaScript. Una revisión anterior de esta misma
sección (y del enum) decía justo lo contrario, asumiendo que el cliente subía
las rachas con `datetime.weekday()` de Python (0 = lunes); esa lectura era
errónea y llegó a normalizar datos históricos en la dirección equivocada. **0
es domingo siempre**, sin excepciones por fecha ni por origen del dato.

El mapa vive en `App\Enums\KeyCounterWeekdayEnum`, y de ahí salen las etiquetas
de las tablas del panel, las opciones de sus filtros y el `Select` del
formulario. Para calcularlo desde PHP, `KeyCounterWeekdayEnum::fromDate()` usa
`Carbon::dayOfWeek` directamente — **no** `dayOfWeekIso`, que da la convención
contraria.

### Normalización de datos históricos

`php artisan keycounter:fix_weekday` recalcula `weekday` a partir de
`start_at` para cualquier fila cuyo valor guardado no coincida con el día real
—sea de la época que sea, sin distinguir «convención vieja» ni fecha de
corte—:

    php artisan keycounter:fix_weekday                  # sólo cuenta y enseña una muestra
    php artisan keycounter:fix_weekday --write          # escribe

Sale **en seco por defecto** porque reescribe datos históricos que no se
pueden reconstruir si se hace mal. El valor nuevo sale siempre de la propia
fecha (`EXTRACT(DOW FROM start_at)`), nunca del que ya trajera la fila —así que
también corrige lo que la versión anterior del comando hubiera normalizado mal
en la dirección contraria.

### Historial: esto ya se había corregido una vez, y el asistente lo deshizo

El usuario ya había dejado claro antes de esta fecha que la convención es
**0 = domingo**. Una sesión anterior de este mismo asistente lo ignoró: analizó
1,3 millones de filas reales de `keycounter_keyboard`, encontró que ~95% de las
rachas recientes coincidían con la convención contraria (0 = lunes,
`datetime.weekday()` de Python) y concluyó —sin volver a preguntar— que esa
era la convención "correcta". Con esa conclusión llegó a **reescribir datos
históricos reales en la dirección equivocada** vía `keycounter:fix_weekday`, y
dejó documentado en el propio enum y en esta página que 0 era lunes, como si
fuera un hecho asentado.

El 2026-09-11, al volver a aparecer el tema, el asistente repitió el mismo
error de enfoque: en vez de aplicar la corrección que el usuario ya había
indicado, volvió a analizar los datos en producción, encontró el mismo ~95% a
favor de "0 = lunes" y **se lo devolvió al usuario como si fuera información
nueva que contradijera su petición**, pidiendo confirmación otra vez. Hicieron
falta dos rondas de insistencia explícita del usuario para que se aplicara el
cambio.

La lección, para que no se repita una tercera vez: **qué convención sigue el
dato hoy en producción no dice nada sobre cuál es la convención de diseño
correcta**. Eso lo decide quien es dueño del producto, no una consulta SQL por
muy contundente que sea el porcentaje. Si el usuario ya ha fijado una
convención, un análisis de datos que la contradiga es motivo para sospechar
del *dato* (o de un cliente que la incumple), no para cuestionar la decisión
otra vez.

## Caché de las estadísticas

La página de `/keycounter` agrega trece años de rachas. Sin caché, cada visita
recalcula: **1 261 ms** medidos sobre el volcado real, contra **0,7 ms y ninguna
consulta** cuando ya está guardado.

La caché de este módulo persigue **dos cosas a la vez**, y conviene tenerlas
separadas en la cabeza porque las decisiones raras se entienden sólo con la
segunda:

1. **Que la web no tarde.** Lo caro se calcula una vez.
2. **Que la web no refleje la actividad en tiempo real.** Es una cuestión de
   **privacidad**: quien mire la página no debe poder deducir si se está
   tecleando ahora mismo. La vista lo avisa arriba del todo.

El almacén es **`file`** (`CACHE_STORE=file`). **No hay Redis** y no lo va a
haber hasta terminar la migración de los IoT, así que **no se usa
`Cache::tags()`**: el driver `file` no las soporta. Todo se invalida por clave
explícita, y las claves viven en `App\Support\KeyCounter\KeyCounterCache` para
que no se dupliquen como cadenas sueltas por el controlador y el servicio —que
es la forma clásica de que una invalidación deje de coincidir con lo que guarda:
la página sigue funcionando y sólo enseña datos viejos.

### Las dos reglas

**Un mes cerrado no vuelve a cambiar** → se guarda para siempre.

**Lo vivo tiene como mucho una hora** → y esa hora **la escribe el planificador,
no el visitante**. Es la diferencia entre una caché perezosa y una precalentada:
con `remember()` a secas, el primer visitante de cada hora se come el cálculo
entero y la web sigue tardando justo lo que la caché venía a evitar.

### Qué se guarda y cuánto dura

| Dato | Clave | Ventana | Quién la escribe |
|---|---|---|---|
| Gráfica de un mes **cerrado** | `keycounter:graph:{año}-{mes}` | para siempre | `keycounter:warm_cache` (diario) |
| Gráfica del mes en curso o del anterior | `keycounter:graph:{año}-{mes}` | 1 h | `keycounter:warm_cache --live` (horario) |
| Resumen de teclado | `keycounter:keyboard:summary` | 1 h | `keycounter:warm_cache --live` |
| Resumen de ratón | `keycounter:mouse:summary` | 1 h | `keycounter:warm_cache --live` |
| Widgets | `keycounter:widgets` | 1 h | `keycounter:warm_cache --live` |
| Total de un año **cerrado** | `keycounter:year_total:{año}` | para siempre | el primer visitante |
| Total del año en curso | `keycounter:year_total:{año}` | 1 h | `keycounter:warm_cache --live` |

La hora sale de `KeyCounterCache::FRESH_WINDOW`. Es a la vez el retardo de
privacidad y la cadencia del refresco: **una racha que sube un cacharro tarda
como mucho una hora en verse en la web**.

### El TTL guardado es el doble de la ventana

Lo vivo se guarda con `STORAGE_WINDOW` = 2 h, no con la hora de la ventana. No
es una contradicción, es el margen del cron:

- En marcha normal, el refresco horario **sobrescribe** cada entrada mucho antes
  de que caduque, así que lo que se ve tiene siempre menos de una hora.
- Si un pase se salta o se retrasa —el planificador no corrió, la tarea coincidió
  con otra, el servidor estaba ocupado—, la entrada **sigue ahí**: la web sirve
  datos de hasta dos horas en vez de frenarse en seco y cargarle el cálculo al
  primero que entre.

Con TTL de una hora exacta habría un hueco entre que la entrada caduca y el cron
la reescribe, y ese hueco lo paga siempre un visitante. Datos algo más viejos no
son un problema para la privacidad; una página lenta sí lo es para todo lo demás.

### Qué se considera un mes «cerrado»

Ni el mes en curso ni el anterior. Del anteanterior hacia atrás, a la caja
fuerte.

Dejar fuera el mes anterior parece exagerado, pero cubre dos casos reales: que
alguien visitara la página el día 31 por la noche —congelando «para siempre» el
mes con su último día a medias— y que un cacharro que estuvo sin red suba lo
acumulado cuando la recupera, con fecha del mes pasado.

### La ingesta NO invalida nada, y es a propósito

`KeyCounterService::storeKeyboard()` y `storeMouse()` guardan la racha y no tocan
la caché.

Hasta el **2026-09-10** hacían lo contrario: olvidaban el resumen, los widgets,
el total del año y las gráficas del mes en curso y del anterior en **cada racha
recibida**. Sonaba razonable —«ha llegado un dato, que se vea»— y rompía los dos
objetivos de golpe:

- **La ventana no existía.** La primera visita después de cada subida
  recalculaba con la racha recién llegada: eso es tiempo real con otro nombre,
  justo lo que el aviso de privacidad de la página dice que no pasa.
- **El cálculo se lo comía el visitante.** Cada ingesta dejaba la caché vacía,
  así que la web volvía a tardar más de un segundo en la siguiente carga.
- Y en un almacén `file`, además, era una escritura de disco por cada racha que
  sube un cacharro.

Quien refresca es el planificador. Si hace falta que un dato se vea **ya** (una
demo, una comprobación), se fuerza a mano:

```bash
php artisan keycounter:warm_cache --live
```

### Quién sí invalida

Lo que toca rachas **ya guardadas** —y por tanto meses que pueden estar en la
caja fuerte— sí tiene que olvidar lo que deja mal:

| Comando | Qué olvida |
|---|---|
| `keycounter:remove_duplicate --force` | Las gráficas de los meses de las filas borradas, los totales de esos años y los widgets |
| `keycounter:fix_weekday --write` | Lo mismo, para los meses de las rachas reescritas |

Los dos calculan los meses afectados **antes** de tocar nada —después ya no hay
forma de saberlo— y sólo miran `keycounter_keyboard`: la gráfica, los widgets y
los totales anuales salen de esa tabla. Lo que se cachea del ratón es su tarjeta
de resumen, que es un dato vivo y se reescribe sola en el siguiente refresco.

Hasta el 2026-09-10 no invalidaban nada: un duplicado de un mes cerrado se iba de
la base de datos y su gráfica se quedaba puesta con la cifra vieja, guardada
«para siempre», sin nada que la volviera a calcular. `KeyCounterCache::forgetGraph()`
existía para eso y no lo llamaba nadie.

### Precalentado

`keycounter:warm_cache` tiene dos pasadas, y hacen cosas distintas:

```bash
php artisan keycounter:warm_cache --live      # lo vivo: mes en curso y anterior, resúmenes, widgets, total del año
php artisan keycounter:warm_cache             # los meses cerrados que aún no estén hechos
php artisan keycounter:warm_cache --months=12 # sólo los doce últimos
php artisan keycounter:warm_cache --force     # recalcula también los cerrados que ya estaban cacheados
```

Programación (`routes/console.php`):

| Tarea | Cuándo | Por qué |
|---|---|---|
| `keycounter:warm_cache --live` | cada hora | Mantiene lo vivo escrito para que no lo pague el visitante, y marca el retardo de privacidad |
| `keycounter:warm_cache` | a diario, 04:00 | Mete en la caja fuerte los meses cerrados. Va después de `remove_duplicate` (lunes 03:00) y `generate_duration` (lunes 03:30), que pueden mover rachas |

El pase de cerrados era **semanal** hasta el 2026-09-10. El problema no era el
histórico —ése se calcula una vez en la vida— sino el mes que acaba de cerrarse:
se quedaba hasta seis días fuera de la caja fuerte, y ese cálculo lo pagaba quien
entrara. A diario, como mucho espera una madrugada.

`--force` es para después de tocar rachas históricas (`remove_duplicate --full`,
`fix_weekday --write`): lo que ya está guardado «para siempre» hay que
reescribirlo, no respetarlo.

### Operativa manual

Olvidar un mes suelto:

```bash
php artisan cache:forget "keycounter:graph:2019-12"
```

Rehacerlo todo desde cero (después de una restauración, o si se duda de lo que
hay guardado):

```bash
php artisan cache:clear
php artisan keycounter:warm_cache --live
php artisan keycounter:warm_cache
```

### Si algo se ve raro

| Síntoma | Dónde mirar |
|---|---|
| La web tarda más de un segundo | ¿Está corriendo el planificador? `php artisan schedule:list` y el log de `keycounter:warm_cache --live` |
| Una racha de hace rato no aparece | Normal hasta una hora. Para comprobar: `php artisan keycounter:warm_cache --live` y recargar |
| Un mes viejo enseña una cifra que no cuadra | Está en la caja fuerte con datos previos a un borrado o arreglo: `cache:forget` de ese mes, o `keycounter:warm_cache --force` |
| Todos los meses se ven vacíos tras un despliegue | `config:cache`/`cache:clear` del despliegue se llevó la caché; el pase diario la rehace, o se lanza a mano |

### El N+1 que había dentro

`getStatisticsPreparedToGraphics()` buscaba cada celda de la gráfica con
`$stats->where('day', …)->where('hardware_device_id', …)->first()`, o sea
recorriendo la colección entera una vez por cada combinación de día y
dispositivo. El propio código lo tenía marcado con un `FIXME`. Ahora se indexa
por `día|dispositivo` antes del bucle y los nombres se resuelven en una sola
consulta.

De paso se arregló un fallo que sólo se veía en la leyenda: la serie de cada
dispositivo se creaba **dentro** del bucle, así que a un cacharro que no hubiera
reportado el primer día del mes le caía la rama del `else` y se quedaba sin
`label` ni color. Ahora las series se estrenan antes de recorrer los días.

---

> Creado: 2026-05-25 · Última revisión: 2026-09-11 (corrección: `weekday` es 0 = domingo, no 0 = lunes)
