# Catálogo de comandos Artisan

Listado completo de los comandos Artisan personalizados del proyecto, agrupados por módulo.

> Para los comandos `debug:seed-*` (datos de prueba), ver además [debug-commands.md](debug-commands.md).

---

## 1. Comandos de proyecto

| Comando | Descripción | Archivo |
|---------|-------------|---------|
| `project:install` | Instalación inicial: migra, semilla básica y enlaces de storage. | `app/Console/Commands/ProjectInstallCommand.php` |
| `project:clear` | Limpia cachés (config, route, view, event, opcache). | `app/Console/Commands/ProjectClearCommand.php` |
| `force:clear` | Variante agresiva de `project:clear` para entornos rotos. | `app/Console/Commands/ForceClearCommand.php` |
| `sitemap:generate` | Genera el sitemap XML público. | `app/Console/Commands/SitemapGeneratorCommand.php` |

---

## 2. AEMET (Estación Meteorológica)

Documentación técnica: [apis/aemet.md](apis/aemet.md).

| Comando | Frecuencia recomendada | Descripción |
|---------|------------------------|-------------|
| `aemet:update-daily` | 1×/día | Predicción diaria municipio (placeholder). |
| `aemet:update-daily8` | 08:00 | Playas, alta mar, radiación solar. |
| `aemet:update-daily12` | 12:00 | Costa, ozono. |
| `aemet:update-daily20` | 20:00 | Costa (segunda actualización). |
| `aemet:update-every4h` | Cada 4 h | Predicción horaria. |
| `aemet:update-every30m` | Cada 30 min | Avisos CAP (eventos adversos). |
| `aemet:update-every10m` | Cada 10 min | Contaminación. |

Todos usan el trait `ValidatesAemetPayload` para validar el payload antes de persistir.

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
| `debug:seed-users` | `--count=5` | Crea usuarios de prueba. |
| `debug:seed-hardware` | `--count=5` | Crea dispositivos hardware. |
| `debug:seed-weatherstation` | `--count=20` | Crea registros de sensores meteo. |
| `debug:seed-airflight` | `--planes=10 --routes=100` | Crea aviones y rutas. |
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

El scheduler está definido en `bootstrap/app.php` (sustituye a `app/Console/Kernel.php` en Laravel 11+). Cron debe estar configurado en el host con:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Para listar los comandos planificados activos:

```bash
php artisan schedule:list
```

---

## 9. Comandos estándar de Laravel más usados

Comandos del framework que se usan habitualmente en este proyecto:

| Comando | Uso |
|---------|-----|
| `migrate` / `migrate:fresh --seed` | Migraciones (en CI, fresh+seed). |
| `db:seed` | Ejecuta los seeders. |
| `queue:work` | Worker de colas (ver Supervisor en [deploy-vps.md](../deploys/deploy-vps.md)). |
| `cache:clear` / `config:cache` / `route:cache` / `view:cache` | Cachés. |
| `storage:link` | Link de `public/storage` a `storage/app/public`. |
| `reverb:start` | Inicia el servidor WebSocket Reverb (ver [websockets.md](websockets.md)). |
| `test` | Suite PHPUnit. |
| `tinker` | REPL interactiva. |
| `optimize` / `optimize:clear` | Atajos de cachés combinados. |

---

## 10. Cómo añadir un comando nuevo

1. Generar:
   ```bash
   php artisan make:command Module/Foo/MyCommand
   ```
2. Implementar `signature`, `description`, y `handle()`.
3. Si necesita correr en cron, registrarlo en `bootstrap/app.php` (sección `withSchedule`).
4. Añadir entrada en esta tabla.
5. Si afecta a un módulo concreto, mencionar el comando en el `docs/info/<modulo>.md` correspondiente.
