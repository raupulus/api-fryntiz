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

> ℹ️ Las tablas antiguas (`hardware_power_generators*`, `hardware_power_loads*`)
> quedan en la base de datos como respaldo histórico frío. Todos sus datos
> (1.750.515 lecturas, 1.833 agregados diarios, 10 históricos y 1.401 lecturas
> de batería huérfanas) fueron migrados íntegramente mediante el comando
> `php artisan iot:migrate-legacy-energy-data`.

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

---

## 3. Telemetría y Contrato Universal `energy`

Los dispositivos IoT pueden subir su telemetría mediante un payload unificado que agrupa
los tres subsistemas en un único bloque semántico `energy`:

```json
{
  "hardware_device_id": 15,
  "duration": 60,
  "energy": {
    "generator": {
      "voltage": 34.5,
      "amperage": 4.2,
      "power": 144.9,
      "charging_status": 3,
      "charging_status_label": "mppt",
      "light_status": false,
      "today_energy_wh": 1250.0,
      "historical_energy_wh": 45000.0
    },
    "battery": {
      "voltage": 13.4,
      "soc": 92,
      "temperature": 24.5,
      "charging_status": 3,
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
        "historical_energy_wh": 12500.0
      }
    ]
  }
}
```

### Principios de Cálculo e Integración:
1. **Sin campos `read_at` en base de datos ni contrato:** La fecha y momento de la lectura se rigen exclusivamente por `created_at` del servidor o momento de recepción, eliminando redundancias y desfases horarios en clientes IoT sin RTC.
2. **Derivación de Magnitudes:**
   - Potencia: $P = V \cdot I$ (W)
   - Energía incremental: $\Delta Wh = \frac{P \cdot \text{duration}}{3600}$
   - Amperios-hora incrementales: $\Delta Ah = \frac{I \cdot \text{duration}}{3600}$
   - Si el dispositivo reporta acumulados nativos (`today_energy_wh`, `historical_energy_wh`), se toma el valor del hardware (`energy_source: device`). De lo contrario, se integran los calculados (`energy_source: derived`).
3. **Respaldo de Tensión Nominal (`nominal_voltage`):** Si un canal de consumo o sensor de corriente simple (ej. INA219 montado en la línea de un router) no mide tensión, el backend recurre a `nominal_voltage` del elemento con `voltage_source: nominal` y emite un warning informativo sin descartar la muestra.
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

---

## 5. Controladores y Rutas API V2

- `POST /api/v2/energy/readings`: Endpoint universal de ingesta (ability `energy:write`).
- `GET /api/v2/energy/readings`: Consulta de lecturas paginadas filtrables por dispositivo, elemento, fecha y ordenación (ability `energy:read`).
- `POST /api/v2/energy/solar-readings`: Endpoint de compatibilidad transitoria para controladores Renogy Rover (con dual-write al esquema unificado).
- `GET /api/v2/energy/solar-readings`: Endpoint legacy de consulta solar.

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
1. **Ahora Mismo:** Potencia de generación actual (W), potencia de consumo actual (W), balance neto instantáneo (W), tensiones de panel y batería (V), porcentajes de carga de batería, intensidad solar (%) y temperatura máxima registrada (°C).
2. **Hoy:** Energía generada hoy (Wh), energía consumida hoy (Wh) y balance de amperios-hora (Ah).
3. **Histórico:** Energía total generada (kWh), total consumida (kWh), días acumulados en operación y ciclos completos de carga de batería.

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
- **Planificación:** Diario a las **00:05** (`Europe/Madrid`) en `routes/console.php`.
- **Comportamiento:**
  1. Cierra y consolida el día anterior (todas las muestras entre 00:00:00 y 23:59:59) en `hardware_energy_today`.
  2. Filtra automáticamente por elementos con `auto_calculate_history = true` e `is_active = true` (los elementos con odómetro propio de hardware como el Renogy Rover se gestionan nativamente).
  3. Reconcilia la serie histórica en `hardware_energy_historical` asegurando que `days_operating`, `readings_count`, `energy_wh` y `energy_ah` correspondan fielmente a la suma consolidada de sus resúmenes diarios.
  4. Excluye lecturas anómalas o sospechosas (`is_suspicious = true`).
  5. Soporta ejecución manual retroactiva mediante opciones: `--date=YYYY-MM-DD`, `--today`, `--element=ID`, `--all`.

---

> Creado: 2026-09-06 · Última revisión: 2026-09-12
