# Módulo: Energía

Lo que un dispositivo **mide** de energía: un controlador solar (Renogy Rover y
compatibles) o un monitor de consumo de varios canales, con sus resúmenes
diarios e históricos unificados.

Es un módulo al mismo nivel que la estación meteorológica, KeyCounter,
SmartPlant o AirFlight, y como todos ellos sus lecturas cuelgan de un
`hardware_device_id`. **El aparato en sí** —inventario y salud: IP, uptime, CPU,
RAM, discos, temperatura— es del módulo [hardware.md](hardware.md).

> Estuvo dentro de Hardware hasta el **2026-09-06**, en rutas `/hardware/*` y con
> la ability `hardware:write`.
>
> En el **Plan 1 (2026-09-12, D115)** se completó la refactorización profunda de
> arquitectura: unificación de tablas segregadas (`hardware_power_generators*` y
> `hardware_power_loads*`) en un esquema limpio de 3 niveles (`_readings`, `_today`,
> `_historical`), backfill de datos con 0% de pérdida, soporte multisensión para
> reinicios de odómetro de hardware y payload universal de telemetría `energy`.

**Contrato HTTP para clientes:** [docs/info/api/v2/energy.md](api/v2/energy.md).

---

## 1. Arquitectura de Base de Datos y Modelos (D115)

El subsistema de energía se estructura en cuatro tablas principales:

| Modelo Eloquent | Tabla | Descripción |
|---|---|---|
| `App\Models\Hardware\HardwareEnergy` | `hardware_energy` | **Catálogo de elementos energéticos**: generador, batería o canal de consumo |
| `App\Models\Hardware\HardwareEnergyReading` | `hardware_energy_readings` | **Telemetría granular unificada**: lecturas periódicas con tensión, corriente, potencia y $\Delta Wh$ |
| `App\Models\Hardware\HardwareEnergyToday` | `hardware_energy_today` | **Agregado diario**: una fila por elemento y fecha con acumulados del día y extremos |
| `App\Models\Hardware\HardwareEnergyHistorical` | `hardware_energy_historical` | **Serie histórica multisensión**: acumulados totales y extremos por sesión de odómetro |

> ℹ️ Las tablas antiguas (`hardware_power_generators*`, `hardware_power_loads*`,
> `hardware_power_generators_solar`) **se quedan intactas en la base como
> respaldo en frío**. Sus datos se traspasan con
> `php artisan energy:migrate-legacy-data`, que:
>
> - toma el dispositivo de cada fila del **catálogo del elemento**, no de la fila
>   vieja, porque el esquema antiguo guardaba a veces el aparato monitorizado en
>   vez del que mide;
> - **descarta las filas sin elemento asignado**, que en el esquema nuevo no se
>   pueden sumar a nada y ensucian cualquier total que no filtre;
> - descompone cada fila de `hardware_power_generators_solar` en tres lecturas
>   —panel, salida de carga y batería—, que es el rango de fechas en el que la
>   tabla dedicada del Rover fue la única que se llenó;
> - empieza por un `TRUNCATE` de las tres tablas nuevas, así que **se lanza antes
>   de que los aparatos empiecen a subir, no después**.

---

## 2. El Elemento Energético (`HardwareEnergy`)

Una fila de `hardware_energy` representa **un rol funcional** dentro de un dispositivo.
Un mismo aparato físico puede cumplir varios roles simultáneos mediante filas distintas:

| `role` | Magnitud / Función | Ejemplos | Límite por medidor |
|---|---|---|---|
| `generator` | Generación de energía | Panel fotovoltaico, generador eólico, dinamo | **1** por medidor |
| `battery` | Almacenamiento de energía | Banco de baterías LiFePO4, AGM, GEL, 18650 | **1** por medidor |
| `load` | Consumo de energía | Router, servidor, Raspberry Pi, farola, salida de carga | **Múltiples** (por canal `sensor_position`) |

### Campos Principales de `HardwareEnergy`:
- `hardware_device_id`: Dispositivo físico que realiza la medición (medidor/sensor).
- `hardware_device_monitorized_id`: Dispositivo físico medido (ej. el router conectado al sensor).
- `role`: Rol del elemento (`generator`, `battery`, `load`).
- `sensor_position`: Canal físico del sensor (0, 1, 2...). En generador y batería es 0.
- `nominal_voltage`: Tensión nominal de diseño (ej. 24.0 V para paneles, 12.8 V para baterías, 12.0 V para routers).
- `voltage_min` y `voltage_max`: Umbrales de plausibilidad y calibración de SOC para baterías.
- `capacity_ah`: Capacidad nominal en Amperios-hora (Ah) para baterías.
- `capacity_wh`: Accesor dinámico calculado como `capacity_ah * nominal_voltage`.
- `auto_calculate_history`: Booleano que indica si los acumulados se recalculan automáticamente desde lecturas.
- `is_active`: Indica si el elemento está activo y debe seguir procesando telemetría.

### 2.1. A qué elemento va cada lectura

