# Contrato API V2 — Energía

> Este archivo documenta **solo el contrato HTTP** de este módulo: rutas, auth,
> parámetros y forma exacta de la respuesta. Está pensado para copiarse a otro
> proyecto (o pegarse en el contexto de una IA) y que con eso baste para
> integrar estos endpoints sin leer el código fuente.
>
> Para el diseño interno (modelos, tablas, decisiones de arquitectura) ver
> [`docs/info/energy.md`](../../energy.md).
>
> Todo lo de aquí está verificado contra
> `tests/Feature/Api/V2/Energy/EnergyContractTest.php`, que fija la forma de
> cada respuesta clave por clave. Si el código cambia, ese test se rompe antes
> de que este documento se quede viejo.

**Qué es este módulo.** Lo que un aparato *mide* de energía: un controlador
solar (Renogy Rover y compatibles), un monitor de consumo de varios canales, un
banco de baterías. Como todos los módulos, sus lecturas cuelgan de un
`hardware_device_id`.

**Qué NO es.** La salud del propio aparato —IP, uptime, CPU, RAM, discos,
temperatura— es del módulo Hardware: `PUT /hardware/devices/{id}/status`, ability
`hardware:write`. Ver [`hardware.md`](./hardware.md). Aun así, este endpoint
acepta ese bloque de paso para ahorrarle una petición al cacharro (ver
[`hardware_device_info`](#bloque-opcional-hardware_device_info--device)).

---

## Base y convenciones comunes a toda la API V2

- **Base URL**: `/api/v2`
- **Todas las respuestas** usan este envelope (`App\Traits\ApiResponseTrait`):

  ```json
  // Éxito
  { "success": true, "message": "Operación exitosa", "data": { ... } }
  // Error
  { "success": false, "message": "Descripción del error", "errors": { "campo": ["detalle"] } }
  ```

  `errors` solo aparece si hay detalle (p. ej. errores de validación 422). Las
  colecciones paginadas añaden `meta: {total, per_page, current_page, last_page, from, to}`.
  Las subidas pueden añadir `warnings` (ver [Avisos](#avisos-warnings)).

  En entornos con `APP_DEBUG=true` se añade además un bloque `debug` con la
  petición recibida. **En producción no aparece**: no lo uses para nada.

- **Autenticación**: Laravel Sanctum, cabecera `Authorization: Bearer <token>`.
  Este módulo usa dos abilities (catálogo completo en
  `app/Support/Auth/TokenAbilities.php`):

  | Ability | Para qué |
  |---|---|
  | `energy:read` | Consultar lecturas (`GET`) |
  | `energy:write` | Subirlas (`POST`). Es la que se graba en el controlador solar o en el contador de consumo |

  Son **excluyentes en la práctica**: un token con `energy:read` recibe `403` al
  intentar un `POST`, y uno con `energy:write` recibe `403` al hacer un `GET`. Si
  el cacharro necesita las dos cosas, pide las dos abilities en el mismo token.

  Un token ligado a un dispositivo (ability `device:{id}`) **sólo puede escribir
  y leer lo de ese dispositivo**. Intentar subir con el `hardware_device_id` de
  otro devuelve `422`.

- **Límites de tasa** (por token; configurables por entorno):

  | Grupo | Ruta | Límite |
  |---|---|---|
  | `api-store` | los `POST` de este módulo | 60/min |
  | `api` | los `GET` de este módulo | 60/min |
  | `api-global` | techo de toda la API | 300/min |

- **Sin campos `read_at`**: la API no acepta ni exige fechas en el payload. El
  momento de la medición es el `created_at` que pone el servidor al recibirla,
  lo que evita desfases en cacharros sin reloj. **Todo se guarda en UTC**; la
  traducción a `Europe/Madrid` es cosa de quien lo pinta.

---

## Antes de subir nada: dar de alta los elementos

Una lectura no cuelga del dispositivo, cuelga de un **elemento energético**
(`hardware_energy`): una fila que dice *qué papel* cumple algo dentro de ese
dispositivo.

| `role` | Qué es | Cuántos por dispositivo |
|---|---|---|
| `generator` | Lo que produce: panel, aerogenerador, dinamo | 1 |
| `battery` | Lo que almacena: banco de baterías | 1 |
| `load` | Lo que consume: router, servidor, farola, salida de carga | varios, uno por `sensor_position` |

Los dos montajes típicos:

- **Controlador solar** (un Renogy): tres elementos del mismo dispositivo, uno
  de cada rol, todos con `sensor_position = 0`. Un controlador es un aparato
  entero, no un monitor de canales.
- **Monitor multicanal** (un INA3221 con tres pinzas): tres elementos del mismo
  dispositivo, los tres con `role = load`, cada uno con su `sensor_position`
  (0, 1, 2) y apuntando con `hardware_device_monitorized_id` al aparato que mide.

Cada elemento lleva su `nominal_voltage`, y eso importa: **sin tensión por
elemento no salen los vatios**. Un servidor a 19 V y una Raspberry a 5 V en la
misma petición, multiplicados por una sola tensión, dan números sin sentido.

Si llega un canal que no está dado de alta, **el servidor lo crea solo** para no
perder el dato, pero sin inventarle tensión nominal, y lo dice en `warnings`. Es
una red de seguridad, no la forma de configurar la instalación: entra en el
panel y rellena su tensión y su fuente.

**En una batería, además, `voltage_min` y `voltage_max` son las tensiones a 0 % y
a 100 % de carga.** De ahí sale el `battery_percentage` cuando el aparato no
manda `soc`. Conviene poner las del banco real y no un rango de tolerancia
ancho.

---

## `POST /energy/readings` — subir telemetría

- **Auth**: `auth:sanctum` + `ability:energy:write`.
- **Rate limit**: `api-store` — 60/min por token.
- **Respuesta**: `201 Created`.

### Cuerpo

| Campo | Tipo | Reglas |
|---|---|---|
| `hardware_device_id` | int | **obligatorio**. Debe existir y ser del usuario del token |
| `duration` | int | opcional, mín. 1. Segundos que cubre la muestra. **Por defecto 60** |
| `energy` | object | **obligatorio**. Al menos uno de `generator`, `battery` o `loads` |
| `energy.duration` | int | opcional, mín. 1. Si está, manda sobre el `duration` de la raíz |
| `energy.generator` | object | opcional. Ver sub-bloque |
| `energy.battery` | object | opcional. Ver sub-bloque |
| `energy.loads` | array | opcional. Lista de consumos, uno por canal |
| `hardware_device_info` | object | opcional. Salud del aparato. Alias: `device` |

**`duration` es el campo que más se olvida y el que más duele.** Es lo que
convierte una potencia instantánea en energía: `Wh = V · A · duration / 3600`.
Si no lo mandas se asumen 60 s, y si tu aparato mide cada 5 minutos estarás
registrando la doceava parte de la energía real.

#### Sub-bloque `energy.generator`

| Campo | Tipo | Qué es |
|---|---|---|
| `voltage` | float | Tensión medida de entrada (V) |
| `amperage` | float | Corriente medida (A) |
| `power` | float | Potencia (W). Si se omite se calcula `V · A` |
| `temperature` | float | Temperatura del controlador o del generador (°C) |
| `fan` | int ≥ 0 | Estado o velocidad del ventilador |
| `charging_status` | int | Código numérico del modo de carga |
| `charging_status_label` | string (≤255) | Etiqueta del modo: `mppt`, `boost`, `float`… |
| `light_status` | bool | Salida de alumbrado encendida |
| `light_brightness` | int 0-100 | Nivel de alumbrado (%) |
| `energy_wh` | float ≥ 0 | Energía **de este intervalo**, si el aparato ya la da. Si viene, `sources.energy` pasa a `device` |
| `energy_ah` | float ≥ 0 | Amperios-hora **de este intervalo**, ídem |
| `today_energy_wh` | float ≥ 0 | Acumulado **del día** que lleva el aparato (Wh) |
| `today_energy_ah` | float ≥ 0 | Acumulado **del día** que lleva el aparato (Ah) |
| `today_voltage_min` | float | Tensión mínima **del día** que ha visto el aparato (V) |
| `today_voltage_max` | float | Tensión máxima **del día** que ha visto el aparato (V) |
| `today_amperage_max` | float | Corriente máxima **del día** que ha visto el aparato (A) |
| `today_power_max` | float | Potencia máxima **del día** que ha visto el aparato (W) |
| `historical_energy_wh` | float ≥ 0 | Acumulado **de por vida** del odómetro del aparato (Wh) |
| `historical_energy_ah` | float ≥ 0 | Acumulado **de por vida** del odómetro del aparato (Ah) |
| `battery_full_charges` | int ≥ 0 | Ciclos de carga completa. Su sitio natural es `battery`; se acepta aquí porque es donde los ponía el contrato de la V1 |
| `battery_over_discharges` | int ≥ 0 | Ciclos de sobredescarga. Ídem |
| `total_operating_days` | int | Días de funcionamiento que declara el aparato. Alias: `days_operating` |

#### Sub-bloque `energy.battery`

| Campo | Tipo | Qué es |
|---|---|---|
| `voltage` | float | Tensión de batería (V) |
| `amperage` | float | Corriente neta (A) |
| `power` | float | Potencia (W). Si se omite se calcula `V · A` |
| `soc` | int 0-100 | Estado de carga (%). Alias: `battery_percentage` |
| `temperature` | float | Temperatura de la batería (°C) |
| `charging_status` | int | Código numérico del modo |
| `charging_status_label` | string (≤255) | Etiqueta del modo |
| `energy_wh` / `energy_ah` | float | Energía del intervalo, si la da el aparato. **Aquí sí pueden ser negativos**: en una batería son el neto, y descargando son negativos |
| `today_energy_wh` | float | Acumulado del día en Wh |
| `today_energy_ah` | float | Acumulado del día en Ah |
| `today_voltage_min` | float | Tensión mínima del día que ha visto el aparato (V) |
| `today_voltage_max` | float | Tensión máxima del día que ha visto el aparato (V) |
| `today_amperage_max` | float | Corriente máxima del día que ha visto el aparato (A) |
| `today_power_max` | float | Potencia máxima del día que ha visto el aparato (W) |
| `historical_energy_wh` | float ≥ 0 | Acumulado de por vida del odómetro en Wh |
| `historical_energy_ah` | float ≥ 0 | Acumulado de por vida del odómetro en Ah |
| `battery_full_charges` | int ≥ 0 | Ciclos de carga completa que cuenta el aparato |
| `battery_over_discharges` | int ≥ 0 | Ciclos de sobredescarga |
| `total_operating_days` | int | Días de funcionamiento. Alias: `days_operating` |

Si mandas `voltage` y **no** mandas `soc`, el servidor lo calcula
proporcionalmente entre el `voltage_min` y el `voltage_max` configurados en el
elemento. Con 11,0 y 14,4 V configurados, 12,7 V da 50 %.

#### Sub-bloque `energy.loads` (array de objetos)

| Campo | Tipo | Qué es |
|---|---|---|
| `channel` | int ≥ 0 | Canal del sensor. Alias: `sensor_position`. **Por defecto 0** |
| `voltage` | float | Tensión del canal (V). Si falta se usa la nominal del elemento |
| `amperage` | float | Corriente consumida (A) |
| `power` | float | Potencia (W). Si se omite se calcula `V · A` |
| `temperature` | float | Temperatura del canal o del sensor (°C) |
| `fan` | int ≥ 0 | Estado del ventilador |
| `energy_wh` / `energy_ah` | float ≥ 0 | Energía del intervalo, si la da el aparato |
| `today_energy_wh` | float ≥ 0 | Consumo acumulado del día (Wh) |
| `today_energy_ah` | float ≥ 0 | Consumo acumulado del día (Ah) |
| `today_voltage_min` | float | Tensión mínima del día que ha visto el aparato (V) |
| `today_voltage_max` | float | Tensión máxima del día que ha visto el aparato (V) |
| `today_amperage_max` | float | Corriente máxima del día que ha visto el aparato (A) |
| `today_power_max` | float | Potencia máxima del día que ha visto el aparato (W) |
| `historical_energy_wh` | float ≥ 0 | Consumo acumulado de por vida (Wh) |
| `historical_energy_ah` | float ≥ 0 | Consumo acumulado de por vida (Ah) |
| `battery_full_charges` | int ≥ 0 | Ciclos de carga completa. Su sitio natural es `battery` |
| `battery_over_discharges` | int ≥ 0 | Ciclos de sobredescarga. Ídem |
| `total_operating_days` | int | Días de funcionamiento. Alias: `days_operating` |

El `channel` casa con el `sensor_position` del elemento. Dos entradas con el
mismo canal en la misma petición se guardan como **dos muestras** de ese canal y
las dos cuentan en el resumen del día. Una lista `loads` vacía es válida y no
inventa ninguna lectura a cero: es lo que manda un controlador que no tiene nada
conectado a su salida.

#### Bloque opcional `hardware_device_info` / `device`

La salud del cacharro que mide, para no tener que hacer una segunda petición a
`PUT /hardware/devices/{id}/status`. Todos los campos son opcionales:

| Campo | Tipo |
|---|---|
| `temp` | float |
| `voltage` | float |
| `battery_level` | int 0-100 |
| `cpu` | float 0-100 |
| `disk` | float 0-100 |
| `ram` | float 0-100 |
| `uptime` | int ≥ 0 |
| `ip_local` | IP |
| `ip_public` | IP |
| `extra` | objeto de valores simples |

La IP pública la resuelve el servidor; no hace falta mandarla.

### Qué hace el servidor con lo que mandas

**La regla de oro: lo que mandas se guarda tal cual; lo que no mandas, se
calcula.** Nunca al revés. Si tu aparato lleva su propio contador, el nuestro no
lo sustituye ni lo corrige.

1. **Lo que manda el aparato, manda.** Si viene `power`, se guarda ese y no
   `V · A`. Si viene `energy_wh`, se guarda como energía del intervalo y
   `sources.energy` queda en `device`; si no, se integra y queda en `derived`.
2. **Derivaciones**, cuando no vienen dadas:
   - `power = voltage · amperage` (W)
   - `energy_ah = amperage · duration / 3600` (Ah)
   - `energy_wh = energy_ah · voltage` (Wh)
3. **La tensión medida se guarda siempre.** La `nominal_voltage` del elemento se
   usa **sólo** cuando no mandas `voltage`, y entonces `sources.voltage` queda en
   `nominal` en vez de `measured`. Si mandas una tensión que se sale del rango
   configurado del elemento, se guarda igual y se avisa en `warnings`: un panel a
   0 V es de noche y una batería por debajo de su mínimo es una sobredescarga, y
   las dos cosas hay que poder verlas. Si no hay ni medida ni nominal, la lectura
   se guarda marcada como sospechosa y no entra en los resúmenes.
4. **Los acumuladores del día sustituyen; los de por vida nunca bajan.**
   `today_energy_*` reemplaza el total del día en vez de sumarse —si se sumara se
   contaría dos veces—, y `historical_energy_*` alimenta el total de por vida,
   que sólo puede subir: si el aparato se reinicia y sus contadores vuelven a
   cero, se abre una sesión nueva y la anterior se conserva entera.
5. **Los vatios-hora y los amperios-hora se tratan por separado.** Puedes mandar
   el odómetro de uno y no del otro: el que mandas se guarda tal cual y el que no,
   se calcula de tus lecturas. Es el caso del Renogy Rover, que lleva contador de
   amperios-hora de batería pero no de vatios-hora de batería.
6. **Los máximos del día que declaras ensanchan, nunca recortan.** Si dices que
   el máximo del día fueron 90 W pero en esta misma petición mandas 104 W, se
   queda el 104: tu contador ve picos que nuestro muestreo se pierde, pero una
   medida concreta no se puede borrar.
7. **Lecturas sospechosas.** Una corriente negativa en un generador o en un
   consumo, o una lectura sin tensión posible, se guardan igual —el dato crudo no
   se tira— pero marcadas con `is_suspicious` y fuera de todos los agregados.
   Siempre se avisa en `warnings`. **En la batería la corriente negativa es
   normal** (está descargando) y no marca nada.

### Respuesta `201 Created`

Una entrada en `data` por cada lectura guardada, en el orden en que se procesan
los bloques: primero `generator`, luego `battery`, luego cada uno de los `loads`.

```json
{
  "success": true,
  "message": "Telemetría de energía almacenada correctamente.",
  "data": [
    {
      "id": 1024,
      "hardware_device_id": 15,
      "hardware_energy_id": 4,
      "role": "generator",
      "measured": {
        "amperage": 4.2,
        "voltage": 34.5,
        "power": 144.9,
        "delta_seconds": 60,
        "temperature": null,
        "battery_voltage": null,
        "battery_percentage": null,
        "fan": null,
        "charging_status": 3,
        "charging_status_label": "mppt",
        "light_status": false,
        "light_brightness": null
      },
      "derived": {
        "energy_wh": 2.415,
        "energy_ah": 0.07
      },
      "sources": {
        "energy": "derived",
        "voltage": "measured"
      },
      "is_suspicious": false,
      "suspicious_reason": null,
      "created_at": "2026-09-12T17:27:35.000000Z"
    }
  ]
}
```

| Campo | Qué es |
|---|---|
| `role` | `generator`, `battery` o `load`: a qué elemento fue la lectura |
| `measured` | Lo que se ha guardado como medido, ya con los respaldos aplicados |
| `derived` | La energía de este intervalo, en Wh y Ah |
| `sources.energy` | `device` si la energía la dio el aparato, `derived` si la calculó el servidor |
| `sources.voltage` | `measured` si la tensión es la medida, `nominal` si se usó la del elemento |
| `is_suspicious` | `true` si la lectura queda fuera de los agregados |
| `suspicious_reason` | Por qué, en texto |
| `created_at` | ISO-8601 **en UTC** |

Las magnitudes vienen redondeadas a la precisión de su columna: 4 decimales en
`energy_wh` y `energy_ah`, 3 en `voltage`, `amperage`, `power` y `temperature`,
2 en `battery_voltage`. El `POST` y el `GET` devuelven exactamente lo mismo para
la misma lectura.

### Avisos (`warnings`)

Cuando algo merece atención pero la petición se ha guardado, la respuesta añade
un array `warnings` de textos. **Si no hay nada que avisar, la clave no
aparece.** Un cliente no debería dar por hecho que existe.

```json
{
  "success": true,
  "message": "Telemetría de energía almacenada correctamente.",
  "data": [ "…" ],
  "warnings": [
    "Consumo canal 0: corriente negativa (-3 A)."
  ]
}
```

Los que puedes encontrarte:

| Aviso | Qué ha pasado | ¿Se guarda? |
|---|---|---|
| `…: se ha dado de alta el elemento #N automáticamente; rellena su tensión nominal…` | Llegó un rol o canal sin configurar | Sí, pero configúralo |
| `…: X V se sale del rango configurado del elemento; se guarda igual.` | La tensión medida cae fuera de `voltage_min`/`voltage_max`. Puede ser el dato bueno: un panel a 0 V es de noche y una batería por debajo de su mínimo es una sobredescarga | Sí, y cuenta en los agregados |
| `…: corriente negativa (X A).` | Sólo en `generator` y en `loads`. **En `battery` la corriente negativa es normal** —está descargando— y no avisa nada | Sí, marcada y **fuera** de los agregados |
| `…: sin tensión ni medida ni nominal.` | Sin tensión no hay vatios que calcular | Sí, marcada y **fuera** de los agregados |
| `…: el elemento está desactivado; lectura ignorada.` | El elemento tiene `is_active = false` | **No** |
| `…: el elemento (#N) está borrado; restáuralo…` | Hay un elemento borrado ocupando ese rol y canal | **No** |

### Qué pasa si un bloque falla y otro no

Los bloques se procesan por separado. Si la salida de carga está desactivada
pero la generación y la batería están bien, **la petición devuelve `201` con las
dos lecturas que sí se pudieron guardar** y un `warnings` explicando la tercera.
Un canal mal configurado no tira al suelo el resto de la muestra.

Si **ninguna** lectura se puede guardar, la respuesta es `422`: antes de D115
esto devolvía `201` y el dato se perdía en silencio durante meses.

### Errores

| Código | Cuándo |
|---|---|
| `401` | Sin token o token inválido |
| `403` | El token no tiene `energy:write` |
| `422` | Falla la validación, el dispositivo no existe, o el token está ligado a otro dispositivo |
| `429` | Se ha pasado del límite de tasa |

Los errores del bloque de energía vienen agrupados bajo la clave `energy`,
porque se validan como un bloque entero:

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "energy": [
      "El estado de carga (SOC) de la batería debe estar comprendido entre 0 y 100 %."
    ]
  }
}
```

Un `energy` vacío (`{}` o `[]`) se trata como si no lo hubieras mandado:
responde que es obligatorio. Un `energy` con contenido pero sin ningún
subsistema responde que debe traer al menos `generator`, `battery` o `loads`.

---

## Ejemplos completos

### Controlador solar (un aparato, los tres papeles)

Un Renogy Rover manda de una vez lo que produce el panel, cómo está la batería y
lo que sale por la salida de carga. Tres elementos, todos en el canal 0, **cada
uno a su tensión**: el panel a 24 V, la batería a 12 V y el consumo a 12 V.

Este ejemplo lleva **todo** lo que el controlador sabe. La correspondencia con
los registros Modbus está en
[`../../hardware/renogy-rover.md`](../../hardware/renogy-rover.md).

```json
{
  "hardware_device_id": 6,
  "duration": 60,
  "device": { "temp": 41.5, "uptime": 864000, "extra": { "system_voltage": 24 } },
  "energy": {
    "generator": {
      "voltage": 34.5,
      "amperage": 4.2,
      "power": 144.9,
      "temperature": 31.5,
      "charging_status": 3,
      "charging_status_label": "mppt",
      "light_status": false,
      "light_brightness": 0,
      "today_energy_wh": 1250.0,
      "today_energy_ah": 52.0,
      "today_amperage_max": 6.1,
      "today_power_max": 148.0,
      "historical_energy_wh": 45000.0,
      "historical_energy_ah": 1875.0,
      "total_operating_days": 1745
    },
    "battery": {
      "voltage": 13.4,
      "amperage": 5.0,
      "power": 67.0,
      "soc": 92,
      "temperature": 24.5,
      "today_voltage_min": 12.1,
      "today_voltage_max": 14.3,
      "today_energy_ah": 40.0,
      "historical_energy_ah": 1500.0,
      "battery_full_charges": 25,
      "battery_over_discharges": 1,
      "total_operating_days": 1745
    },
    "loads": [
      {
        "channel": 0,
        "voltage": 12.1,
        "amperage": 2.5,
        "power": 30.3,
        "fan": 0,
        "today_energy_wh": 310.0,
        "today_energy_ah": 25.6,
        "today_amperage_max": 4.4,
        "today_power_max": 56.0,
        "historical_energy_wh": 12500.0,
        "historical_energy_ah": 1030.0,
        "total_operating_days": 1745
      }
    ]
  }
}
```

Devuelve tres lecturas: una `generator`, una `battery` y una `load`.

**Qué pasa con cada magnitud.** El panel y el consumo declaran sus dos
acumuladores de por vida, así que los dos quedan congelados como del aparato y
nadie los recalcula. La batería sólo declara amperios-hora —el Rover no tiene
registro de vatios-hora de batería—, así que sus Wh se calculan de las lecturas.
Eso se ve en la respuesta del `GET` y en el panel de administración, donde cada
sesión histórica dice de dónde sale cada una de las dos cifras.

### Monitor de tres canales (un aparato, tres consumos ajenos)

Tres pinzas midiendo tres cacharros distintos. No manda acumulados: el servidor
integra la energía de cada intervalo y la va sumando.

```json
{
  "hardware_device_id": 12,
  "duration": 300,
  "energy": {
    "loads": [
      { "channel": 0, "voltage": 12.0, "amperage": 2.0 },
      { "channel": 1, "voltage": 19.0, "amperage": 3.0 },
      { "channel": 2, "voltage": 5.0,  "amperage": 1.2 }
    ]
  }
}
```

Devuelve tres lecturas, cada una con la tensión de **su** elemento: 24 W, 57 W y
6 W. Con `duration = 300`, son 2 Wh, 4,75 Wh y 0,5 Wh de ese intervalo.

### Nodo autónomo (placa pequeña, batería y su propio consumo)

Un microcontrolador con un INA que mide las tres cosas y no lleva contadores de
nada. Manda tensión, corriente y el intervalo, y el servidor hace el resto.

```json
{
  "hardware_device_id": 21,
  "duration": 1800,
  "energy": {
    "generator": { "voltage": 5.9, "amperage": 0.08 },
    "battery":   { "voltage": 3.95, "amperage": 0.05 },
    "loads":     [ { "channel": 0, "voltage": 3.3, "amperage": 0.03 } ]
  }
}
```

Con media hora de intervalo salen 0,236 Wh generados, 0,0988 Wh a la batería y
0,0495 Wh consumidos. El porcentaje de carga de la batería se deduce de su
tensión si el elemento tiene puestas las de 0 % y 100 %.

### El que se mide a sí mismo

```json
{
  "hardware_device_id": 12,
  "energy": { "duration": 600, "loads": [ { "voltage": 5.14, "amperage": 0.23 } ] }
}
```

Canal 0 por defecto. Diez minutos a 1,18 W son 0,197 Wh.

### Lo mínimo que se puede mandar

```json
{
  "hardware_device_id": 12,
  "energy": { "loads": [ { "amperage": 1.5 } ] }
}
```

Canal 0 por defecto, `duration` 60 s por defecto, y la tensión sale de la
nominal del elemento. Funciona, pero `sources.voltage` dirá `nominal` y, si tu
aparato no sube cada minuto, la energía registrada no será la real: **manda
siempre `duration`.**

---

## `GET /energy/readings` — consultar lecturas

- **Auth**: `auth:sanctum` + `ability:energy:read`.
- **Rate limit**: `api` — 60/min por token.

| Parámetro | Tipo | Qué hace |
|---|---|---|
| `hardware_device_id` | int | Filtra por dispositivo medidor |
| `hardware_energy_id` | int | Filtra por elemento concreto |
| `role` | string | Filtra por rol: `generator`, `battery` o `load` |
| `is_suspicious` | bool | `1`/`0` para ver sólo las marcadas o sólo las buenas |
| `created_at` | fecha | Filtra por momento de la lectura |
| `sort` | string | `id` o `created_at`, con `-` delante para descendente. Por defecto `-created_at` |
| `per_page` | int | Por página (por defecto 25, máx. 100) |
| `page` | int | Número de página |

Sólo devuelve lecturas de dispositivos del usuario del token y, si el token está
ligado a dispositivos concretos, sólo las de ésos.

```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": [ "…igual que en el POST…" ],
  "meta": {
    "total": 3,
    "per_page": 2,
    "current_page": 1,
    "last_page": 2,
    "from": 1,
    "to": 2
  }
}
```

---

> Creado: 2026-09-06 · Última revisión: 2026-09-13
