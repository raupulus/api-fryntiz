# API AEMET OpenData — Integración

Documentación técnica de la integración con la API oficial de **AEMET OpenData** (Agencia Estatal de Meteorología) en el proyecto `api-fryntiz`.

- Documentación oficial: <https://opendata.aemet.es/dist/index.html>
- Solicitud de API key: <https://opendata.aemet.es/centrodedescargas/altaUsuario>

> Para detalles del módulo y los modelos de Eloquent, ver [weather-station.md](../weather-station.md).

---

## 1. Arquitectura

```
┌────────────────────────────┐         ┌────────────────────────┐
│  Comandos Artisan          │  uses   │  AEMETService          │
│  aemet:update-* (7)        │ ──────▶ │  (Cache + Retry + 2-hop)│
└────────────────────────────┘         └────────────┬───────────┘
                                                    │ Http::retry
                                                    ▼
                                       ┌────────────────────────┐
                                       │  opendata.aemet.es     │
                                       │  /opendata/api/*       │
                                       └────────────────────────┘
```

- **Servicio canónico**: `App\Services\WeatherStation\AEMETService`
- **Helper legacy**: `\AEMETHelper::*` (función global, usada por los comandos actuales hasta migración completa).
- **Trait de validación de payload**: `App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload` — provee el método `guardedSave($label, $producer, $persistor)` que valida y persiste con manejo de errores.

---

## 2. Flujo de petición de AEMET (dos saltos)

Todas las llamadas a AEMET son de **dos saltos**:

1. **Envelope**: `GET https://opendata.aemet.es/opendata/api/<endpoint>` con header `api_key`.
   Respuesta JSON:
   ```json
   {
     "descripcion": "exito",
     "estado": 200,
     "datos": "https://opendata.aemet.es/opendata/sh/<token>",
     "metadatos": "https://opendata.aemet.es/opendata/sh/<token-meta>"
   }
   ```
2. **Payload real**: `GET <datos>` (sin auth). Devuelve el JSON con la información solicitada.

`AEMETService::makeRequest()` encapsula este flujo y devuelve siempre el payload final (o `null` si cualquiera de los dos saltos falla).

---

## 3. Rate limiting

AEMET aplica límites:

- ~100 peticiones por minuto por API key.
- ~3000 peticiones por día por API key.
- En caso de excederlos, devuelve **HTTP 429** (Too Many Requests).

### Estrategia implementada

| Mecanismo | Configuración (`config/aemet.php`) | Comportamiento |
|-----------|------------------------------------|----------------|
| Caché por endpoint | `cache_ttl.*` (s) | Cada llamada se cachea por TTL específico. |
| Retry con backoff exponencial | `rate_limit.retry_attempts` (3) y `rate_limit.retry_base_delay_ms` (1000) | Reintenta hasta 3 veces con 1s, 2s, 4s entre reintentos. |
| Filtro de retry | Solo HTTP 429 y 5xx | No reintenta en 4xx no transitorios. |
| Margen de seguridad | `max_requests_per_minute = 50` | Cota documental. |

### Tabla de TTL por endpoint

| Endpoint | Clave TTL | Valor por defecto | Razonamiento |
|----------|-----------|-------------------|--------------|
| Predicción diaria municipio | `daily_prediction` | 600 s (10 min) | Cambios suaves a lo largo del día. |
| Contaminación | `contamination` | 1800 s (30 min) | Datos no críticos. |
| Avisos adversos (CAP) | `adverse_events` | 300 s (5 min) | Críticos para alertas. |
| Costa | `coast` | 1800 s (30 min) | Cambios cada 12h. |
| Alta mar | `high_sea` | 1800 s | id. |
| Ozono | `ozone` | 3600 s (1h) | Mediciones semanales (globo sonda). |
| Radiación solar | `sun_radiation` | 3600 s | id. |
| Predicción playa | `prediction_beach` | 1800 s | id. |

---

## 4. Configuración

### Variables `.env`

```env
AEMET_API_KEY="<tu_api_key>"
AEMET_BASE_URL="https://opendata.aemet.es/opendata/api"
AEMET_DEFAULT_MUNICIPIO="11015"   # Chipiona
AEMET_DEFAULT_PLAYA="1101501"
AEMET_DEFAULT_COSTA="11"
AEMET_DEFAULT_AREA="61"
```

### `config/aemet.php`

Estructura completa: ver el archivo `config/aemet.php` del proyecto. Las claves principales:

- `api_key`, `base_url`
- `default_municipio`, `default_playa`, `default_costa`, `default_area`
- `rate_limit.{retry_attempts, retry_base_delay_ms, max_requests_per_minute, max_requests_per_day}`
- `cache_ttl.{daily_prediction, contamination, adverse_events, coast, high_sea, ozone, sun_radiation, prediction_beach}`

---

## 5. Endpoints implementados

