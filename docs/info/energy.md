# Módulo: Energía

Lo que un dispositivo **mide** de energía: un controlador solar (Renogy Rover y
compatibles) o un monitor de consumo de varios canales, con sus resúmenes
diarios e históricos.

Es un módulo al mismo nivel que la estación meteorológica, KeyCounter,
SmartPlant o AirFlight, y como todos ellos sus lecturas cuelgan de un
`hardware_device_id`. **El aparato en sí** —inventario y salud: IP, uptime, CPU,
RAM, discos, temperatura— es del módulo [hardware.md](hardware.md).

> Estuvo dentro de Hardware hasta el **2026-09-06**, en rutas `/hardware/*` y con
> la ability `hardware:write`. Era una excepción sin motivo: ningún otro módulo
> vivía ahí, y además metía dos permisos en la misma casilla —el token de un
> contador de consumo podía reescribir la salud del aparato—.

**Contrato HTTP para clientes:** [docs/info/api/v2/energy.md](api/v2/energy.md).

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Hardware/EnergySourceType.php` | `energy_source_types` | Catálogo de fuentes: solar, eólica, autoabastecido, batería, red |
| `app/Models/Hardware/EnergySystem.php` | `energy_systems` | La instalación: agrupa elementos que comparten batería y tensión |
| `app/Models/Hardware/HardwareEnergy.php` | `hardware_energy` | **El elemento energético**: un panel, un router, una batería |
| `app/Models/Hardware/HardwarePowerGenerator.php` | `hardware_power_generators` | Lectura de generación |
| `app/Models/Hardware/HardwarePowerGeneratorToday.php` | `hardware_power_generators_today` | Resumen diario del generador |
| `app/Models/Hardware/HardwarePowerGeneratorHistorical.php` | `hardware_power_generators_historical` | Histórico del generador |
| `app/Models/Hardware/HardwarePowerLoad.php` | `hardware_power_loads` | Lectura de consumo |
| `app/Models/Hardware/HardwarePowerLoadToday.php` | `hardware_power_loads_today` | Resumen diario de consumos |
| `app/Models/Hardware/HardwarePowerLoadHistorical.php` | `hardware_power_loads_historical` | Histórico de consumos |
| `app/Models/Hardware/HardwarePowerGeneratorSolar.php` | `hardware_power_generators_solar` | Lectura de controlador solar. `extends HardwarePowerGenerator` |

### Controladores, requests y resources (API V2)
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Controllers/Api/Energy/V2/EnergyMonitorController.php` | Índice y subida de lecturas de energía |
| `app/Http/Controllers/Api/Energy/V2/SolarReadingController.php` | Índice y subida de lecturas del controlador solar |
| `app/Http/Requests/Api/Energy/V2/StoreEnergyRequest.php` | Validación de la subida de energía |
| `app/Http/Requests/Api/Energy/V2/StoreSolarReadingRequest.php` | Validación de la lectura solar. **Único sitio donde se traduce el vocabulario del Renogy Rover** |
| `app/Http/Resources/V2/Energy/EnergyMonitorResource.php` | Una lectura de energía, ya asignada a su elemento |
| `app/Http/Resources/V2/Energy/SolarReadingResource.php` | Una lectura del controlador solar |
| `routes/energy/v2.php` | Las cuatro rutas del módulo |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Hardware/HardwareService.php` | `storeEnergyData()` y `storeSolarReading()`: el reparto en tablas y los avisos |
| `app/Policies/EnergySystemPolicy.php` | Instalaciones energéticas, sobre `OwnedResourcePolicy` |
| `app/Policies/HardwareEnergyPolicy.php` | Elementos de energía; el dueño se resuelve por el sistema del que cuelgan |
| `app/Traits/IsEnergyReading.php` | Lo común a las tablas de lecturas: elemento, scopes y marca de sospecha |
| `app/Traits/SummarisesEnergyDay.php` | Resumen del día: una fila por elemento y día |
| `app/Traits/AccumulatesEnergyHistory.php` | Acumulado, recalculado desde los resúmenes diarios |
| `database/seeders/EnergySystemsSeeder.php` | Las cuatro instalaciones reales |

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| GET | `/api/v2/energy/readings` | `ability:energy:read` | api | Lecturas de energía paginadas (`?type=load\|generator`) |
| GET | `/api/v2/energy/solar-readings` | `ability:energy:read` | api | Lecturas del controlador solar, paginadas |
| POST | `/api/v2/energy/readings` | `ability:energy:write` | api-store | Subida del monitor de energía |
| POST | `/api/v2/energy/solar-readings` | `ability:energy:write` | api-store | Subida del controlador solar |

Las dos subidas devuelven `warnings` en el cuerpo cuando algo es raro pero se ha
guardado. Un `warnings` no vacío significa que hay algo que revisar en el montaje
o en la configuración de los elementos.

Las dos admiten además `hardware_device_info` para mandar la salud del propio
aparato en la misma petición, sin necesidad de `hardware:write` ni de una segunda
llamada.

### Token de un cacharro de energía

```bash
php artisan iot:device-token <id-del-dispositivo> --abilities=energy:write
```

Se le añade sola la ability `device:{id}`, que lo ata a ese aparato: aunque el
token se filtre, sólo puede escribir lecturas de ése.

## Qué escribe una subida del controlador solar

Una sola petición a `POST /energy/solar-readings` toca **seis tablas**, igual que
en la V1:

| Tabla | Qué se guarda |
|---|---|
| `hardware_power_generators_solar` | La lectura cruda completa del controlador |
| `hardware_power_generators_today` | Resumen del día del elemento generador |
| `hardware_power_generators_historical` | Acumulado del elemento generador |
| `hardware_power_loads` | La salida de carga del controlador, como consumo |
| `hardware_power_loads_today` | Resumen del día del elemento de consumo |
| `hardware_power_loads_historical` | Acumulado del elemento de consumo |

Las cinco últimas **no se escribían** desde la V2: `storeSolarReading()` guardaba
sólo la fila cruda. El panel de energía y sus gráficas leen de los resúmenes, así
que se quedaron sin datos nuevos mientras la tabla de lecturas crecía. Corregido
el 2026-09-06.

### Tres reglas

1. **Si lo manda el aparato, se toma; si no, se calcula.** Vale para la potencia
   de la lectura y para los acumulados del día y del total. Un controlador solar
   lleva sus propias cuentas (`day_power_generation_wh`, `total_*`…) y las manda
   en cada lectura: sumar las nuestras encima daría el doble.
2. **El acumulado nunca baja.** Un controlador se resetea y vuelve a contar desde
   cero; ese día su «total» es menor que lo guardado. Se conserva el mayor entre
   lo que había, lo que suman los resúmenes diarios y lo que dice el aparato. Es
   la regla que ya tenía la V1, y por eso el Rover de producción conserva 66.388
   Wh acumulados mientras el aparato dice 36.087.
3. **La salida de carga es consumo.** `load_voltage`, `load_current` y
   `load_power` van a las tablas de consumo. Necesitan un elemento de rol `load`
   dado de alta en el mismo dispositivo; si no lo hay, la respuesta lo avisa en
   `warnings` en vez de tirar el dato en silencio.

## Modelo de energía (D115)

Tres problemas que arrastraba el módulo y que este modelo resuelve:

1. **No existía la entidad «elemento energético».** Las lecturas se indexaban
   sólo por dispositivo. Un monitor mide un panel y un router a la vez, y el
   panel no existía como fila en ningún sitio: no había dónde guardar su tensión
   ni su tipo. `hardware_energy` **ya era esa entidad** desde 2022, sólo que sin
   los campos que la hacen útil.
2. **Sin tensión por elemento, los vatios estaban mal.** Se multiplicaba la
   corriente de cada canal por *el único voltaje de la petición*: un panel de
   24 V y una Pico de 3,7 V en la misma petición daban números sin sentido.
3. **Sólo se guardaba potencia instantánea.** `SUM(power)` no son vatios-hora:
   da un número que sube si el sensor mide más veces.

### Lo que manda un dispositivo

`POST /api/v2/energy/readings`

```jsonc
{
  "hardware_device_id": 7,        // quién mide
  "duration": 300,                // segundos que cubre la medición
  "temperature": 41.5,            // opcional, del propio monitor
  "readings": [
    {
      "pos": 0,                   // canal -> hardware_energy.sensor_position
      "amperage": 1.42,           // corriente MEDIA del periodo (A)
      "voltage": 12.4,            // tensión del periodo (V)
      "energy_wh": null,          // opcional: sólo si el aparato lo calcula mejor
      "duration": null            // opcional: si este canal mide a otra cadencia
    }
  ],
  "battery_voltage": 3.92,        // opcional, del PROPIO dispositivo (D108)
  "battery_percentage": 78        // opcional
}
```

`amperage` es la corriente **media del periodo**, no una instantánea. Si falla
internet, el aparato sigue promediando y `duration` crece: una media de 20
minutos y una de 5 son igual de válidas, porque `A · s` da lo mismo.

**No hay traducción de nombres antiguos.** Éste es el vocabulario; el firmware
se ajusta a él. La única excepción del proyecto es el Renogy Rover, que es un
aparato comercial cuyo protocolo no se puede cambiar.

### Lo que deriva el backend

```
W  = V · A
Ah = A · s / 3600
Wh = V · A · s / 3600
```

Si `energy_wh` viene del aparato, **gana** al calculado. Los tres crudos
(`amperage`, `voltage`, `delta_seconds`) **no se tiran nunca**: si un día se
descubre que un elemento tenía la tensión mal puesta, con ellos se recalcula el
histórico entero.

De cada número se guarda de dónde salió:

| Columna | Valores | Qué dice |
|---|---|---|
| `energy_source` | `device` / `derived` | Si los Wh los dio el aparato o los calculamos |
| `voltage_source` | `measured` / `nominal` | Si la tensión es la medida o la nominal del elemento |

### Lecturas sospechosas

Una lectura rara **se marca, nunca se descarta** (D72): queda fuera de los
agregados del día, pero sigue en su tabla para poder mirarla, y la respuesta
lleva un `warnings` explicando qué pasó.

| Situación | Qué se hace |
|---|---|
| Corriente negativa | Se marca. Es una avería de cableado, no un dato (D110) |
| Tensión medida fuera de rango | Se usa `nominal_voltage` del elemento y se anota `voltage_source: nominal` |
| Ni tensión medida ni nominal | Se marca: sin tensión no hay vatios. **No se inventa un 0** |
| Sin `duration` | Se guarda la potencia, no los vatios-hora. Sólo aviso |

**Nada de corrientes con signo** (D110). El estado de carga es un campo propio,
`charging_status`, que el Rover ya manda.

### Los tres perfiles de subida (D108)

| Perfil | Qué sube | Qué se escribe |
|---|---|---|
| IoT modesto | A + V + segundos | Lectura. Sin histórico |
| Consumo puro (routers a 12,4 V) | Igual. No hay generación | Lectura, elemento con `role = load` |
| Controlador solar | Lo anterior **+** acumulados desde el reinicio | Lectura en `…_generators_solar`, con detección de reinicio |

### El reparto en tablas

```
readings[].pos --(== hardware_energy.sensor_position)--> elemento
                              |
             role: generator  |  role: load | storage
                   v          |          v
   hardware_power_generators  |  hardware_power_loads
                   +----------+----------+
                              v
                 ..._today       (una fila por elemento y día)
                 ..._historical  (acumulado, recalculado desde los diarios)
