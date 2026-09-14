# Renogy Rover — el controlador solar

Mapa de registros del controlador **Renogy Rover 20A/40A** y cómo entra cada
bloque en esta plataforma.

Existe porque **esta información no está en ningún otro sitio del repositorio**:
el mapa Modbus es del fabricante, el firmware que lo lee es otro repositorio
(`rpi-pico-monitor-renogy-rover-li-solar-controller`) y aquí sólo llega el JSON
ya montado. Sin esta página no hay forma de saber de dónde sale cada campo ni
qué significa una unidad.

> Cómo se guardan estas lecturas y arquitectura interna: [`../energy.md`](../energy.md).
> Contrato HTTP detallado: [`../api/v2/energy.md`](../api/v2/energy.md).

---

## La instalación real

| | Tensión | Elemento |
|---|---|---|
| Placas solares | **24 V** | generador (#4) |
| Banco de baterías | **12 V** | batería (#11) |
| Salida de carga | **12 V** | consumo (#7) |

**Las tres tensiones son distintas y se guardan por separado.** Nada se
normaliza a 12 V al guardar. Sólo al **pintar amperios** en el panel público se
refieren todos a la tensión del bus, porque comparar 5 A a 24 V contra 5 A a
12 V no significa nada: los primeros mueven el doble de energía.

El `system_voltage` del controlador (registro `000AH`) es configurable; en esta
instalación son 24 V.

---

## Cómo llega el dato

```
Renogy Rover ──RS232──► Raspberry Pi Pico ──HTTP──► POST /api/v2/energy/readings
   (Modbus)                (firmware)                 [bloque energy: generator, battery, loads]
                                                                   │
                                                                   ▼
                                                       hardware_energy_readings
                                                       ├── #4  (Generador FV, 24 V)
                                                       ├── #7  (Consumo Load, 12 V)
                                                       └── #11 (Batería LiFePO4, 12 V)
```

**La traducción de nombres la hace el firmware de la Pico**, que empaqueta los
registros Modbus directamente en el contrato universal. El servidor no traduce
nada: recibe `generator`, `battery` y `loads` y ya está. No hay ni habrá un
endpoint específico para este controlador.

---

## 1. Tiempo real

| Registro | Qué es | Unidad | Campo del contrato | Elemento |
|---|---|---|---|---|
| `000AH` | Tensión de sistema (byte alto) / corriente de sistema (byte bajo) | V / A | `device.extra.system_voltage`, `device.extra.system_intensity` | — |
| `000BH` | Corriente nominal de descarga / tipo de producto | A | — | — |
| `0100H` | Batería: estado de carga (SOC) | % | `battery.soc` | #11 |
| `0101H` | Batería: tensión | V (×0,1) | `battery.voltage` | #11 |
| `0102H` | Batería: corriente de carga | A (×0,01) | `battery.amperage` | #11 |
| `0103H` | Temperatura del controlador / de la batería | °C | `generator.temperature` / `battery.temperature` | #4 / #11 |
| `0104H` | Carga (*load*): tensión | V (×0,1) | `loads[].voltage` | #7 |
| `0105H` | Carga: corriente | A (×0,01) | `loads[].amperage` | #7 |
| `0106H` | Carga: potencia | W | `loads[].power` | #7 |
| `0107H` | Panel solar: tensión | V (×0,1) | `generator.voltage` | #4 |
| `0108H` | Panel solar: corriente | A (×0,01) | `generator.amperage` | #4 |
| `0109H` | Potencia de carga (del panel) | W | `generator.power` | #4 |

La corriente de batería va **con signo**: negativa mientras descarga. Se guarda
tal cual, y la potencia y los amperios-hora del intervalo salen negativos con
ella. Eso es correcto: en una batería la magnitud es el neto.

---

## 2. Estadísticas del día

**El Rover lleva sus propios contadores del día, pero no se ponen a cero a las
00:00.** Generación y carga de batería vuelven a cero hacia las **05:43 UTC**;
consumo y descarga, hacia las **19:14 UTC**. Como el servidor corta el día en UTC
y un `today_*` declarado sustituye al total, mandarlos mezclaba días: el
13/09/2026 el consumo quedó en 108 Wh, sólo lo gastado desde las 19:14.

**Desde el 2026-09-14 el firmware no manda ningún `today_*`.** Sigue leyendo
estos registros, pero sólo para restar dos lecturas seguidas y mandar la energía
del intervalo (`energy_wh` / `energy_ah`); el total del día lo suma el servidor.
La resta aguanta el paso a cero: si el contador baja, se toma su valor entero.
La columna de abajo dice a qué campo correspondería cada registro, no lo que se
envía. El motivo completo está en `docs/info/decisiones-tecnicas.md` §19 del
repositorio del firmware.

| Registro | Qué es | Unidad | Campo del contrato (no se envía) | Elemento |
|---|---|---|---|---|
| `010BH` | Tensión mínima de batería del día | V (×0,1) | `battery.today_voltage_min` | #11 |
| `010CH` | Tensión máxima de batería del día | V (×0,1) | `battery.today_voltage_max` | #11 |
| `010DH` | Corriente máxima de carga | A (×0,01) | `generator.today_amperage_max` | #4 |
| `010EH` | Corriente máxima de descarga | A (×0,01) | `loads[].today_amperage_max` | #7 |
| `010FH` | Potencia máxima de carga | W | `generator.today_power_max` | #4 |
| `0110H` | Potencia máxima de descarga | W | `loads[].today_power_max` | #7 |
| `0111H` | Amperios-hora cargados hoy | Ah | `battery.today_energy_ah` | #11 |
| `0112H` | Amperios-hora descargados hoy | Ah | `loads[].today_energy_ah` | #7 |
| `0113H` | ⭐ **Generación de energía hoy** | kWh/10000 (= 0,1 Wh) | `generator.today_energy_wh` | #4 |
| `0114H` | ⭐ **Consumo de energía hoy** | kWh/10000 (= 0,1 Wh) | `loads[].today_energy_wh` | #7 |

⚠️ **La unidad de `0113H` y `0114H` no son Wh directos**: son diezmilésimas de
kWh, o sea **décimas de vatio-hora**. La conversión la hace el firmware antes de
subirlo; si algún día se lee el registro desde aquí, hay que aplicarla.

Los mínimos y máximos del día tampoco se mandan: arrastrarían los de la tarde
anterior. El resumen del día los saca de las lecturas.

---

## 3. Acumulado de por vida y odómetro de hardware

| Registro | Qué es | Unidad | Campo del contrato | Elemento |
|---|---|---|---|---|
| `0115H` | Días totales de funcionamiento | días | `*.total_operating_days` | los tres |
| `0116H` | Número de sobredescargas | conteo | `battery.battery_over_discharges` | #11 |
| `0117H` | Número de cargas completas | conteo | `battery.battery_full_charges` | #11 |
| `0118H`–`0119H` | Amperios-hora cargados totales | Ah | `battery.historical_energy_ah` | #11 |
| `011AH`–`011BH` | Amperios-hora descargados totales | Ah | `loads[].historical_energy_ah` | #7 |
| `011CH`–`011DH` | Generación acumulada de por vida | kWh/10000 | `generator.historical_energy_wh` | #4 |
| `011EH`–`011FH` | Consumo acumulado de por vida | kWh/10000 | `loads[].historical_energy_wh` | #7 |
| `0120H` | Estado de la luz / estado de carga | bitmap | `generator.light_status`, `light_brightness`, `charging_status` | #4 |
| `0121H`–`0122H` | Fallos y avisos | bitmap de 32 bits | `device.extra` | — |

### Qué magnitud lleva odómetro y cuál no

Esto es lo que decide si el servidor recalcula o respeta:

| Elemento | Vatios-hora | Amperios-hora |
|---|---|---|
| Generador #4 | del aparato (`011CH`-`011DH`) | **calculado**: el Rover no mide los Ah del panel |
| Consumo #7 | del aparato (`011EH`-`011FH`) | del aparato (`011AH`-`011BH`) |
| Batería #11 | **calculado**: no hay registro de Wh de batería | del aparato (`0118H`-`0119H`) |

**Por qué los amperios-hora de carga van a la batería y no al panel.** El
registro `0118H` cuenta lo que *entra en el banco*, no lo que produce el panel:
entre los dos hay el rendimiento del MPPT. El criterio del contrato es que cada
bloque describe su elemento, así que van a `battery`. Los del panel, que el Rover
no mide, los calcula el servidor de las lecturas.

Con eso el balance queda descrito entero sin duplicar nada: lo que entra al banco
(`battery`), lo que sale a los consumos (`loads[]`) y lo que produce el panel
(`generator`, en Wh del aparato y Ah calculados).

**Lo que se calcula, se calcula con la tensión nominal del elemento**: los Ah del
panel son `Wh ÷ 24 V` y los Wh del banco son `Ah × 12 V`. La comprobación de que
está bien es dividir las dos columnas de una fila: tiene que salir la tensión de
ese elemento. Cuando el traspaso dejaba los Ah de carga en la fila del panel,
salían **13,27 V** —los del banco— en un elemento de 24 V.

`hardware_energy_historical` guarda esa decisión en `energy_wh_source` y
`energy_ah_source`, **una por magnitud**. Con una sola marca para las dos, los
vatios-hora de la batería se quedaban congelados a 0 para siempre mientras el
resumen del día sí los calculaba.

### La detección de reinicio multisesión (`session_index`)

Si el controlador se apaga o sufre un reseteo, sus contadores internos vuelven a
cero. En lugar de sobrescribir el acumulado anterior o ignorar los nuevos datos:

1. El backend detecta la caída drástica del contador reportado.
2. Congela la sesión histórica previa (`session_index = N`).
3. Abre una nueva sesión (`session_index = N + 1`).
4. El total absoluto del elemento es la **suma de todas sus sesiones**.

Ver [`../energy.md`](../energy.md) §4.

---

## 4. Reglas de integridad

- **Un campo ausente deja `NULL`, nunca `0`.** Un 0 real significa que el panel
  no produce (de noche); la ausencia de lectura deja la columna a null sin
  contaminar las medias.
- **Un 0 medido se guarda como 0.** El panel a 0 V de noche se guarda a 0 V, no
  a su tensión nominal, para que el mínimo del día sea el real.
- **Una tensión fuera de rango se guarda igual, con aviso.** Una batería por
  debajo de su mínimo configurado es una sobredescarga: sustituirla por la
  nominal la hacía invisible.
- **El odómetro está protegido.** Ninguna magnitud marcada como del aparato se
  recalcula desde nuestros intervalos, ni en la ingesta ni en el cron nocturno.

---

## 5. Qué falta por confirmar

`battery_current` y `battery_power` (registro `0102H` y su producto) **ya
llegan**: 1.353 de las 1.401 filas de la tabla antigua los traen. Lo que queda
es comprobar que, con el contrato nuevo, acaban en `amperage` y `power` del
elemento #11 con su signo.

---

## Referencias

- Arquitectura del módulo y diseño unificado: [`../energy.md`](../energy.md)
- Especificación HTTP completa: [`../api/v2/energy.md`](../api/v2/energy.md)
- Firmware de la Raspberry Pi Pico: repositorio `rpi-pico-monitor-renogy-rover-li-solar-controller`

---

> Creado: 2026-08-30 · Última revisión: 2026-09-14