`HardwareService::resolveTelemetryElement()` decide, para cada bloque de la
telemetría, a qué fila de `hardware_energy` corresponde. El orden importa,
porque el índice único
`(hardware_device_id, hardware_device_monitorized_id, role, sensor_position)`
no sabe nada de `is_active` ni de `deleted_at`:

1. **Se prefiere el elemento activo y sin borrar.** Un mismo aparato puede tener
   dos elementos del mismo rol midiendo cosas distintas —el índice único incluye
   el dispositivo monitorizado—, así que coger «el primero» descartaba la lectura
   si ese primero estaba desactivado habiendo otro perfectamente activo.
2. **Si sólo hay uno borrado lógicamente, la lectura se descarta con un aviso.**
   Ni se resucita el elemento ni se crea otro igual: lo segundo reventaba contra
   el índice único y dejaba al dispositivo sin poder subir nada hasta que alguien
   lo arreglara a mano. Restaurarlo es decisión de quien lo borró.
3. **Si sólo hay uno desactivado, la lectura se descarta con un aviso.**
4. **Si no hay ninguno, se crea**, para no perder el dato, pero **sin inventarle
   `nominal_voltage`**: tomarla de la primera lectura que llegue dejaría fijado
   como referencia permanente lo que igual era un pico de arranque, y a partir de
   ahí todas las lecturas buenas saldrían con un aviso de fuera de rango. Se
   avisa en `warnings` para que se configure.

Todos esos casos se prueban en
`tests/Feature/Api/V2/Energy/EnergyElementResolutionTest.php`.

### 2.2. Qué identifica una fila de resumen

`hardware_energy_today` y `hardware_energy_historical` llevan un
`hardware_device_id`, pero **no forma parte de la identidad de la fila**: es una
desnormalización de `hardware_energy.hardware_device_id` para poder filtrar y
pintar sin unir tablas. Quien manda son los índices únicos:

| Tabla | Clave |
|---|---|
| `hardware_energy_today` | (`hardware_energy_id`, `date`) |
| `hardware_energy_historical` | (`hardware_energy_id`, `session_index`) |

Toda búsqueda de «la fila de este elemento» —la ingesta, la sesión abierta del
histórico y las dos del cron— usa **esa** clave y nada más. Buscar además por
dispositivo dejaba invisible cualquier fila cuyo `hardware_device_id` no
coincidiera: no se encontraba, se intentaba insertar otra, chocaba contra el
índice único, la relectura volvía a no encontrar nada y el aparato se comía un
**500 en cada subida**. Pasa de dos maneras: filas que la migración del esquema
viejo atribuyó al dispositivo *monitorizado* en vez de al que mide, y reasignar
un elemento a otro medidor desde Filament, que es un campo editable.

Cuando la fila encontrada trae otro dispositivo, **se realinea con el del
catálogo**, que es quien dice qué aparato mide ese elemento. Así una reasignación
mueve la serie entera en vez de partirla en dos.

Cubierto por `tests/Feature/Api/V2/Energy/EnergyRowIdentityTest.php`.

---

## 3. Telemetría y Contrato Universal `energy`

Los dispositivos IoT suben su telemetría mediante un payload unificado que organiza
las mediciones en **tres bloques normalizados** (`generator`, `battery`, `loads`):

```json
{
  "hardware_device_id": 15,
  "duration": 60,
  "device": {
    "temp": 42.1,
    "uptime": 86400
  },
  "energy": {
    "duration": 60,
    "generator": {
      "voltage": 34.5,
      "amperage": 4.2,
      "power": 144.9,
      "charging_status": 3,
      "charging_status_label": "mppt",
      "light_status": false,
      "today_energy_wh": 1250.0,
      "today_energy_ah": 52.0,
      "today_amperage_max": 6.1,
      "today_power_max": 148.0,
      "historical_energy_wh": 45000.0,
      "historical_energy_ah": 1875.0
    },
    "battery": {
      "voltage": 13.4,
      "soc": 92,
      "temperature": 24.5,
      "charging_status": 3,
      "today_voltage_min": 12.1,
      "today_voltage_max": 14.3,
      "today_energy_ah": 40.0,
      "historical_energy_ah": 1500.0,
      "battery_full_charges": 25,
      "battery_over_discharges": 1
    },
    "loads": [
      {
        "channel": 0,
        "voltage": 12.1,
        "amperage": 2.5,
        "power": 30.25,
        "today_energy_wh": 310.0,
        "today_energy_ah": 25.6,
        "today_amperage_max": 4.4,
        "historical_energy_wh": 12500.0,
        "historical_energy_ah": 1030.0
      }
    ]
  }
}
```

> **Flexibilidad de integración para microcontroladores:**
> - `duration`: Puede enviarse en la raíz del objeto o dentro de `energy.duration` (si se omite, toma 60 segundos por defecto).
> - `hardware_device_info`: Se acepta tanto con su clave canónica como con el alias abreviado `device`.
> - **Todos los campos `today_*` e `historical_*` son opcionales en los tres
>   bloques.** Un microcontrolador que sólo mide tensión y corriente manda eso y
>   ya; un controlador que lleva sus propios contadores los manda y mandan ellos.
>   Ver §4.3 para qué hace cada uno.

