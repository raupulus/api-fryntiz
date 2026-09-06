# Módulo: Hardware y Energía

Módulo IoT para gestionar dispositivos hardware, monitorizar consumos de energía y registrar datos de cargas solares, generadores y consumos con resúmenes diarios e históricos.

## Archivos principales

### Modelos
| Archivo | Tabla | Descripción |
|---------|-------|-------------|
| `app/Models/Hardware/HardwareDevice.php` | `hardware_devices` | Dispositivo hardware principal |
| `app/Models/Hardware/HardwareType.php` | `hardware_types` | Tipos de dispositivo |
| `app/Models/Hardware/HardwareComponent.php` | `hardware_components` | Componentes de un dispositivo |
| `app/Models/Hardware/HardwareAvailableComponent.php` | `hardware_available_components` | Catálogo de componentes disponibles |
| `app/Models/Hardware/EnergySourceType.php` | `energy_source_types` | Catálogo de fuentes: solar, eólica, autoabastecido, batería, red |
| `app/Models/Hardware/EnergySystem.php` | `energy_systems` | La instalación: agrupa elementos que comparten batería y tensión |
| `app/Models/Hardware/HardwareEnergy.php` | `hardware_energy` | **El elemento energético**: un panel, un router, una batería |
| `app/Models/Hardware/HardwarePowerGenerator.php` | `hardware_power_generators` | Datos de generador solar en tiempo real |
| `app/Models/Hardware/HardwarePowerGeneratorToday.php` | `hardware_power_generators_today` | Resumen diario del generador |
| `app/Models/Hardware/HardwarePowerGeneratorHistorical.php` | `hardware_power_generators_historical` | Histórico del generador |
| `app/Models/Hardware/HardwarePowerLoad.php` | `hardware_power_loads` | Datos de consumo en tiempo real |
| `app/Models/Hardware/HardwarePowerLoadToday.php` | `hardware_power_loads_today` | Resumen diario de consumos |
| `app/Models/Hardware/HardwarePowerLoadHistorical.php` | `hardware_power_loads_historical` | Histórico de consumos |
| `app/Models/Hardware/HardwarePowerGeneratorSolar.php` | `hardware_power_generators_solar` | Lectura de controlador solar. `extends HardwarePowerGenerator` |

### Controladores
| Archivo | Versión | Descripción |
|---------|---------|-------------|
| `app/Http/Controllers/Api/Hardware/V2/HardwareDeviceController.php` | API V2 | Ver dispositivo, listar computadores |
| `app/Http/Controllers/Api/Hardware/V2/EnergyMonitorController.php` | API V2 | Store datos de energía |
| `app/Http/Controllers/Api/Hardware/V2/SolarReadingController.php` | API V2 | Store de lectura del controlador solar |
| `app/Http/Controllers/Hardware/*.php` | Web | Controladores frontend (10 archivos) |

### Servicios
| Archivo | Descripción |
|---------|-------------|
| `app/Services/Hardware/HardwareService.php` | Lógica: info dispositivo, store energía/solar, lista computadores |
| `app/Services/Hardware/DeviceTokenService.php` | Emisión de tokens Sanctum ligados a un dispositivo (fuente única para el comando y Filament) |

### Resources API V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Resources/V2/Hardware/HardwareDeviceResource.php` | Resource dispositivo |
| `app/Http/Resources/V2/Hardware/EnergyMonitorResource.php` | Resource energía |
| `app/Http/Resources/V2/Hardware/SolarReadingResource.php` | Resource de lectura solar |
| `app/Http/Resources/V2/Hardware/DeviceStatusResource.php` | Resource último estado del dispositivo |

### FormRequests V2
| Archivo | Descripción |
|---------|-------------|
| `app/Http/Requests/Api/Hardware/V2/StoreEnergyRequest.php` | Validación store energía |
| `app/Http/Requests/Api/Hardware/V2/StoreSolarReadingRequest.php` | Validación de la lectura solar. **Único sitio donde se traduce el vocabulario del Renogy Rover** |
| `app/Http/Requests/Api/Hardware/V2/StoreDeviceStatusRequest.php` | Validación store estado del dispositivo (acepta `hardware_device_info` agrupado) |