```

Un elemento con `role = storage` es la batería: su corriente se registra en la
tabla de consumos, porque los agregados de generación de una instalación cuentan
**sólo** los elementos con `role = generator`. Así una batería cargándose no se
cuenta como energía producida.

La lectura se atribuye al dispositivo **monitorizado**, no al monitor.

### Los resúmenes

En `…_today` y `…_historical` lo que se acumula es `energy_wh` y `energy_ah`.
Las columnas sueltas `power` y `amperage` de esas cuatro tablas **ya no existen**:
eran acumuladores de potencia instantánea, y el nombre era el que estaba mal. Los
`*_min` y `*_max` sí se quedan, porque un extremo de la magnitud instantánea sí
significa algo.

`days_operating` es el número de días **distintos** con lecturas. Antes era
`count(id)` sobre una tabla que, por buscar la última fila del dispositivo sin
filtrar por fecha, sólo llegó a tener una fila por dispositivo: valía 1 desde
2022.

### La batería del propio dispositivo (D108)

`hardware_devices.battery_voltage`, `battery_percentage` y `battery_read_at`. La
puede mandar **cualquier** endpoint IoT —estación, keycounter, smartplant,
energía— y siempre es opcional. `battery_read_at` existe para distinguir un dato
de ahora de uno de hace tres semanas.

Meterla en las tablas de energía era exactamente lo que hacía el caso especial
del dispositivo 14 en V1.

### El controlador solar (D109)

> El **mapa de registros Modbus** del Rover —qué da el aparato, en qué unidades y
> a qué columna va cada registro— está en
> [`hardware/renogy-rover.md`](hardware/renogy-rover.md). No está en ningún otro
> sitio del repositorio.

`hardware_power_generators_solar` es un **superconjunto** de la tabla de
generadores, y su modelo hereda de `HardwarePowerGenerator`. Añade lo que un
generador genérico no tiene: el bloque `day_*` de estadísticas del día y el
`total_*` de acumulado histórico del mapa Modbus.

`total_operating_days` sólo puede subir. Si en una lectura **baja**, el
controlador se ha reseteado: esa lectura abre **fila nueva** y no machaca la
anterior. Sin eso, un reset borra el acumulado de años.

### Las cuatro instalaciones

| Instalación (`slug`) | Tensión | Batería | Qué alimenta |
|---|---|---|---|
| `casa` | 24 V placas / 12 V batería | 500 Ah | Portátil, servidor, pantallas. Es el Renogy Rover |
| `autonomo-grande` | 12 V | 100 Ah | IoT y routers |
| `banco-routers` | 12,4 V estabilizado | — | Routers, switches, modems. Sólo consumo |
| `nodos-iot` | 3,7-6 V | 500-2000 mAh | Uno por cacharro, `is_standalone` |

Las crea `EnergySystemsSeeder`. Sus **elementos** se asignan desde el panel
poniéndole a cada fila de `hardware_energy` su `energy_system_id`, su `role` y su
`nominal_voltage` — esta última es la que arregla el cálculo de los vatios.

## Campos del modelo HardwareEnergy (el elemento)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `hardware_device_id` | int | Dispositivo que **mide** |
| `hardware_device_monitorized_id` | int | Dispositivo **medido** |
| `energy_system_id` | int | Instalación a la que pertenece |
| `energy_source_type_id` | int | Tipo de fuente |
| `name` | string | «Panel sur», «Router principal» |
| `role` | string | `generator` \| `load` \| `storage` |
| `is_generator` | bool | Se conserva hasta migrar del todo a `role` (D70) |
| `sensor_position` | int | Canal del monitor: casa con `readings[].pos` |
| `nominal_voltage` | decimal | Tensión nominal. **La que arregla el cálculo de los vatios** |
| `voltage_min` / `voltage_max` | decimal | Fuera de este rango, la tensión medida no se cree |
| `rated_power_w` | decimal | Potencia nominal (W) |
| `capacity_mah` / `capacity_wh` | decimal | Capacidad de la batería del elemento |
| `is_active` | bool | Un elemento retirado deja de aceptar lecturas nuevas |

## Campos de una lectura (`HardwarePowerGenerator` / `HardwarePowerLoad`)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `hardware_device_id` | int | Dispositivo **medido** |
| `hardware_energy_id` | int | Elemento al que corresponde la lectura |
| `amperage` | decimal(10,3) | **Crudo**: corriente media del periodo (A) |
| `voltage` | decimal(10,3) | **Crudo**: tensión del periodo (V) |
| `delta_seconds` | int | **Crudo**: segundos que cubre la media |
| `power` | decimal(12,3) | `V·A`. Potencia **media** del periodo, no instantánea |
| `energy_wh` | decimal(14,4) | `V·A·s/3600`. Esto sí se suma |
| `energy_ah` | decimal(14,4) | `A·s/3600`. Esto sí se suma |
| `energy_source` | string | `device` \| `derived` |
| `voltage_source` | string | `measured` \| `nominal` |
| `is_suspicious` | bool | Queda fuera de los agregados, pero se conserva |
| `suspicious_reason` | string | Por qué |
| `temperature` | decimal | Temperatura del aparato |
| `battery_voltage` / `battery_percentage` | | Batería del elemento medido |
| `read_at` | timestamp | Cuándo se **midió**, no cuándo llegó |

Sólo en generadores: `charging_status`, `charging_status_label`,
`battery_temperature`, `light_status`, `light_brightness`.
Sólo en consumos: `fan`.

---

> Creado: 2026-09-06 (separado de `hardware.md`) · Última revisión: 2026-09-06