### 3.2. Las dos magnitudes temporales: `read_at` y `duration`

Son cosas distintas y se confunden con facilidad:

| | Qué contesta | Quién lo manda |
|---|---|---|
| `read_at` | **Cuándo** se tomó la muestra | Sólo los aparatos con reloj sincronizado |
| `duration` | **Cuánto duró** el intervalo que resume | Los que promedian entre subidas |

Un controlador solar con reloj manda `read_at` y no necesita `duration`, porque
declara sus propios acumuladores. Un nodo con un INA y sin reloj manda `duration`
y no puede mandar `read_at`. Un aparato puede mandar los dos.

**`read_at` sustituye a la hora de llegada.** Sin él, un reintento tras un corte
de red guarda media hora de muestras todas con la hora del reintento, y la curva
del día sale plana durante el corte con un pico al final.

Afecta a dos cosas: a `created_at` de la lectura **y al día en el que cae su
resumen**. Una muestra de ayer reenviada hoy suma en el resumen de ayer.

Se rechaza con 422 si no es una fecha, si es anterior al año 2000 o si va más de
una hora por delante del servidor: un reloj mal puesto metería lecturas en días
que aún no existen y el cierre nocturno no volvería a pasar por ellos. Se
toleran hasta 60 minutos de adelanto para no castigar un reloj con deriva.

Las dos se aceptan en la raíz del payload o dentro de `energy`; si van en los dos
sitios gana el de dentro.

### Principios de Cálculo e Integración:
1. **La marca de tiempo es `created_at`, y el cliente puede fijarla.** Las tablas
   no tienen columna `read_at`: el momento de una lectura es su `created_at` y el
   de un resumen su `date`. Por defecto lo pone el servidor al recibir, que es lo
   que necesita un cacharro sin reloj. Un aparato con reloj sincronizado puede
   mandar `read_at` en el payload y entonces esa hora **sustituye** a la de
   llegada. Ver §3.2.
2. **Derivación de Magnitudes:**
   - Potencia: $P = V \cdot I$ (W)
   - Energía incremental: $\Delta Wh = \frac{P \cdot \text{duration}}{3600}$
   - Amperios-hora incrementales: $\Delta Ah = \frac{I \cdot \text{duration}}{3600}$
   - `hardware_energy_readings.energy_source` describe el origen del **delta de esta lectura** (`energy_wh` / `energy_ah`), no el de los acumulados: vale `device` si el aparato manda ya el consumo del intervalo (`energy_wh` dentro del bloque) y `derived` si el servidor lo integra a partir de `power` y `duration`. No confundirlo con `energy_wh_source` / `energy_ah_source` de `hardware_energy_historical`, que son los de la sesión (§4.1).
   - Los acumulados nativos (`today_energy_wh`, `today_energy_ah`, `historical_energy_wh`, `historical_energy_ah`) mandan siempre sobre lo calculado en sus tablas de resumen.
3. **Respaldo de Tensión Nominal (`nominal_voltage`):** Si un canal de consumo o sensor de corriente simple (ej. INA219 montado en la línea de un router) no mide tensión, el backend recurre a `nominal_voltage` del elemento con `voltage_source: nominal` y emite un warning informativo sin descartar la muestra. **Sólo se usa cuando no hay medida**: una tensión medida se guarda tal cual aunque se salga del rango del elemento (§4.5).
4. **Cálculo Automático de SOC de Batería:** Si el payload incluye tensión de batería pero omite el porcentaje (`soc`), el backend lo calcula de forma proporcional entre `voltage_min` y `voltage_max`.

---

## 4. Gestión Multisensión y Reseteo de Odómetro

Los microcontroladores y controladores de carga pueden reiniciar sus contadores internos
(ej. tras apagado prolongado, corte de suministro o desborde numérico).
Para evitar la pérdida de años de históricos acumulados:

1. `HardwareEnergyHistorical` organiza los datos en sesiones (`session_index`).
2. Cuando se detecta una caída drástica en el acumulado reportado ($< 50\%$ del valor previamente alcanzado habiendo acumulado $> 50\text{ Wh}$ o $50\text{ Ah}$):
   - La sesión previa se cierra y preserva con su valor íntegro alcanzado.
   - Se crea automáticamente una nueva fila con `session_index = anterior + 1`.
   - El nuevo valor del odómetro reiniciado comienza a contar en la nueva sesión.
3. El acumulado total absoluto de un elemento corresponde a la suma de `energy_wh` y `energy_ah` de todas sus sesiones históricas.