### Otros
| Archivo | Descripción |
|---------|-------------|
| `app/Policies/HardwarePolicy.php` | Política de autorización de dispositivos. El administrador **con sesión** alcanza los ajenos; un token de cacharro sigue atado a su `device:{id}` |
| `app/Policies/EnergySystemPolicy.php` | Instalaciones energéticas, sobre `OwnedResourcePolicy` |
| `app/Policies/HardwareEnergyPolicy.php` | Módulos de energía; el dueño se resuelve por el sistema del que cuelgan |
| `app/Filament/Concerns/ScopesToOwner.php` | Usado por `HardwareDeviceResource`, `EnergySystemResource` y `HardwareEnergyResource` |
| `app/Enums/HardwareTypeEnum.php` | Enum tipos de hardware |
| `app/Traits/BelongsToHardwareDevice.php` | Trait relación con dispositivo hardware |
| `app/Traits/IsEnergyReading.php` | Lo común a las tablas de lecturas: elemento, scopes y marca de sospecha |
| `app/Traits/SummarisesEnergyDay.php` | Resumen del día: una fila por elemento y día |
| `app/Traits/AccumulatesEnergyHistory.php` | Acumulado, recalculado desde los resúmenes diarios |
| `database/seeders/EnergySystemsSeeder.php` | Las cuatro instalaciones reales |
| `app/Http/Controllers/Api/Hardware/V2/Concerns/HandlesHardwareDeviceInfo.php` | Trait para adjuntar estado del dispositivo (`hardware_device_info`) en subidas IoT — lo usan Energía y Solar (este módulo) y también KeyCounter, SmartPlant, WeatherStation y AirFlight |
| `app/Rules/OwnedHardwareDevice.php` | Regla de validación: pertenencia del dispositivo (por usuario + ligado estricto por token) |
| `app/Console/Commands/IoT/IssueDeviceTokenCommand.php` | Comando `iot:device-token` (usa `DeviceTokenService`) |

## Campos del modelo HardwareDevice

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | PK |
| `user_id` | int | FK → `users.id` — propietario |
| `hardware_type_id` | int | FK → `hardware_types.id` — tipo de dispositivo |
| `referred_thing_id` | int | FK opcional — dispositivo referenciado |
| `name` | string | Nombre técnico |
| `name_friendly` | string | Nombre amigable |
| `ref` | string | Referencia |
| `model` | string | Modelo del hardware |
| `brand` | string | Marca |
| `software_version` | string | Versión de software |
| `hardware_version` | string | Versión de hardware |
| `serial_number` | string | Número de serie |
| `battery_type` | string | Tipo de batería |
| `battery_nominal_capacity` | string | Capacidad nominal de batería |
| `url_company` | string | URL del fabricante |
| `description` | text | Descripción |
| `buy_at` | date | Fecha de compra |
| `last_seen_at` | timestamp | Última conexión (se actualiza en cada subida de estado) |
| `ip_local` | string | IP local |
| `ip_public` | string | IP pública. **La pone el servidor** desde la petición; lo que mande el dispositivo se ignora |
| `ram` | decimal(5,2) | Último uso de memoria conocido en porcentaje (0-100) |
| `temp` | decimal | Último estado: temperatura del dispositivo (ºC) |
| `voltage` | decimal | Último estado: tensión del dispositivo (V) |
| `battery_level` | smallint | Último estado: nivel de batería (0-100) |
| `cpu` | decimal | Último estado: uso de CPU (0-100) |
| `disk` | decimal | Último estado: uso de disco (0-100) |
| `uptime` | bigint | Último estado: tiempo de actividad (segundos) |
| `extra` | json | Último estado: métricas adicionales (RAM, procesos, etc.) |

