# Revisar AEMET

> **Estado:** parcialmente resuelto. Sólo queda pendiente el punto 3.
> **Decidido el:** 2026-08-19 · **Revisado el:** 2026-08-30

## Ya resuelto (no hace falta releer esto para retomarlo)

- **Robustez del parseo:** `App\Services\WeatherStation\AEMETService` respeta el charset
  declarado, detecta cuerpo vacío, HTML de error con 200 y avisa si el contenido está rancio.
  `App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload::guardedSave()` persiste lo que
  llega válido y no aborta el comando si un campo falta.
- **Errores de red y cuota:** timeout explícito, reintento con backoff largo y sin insistir en
  un `429`, caché por TTL (`Cache::remember`) y cuota registrada por plantilla de endpoint. Ver
  `AEMETService::httpWithRetry()` / `recordQuotaUsage()`.
- **Cuota diaria:** documentada en [`docs/info/apis/aemet.md`](../info/apis/aemet.md) §4, con la
  tabla de cadencia por producto y los números medidos en
  [`docs/apis/aemet/LIMITACIONES.md`](../apis/aemet/LIMITACIONES.md).

Detalle completo de todo lo anterior: [`docs/info/apis/aemet.md`](../info/apis/aemet.md).

## Pendiente: endpoints que faltan

AEMET OpenData publica su especificación completa (OpenAPI); hoy sólo se consumen 9 productos
(los que tienen comando `aemet:*`), de los **64 endpoints** ya verificados contra la API real en
`docs/apis/aemet/`. Falta **decidir** cuáles de los 55 restantes interesan a este proyecto.

> ⚠️ **No fiarse del todo de la especificación.** `docs/apis/aemet/` documenta lo que cada
> endpoint devuelve **de verdad**, no siempre lo que dice el spec. Validar contra una respuesta
> real antes de dar por bueno un contrato nuevo.

Modelos AEMET que ya existen (para no duplicar): `AEMET`, `AEMETPrediction`,
`AEMETPredictionBeach`, `AEMETCoast`, `AEMETHighSea`, `AEMETOzone`, `AEMETContamination`,
`AEMETSunRadiation`, `AEMETAdverseEvents`.

## Referencias

- AEMET OpenData: https://opendata.aemet.es/
- Alta de API key: https://opendata.aemet.es/centrodedescargas/altaUsuario
- Documentación interna: [`docs/info/apis/aemet.md`](../info/apis/aemet.md)
- Documentación oficial destilada: [`docs/apis/aemet/`](../apis/aemet/README.md)

## Nota

Esto **no bloquea** ningún despliegue.