**Ese criterio vale para todo el que lea esta tabla.** El panel público
(`EnergyController`) y el widget del panel de administración
(`EnergyStatsWidget`) suman las sesiones; quedarse con la última descarta todo
lo anterior al último reinicio. La excepción son los **días**: ahí se coge el
máximo, porque dos elementos que llevan 1.700 días cada uno no hacen 3.400 días
de instalación. Lo fija `tests/Feature/Hardware/EnergyHistoricalReadingTest.php`.

### 4.1. De dónde sale cada acumulado (`energy_wh_source` y `energy_ah_source`)

**La regla del módulo entero: lo que el aparato manda se guarda tal cual; lo que
no manda, se calcula.**

Cada fila de `hardware_energy_historical` lo declara **por magnitud**, en dos
columnas:

| Valor | Qué significa |
|---|---|
| `device` | Lo lleva el odómetro del aparato. Se guarda el mayor entre lo que había y lo reportado, y **nunca se le suman nuestros deltas** |
| `derived` | Lo llevamos nosotros sumando la energía de cada intervalo |

**Las fija la primera lectura que trae odómetro de esa magnitud**, y desde
entonces esa magnitud ignora los deltas. La otra sigue su propio camino.

#### Por qué son dos columnas y no una

Un aparato puede traer contador de una magnitud y no de la otra. El Renogy Rover
es exactamente ese caso:

| Elemento | Wh | Ah |
|---|---|---|
| Generador (panel) | del aparato | del aparato |
| Consumo (salida de carga) | del aparato | del aparato |
| Batería | **calculado** | del aparato |

No hay registro Modbus de vatios-hora de batería. Con una sola marca por sesión,
poner el elemento en `device` congelaba **también** los Wh de la batería y los
dejaba clavados a 0 para siempre, mientras `hardware_energy_today` sí los
calculaba: dos tablas dando respuestas distintas a la misma pregunta.

#### Por qué no se usa `auto_calculate_history`

Porque es una casilla que se puede quedar mal puesta —su `default(true)` marcó
como auto-calculados a los controladores que traen odómetro propio—. Las dos
columnas de origen son un **hecho observado** de la serie, no una intención
declarada. `auto_calculate_history` sigue existiendo y gobierna si el cron
nocturno se ocupa del elemento, pero no decide qué es odómetro y qué no.

### 4.2. Dónde va cada medida: el criterio para no duplicar

**Cada bloque del contrato describe su elemento.** `generator` habla de lo que
produce, `battery` de lo que almacena y `loads[]` de lo que gasta. Un campo
llamado igual significa lo mismo en los tres, referido al elemento de ese bloque.

Los amperios-hora son la medida que más se presta a acabar en dos sitios, porque
la misma corriente se mira desde donde sale o desde donde entra:

| Lo que se mide | Bloque | Por qué |
|---|---|---|
| Amperios-hora que **entran en la batería** | `battery` | Es carga del banco, aunque venga del panel. Entre los dos está el rendimiento del MPPT: no son el mismo número |
| Amperios-hora que **salen hacia los consumos** | `loads[]` | Es lo que gastan, aunque salga de la batería |
| Amperios-hora que **produce el generador** | `generator` | Sólo si el aparato lo mide aparte |

Lo que el aparato no mida, **no se manda ni se duplica**: se omite y el servidor
lo calcula de las lecturas. Un montaje que declara lo que entra al banco y lo que
sale a los consumos ya describe el balance entero.

> **El esquema viejo lo hacía al revés.** El contrato de la V1 ponía los
> amperios-hora de carga en el generador, así que el elemento 4 del Renogy
> arrastra ese valor de la migración. Se conserva —es un dato real— pero marcado
> como calculado, no como odómetro: el Rover no mide los amperios-hora del panel.

### 4.3. Qué declara el aparato y qué calculamos nosotros

Todo campo `today_*` e `historical_*` del contrato es opcional y, cuando llega,
gana. La tabla completa, con su efecto:

| Campo del contrato | Dónde cae | Qué hace |
|---|---|---|
| `today_energy_wh` / `today_energy_ah` | `hardware_energy_today.energy_wh` / `.energy_ah` | **Sustituye** el total del día. Sumarlo lo contaría dos veces |
| `today_voltage_min` / `today_voltage_max` | `.voltage_min` / `.voltage_max` | **Ensancha** el rango; nunca lo recorta |
| `today_amperage_max` | `.amperage_max` | Ensancha |
| `today_power_max` | `.power_max` | Ensancha |
| `historical_energy_wh` / `historical_energy_ah` | `hardware_energy_historical.energy_wh` / `.energy_ah` | Fija la magnitud como `device` y guarda el mayor de los dos |
| `battery_full_charges` / `battery_over_discharges` | `.number_battery_*` | Guarda el mayor |
| `total_operating_days` (alias `days_operating`) | `.days_operating` | Guarda el mayor |

«Ensancha» quiere decir que si nuestra propia lectura da un valor más extremo
que el declarado, gana el nuestro: el máximo del controlador ve picos que un
muestreo cada minuto se pierde, pero una medida concreta no se puede borrar.

Lo que **no** llega se deriva de lo que sí hay, en este orden de preferencia
(`HardwareEnergy::deriveMagnitudes()`):