> **Estado de dispositivo (sin histórico):** las columnas `temp`, `voltage`, `battery_level`, `cpu`, `disk`, `ram`, `uptime`, `extra`, `ip_local`, `ip_public` y `last_seen_at` reflejan siempre el **último estado conocido** del propio dispositivo. No se guarda histórico. Se actualizan mediante el endpoint dedicado `PUT /api/v2/hardware/devices/{device}/status` o adjuntando una clave opcional `hardware_device_info` en **cualquier** subida IoT que reciba un `hardware_device_id`: energía y carga solar (este módulo), y también KeyCounter, SmartPlant, WeatherStation y AirFlight — ver `docs/planning/PLAN-HARDWARE-DEVICE-INFO.md` para el histórico de por qué se generalizó.
| `battery_voltage` | decimal | Batería del **propio** dispositivo (V). D108 |
| `battery_percentage` | int | Batería del propio dispositivo (%) |
| `battery_read_at` | timestamp | Cuándo se midió esa batería |

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

`POST /api/v2/hardware/energy-readings`

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

## Relaciones

- `HardwareDevice` → `BelongsTo` → `User` (vía `user_id`)
- `HardwareDevice` → `BelongsTo` → `HardwareType` (vía `hardware_type_id`)
- `HardwareDevice` → `HasMany` → `HardwareComponent`
- `HardwareDevice` → `HasMany` → `ApiToken` (vía `apiTokens()`, solo lectura: tokens del usuario propietario con nombre `device:{id}`)
- `HardwareEnergy` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_id`, el que mide)
- `HardwareEnergy` → `BelongsTo` → `HardwareDevice` (vía `hardware_device_monitorized_id`, el medido)
- `HardwareEnergy` → `BelongsTo` → `EnergySystem` / `EnergySourceType`
- `HardwareEnergy` → `HasMany` → `HardwarePowerLoad` / `HardwarePowerGenerator` / `HardwarePowerGeneratorSolar`
- `EnergySystem` → `BelongsTo` → `User`, `HasMany` → `HardwareEnergy`
- `HardwarePowerGenerator/Load` → `BelongsTo` → `HardwareDevice` y → `HardwareEnergy`

## Rutas API V2

| Método | Ruta | Auth | Throttle | Descripción |
|--------|------|------|----------|-------------|
| GET | `/api/v2/hardware/devices` | `ability:hardware:read` | — | Listar dispositivos (`?type=laptop` filtra) |
| GET | `/api/v2/hardware/devices/{device}` | `ability:hardware:read` | — | Ver dispositivo |
| PUT | `/api/v2/hardware/devices/{device}/status` | `ability:hardware:write` | api-store | Último estado conocido del dispositivo |
| GET | `/api/v2/hardware/energy-readings` | `ability:hardwareenergy:read` | api | Lecturas de energía paginadas (`?type=load\|generator`) |
| GET | `/api/v2/hardware/solar-readings` | `ability:hardwareenergy:read` | api | Lecturas del controlador solar, paginadas |
| POST | `/api/v2/hardware/energy-readings` | `ability:hardwareenergy:write` | api-store | Lecturas del monitor de energía (admite `hardware_device_info` opcional) |
| POST | `/api/v2/hardware/solar-readings` | `ability:hardwareenergy:write` | api-store | Lectura del controlador solar (admite `hardware_device_info` opcional) |

**Energía es su propio módulo desde el 2026-09-06** (`hardwareenergy:*`). Antes
iba con `hardware:write`, así que el token de un contador de consumo —que sólo
tiene que mandar vatios— también podía reescribir el último estado conocido del
aparato. La migración `2026_09_06_000003_split_hardware_energy_abilities` añadió
la ability nueva a los tokens ya emitidos, para que ningún cacharro dejara de
subir al desplegar; los tokens nuevos de un aparato de energía se emiten con
`hardwareenergy:write` y **no necesitan** `hardware:write`.

Las dos subidas de energía devuelven `warnings` en el cuerpo cuando algo es raro
pero se ha guardado. Un `warnings` no vacío significa que hay algo que revisar en
el montaje o en la configuración de los elementos.

### Estado del dispositivo (`device-status`)

Pensado para NAS, Raspberry Pi, portátiles, etc. que suben periódicamente su
estado. El cuerpo debe incluir siempre `hardware_device_id` (id del dispositivo,
validado con `OwnedHardwareDevice`). Campos opcionales: `temp`, `voltage`,
`battery_level`, `cpu`, `disk`, **`ram`**, `uptime`, `ip_local`, `extra`.
No se guarda histórico: solo se sobrescribe el último estado y se actualiza
`last_seen_at` al momento actual.

> ⚠️ **`ip_public` ya NO se acepta del cliente** (2026-09-06). Si se manda, se
> **ignora**: el servidor la sobreescribe siempre con la IP de origen de la
> propia petición.
>
> El dispositivo conoce su IP de la intranet y la manda en `ip_local`; la
> pública no la sabe de forma fiable —tendría que preguntársela a un servicio
> externo en cada envío— y, si la manda, no hay forma de comprobar que dice la
> verdad. La resuelve {@see App\Support\Http\ClientIp} leyendo la cabecera que
> escribe el proxy: `CF-Connecting-IP`, `True-Client-IP`, `X-Forwarded-For` (la
> primera de la lista) o `X-Real-IP`, descartando privadas y reservadas.
>
> Si no se puede determinar ninguna IP pública —desarrollo, o una NAT sin proxy
> delante— se guarda `null`, en vez de meter una privada en una columna que dice
> «pública».
>
> Vale igual cuando el estado viaja agrupado en `hardware_device_info`
> dentro de una subida de sensores (estación meteorológica, KeyCounter,
> SmartPlant, AirFlight, energía y carga solar): en las nueve rutas la
> resuelve el servidor.
>
> **Para el cliente:** quitar `ip_public` del cuerpo. No rompe nada dejarlo —se
> descarta en silencio—, pero el valor que llegue no se guarda.

> **`ram`** (nuevo, 2026-09-06): uso de memoria en porcentaje (0-100), igual que
> `cpu` y `disk`. Antes sólo cabía dentro de `extra`, que es JSON y no se puede
> ordenar ni graficar. Migración `2026_09_06_000001_add_ram_to_hardware_devices_table`.

> **Nombre canónico del dispositivo:** el identificador del dispositivo es
> **`hardware_device_id`** en entrada y salida en todos los endpoints de
> escritura del módulo (`energy`, `solar-charge`, `device-status`). La columna
> física en `solar_charges` también se llama `hardware_device_id` desde la
> migración `2026_07_06_000002`.

Ejemplo de cuerpo:

```json
{
    "hardware_device_id": 1,
    "temp": 33,
    "voltage": 3.7,
    "battery_level": 48,
    "ip_local": "192.168.1.100",
    "cpu": 33,
    "ram": 62.5,
    "uptime": 123456,
    "disk": 80,
    "extra": {}
}
```

En subidas conjuntas con otros datos, el estado puede agruparse dentro de
`hardware_device_info` (se aplana automáticamente):

```json
{
    "hardware_device_id": 1,
    "hardware_device_info": { "temp": 33, "voltage": 3.7, "uptime": 123456 },
    "data": {}
}
```

## Rutas Web

| Ruta | Descripción |
|------|-------------|
| `/hardware` | Listado de dispositivos |
| `/hardware/energy` | Dashboard de energía con iconos Material Symbols |

### Frontend (Fix 5)

- **Iconos Material Symbols:** Las tarjetas de estadísticas ahora soportan un campo `icon` opcional (fallback a `bolt`, `electric_meter`, `energy_savings_leaf` según sección).
- **Tarjetas de dispositivos:** Icono `solar_power` con colores amber para cada dispositivo hardware.
- **Diseño profesional:** Fondos con gradientes por sección (amber para "Ahora", emerald para "Hoy", indigo para "Histórico").

### Comandos de debug

```bash
# Crear dispositivos hardware sueltos (con último estado de ejemplo:
# temp, voltage, battery_level, cpu, disk, ram, uptime y extra poblados)
php artisan debug:seed-hardware --count=5