| Modelo | Método del servicio | Endpoint base |
|--------|---------------------|---------------|
| `AEMETPrediction` | `getDailyPrediction($code = null)` | `/prediccion/especifica/municipio/diaria/{codigoMunicipio}` |
| `AEMETPredictionBeach` | `getBeachPrediction($code = null)` | `/prediccion/especifica/playa/{codigoPlaya}` |
| `AEMETCoast` | `getCoastPrediction($code = null)` | `/prediccion/maritima/costera/costa/{codigoCosta}` |
| `AEMETHighSea` | `getHighSeaPrediction($code = null)` | `/prediccion/maritima/altamar/area/{codigoArea}` |
| `AEMETContamination` | `getContamination()` | `/red/especial/contaminacionfondo` |
| `AEMETOzone` | `getOzone()` | `/red/especial/ozono` |
| `AEMETSunRadiation` | `getSunRadiation()` | `/red/especial/radiacionsolar` |
| `AEMETAdverseEvents` | `getAdverseEvents($area = null)` | `/avisos_cap/ultimoelaborado/area/{areaId}` |

Todos los métodos devuelven `?array` (null si la petición falla o el payload es inválido).

---

## 6. Comandos Artisan

Todos están en `app/Console/Commands/AEMET/`:

| Comando | Frecuencia | Endpoint(s) que consume |
|---------|-----------|--------------------------|
| `aemet:update-daily` | 1×/24h | Predicción diaria (placeholder actual). |
| `aemet:update-daily8` | 08:00 | Playas, alta mar, radiación solar. |
| `aemet:update-daily12` | 12:00 | Costa, ozono. |
| `aemet:update-daily20` | 20:00 | Costa. |
| `aemet:update-every4h` | Cada 4h | Predicción horaria. |
| `aemet:update-every30m` | Cada 30 min | Avisos CAP (eventos adversos). |
| `aemet:update-every10m` | Cada 10 min | Contaminación. |

Todos los comandos:

1. Usan el trait `ValidatesAemetPayload::guardedSave()`.
2. Si el payload no es un array no vacío, registran un warning en `storage/logs/laravel.log` y omiten la persistencia (no rompen el siguiente endpoint del mismo comando).
3. Si lanza excepción durante `saveFromApi`, registran error y siguen con el resto.

---

## 7. Manejo de errores

| Situación | Respuesta del servicio | Log |
|-----------|------------------------|-----|
| API key no configurada | `null` | `warning`: "no se ha configurado AEMET_API_KEY". |
| HTTP 4xx no transitorio (400, 401, 403, 404) | `null` | `warning`: "respuesta no exitosa" con status. |
| HTTP 429 / 5xx | Retry 3× con backoff exponencial; si falla, `null`. | `warning` tras agotar reintentos. |
| Envelope sin clave `datos` | `null` | `warning`: "envelope sin clave datos". |
| Payload final no es JSON array | `null` | `warning`: "payload no es JSON array". |
| Excepción de red | `null` | `error`: "excepción durante la petición". |

---

## 8. Verificación en local

```bash
# Configurar API key en .env
echo "AEMET_API_KEY=\"<TU_KEY>\"" >> .env

# Limpiar cache
php artisan cache:clear

# Probar comandos
php artisan aemet:update-every10m
php artisan aemet:update-every4h
php artisan aemet:update-daily8

# Ver logs
tail -f storage/logs/laravel.log
```

Para probar **sin API key real**, los tests deberían usar `Http::fake()`:

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

Cache::flush();
Http::fake([
    'opendata.aemet.es/opendata/api/*' => Http::response([
        'descripcion' => 'exito',
        'datos' => 'https://opendata.aemet.es/datos/x.json',
    ], 200),
    'opendata.aemet.es/datos/x.json' => Http::response([
        ['prediccion' => ['dia' => [['fecha' => now()->toDateString()]]]],
    ], 200),
]);

$svc = new \App\Services\WeatherStation\AEMETService();
$data = $svc->getDailyPrediction('11015');
// $data debe ser un array con la predicción.
```

---

## 9. Migración pendiente

El proyecto contiene un helper global `\AEMETHelper::*` (ver `app/Helpers/AEMETHelper.php`) que los comandos siguen usando por compatibilidad histórica. **Plan de migración**:

1. Replicar cada método del helper en `AEMETService` (ya están todos los endpoints públicos).
2. Sustituir progresivamente `\AEMETHelper::xxx()` → `app(AEMETService::class)->xxx()` en los comandos.
3. Una vez todos los comandos usen el servicio, eliminar `AEMETHelper.php`.

Este paso se hará en una refactorización futura. Por ahora, el trait `ValidatesAemetPayload` garantiza que los fallos del helper se manejan sin perder datos.

---

## 10. Referencias

- [Documentación oficial OpenData AEMET](https://opendata.aemet.es/dist/index.html)
- Modelos y migraciones del módulo: [weather-station.md](../weather-station.md)
- Catálogo de comandos: [commands.md](../commands.md)
