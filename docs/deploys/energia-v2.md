# Subir el módulo de energía a producción

Checklist para desplegar el esquema unificado de energía sobre una producción
que **ya tiene datos y un aparato subiendo**. No es un despliegue normal: hay un
corte de contrato y un comando que vacía tablas, así que el orden importa.

Para el despliegue general del VPS ver [`deploy-vps.md`](deploy-vps.md).

---

## Lo que hay que saber antes de empezar

**1 · El endpoint que usa la Pico desaparece.**

| Antes | Ahora |
|---|---|
| `POST /api/v2/energy/solar-readings` | **no existe** |
| `GET /api/v2/energy/solar-readings` | **no existe** |
| `POST /api/v2/energy/readings` | sigue, con el contrato universal |

En cuanto el código nuevo esté vivo, la Pico recibirá `404` y **sus lecturas se
perderán hasta que se le cambie el firmware**. Ten el firmware nuevo listo para
grabar antes de tocar el servidor.

**2 · El comando de traspaso empieza por un `TRUNCATE`.**

`energy:migrate-legacy-data` vacía `hardware_energy_readings`,
`hardware_energy_today` y `hardware_energy_historical` antes de rellenarlas desde
las tablas antiguas. Todo lo que haya entrado en vivo en esas tres tablas
desaparece. Por eso va **antes** de que la Pico empiece a subir con el contrato
nuevo, no después.

**3 · Las tablas antiguas no se tocan.** `hardware_power_generators*`,
`hardware_power_loads*` y `hardware_power_generators_solar` se quedan intactas
como respaldo en frío. Si algo sale mal, el traspaso se puede repetir.

**4 · Hazlo de noche.** Entre el paso 4 y el 7 la Pico no puede subir. De noche
el panel no produce y lo que se pierde es casi nada.

---

## Checklist

### 0 · Antes de tocar nada

- [ ] **El firmware nuevo de la Pico está compilado y listo para grabar.** Sin
      esto no empieces: el hueco sin subir se alarga lo que tardes en hacerlo.
- [ ] Tienes el contrato a mano: [`../info/api/v2/energy.md`](../info/api/v2/energy.md).
- [ ] Sabes en qué commit está producción:
      ```bash
      cd /var/www/public/www.api.raupulus.dev && git log --oneline -1
      ```

### 1 · Copia de seguridad

- [ ] Volcado completo de la base, **con fecha en el nombre**:
      ```bash
      pg_dump -Fc -d raupulus_api -f ~/backups/raupulus_api_$(date +%F_%H%M).dump
      ```
- [ ] Comprueba que el fichero pesa lo que debe (cientos de MB, no cero):
      ```bash
      ls -lh ~/backups/raupulus_api_*.dump | tail -1
      ```

### 2 · Fotografía del estado actual

Para poder comparar después. Guarda la salida:

```bash
psql -d raupulus_api -c "
SELECT 'generators' t, count(*) FROM hardware_power_generators
UNION ALL SELECT 'loads',        count(*) FROM hardware_power_loads
UNION ALL SELECT 'solar',        count(*) FROM hardware_power_generators_solar
UNION ALL SELECT 'gen_today',    count(*) FROM hardware_power_generators_today
UNION ALL SELECT 'load_today',   count(*) FROM hardware_power_loads_today
UNION ALL SELECT 'gen_hist',     count(*) FROM hardware_power_generators_historical
UNION ALL SELECT 'load_hist',    count(*) FROM hardware_power_loads_historical;
"
```

- [ ] Guardada.

### 3 · Desplegar el código

- [ ] `git pull` de la rama `v2`.
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `pnpm install && pnpm build` (se han tocado vistas del panel público).
- [ ] **Limpiar y recachear.** Las rutas nuevas dan `404` hasta que se recachea:
      ```bash
      php8.5 artisan project:clear
      ```

### 4 · Migraciones de esquema

- [ ] Ver qué va a correr, **antes** de correrlo:
      ```bash
      php8.5 artisan migrate:status | grep Pending
      ```
      Deben salir tres:
      `2026_09_13_000001` (origen por magnitud en el histórico),
      `2026_09_13_000002` (lo mismo en el resumen diario) y
      `2026_09_13_000003` (`default_interval_seconds` en `hardware_energy`).
- [ ] Ejecutarlas:
      ```bash
      php8.5 artisan migrate --force
      ```
- [ ] Las tres son columnas nuevas con valor por defecto: no tocan ninguna fila
      existente. Si alguna falla, **para aquí** y restaura la copia.

### 5 · Traspasar los datos antiguos

- [ ] En seco primero, para ver los recuentos:
      ```bash
      php8.5 artisan energy:migrate-legacy-data --dry-run
      ```
      Compara con la fotografía del paso 2: deben cuadrar.
- [ ] Ejecutarlo de verdad:
      ```bash
      php8.5 artisan energy:migrate-legacy-data --force
      ```

### 6 · Comprobar que el traspaso quedó limpio

