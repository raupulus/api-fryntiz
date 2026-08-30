# Renogy Rover — el controlador solar

Mapa de registros del controlador **Renogy Rover 20A/40A** y cómo entra cada
bloque en esta plataforma.

Existe porque **esta información no está en ningún otro sitio del repositorio**:
el mapa Modbus es del fabricante, el firmware que lo lee es otro repositorio
(`rpi-pico-monitor-renogy-rover-li-solar-controller`) y aquí sólo llega el JSON
ya montado. Sin esta página no hay forma de saber de dónde sale cada campo ni
qué significa una unidad.

> Cómo se guardan estas lecturas y por qué: [`../hardware.md`](../hardware.md),
> sección «El controlador solar (D109)».

## Cómo llega el dato

```
Renogy Rover ──RS232──► Raspberry Pi Pico ──HTTP──► POST /api/v2/hardware/solar-readings
   (Modbus)                (firmware)                  StoreSolarReadingRequest
                                                              │  traduce nombres
                                                              ▼
                                              hardware_power_generators_solar
```

El Rover es un aparato comercial: **manda los nombres que le da la gana y no se
le puede cambiar el protocolo**. Por eso `StoreSolarReadingRequest` es el único
sitio del proyecto con tabla de alias — en todo lo demás el firmware es nuestro y
se ajusta al contrato.

## 1. Tiempo real

| Registro | Qué es | Unidad | Columna |
|---|---|---|---|
| `000AH` | Voltaje de sistema (byte alto) / corriente de sistema (byte bajo) | V (12/24/36/48/96 o auto) / A | `system_voltage`, `system_intensity` |
| `000BH` | Corriente nominal de descarga / tipo de producto | A | — |
| `0100H` | Batería: estado de carga (SOC) | % | `battery_percentage` |
| `0101H` | Batería: voltaje | V (×0,1) | `battery_voltage` |
| `0103H` | Temperatura del controlador / batería | °C | `temperature`, `battery_temperature` |
| `0104H` | Carga (*load*): voltaje | V (×0,1) | `load_voltage` |
| `0105H` | Carga: corriente | A (×0,01) | `load_current` |
| `0106H` | Carga: potencia | W | `load_power` |
| `0107H` | Panel solar: voltaje | V (×0,1) | `voltage` |
| `0108H` | Panel solar: corriente | A (×0,01) | `amperage` |
| `0109H` | Potencia de carga (del panel) | W | `power` |

> El panel va a `voltage` / `amperage` / `power` y no a `pv_*` porque
> `HardwarePowerGeneratorSolar` **extiende** `HardwarePowerGenerator`: es lo mismo
> que mide cualquier generador, sólo que con el nombre del fabricante. Un dato,
> un nombre.

## 2. Estadísticas del día

**El Rover ya da los vatios-hora del día hechos.** Para este aparato no hay que
integrar nada: hay que dejar de tirarlos, que es lo que se hacía.

| Registro | Qué es | Unidad | Columna |
|---|---|---|---|
| `010BH` | Voltaje mínimo de batería del día | V (×0,1) | `day_battery_voltage_min` |
| `010CH` | Voltaje máximo de batería del día | V (×0,1) | `day_battery_voltage_max` |
| `010DH` | Corriente máxima de carga | A (×0,01) | `day_charging_current_max` |
| `010EH` | Corriente máxima de descarga | A (×0,01) | `day_discharging_current_max` |
| `010FH` | Potencia máxima de carga | W | `day_charging_power_max` |
| `0110H` | Potencia máxima de descarga | W | `day_discharging_power_max` |
| `0111H` | Amperios-hora cargados hoy | Ah | `day_charging_amp_hours` |
| `0112H` | Amperios-hora descargados hoy | Ah | `day_discharging_amp_hours` |
| `0113H` | ⭐ **Generación de energía hoy** | kWh/10000 (= 0,1 Wh) | `day_power_generation_wh` |
| `0114H` | ⭐ **Consumo de energía hoy** | kWh/10000 (= 0,1 Wh) | `day_power_consumption_wh` |

