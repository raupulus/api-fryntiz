# Catálogo de comandos Artisan

Listado completo de los comandos Artisan personalizados del proyecto, agrupados por módulo.

> Para los comandos `debug:seed-*` (datos de prueba), ver además [debug-commands.md](debug-commands.md).

> ✅ **El scheduler ya no llama a comandos inventados.** Durante mucho tiempo programó
> `aemet:adverse-events`, `aemet:contamination`, `aemet:predictions` y `keycounter:maintenance`
> sin que ninguno existiera, y ése era el motivo de fondo de que los datos de AEMET
> llevaran años sin actualizarse: artisan no encuentra el comando, la tarea «termina»
> y no se queja nadie. `tests/Feature/Consola/SchedulerTest.php` comprueba ahora que
> todo lo programado —y todo botón del panel de AEMET— apunte a un comando que existe.

---

## 1. Comandos de proyecto

| Comando | Alias | Descripción | Archivo |
|---------|-------|-------------|---------|
| `project:install` | `xerintel:install` | Instalación inicial: migra, semilla básica y enlaces de storage. | `app/Console/Commands/ProjectInstallCommand.php` |
| `project:clear` | `xerintel:clear` | Limpia todas las cachés, colas, regenera clave segura y recompone autoload. | `app/Console/Commands/ProjectClearCommand.php` |
| `project:dummy` | `xerintel:dummy` | Genera contenido y telemetría corporativa de ejemplo para todos los módulos. | `app/Console/Commands/ProjectDummyCommand.php` |
| `force:clear` | — | Variante agresiva de `project:clear` para entornos rotos. | `app/Console/Commands/ForceClearCommand.php` |
| `sitemap:generate` | — | Genera el sitemap XML público navegable del sitio. | `app/Console/Commands/SitemapGeneratorCommand.php` |
| `mcp:inspector` | — | Lanza el inspector de MCP contra el servidor del proyecto. | `app/Console/Commands/Mcp/InspectorCommand.php` |
| `serve` | — | Sobrescribe el `serve` nativo de Laravel: si `BROADCAST_CONNECTION=reverb`, arranca también `reverb:start` en segundo plano (mismo ciclo de vida, se detiene al cerrar `serve`). | `app/Console/Commands/ServeCommand.php` |

---

## 2. AEMET (Estación Meteorológica)

Documentación técnica: [apis/aemet.md](apis/aemet.md).

**Un comando por producto.** Antes eran comandos por horario (`aemet:update-daily8`,
`aemet:update-every4h`…), que agrupaban productos distintos y hacían imposible
relanzar uno solo: el botón «Alta mar» del panel acababa trayendo la predicción
horaria. La cadencia de cada uno sale de la `periodicidad` que declara AEMET, no
de un número inventado.

| Comando | Cuándo | Producto |
|---------|--------|----------|
| `aemet:adverse-events` | Cada 30 min | Avisos de fenómenos adversos (CAP) |
| `aemet:contamination` | Cada hora | Contaminación atmosférica |
| `aemet:hourly-prediction` | Cada 3 h | Predicción horaria del municipio |
| `aemet:beaches` | Diario | Predicción de playas |
| `aemet:coast` | Diario | Predicción de costa |
| `aemet:high-sea` | 08:15 | Alta mar |
| `aemet:sun-radiation` | 08:25 | Radiación solar |
| `aemet:ozone` | 12:25 | Ozono en superficie |
| `aemet:check-api-key` | 08:00 | **Vigila la caducidad de la clave** |

Todos usan el trait `ValidatesAemetPayload` para validar el payload antes de persistir.

⚠️ `aemet:check-api-key` no trae datos: comprueba la clave. Existe porque la
`AEMET_API_KEY` es un JWT que caduca a los ~100 días y **su caducidad no da
error** —AEMET responde 200 con el cuerpo vacío—, así que sin esto la
integración se queda muda y no se entera nadie. Sale con código 1 cuando hay que
renovarla. Ver [apis/aemet.md](apis/aemet.md).

---

## 3. AirFlight

| Comando | Descripción |
|---------|-------------|
| `airflight:fix` | Repara registros de ruta inconsistentes. |

---

## 4. Content