# Crear dispositivos y registros de energía solar (voltaje, corriente, potencia)
php artisan debug:seed-energy --devices=5 --records=100
```

> **fix_11:** `debug:seed-energy` crea ahora asociaciones en `hardware_energy`
> (tabla pivote monitor ↔ monitorizado): auto-monitorización, monitorización
> cruzada y bidireccional. Sin estas asociaciones las vistas de energía no pueden
> resolver qué dispositivo monitoriza a cuál.

## Tokens IoT por dispositivo

Las escrituras IoT se autentican con **tokens Sanctum por dispositivo**. Cada
token se crea sobre el **usuario propietario** del dispositivo, se nombra
`device:{id}` y, además de las abilities de módulo (`hardwareenergy:write`,
`weatherstation:write`, etc.), incluye la ability **`device:{id}`** que lo liga
de forma estricta a ese dispositivo concreto.

### Emisión

- **Terminal:** `php artisan iot:device-token <id> --abilities=hardwareenergy:write [--expires=días]`
  (la ability que toque: un controlador solar lleva `hardwareenergy:write`, una
  estación `weatherstation:write`, un teclado `keycounter:write`…).
- **Filament:** desde la ficha del dispositivo, pestaña **"Tokens IoT"** →
  botón *Emitir token* (selección de abilities de módulo + expiración opcional).
  El token en claro se muestra una sola vez en una notificación persistente.

Ambas vías usan `DeviceTokenService::issue()`, que añade automáticamente la
ability `device:{id}`. El comando y Filament son equivalentes.

### Validación de pertenencia (`OwnedHardwareDevice`)

Regla aplicada al campo `hardware_device_id` en todos los FormRequest de
escritura IoT (Hardware, WeatherStation, KeyCounter, SmartPlant). Comprueba:

1. **Pertenencia por usuario:** el dispositivo debe pertenecer al usuario
   autenticado.
2. **Ligado estricto por dispositivo:** si el token declara abilities
   `device:{id}`, el dispositivo indicado debe coincidir con una de ellas — un
   dispositivo no puede escribir datos con el `hardware_device_id` de otro.

Los tokens sin ability `device:*` (p. ej. `*` o tokens ajenos a dispositivos)
solo pasan la comprobación por usuario; como la regla vive únicamente en los
FormRequest IoT, no afecta a otros endpoints ni tipos de token. Mantiene
compatibilidad con tokens antiguos (solo ligados al usuario).

## Configuración Filament

Panel **Admin**, grupo de navegación **Hardware**
(`HardwareDeviceResource`). Además de los campos del dispositivo, incluye dos
RelationManagers en la ficha de edición:

- **Componentes instalados** (`ComponentsRelationManager`).
- **Tokens IoT** (`TokensRelationManager`): lista solo los tokens de ESE
  dispositivo (nombre `device:{id}`), permite *emitir* uno nuevo ligado al
  dispositivo y *revocar* los existentes. El listado global de todos los tokens
  sigue disponible en el recurso **API Tokens** (grupo *Sistema*).

Además, la ficha de edición incluye una sección **"Stats de hardware"**
(colapsada, solo lectura) al final del formulario que muestra el último estado
conocido reportado por la API: `temp`, `voltage`, `battery_level`, `cpu`,
`disk`, `ram`, `uptime`, `ip_local`, `ip_public` y `extra` (JSON formateado).

### Widget del dashboard — Estado de dispositivos

`DeviceStatusWidget` (`app/Filament/Admin/Widgets/DeviceStatusWidget.php`,
vista `resources/views/filament/admin/widgets/device-status-widget.blade.php`)
se muestra en la página principal del panel Admin ocupando **todo el ancho**.
Lista cada dispositivo que reporta al menos un valor no nulo de `temp`,
`voltage`, `cpu` o `disk`, mostrando su nombre a la izquierda y, a la derecha,
un *chip* con icono por cada métrica no nula entre `temp`, `voltage`, `cpu`,
`disk`, `uptime` y `battery_level`. Se refresca cada 60 s.

## Impresoras

El submódulo de impresoras (modelos `Printer`, `PrinterStack`,
`PrinterAvailableType`) se documenta aparte en [printers.md](printers.md). Su
Resource Filament aparece bajo el grupo de navegación **Hardware**.

---

> Creado: 2026-05-25 · Última revisión: 2026-09-06