⚠️ **La unidad de `0113H` y `0114H` no son Wh directos**: son diezmilésimas de
kWh, o sea **décimas de vatio-hora**. La conversión la hace el firmware antes de
subirlo; si algún día se lee el registro desde aquí, hay que aplicarla.

## 3. Acumulado histórico

Es el bloque que pidió Raúl explícitamente:

> *«quiero subir siempre todos los acumulados históricos que recibo por serial
> también»*

| Registro | Qué es | Unidad | Columna |
|---|---|---|---|
| `0115H` | Días totales de funcionamiento | días | `total_operating_days` |
| `0116H` | Número de sobredescargas | conteo | `total_battery_over_discharges` |
| `0117H` | Número de cargas completas | conteo | `total_battery_full_charges` |
| `0118H`–`0119H` | Amperios-hora cargados totales | Ah | `total_charging_amp_hours` |
| `011AH`–`011BH` | Amperios-hora descargados totales | Ah | `total_discharging_amp_hours` |
| `011CH`–`011DH` | Generación acumulada de por vida | kWh/10000 | `total_power_generation_wh` |
| `011EH`–`011FH` | Consumo acumulado de por vida | kWh/10000 | `total_power_consumption_wh` |
| `0120H` | Estado de la luz / estado de carga | bitmap | `light_status`, `light_brightness`, `charging_status` |
| `0121H`–`0122H` | Fallos y avisos | bitmap de 32 bits | ❌ sin columna todavía |

### La detección de reinicio

`total_operating_days` (`0115H`) **sólo puede subir**. Si en una lectura **baja**,
el controlador se ha reseteado y todos sus contadores han vuelto a cero: esa
lectura **abre fila nueva** y no machaca la anterior.

Sin eso, un reset del aparato borra el acumulado de años. La lógica venía de V1,
se había perdido en v2 y se recuperó en la fase 7
(`HardwarePowerGeneratorSolar::hasRestarted()`).

## 4. Traducción de nombres

Tres vocabularios para lo mismo, y por eso hace falta una tabla de alias:

| Columna | Lo que manda el firmware | Lo que decía la API V1 |
|---|---|---|
| `voltage` | `solar_voltage`, `pv_voltage` | `energy_voltage` |
| `amperage` | `solar_current`, `pv_current` | `energy_amperage` |
| `power` | `solar_power`, `pv_power` | `energy_power` |
| `battery_percentage` | `battery_soc` | `battery_soc` |
| `temperature` | `controller_temperature` | `controller_temperature` |
| `charging_status` | `charging_status` | `energy_charging_status` |
| `light_status` | `street_light_status` | `street_light_status` |
| `day_power_generation_wh` | `today_power_generation` | `today_energy_power` |
| `total_operating_days` | `historical_total_days_operating` | `days_operating` |

La tabla completa está en `app/Http/Requests/Api/Hardware/V2/StoreSolarReadingRequest.php`,
en la constante `ALIAS`.

> ⚠️ **`energy_*` NO era el voltaje de sistema.** Es el vocabulario de V1 para el
> **lado de generación**, y en la tabla vieja duplicaba `pv_*`: el mismo dato con
> dos nombres. El «da 12 V siempre» que se veía por serie es `system_voltage`,
> que es un campo aparte y tiene su propia columna.

## 5. Una regla que no se salta

**Un campo ausente deja `NULL`, nunca `0`.** V1 casteaba a la brava
(`(float) $this->campo`) y convertía «no tengo dato» en un cero que contamina
todas las medias. Pero **un 0 real sí se guarda**: de noche el panel da 0 W y eso
es un dato, no una ausencia de dato.

## Lo que queda por hacer

- Los bitmaps de fallos y avisos (`0121H`–`0122H`) no tienen columna. El firmware
  tampoco los sube todavía.
- El estado de carga se guarda como código y etiqueta tal cual los manda el
  aparato; no hay tabla que traduzca el código del fabricante a un texto propio.

## Referencias

- Cómo se guarda todo esto: [`../hardware.md`](../hardware.md)
- Firmware que lee el Modbus: repositorio `rpi-pico-monitor-renogy-rover-li-solar-controller`
- Endpoint: `POST /api/v2/hardware/solar-readings`

---

> Creado: 2026-08-30 · Última revisión: 2026-08-30