| Comando | Descripción |
|---------|-------------|
| `content:publish` | Publica los contenidos programados cuya fecha de publicación ya ha pasado. |

---

## 5. KeyCounter

| Comando | Descripción |
|---------|-------------|
| `keycounter:generate_duration` | Calcula duraciones agregadas de actividad. |
| `keycounter:remove_duplicate` | Elimina duplicados en eventos por timestamp. |

---

## 6. IoT — Tokens de dispositivo

Emite tokens Sanctum con abilities limitadas para que un dispositivo IoT pueda escribir datos en la API sin credenciales de usuario completas.

```bash
php artisan iot:device-token {device_id} --abilities={scope} [--expires={días}]
```

**Parámetros:**

| Parámetro | Requerido | Descripción |
|-----------|-----------|-------------|
| `device_id` | Sí | ID del `HardwareDevice` al que se asocia el token. |
| `--abilities` | Sí | Scope(s) del token. Repetible para múltiples abilities. |
| `--expires` | No | Días hasta la expiración. Sin este flag el token no expira. |

**Abilities disponibles (ejemplos):**

| Ability | Módulo |
|---------|--------|
| `weatherstation:write` | Estación meteorológica |
| `energy:write` | Energía |
| `hardware:write` | Hardware genérico |
| `smartplant:write` | Smart Plant |

**Ejemplos de uso:**

```bash
# Token para estación meteorológica con expiración de 1 año
php artisan iot:device-token 10 --abilities=weatherstation:write --expires=365

# Token con múltiples abilities, sin expiración
php artisan iot:device-token 7 --abilities=energy:write --abilities=hardware:write

# Token permanente para dispositivo concreto
php artisan iot:device-token 3 --abilities=smartplant:write
```

**Salida esperada:**

```
Token emitido correctamente para el dispositivo #10
Abilities: weatherstation:write
Expira: 2027-06-17 16:18:29

Guarda este token ahora (no se volverá a mostrar):
5|jYQ5lOukVw1izffYl2a2408i48WalBXg6jK6BG5i161f6026
```

> ⚠️ El token en texto plano **solo se muestra una vez**. Guárdalo en el `.env` del dispositivo o en el gestor de secretos antes de cerrar la terminal.

El token se registra en Sanctum como `device:{id}` para facilitar la trazabilidad en la tabla `personal_access_tokens`. El propietario del token es el usuario asociado al `HardwareDevice`.

---

## 7. Debug — Datos de prueba

> ⚠️ Solo para entornos de desarrollo. **No ejecutar en producción.**

| Comando | Opciones | Descripción |
|---------|----------|-------------|
| `debug:seed-all` | `--small` | Ejecuta todos los seeders debug. `--small` divide por 5 las cantidades por defecto. |
| `debug:seed-hardware` | `--count=5` | Crea dispositivos hardware. |
| `debug:seed-weatherstation` | `--count=20` | Crea registros de sensores meteo. |
| `debug:seed-airflight` | `--planes=10 --routes=100` | Crea aviones con trayectorias coherentes (rumbo/velocidad/altitud continuos). |
| `debug:seed-smartplant` | `--plants=5 --registers=50` | Crea plantas y registros. |
| `debug:seed-keycounter` | `--count=50` | Crea registros KeyCounter. |
| `debug:seed-energy` | `--devices=5 --records=100` | Crea registros de energía. |
| `debug:seed-content` | `--count=10` | Crea contenidos. |
| `debug:seed-cv` | — | Crea un CV de prueba con repositorios. |
| `debug:seed-newsletter` | `--count=10` | Crea suscripciones de newsletter. |
| `debug:seed-platform` | `--count=3` | Crea plataformas. |
| `debug:seed-contact` | `--count=10` | Crea mensajes de contacto. |

Todos los comandos `debug:seed-*` usan el trait `ResolvesDebugDefaults` para resolver IDs de dispositivos/usuarios cuando los placeholders por defecto no existen aún.

---

## 8. Comandos del scheduler

El scheduler está definido en **`routes/console.php`** (sustituye a `app/Console/Kernel.php` desde Laravel 11). Cron debe estar configurado en el host con:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Para listar los comandos planificados activos:

```bash
php artisan schedule:list
```

### Contenido actual de `routes/console.php`

Lo programado sale de `routes/console.php`, y `SchedulerTest` comprueba que
todo apunte a un comando real. Todas las tareas llevan `withoutOverlapping()`,
las pesadas `runInBackground()`, las que tienen que caer a una hora local
concreta `->timezone('Europe/Madrid')` para que no se muevan con el cambio de
hora, y todas dejan constancia del fallo con `onFailure()`.

```bash
php artisan schedule:list   # la lista de verdad, siempre actualizada
```

---

## 9. Generación de Sitemap (`sitemap:generate`)

El comando `php artisan sitemap:generate` rastrea y consolida todos los recursos públicos navegables de la plataforma en un archivo XML compatible con los estándares de motores de búsqueda (`sitemap.xml`).

### Lógica de Funcionamiento
1. **Control de Concurrencia:** Utiliza la clave de caché `sitemap_generation_lock` con TTL de 1 hora para evitar ejecuciones duplicadas simultáneas. Puede forzarse con `--force`.
2. **URLs Estáticas Base:**
   - Portada (`home`, prioridad 1.0, frecuencia mensual).
   - Índice de Plantas Inteligentes (`smartplant.index`, prioridad 0.7, frecuencia semanal).
3. **URLs Dinámicas de Módulos:**
   - **SmartPlant:** Registra la vista de detalle de cada planta (`smartplant.show`) con fecha de modificación real y frecuencia semanal.
   - **WeatherStation:** Registra el índice del módulo y las vistas de detalle de cada sensor activo (`weather_station.sensor`) por estación meteorológica (`HardwareDevice::weatherStations()`), obteniendo la fecha exacta del último registro persistido (`max('created_at')`).
4. **Escritura Atómica con Respaldo:**
   - Realiza copia de seguridad previa de `public/sitemap.xml` a `public/sitemap_backup.xml`.
   - Escribe el nuevo XML y valida que no esté vacío antes de eliminar el backup. En caso de excepción, restaura automáticamente el backup previo.

### Opciones
- `--force`: Fuerza la regeneración omitiendo el bloqueo de caché.
- `--chunk=100`: Configura el tamaño de bloque para la consulta de registros.

### Ejecución Periódica
El comando está programado en `routes/console.php` para ejecutarse **diariamente**:
```php
Schedule::command('sitemap:generate')->daily();
```

---

## 10. Comandos estándar de Laravel más usados

Comandos del framework que se usan habitualmente en este proyecto:

| Comando | Uso |
|---------|-----|
| `migrate` / `migrate:fresh --seed` | Migraciones (en CI, fresh+seed). |
| `db:seed` | Ejecuta los seeders. |
| `queue:work` | Worker de colas (ver Supervisor en [deploy-vps.md](../deploys/deploy-vps.md)). |
| `cache:clear` / `config:cache` / `route:cache` / `view:cache` | Cachés. |
| `storage:link` | Link de `public/storage` a `storage/app/public`. |
| `test` | Suite PHPUnit. |
| `tinker` | REPL interactiva. |
| `optimize` / `optimize:clear` | Atajos de cachés combinados. |
| `sanctum:prune-expired` | Purga tokens caducados (no programado todavía). |

> `reverb:start` levanta el servidor de WebSockets. Está **implementado pero apagado**
> por defecto (`BROADCAST_CONNECTION=null`). Poniéndolo a `reverb` en `.env`, el propio
> `php artisan serve` lo arranca en segundo plano (ver `serve` arriba); también puede
> lanzarse suelto para producción. Ver [websockets.md](websockets.md).

---

## 11. Cómo añadir un comando nuevo

1. Generar:
   ```bash
   php artisan make:command Module/Foo/MyCommand
   ```
2. Implementar `signature`, `description`, y `handle()`.
3. Si necesita correr en cron, registrarlo en `bootstrap/app.php` (sección `withSchedule`).
4. Añadir entrada en esta tabla.
5. Si afecta a un módulo concreto, mencionar el comando en el `docs/info/<modulo>.md` correspondiente.

---

> Creado: 2026-05-26 · Última revisión: 2026-08-30