```bash
psql -d raupulus_api -c "
SELECT
  (SELECT count(*) FROM hardware_energy_readings   WHERE hardware_energy_id IS NULL) AS readings_sin_elemento,
  (SELECT count(*) FROM hardware_energy_today      WHERE hardware_energy_id IS NULL) AS today_sin_elemento,
  (SELECT count(*) FROM hardware_energy_historical WHERE hardware_energy_id IS NULL) AS hist_sin_elemento,
  (SELECT count(*) FROM hardware_energy_historical h
     JOIN hardware_energy e ON e.id = h.hardware_energy_id
    WHERE h.hardware_device_id <> e.hardware_device_id) AS mal_atribuidas;
"
```

- [ ] **Las cuatro cifras a cero.** Si no, no sigas: repite el traspaso.
- [ ] Y que la batería del controlador tenga su acumulado de por vida:
      ```bash
      psql -d raupulus_api -c "
      SELECT hardware_energy_id, energy_wh, energy_wh_source, energy_ah, energy_ah_source
      FROM hardware_energy_historical
      WHERE hardware_device_id = 6 ORDER BY hardware_energy_id;"
      ```
      Tres filas: el panel, el consumo y la batería.

### 7 · Configurar los elementos del controlador solar

Los ids de la instalación real: **4** panel, **7** salida de carga, **11**
batería, todos del dispositivo **6**.

- [ ] Marcarlos como de odómetro propio y con su intervalo real:
      ```bash
      psql -d raupulus_api -c "
      UPDATE hardware_energy
      SET auto_calculate_history = false,
          default_interval_seconds = 334,
          updated_at = now()
      WHERE id IN (4, 7, 11);"
      ```
      `auto_calculate_history = false` porque sus totales los lleva el
      controlador; `334` es la cadencia medida de la Pico. Si cambias la cadencia
      del firmware, cambia también este número.
- [ ] Comprobar el resto de elementos desde el panel: cada uno con su
      `nominal_voltage`, y las baterías con sus tensiones a 0 % y a 100 %.

### 8 · Grabar el firmware nuevo en la Pico

- [ ] Apunta a `POST /api/v2/energy/readings` con el contrato universal.
- [ ] Token con ability `energy:write`.
- [ ] Manda `read_at` (lleva reloj por NTP).

### 9 · Verificar de punta a punta

- [ ] Que llegó la primera lectura, con sus tres elementos:
      ```bash
      psql -d raupulus_api -c "
      SELECT hardware_energy_id, voltage, amperage, power, delta_seconds,
             voltage_source, created_at
      FROM hardware_energy_readings
      ORDER BY id DESC LIMIT 5;"
      ```
- [ ] Que el resumen del día refleja los contadores del controlador:
      ```bash
      psql -d raupulus_api -c "
      SELECT hardware_energy_id, energy_wh, energy_wh_source, energy_ah, energy_ah_source
      FROM hardware_energy_today WHERE date = CURRENT_DATE;"
      ```
      Los del panel y el consumo deben decir `device`.
- [ ] El panel público responde y enseña números:
      `https://www.api.raupulus.dev/hardware/energy`
- [ ] El panel de administración enseña las lecturas del elemento y **no** tiene
      botón de crear ni de editar.

### 10 · El cierre nocturno

- [ ] Que el cron está programado:
      ```bash
      php8.5 artisan schedule:list | grep energy
      ```
      Debe salir `energy:aggregate-daily` a las **00:05 UTC**.
- [ ] A la mañana siguiente, comprobar que no ha alterado los acumulados del
      controlador:
      ```bash
      psql -d raupulus_api -c "
      SELECT hardware_energy_id, energy_wh, energy_ah, days_operating
      FROM hardware_energy_historical WHERE hardware_device_id = 6;"
      ```

---

## Si algo sale mal

**Antes del paso 5** (sólo migraciones de esquema): se revierten solas.

```bash
php8.5 artisan migrate:rollback --step=3
```

**Después del paso 5**: el traspaso se puede repetir cuantas veces haga falta —
vuelve a vaciar y a rellenar desde las tablas antiguas, que no se han tocado—.

```bash
php8.5 artisan energy:migrate-legacy-data --force
```

**Vuelta atrás completa**, si hay que deshacer el despliegue entero:

```bash
# Código
git checkout <commit-anterior> && php8.5 artisan project:clear
# Datos
pg_restore -c -d raupulus_api ~/backups/raupulus_api_<fecha>.dump
```

---

## Lo que más se olvida

| | |
|---|---|
| Recachear tras el `git pull` | Las rutas nuevas dan `404` hasta que se hace. Si dudas, prueba un endpoint viejo: si ése responde y el nuevo no, es la caché |
| El firmware antes que el servidor | No lo grabes antes, pero tenlo listo: cada minuto entre el despliegue y la grabación son lecturas perdidas |
| `--force` en producción | `migrate` y `energy:migrate-legacy-data` preguntan por consola; sin `--force` se quedan esperando |
| No usar `--rebuild` en el cron | `energy:aggregate-daily --rebuild` es destructivo: sustituye acumulados aunque los resúmenes diarios no cubran toda su historia. Sólo a mano y sabiendo por qué |
| Reiniciar los procesos de larga vida | Los workers de cola y Reverb mantienen el código en memoria |

---

> Creado: 2026-09-13 · Última revisión: 2026-09-13
