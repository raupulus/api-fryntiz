# Revisar AEMET

> **Estado:** pendiente. No bloquea ningún despliegue.

## ✅ `aemet:ozono` pedía el endpoint equivocado — corregido el 2026-09-14

**Verificado lanzando ambas peticiones reales contra la API de AEMET** (no sólo contra el código y los
datos ya guardados): el sobre de `red/especial/perfilozono/estacion/peninsula` trajo un sondeo fechado
**5 días atrás** (`Started at 9 September 2026`); el de `red/especial/ozono` trajo el CSV de 7
estaciones fechado **de ayer** (`"13-09-26"`). Confirma exactamente lo que decían el código y los
datos de producción.

`AEMETHelper::$PATHS['ozono']` apunta a:

```
red/especial/perfilozono/estacion/peninsula
```

Ese es el **perfil vertical de ozono** (sondeo de una ozonosonda: presión, altura, temperatura,
velocidad de ascenso…), documentado en
[`09-redes-especiales.md`](../apis/aemet/09-redes-especiales.md#perfiles-verticales-de-ozono) como
`periodicidad: Cada 7 días`, **observado con hasta 28 días de retraso**. El modelo `AEMETOzone` y su
`saveFromApi()` están escritos para ese formato (campos `time_min`, `pressure`, `ozone_probe_read_at`…),
así que no es un desliz aislado: todo el pipeline de este comando es el del perfil, no el del ozono de
superficie.

Pero el comando (`AEMETOzoneCommand`), su descripción (*"Ozono en superficie. Publicación diaria."*)
y la tabla de cadencia de [`docs/info/apis/aemet.md`](../info/apis/aemet.md#cadencia-de-cada-producto)
(`aemet:ozone | diario 12:25 | TTL 12h`) describen el **otro** producto: **"Contenido total de ozono"**
(`GET red/especial/ozono`, CSV `Estación;Indicativo;Ozono`, sí diario, sí TTL 12-24h).

Los datos de producción confirman el desfase: `meteorology_aemet_ozone` recibe lotes cada 7-8 días
(`2026-09-11`, `2026-09-03`, `2026-07-30`, `2026-07-23`…), no a diario, pese a que el scheduler
(`routes/console.php`) lanza el comando **todos los días a las 12:25**. La mayoría de esas ejecuciones
diarias no traen nada nuevo — gastan cuota contra un endpoint cuyo dato no ha cambiado.

**Lo aplicado (2026-09-14), sin tocar la tabla `meteorology_aemet_ozone` ni sus 686 mil filas — son
datos de sondeo legítimos, sólo estaban mal etiquetados:**

- [x] `aemet:ozone` → `aemet:ozone-profile` (`app/Console/Commands/AEMET/AEMETOzoneCommand.php`),
      descripción corregida.
- [x] Scheduler (`routes/console.php`): de `dailyAt('12:25')` a `weeklyOn(1, '12:25')` — coherente con
      la periodicidad real de 7 días.
- [x] `AemetDashboard.php`, `docs/info/commands.md`, `docs/info/apis/aemet.md`, `AGENTS.md` y
      `docs/info/weather-station.md` actualizados con el nombre y la descripción reales.

**Implementado el mismo día (2026-09-14), a petición expresa:**

- [x] **Ozono de superficie diario** (`red/especial/ozono`, CSV de 7 estaciones): comando nuevo
      `aemet:ozone-total`, modelo `AEMETOzoneTotal`, migración
      `meteorology_aemet_ozone_total` (una fila por estación y día, `unique(station_code, measured_on)`
      para que una segunda ejecución el mismo día actualice en vez de duplicar), parseo en
      `AEMETHelper::getOzoneTotal()`, scheduler diario a las 08:30 (AEMET no declara hora de
      publicación; va 5 min detrás del último de la tanda de la mañana). Probado contra la API real:
      guarda las 7 estaciones, no duplica en una segunda ejecución, y no revienta con un cuerpo vacío.
      Tests en `tests/Feature/Console/AemetOzoneTotalCommandTest.php`.
- [x] **Rutas rotas de `AEMETService`**: `getContamination()` pedía
      `red/especial/contaminacionfondo` sin estación (**404**, la estación no es opcional) y
      `getSunRadiation()` pedía `red/especial/radiacionsolar` (**404**, la ruta real es
      `red/especial/radiacion`) — ambas documentadas como incorrectas en
      [`09-redes-especiales.md`](../apis/aemet/09-redes-especiales.md#dos-rutas-que-se-documentan-mal-a-menudo).
      Las tres (`getContamination()`, `getOzone()`, `getSunRadiation()`) además asumían un cuerpo JSON
      para un producto que es texto/CSV — `decodeJson()` habría devuelto `null` siempre, incluso con la
      URL bien. Se corrigieron las rutas y se añadió el parámetro `$comoJson` a `cachedRequest()` /
      `makeRequest()`. Verificado lanzando las tres contra la API real: las tres devuelven ahora el
      cuerpo correcto. Sigue sin llamarlas nadie en producción (los comandos reales usan
      `AEMETHelper`), pero ya no son una trampa para quien retome la migración del punto 8 de
      [`docs/info/apis/aemet.md`](../info/apis/aemet.md). Tests en
      `tests/Unit/Services/AEMETServiceTest.php`.

## Endpoints que faltan por decidir

AEMET OpenData publica su especificación completa (OpenAPI). Hoy se consumen **9 productos** —los que
tienen comando `aemet:*` y no son la vigilancia de la clave— de los **64 endpoints** ya verificados
contra la API real en `docs/apis/aemet/`. Falta **decidir cuáles de los 54 restantes interesan** a
este proyecto.

> ⚠️ **No fiarse del todo de la especificación.** `docs/apis/aemet/` documenta lo que cada endpoint
> devuelve **de verdad**, no siempre lo que dice el spec. Validar contra una respuesta real antes de
> dar por bueno un contrato nuevo.

Modelos AEMET que ya existen, para no duplicar: `AEMETPrediction`, `AEMETPredictionBeach`,
`AEMETCoast`, `AEMETHighSea`, `AEMETOzone`, `AEMETOzoneTotal`, `AEMETContamination`,
`AEMETSunRadiation`, `AEMETAdverseEvents` (los 9, bajo `app/Models/WeatherStation/AEMET/`; no existe
un modelo base `AEMET` suelto, pese a lo que decía esta lista antes).

## Referencias

- AEMET OpenData: https://opendata.aemet.es/
- Alta de API key: https://opendata.aemet.es/centrodedescargas/altaUsuario
- Cómo se usa aquí: [`docs/info/apis/aemet.md`](../info/apis/aemet.md)
- Documentación oficial destilada: [`docs/apis/aemet/`](../apis/aemet/README.md)

> Creado: 2026-08-30 · Última revisión: 2026-09-14