| Magnitud | 1.º | 2.º | 3.º | Si no |
|---|---|---|---|---|
| Potencia | `power` | `V · A` | — | `null` |
| Vatios-hora | `energy_wh` | `A · V · s / 3600` | `P · s / 3600` | `null` |
| Amperios-hora | `energy_ah` | `A · s / 3600` | `Wh / V` | `null` |

**Nunca se inventa un 0.** Un 0 diría que se midió y dio cero, y eso baja todas
las medias. Una magnitud que no se puede calcular se queda a `null`.

El tercer escalón de los vatios-hora existe porque hay sensores que dan vatios y
no amperios: sin él, esos aparatos registraban `energy_wh` a nulo y su resumen
del día sumaba **cero**, con un 201 y sin un aviso.

Esos totales del día quedan marcados en `hardware_energy_today` con las mismas
dos columnas de origen que el acumulado de por vida, y por el mismo motivo: sin
ellas, el cierre nocturno hacía `max(lo declarado, la suma de nuestras lecturas)`
y **sustituía el contador del aparato** en cuanto el nuestro salía mayor. Un
controlador que declaraba 800 Wh del día amanecía con 1.500.

### 4.4. El intervalo cuando no llega `duration`

`duration` es lo que convierte una potencia en energía. Cuando la subida no lo
trae, el intervalo lo pone **el elemento**, en
`hardware_energy.default_interval_seconds` (60 s de partida, editable en el
panel).

Antes eran 60 s fijos para todo el mundo, y eso es una mentira distinta en cada
instalación: un nodo que sube cada diez minutos registraba la sexta parte de la
energía real, en silencio y sin forma de notarlo hasta comparar con una pinza.

Que el aparato mande `duration` en cada subida sigue siendo lo mejor: sólo él
sabe cuántos segundos pasaron de verdad cuando hubo un corte de red. La columna
es el respaldo para cuando no puede.

### 4.5. La tensión: se guarda lo medido

`nominal_voltage` es **respaldo**, no corrección. Entra sólo cuando el aparato no
manda `voltage`; entonces `voltage_source` queda en `nominal` y no en `measured`.

Una tensión medida que se sale del rango del elemento **se guarda igual** y se
avisa en `warnings`. Sustituirla, que es lo que se hacía antes, borraba justo lo
que hay que ver:

- el panel a 0 V de noche se guardaba a 24 V, y el mínimo del día del panel era
  24 V todos los días;
- una batería a 10,4 V con `voltage_min` a 11 se guardaba a 12 V con un 25 % de
  carga, así que una sobredescarga real no aparecía en ninguna pantalla.

En una batería, `voltage_min` y `voltage_max` son además las tensiones a 0 % y a
100 % de carga: de ahí sale `battery_percentage` cuando el aparato no lo manda.
Conviene que sean las del banco real y no un rango de tolerancia ancho.

---

## 5. Controladores y Rutas API V2

- `POST /api/v2/energy/readings`: Endpoint universal de ingesta (ability `energy:write`).
- `GET /api/v2/energy/readings`: Consulta de lecturas paginadas filtrables por dispositivo, elemento, fecha y ordenación (ability `energy:read`).

**Son los dos únicos.** No hay endpoint específico para el controlador solar ni
para ningún otro aparato: los tres bloques del contrato universal cubren desde
un Renogy Rover que manda ocho acumuladores hasta un microcontrolador que manda
tensión y corriente. La traducción de los registros Modbus del Rover la hace su
firmware antes de enviar.

---

## 6. Seguridad y Aislamiento

- **Abilities Sanctum:**
  - `energy:write`: Exclusiva para subida de telemetría desde dispositivos medidores.
  - `energy:read`: Para paneles y aplicaciones consumidoras de datos.
- **Aislamiento por Dispositivo (`device:{id}`):** Un token asignado a un dispositivo no puede reportar ni consultar datos de dispositivos ajenos.
- **Aislamiento Multiusuario:** Las policies y reglas (`OwnedHardwareDevice`) impiden cualquier acceso cruzado entre cuentas de usuario.

---

## 7. Panel de Administración Filament (Admin Back-Office)

El panel de administración centraliza la gestión y visualización del módulo de energía bajo el cluster `App\Filament\Admin\Clusters\Energy`:

### 7.1. Recurso Unificado `HardwareEnergyResource`
- **Gestión centralizada:** Administra todos los elementos energéticos del usuario (tanto controladores solares como monitores de consumo) sin segregación artificial en recursos separados.
- **Agrupación en tabla:** Los elementos se presentan agrupados por el dispositivo medidor (`hardwareDevice.name`), permitiendo identificar rápidamente todos los canales y roles que cuelgan de cada microcontrolador.
- **Formulario especializado:** `HardwareEnergyForm` configura la tensión nominal (`nominal_voltage`), umbrales de tensión (`voltage_min`, `voltage_max`), capacidad en Ah (`capacity_ah`) y flags de recálculo de históricos.

### 7.2. Relation Managers de Telemetría (Ficha del Elemento)

> **La telemetría no se crea ni se edita a mano.** Las tres pantallas —lecturas,
> resúmenes del día y sesiones históricas— son de **sólo lectura**: los datos
> entran exclusivamente por `POST /api/v2/energy/readings`. Ni siquiera declaran
> un formulario.
>
> Editar un vatio a mano es inventarse un dato: deja de haber forma de saber qué
> número salió de un aparato. Si hay que rectificar, se arregla el firmware, o el
> código si el fallo es nuestro.
>
> **Borrar sí, sólo administradores y con confirmación**: hace falta para limpiar
> una serie corrupta, pero el dato no se puede volver a pedir —el aparato ya lo
> mandó y no lo reenvía—.
>
> El catálogo de elementos (`hardware_energy`) **sí** se crea y se edita desde el
> panel: eso es configurar la instalación, no inventarse telemetría.
En la pantalla de edición de cada elemento energético (`EditHardwareEnergy`) se montan cuatro relation managers:
1. `RolesRelationManager`: Permite inspeccionar y dar de alta de forma contextual los roles complementarios en el mismo dispositivo físico (`create_generator`, `create_battery`, `create_load`) sin abandonar la ficha.
2. `ReadingsRelationManager`: Tabla paginada de telemetría granular (`hardware_energy_readings`) con tensión, corriente, potencia, $\Delta Wh$, $\Delta Ah$, estado de carga y marcas de sospecha (`is_suspicious` y `suspicious_reason`).
3. `TodayRelationManager`: Visualización de agregados diarios (`hardware_energy_today`) con Wh y Ah del día, número de lecturas registradas y extremos (mínimos, máximos y medias de tensión, corriente y potencia).
4. `HistoricalRelationManager`: Desglose de sesiones de odómetro (`hardware_energy_historical`) mostrando el índice de sesión, días en operación, total de Wh/Ah acumulados y ciclos de batería.

### 7.3. Dashboard y Widgets Analíticos
- **`EnergyDashboard` (`/admin/energy/energy-dashboard`):** Página principal del cluster de energía, accesible para administradores.
- **`EnergyStatsWidget`:** Cuadrícula de tarjetas con métricas en tiempo real (consumo actual en W, generación actual en W, balance neto con indicador de superávit/déficit, nivel medio de batería), agregados de hoy (Wh consumidos, Wh generados, pico de consumo) y acumulados de los últimos 30 días junto a métricas de odómetro.
- **`EnergyHistoricalChart`:** Gráfico lineal temporal de los últimos 30 días que enfrenta la curva de generación total contra la curva de consumo total en vatios-hora (Wh) consultando `hardware_energy_today`.

---

## 8. Frontend Web Público (`/hardware/energy`)

La plataforma expone una interfaz web pública para la monitorización en tiempo real e histórica de generación y consumos:

- **Ruta:** `GET /hardware/energy` (`route('hardware.energy.index')`).
- **Controlador:** `App\Http\Controllers\Hardware\EnergyController`.
- **Vista:** `resources/views/hardware/energy/index.blade.php`.

### 8.1. Métricas Agregadas
La interfaz presenta tres bloques de tarjetas analíticas agregadas:
1. **Ahora Mismo:** Potencia de generación actual (W), potencia de consumo actual (W), balance neto instantáneo (W y A), tensiones de panel y batería (V), porcentajes de carga de batería, intensidad solar (%) y temperatura máxima registrada (°C).
2. **Hoy:** Energía generada hoy (Wh), energía consumida hoy (Wh) y los mismos dos valores en amperios-hora referidos a la tensión del bus.
3. **Histórico:** Energía total generada (kWh), total consumida (kWh), días acumulados en operación y ciclos completos de carga de batería.

#### La tensión de referencia

**Los vatios no dependen de la tensión y los amperios sí.** El balance en W
—generado menos consumido— sale bien tal cual. El balance en amperios no: el
panel del Renogy genera a 24 V y el consumo va a 12 V, así que generar 5 A no
compensa consumir 5 A. Son 10 A referidos a 12 V frente a 5, o sea 5 A netos a
favor, no 0.

Por eso **todo lo que se pinta en amperios se lleva antes a una tensión común**,
pasando por la potencia, que es la magnitud que no depende de ella:

```
A_ref = W / V_ref        Ah_ref = Wh / V_ref
```

`V_ref` es la `nominal_voltage` del elemento **batería** de la instalación, que
es la tensión del bus —lo que el Renogy llama tensión de sistema—. Si no hay
batería configurada se usa la del consumo y, a falta de las dos, 12 V. Las
tarjetas llevan la tensión en el título (`Generado a 12 V`) para que no haya
duda de contra qué se comparan.

Las tarjetas por dispositivo van todas en W y Wh, así que no necesitan
referencia.

### 8.2. Tarjetas de Dispositivos y Ordenación en Cascada
Cada dispositivo físico medidor se renderiza en una tarjeta individual con su miniatura, versión de software, resumen de kWh históricos, porcentaje de batería y barras comparativas de potencia instantánea y energía diaria.

Para ofrecer una visualización priorizada y relevante, las tarjetas se ordenan mediante un algoritmo en cascada:
1. **Activos en la última hora:** Dispositivos reportando potencia instantánea (`generated_now > 0 || consumed_now > 0`) aparecen en primer lugar.
2. **Mayor actividad hoy:** En caso de empate, se prioriza el dispositivo que más energía haya movido en el día (`generated_today + consumed_today`).
3. **Mayor acumulado histórico:** Si no hay actividad hoy, se ordenan por su acumulado histórico total (`generated_historical_kwh + consumed_historical_kwh`).

### 8.3. Tokens de Diseño y Accesibilidad
La interfaz respeta estrictamente la paleta del sistema de diseño («Obsidian Flux / Raupulus Slate») mediante tokens semánticos `@theme` de Tailwind v4 (`bg-surface`, `bg-surface-container-*`, `text-on-surface*`), asegurando un ratio de contraste WCAG AA en modos claro y oscuro verificado por `tests/Unit/Design/ContrastTest.php`.

---

## 9. Tareas Programadas y Mantenimiento Nocturno

Para garantizar la integridad y coherencia matemática de las agregaciones a largo plazo:

- **Comando:** `php artisan energy:aggregate-daily` (`App\Console\Commands\Energy\AggregateDailyEnergyCommand`).
- **Planificación:** Diario a las **00:05 UTC** en `routes/console.php`.
- **Comportamiento:**
  1. Cierra y consolida el día anterior (todas las muestras entre 00:00:00 y 23:59:59 **UTC**) en `hardware_energy_today`.
  2. Filtra automáticamente por elementos con `auto_calculate_history = true` e `is_active = true` (los elementos con odómetro propio de hardware como el Renogy Rover se gestionan nativamente).
  3. Reconcilia la serie histórica en `hardware_energy_historical` **sesión a sesión**: cada fila de `session_index` se rehace con los resúmenes diarios comprendidos entre su apertura y la de la siguiente, de forma que el acumulado del elemento —que es la suma de sus sesiones— no se duplica al reiniciarse el odómetro.
  4. Excluye lecturas anómalas o sospechosas (`is_suspicious = true`).
  5. Soporta ejecución manual retroactiva mediante opciones: `--date=YYYY-MM-DD`, `--today`, `--element=ID`, `--all` y `--rebuild`.

### 9.1. Cuándo un acumulado puede bajar

La reconciliación **sólo sustituye un acumulado si los resúmenes diarios cubren
toda la historia de esa sesión**, es decir si los días distintos encontrados en
`hardware_energy_today` son al menos los que la sesión ya declaraba en
`days_operating`.

Los históricos que llegaron del esquema antiguo arrastran años sin un
`hardware_energy_today` por día: sobrescribirlos con la suma de los días que sí
existen borraba el resto. Cuando no se cubre toda la historia, el comando lo
avisa por consola y se limita a ampliar el acumulado, nunca a reducirlo.

`--rebuild` fuerza la sustitución de todas formas. Es destructivo y está para
cuando se quiere rehacer un histórico a conciencia, no para el uso diario.

### 9.2. Qué sesiones toca y cuáles no

El comando **nunca toca una magnitud marcada como `device`**: su total es el del
odómetro del aparato y cubre tiempo que nosotros no hemos medido. Ni la rehace ni
la amplía, porque ampliarla con la suma de nuestros deltas sería mezclar las dos
fuentes. Ver §4.1.

Las magnitudes `derived` sí se rehacen, con el límite de cobertura de §9.1, y se
decide **una por una**: en un elemento con los Wh del aparato y los Ah
calculados, el cron rehace los Ah y no roza los Wh.

`days_operating` y `readings_count` describen la sesión entera, no una magnitud.
En cuanto **alguna** de las dos viene del odómetro, la sesión cubre tiempo que no
hemos medido y esos dos campos sólo se amplían, nunca se sustituyen.

---

## 10. Cobertura de pruebas

Este módulo se rompió en silencio varias veces —respondiendo 201 mientras los
resúmenes se quedaban vacíos—, así que la cobertura está pensada para que eso
haga ruido. Qué prueba cada archivo:

### Contrato HTTP

| Archivo | Qué sujeta |
|---|---|
| `Api/V2/Energy/EnergyContractTest.php` | **La forma exacta de cada respuesta**, clave por clave, más alias, precisión, errores, abilities, filtros y paginación. Es la red que sujeta a `docs/info/api/v2/energy.md` |
| `Api/V2/Energy/EnergyInstallationScenarioTest.php` | Los dos montajes reales: un controlador solar con sus tres roles y un monitor de tres canales midiendo tres aparatos distintos. Incluye que no se mezclen entre sí |
| `Api/V2/Energy/EnergySolarIngestionTest.php` | Ingesta completa del Renogy por el bloque `energy` e inferencia de SOC |
| `Api/V2/Energy/EnergySecurityTest.php` | Aislamiento entre usuarios y entre dispositivos |

### Reparto y acumulación

| Archivo | Qué sujeta |
|---|---|
| `Api/V2/Energy/EnergyElementResolutionTest.php` | A qué elemento va cada lectura: elemento borrado, desactivado, auto-creado, y de dónde sale el acumulado de la sesión |
| `Api/V2/Energy/EnergyDerivationTest.php` | **Qué se calcula y qué no con cada hueco**: el orden de preferencia de las tres magnitudes, que un nulo sea igual que no mandar nada, que sin corriente ni potencia no haya energía, y cómo se construyen los acumulados del día y de por vida |
| `Api/V2/Energy/EnergyReadAtTest.php` | Que `read_at` sustituya la marca de la lectura y coloque su resumen en el día correcto, y que un reloj absurdo se rechace |
| `Api/V2/Energy/EnergySampleIntervalTest.php` | El intervalo por elemento cuando la subida no trae `duration`, y que `duration` siga mandando cuando llega |
| `Api/V2/Energy/EnergyRowIdentityTest.php` | Que la fila de resumen la identifique **el elemento**, no el dispositivo: una fila mal atribuida no puede devolver un 500 ni abrir otra en paralelo |
| `Api/V2/Energy/EnergyDeclaredValuesTest.php` | **Que todo lo que el aparato manda se guarde y lo que no manda se calcule.** Los ocho acumuladores del Rover, los máximos del día, el origen por magnitud, la tensión medida fuera de rango y el signo de la batería |
| `Api/V2/Energy/EnergyHistoricalResetTest.php` | Que un reinicio de odómetro abra sesión nueva sin tocar la anterior |
| `Api/V2/Energy/EnergyMonitorSimpleTest.php` | Derivación de potencia, Wh y Ah, y respaldo de tensión nominal |

### Cierre diario e históricos

| Archivo | Qué sujeta |
|---|---|
| `Api/V2/Energy/EnergyDailyCycleTest.php` | El ciclo entero: subidas repartidas por el día, cierre nocturno, totales, idempotencia, lecturas sospechosas y corte del día en UTC |
| `Console/Energy/AggregateDailyHistoricalTest.php` | Que el cron no borre acumulados que los resúmenes no cubren, que `--rebuild` sí lo haga, y que cada sesión se reconcilie sólo con sus días |
| `Console/Energy/AggregateDailyEnergyCommandTest.php` | Opciones del comando y filtrado por `auto_calculate_history` |
| `Console/Energy/MigrateLegacyEnergyDataCommandTest.php` | El traspaso del esquema viejo: `--dry-run` no escribe, las filas sin elemento no pasan, relanzarlo deja lo mismo y su `TRUNCATE` se lleva lo que hubiera entrado en vivo |
| `Console/Energy/SeedEnergyDebugCommandTest.php` | Que `debug:seed-energy` escriba en la forma del esquema: un resumen por día y **un** acumulado por elemento |

### Lectura y pantallas

| Archivo | Qué sujeta |
|---|---|
| `Hardware/EnergyHistoricalReadingTest.php` | Que el panel público y el widget de administración lean el histórico con el mismo criterio, y que la batería salga del rol `battery` |
| `Hardware/EnergyCardOrderTest.php` | El orden en cascada de las tarjetas de `/hardware/energy` y **el escalado de los amperios a la tensión de referencia** |
| `Filament/EnergyTelemetryReadOnlyTest.php` | Que ninguna pantalla de telemetría deje crear **ni editar** a mano, que borrar sea de administradores y con confirmación, y que el catálogo sí deje dar de alta elementos |
| `Filament/EnergyElementFormTest.php` | El alta de un elemento: canal repetido como error de formulario y no como 500, los tres papeles de un controlador, los tres canales de un INA, y qué campos se piden en cada papel |
| `Filament/EnergyWidgetsTest.php`, `EnergyRelationManagersTest.php`, `EnergyListsTest.php`, `DeviceEnergyRelationTest.php` | El panel de administración |
| `Unit/Models/HardwareEnergyModelTest.php` | Accesores, casts, scopes y relaciones del elemento |
| `Unit/Rules/EnergyTelemetryPayloadTest.php` | La validación del bloque `energy` |
| `Unit/Rules/EnergyContractSurfaceTest.php` | **Que el contrato no se descuelgue del código**: que todo campo que lee el servicio esté validado, que todo campo validado lo lea el servicio, que los tres bloques ofrezcan los mismos acumuladores, y que la tabla por bloque del contrato diga exactamente lo que acepta el servidor |
| `Api/V2/Energy/EnergyDocumentedExamplesTest.php` | Que los ejemplos JSON de `docs/info/api/v2/energy.md` se suban de verdad, den 201 y no levanten un solo aviso |

---

> Creado: 2026-09-06 · Última revisión: 2026-09-13
