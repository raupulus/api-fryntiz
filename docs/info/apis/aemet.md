# AEMET OpenData — cómo la usamos

Cómo consume esta plataforma la API de **AEMET OpenData**: qué pedimos, cada
cuánto, qué guardamos y qué hay que vigilar.

> **Esto no es la documentación de AEMET.** La documentación de la API —lo que
> devuelve cada endpoint, sus erratas y sus límites reales— está en
> [`docs/apis/aemet/`](../../apis/aemet/README.md), y es donde hay que mirar
> antes de tocar nada aquí. Los datos de este archivo salen de ahí; si algo no
> cuadra, manda `docs/apis/aemet/`.
>
> Para los modelos y las tablas, ver [weather-station.md](../weather-station.md).

---

## 1. Las piezas

```
Comandos artisan (12 productos + 1 de vigilancia)
        │
        │  \AEMETHelper::getLoQueSea()     ← parsea cada producto
        ▼
  AEMETService                            ← una sola puerta de salida
        │  · clave en la cabecera api_key, NUNCA en la query
        │  · charset ISO-8859-15 respetado
        │  · cuota por endpoint, reintentos acotados
        │  · detecta cuerpo vacío y HTML de error con 200
        ▼
  opendata.aemet.es
```

| Pieza | Qué hace |
|---|---|
| `App\Services\WeatherStation\AEMETService` | **Todas** las peticiones salen por aquí. `fetchRaw()` para JSON y texto, `fetchBinary()` para el `tar` de los avisos |
| `support/helpers/AEMETHelper.php` | El parseo de cada producto. Es lo que llaman los comandos; ya no hace peticiones por su cuenta |
| `App\Support\WeatherStation\CapWarnings` | Lee el paquete CAP de los avisos. **No necesita red**: se le dan los bytes del `tar` |
| `App\Support\WeatherStation\AemetApiKey` | El estado de la clave: si caduca, cuándo y qué hacer |
| `App\Console\Commands\AEMET\Concerns\ValidatesAemetPayload` | `guardedSave()`: valida el payload y persiste sin dejar que una excepción tumbe el comando |

---

## 2. Las dos cosas que hay que saber sí o sí

### La clave caduca y no da error

La `AEMET_API_KEY` es un JWT que **caduca a los ~100 días**. Cuando caduca, AEMET
**no responde 401**: responde **200 con el cuerpo vacío**. En los logs eso es
indistinguible de «hoy no hay datos», así que la integración se queda muda y no
se entera nadie hasta que alguien echa de menos un dato semanas después.

Por eso:

- `AEMET_API_KEY_EXPIRES_AT` se apunta **a mano en el `.env`** al renovar la clave.
  Si no está, se lee el `exp` del propio JWT como respaldo.
- `aemet:check-api-key` corre a diario a las 08:00 y avisa **15 días antes**
  (`config('aemet.warn_days_before_expiry')`). El aviso va al log como `WARNING`;
  el comando sale con código 0 aunque toque renovar, para que el planificador no
  añada una traza suya encima del aviso cada uno de esos quince días.
- El panel de AEMET enseña el aviso arriba del todo.
- Cuando un payload llega vacío, `ValidatesAemetPayload` dice en el log si la
  clave tiene algo que ver. Es el único momento en que alguien lo va a leer.

Se renueva en <https://opendata.aemet.es/centrodedescargas/altaUsuario>.

### Citar a AEMET es obligatorio

No es cortesía. La nota legal de AEMET exige citarla como fuente y conservar sus
metadatos, y la Ley 18/2015 trae **régimen sancionador**. Mostrar su predicción
en una web es un «servicio de valor añadido», y para esos la mención explícita es
exigible.

Los textos están en `config('aemet.attribution')` y son **los literales
oficiales**: no se reescriben ni se traducen.

| Clave | Texto |
|---|---|
| `corta` | `Fuente: AEMET` |
| `larga` | `Información elaborada utilizando, entre otras, la obtenida de la Agencia Estatal de Meteorología` |
| `copyright` | `© AEMET. Autorizado el uso de la información y su reproducción citando a AEMET como autora de la misma.` |
| `nota_legal` | <https://www.aemet.es/es/nota_legal> |

**Antes de publicar un dato de AEMET en cualquier sitio:**

- [ ] Se ve `Fuente: AEMET` (o el texto largo).
- [ ] Se ve **la fecha de elaboración del dato** — también es obligatoria.
- [ ] Si el producto trae `origen.copyright` y `origen.notaLegal`, se propagan.
- [ ] Nada sugiere que AEMET patrocina o valida el sitio.

Detalle completo en
[`docs/apis/aemet/12-uso-legal-y-atribucion.md`](../../apis/aemet/12-uso-legal-y-atribucion.md).

---

## 3. El flujo de dos saltos

Toda petición a AEMET son dos:

1. **El sobre**: `GET /opendata/api/<endpoint>` con la cabecera `api_key`.
   Devuelve un JSON con `estado`, `descripcion` y una URL en `datos`.
2. **Los datos**: `GET <datos>`, **sin autenticación** — esa URL es efímera y
   mandarle la clave sería filtrarla a un host que no la necesita.

La clave va **siempre en la cabecera `api_key`**, nunca en la query string: en la
query acaba en los logs del servidor, en los del proxy y en el `Referer`.

---

## 4. La cuota, que no es lo que parece

El límite de AEMET **no es un número de peticiones por minuto**. Es un cubo de
~40 **por plantilla de endpoint** (15 en los productos pesados) que además va
ligado a la **IP**, no sólo a la clave:

- Generar otra API Key **no** desbloquea un endpoint agotado.
- Dos entornos en el mismo servidor **comparten cuota**.
- El 429 **no trae `Retry-After`** y la recuperación tarda **más de una hora**.

Por eso el backoff son 30 s y sólo 2 reintentos: insistir no recupera nada y
quema el cubo. La cabecera `Remaining-request-endpoint` dice lo que queda y
`AEMETService` la va anotando por endpoint.

Números medidos y razonados en
[`docs/apis/aemet/LIMITACIONES.md`](../../apis/aemet/LIMITACIONES.md).

### Cadencia de cada producto

Las TTL y las horas del planificador salen del campo `periodicidad` que declara
AEMET para cada producto. **Pedir más a menudo no trae datos nuevos**: sólo gasta
cuota.

| Producto | Comando | Cuándo | TTL |
|---|---|---|---|
| Avisos adversos (CAP) | `aemet:adverse-events` | cada 30 min | 20 min |
| Contaminación | `aemet:contamination` | cada hora | 1 h |
| Predicción horaria | `aemet:hourly-prediction` | cada 3 h | 3 h |
| Predicción diaria | `aemet:daily-prediction` | cada 6 h | 3 h |
| Predicción de playas | `aemet:beaches` | diario | 6 h |
| Predicción de costa | `aemet:coast` | diario | 6 h |
| Alta mar | `aemet:high-sea` | diario 08:15 | 6 h |
| Radiación solar | `aemet:sun-radiation` | diario 08:25 | 12 h |
| Perfil de ozono (sondeo) | `aemet:ozone-profile` | lunes 12:30 | días |
| Ozono total (superficie) | `aemet:ozone-total` | diario 08:30 | 12-24 h |
| Índice UV | `aemet:uvi` | diario 08:35 | 12 h |
| Observación de estaciones | `aemet:station-observations` | cada 30 min | 30-60 min |
| **Vigilancia de la clave** | `aemet:check-api-key` | diario 08:00 | — |

Horas en `Europe/Madrid`, fijadas para que no se muevan con el cambio de hora.
AEMET declara "Cada 24 h" para el ozono total pero no una hora concreta de
publicación, así que va en la tanda de la mañana, 5 min detrás del último.

> **`aemet:ozone-profile` no era ozono de superficie, y ya existe el que sí lo
> es (2026-09-14).** Hasta esta fecha el comando se llamaba `aemet:ozone`,
> corría a diario a las 12:25 y su descripción decía "Ozono en superficie.
> Publicación diaria." — pero `AEMETHelper::getOzone()` siempre ha pedido
> `red/especial/perfilozono/estacion/peninsula` (el perfil vertical de una
> ozonosonda: presión, altura, temperatura, velocidad de ascenso…), no
> `red/especial/ozono` (el ozono total diario, CSV con 7 estaciones). Lo
> verifiqué lanzando ambas peticiones reales contra la API: el perfil traía
> datos fechados **5 días atrás** y el ozono total, de **ayer** — coherente con
> la `periodicidad` que declara AEMET para cada uno (ver
> [`09-redes-especiales.md`](../../apis/aemet/09-redes-especiales.md)): el
> perfil se publica cada 7 días y se ha observado hasta con 28 de retraso; el
> total, a diario. El modelo `AEMETOzone` está construido para el perfil (los
> campos son los del sondeo), así que no fue un desliz aislado del comando:
> todo el pipeline era del perfil. Se renombró el comando y se bajó su
> cadencia a semanal.
>
> El ozono total de superficie que el nombre viejo prometía **ya está
> implementado**: `aemet:ozone-total`, modelo `AEMETOzoneTotal`
> (`meteorology_aemet_ozone_total`, una fila por estación y día), parseado en
> `AEMETHelper::getOzoneTotal()`. De paso se repararon
> `AEMETService::getContamination()`, `getOzone()` y `getSunRadiation()`:
> las dos primeras pedían rutas que `09-redes-especiales.md` documenta como
> **incorrectas (404)** —`red/especial/contaminacionfondo` sin estación y
> `red/especial/radiacionsolar`— y las tres asumían un cuerpo JSON para un
> producto que en realidad es texto/CSV. Nada de esto lo llama ningún comando
> real hoy (los comandos usan `AEMETHelper`), pero ya no son una trampa para
> quien retome la migración descrita en el punto 8.

> **Predicción diaria, UVI y observación de estaciones, nuevos (2026-09-16).**
>
> - **`aemet:uvi`** guarda el índice UV máximo previsto de una sola ciudad
>   (`config('aemet.uvi_city_code')`, Cádiz capital — `id 11012`, verificado en
>   directo; **no** es el código de municipio de Chipiona, que es otro
>   catálogo). `AEMETHelper::getUvi()` filtra la ciudad propia de las 59 que
>   trae la respuesta. Tabla `meteorology_aemet_uvi`, una fila por día
>   (`unique(valid_date)`).
> - **`aemet:station-observations`** guarda el dato real (no predicción) de
>   tres estaciones cercanas a Chipiona, `config('aemet.stations')`:
>   `chipiona_eca` (`5906X`), `rota_base_naval` (`5910X`), `almonte` (`5858X`).
>   Tabla `meteorology_aemet_station_observations`
>   (`unique(station_id, observed_at)`).
>
>   ⚠️ **El `idema` de la observación convencional no siempre coincide con el
>   del inventario de valores climatológicos para la misma estación física.**
>   Rota es `5910X` aquí y `5910` allí — con el código sin `X`, este endpoint
>   responde 200 con `estado: 404` dentro (comprobado que no era cuota:
>   `Remaining-request-endpoint` de sobra, HTTP real 200 no 429). Antes de dar
>   un `idema` por bueno para un producto, verificarlo contra ESE endpoint, no
>   fiarse de otro inventario.
>
>   Rota no reporta viento en ninguno de sus registros (`wind_*` quedan
>   `null`, no `0`); `dew_point`/`pressure`/`visibility`/`snow_depth` no llegan
>   hoy en ninguna de las tres estaciones, pero se guardan igual a la espera —
>   decisión del usuario, revisar en unas semanas/meses si conviene quitarlas.
>   Detalle completo de la investigación en
>   [`docs/future/archived/revisar-aemet.md`](../../future/revisar-aemet.md).
>
> - **`aemet:daily-prediction`** guarda el resumen diario del municipio (hasta
>   7 días por sondeo). Tabla `meteorology_aemet_daily_predictions`, una fila
>   por día (`unique(date)`).
>
>   ⚠️ **Es una forma distinta a la de la predicción horaria, no la misma
>   estructura con otros tramos.** Al intentar verificarla la primera vez,
>   AEMET devolvió 429 desde la máquina de desarrollo — la familia
>   `/prediccion/especifica/municipio/*` tiene un cubo de cuota pequeño y la
>   cuota va ligada a la IP, no a la clave (una clave nueva no lo arregla,
>   comprobado). Resuelto lanzando la petición real desde un VPS con otra IP.
>   La respuesta real: `viento` y `rachaMax` van **separados** (no hay
>   `vientoAndRachaMax` combinado como en la horaria), no hay `precipitacion`
>   en mm ni `orto`/`ocaso`, y sí hay `uvMax` — pero **no en todos los días**:
>   ausente en los dos más lejanos del rango de siete. Cada campo de día trae
>   hasta 7 tramos para los días próximos, menos para los medios, y un único
>   elemento sin `periodo` para los más lejanos; `AEMETHelper::getDailyPrediction()`
>   siempre coge el tramo `00-24` (día completo) o el único elemento si no hay
>   más.
>
>   Detalle completo de la investigación (con el JSON real capturado del
>   VPS) en
>   [`docs/future/archived/revisar-aemet.md`](../../future/revisar-aemet.md).

---

## 5. Los avisos de fenómenos adversos (CAP)

Es el producto con el formato más raro de toda la API y el que más trampas tiene.

### El formato

- **Un `tar` SIN comprimir**, aunque el `Content-Type` sea `application/x-gtar`.
  Se comprueba la firma `ustar` en el offset 257 antes de abrirlo, porque AEMET
  responde 200 con una página de error HTML más a menudo de lo que debería.
- Se descarga con `fetchBinary()`, **no** con `fetchRaw(..., false)`: el
  `Content-Type` declara `charset=ISO-8859-15` y pasarle un binario por
  `mb_convert_encoding()` lo deja irreconocible.
- Dentro, un XML **CAP 1.2** por aviso y zona, declarado en UTF-8 (a diferencia
  del resto de la API).
- **El paquete es el estado completo y vigente**, no un incremento: cada descarga
  reemplaza. Los avisos caducados y los actualizados AEMET los quita del paquete.

### Lo que se filtra al leerlo, y por qué

| Se descarta | Motivo |
|---|---|
| `status` distinto de `Actual` | `Test` son mensajes de prueba |
| `severity = Minor` (nivel verde) | El Plan Meteoalerta **suprimió el nivel verde en 2022**: no es un aviso, es la ausencia de aviso. Eran 177 de los 252 mensajes del paquete nacional |
| El bloque `<info>` en `en-GB` | Cada XML trae el **mismo aviso dos veces**, en `es-ES` y en inglés. Recorrer los dos lo duplica |
| Zonas fuera de `config('aemet.warnings.zones')` | Sólo interesan las de aquí |

El filtro de zona es por **código**, no por nombre: el nombre viaja en `areaDesc`
y cambia, el código es estable y es el mismo `zona_comarcal` que devuelve el
maestro de municipios. Se acepta el código exacto o un prefijo, así que `6111` es
toda la provincia de Cádiz.

```
   61     11     03  C
   └┬┘    └┬┘    └┬┘ └ variante costera de la misma zona
  CCAA  provincia comarca
        (INE)
```

### Lo que se guarda

Una fila **por aviso y por zona**: un mismo aviso cubre varias comarcas y lo que
se pregunta es «¿hay aviso en mi zona?».

| Campo | Para qué |
|---|---|
| `identifier` + `geocode` | La clave natural. Es lo que deduplica |
| `event` | `«Aviso de lluvias de nivel amarillo»`. Ya viene redactado en español y con el nivel dentro: es lo que conviene enseñar |
| `severity` / `level` | `Moderate`=amarillo, `Severe`=naranja, `Extreme`=rojo. La correlación es exacta |
| `onset_at` / `expires_at` | Ventana de vigencia |
| `parameter` | `«P1;Precipitación acumulada en una hora;15 mm»`. El umbral **cambia por zona y por época**: no es una constante |
| `polygons` | Lista de polígonos, pares `lat,lon`. **Al revés que GeoJSON** |
| `others_fields_json` | Lo que AEMET mande y no esté contemplado, incluidos `senderName`, `web` y `contact` —que son los metadatos de procedencia que la nota legal obliga a conservar— |

### Filtrar por `expires_at`, siempre

AEMET **no emite `Cancel`**. Para retirar un aviso manda otro de nivel amarillo
**que nace caducado** (`expires` = `sent`). Si te fías de `msg_type` verás un
`Update` amarillo y enseñarás un aviso que ya no existe.

Para eso está `AEMETAdverseEvents::current()`.

### Las horas no vienen todas igual

`sent` llega en **UTC** y `effective`, `onset` y `expires` en **hora local**
(`+01:00`/`+02:00`). Compararlas sin normalizar da errores de una o dos horas
según la época del año. El lector las pasa todas a UTC al guardarlas (D100).

### Cómo se prueba

Con un `tar` construido a mano en el propio test
(`tests/Unit/WeatherStation/CapWarningsTest.php`). Es la única forma: los avisos
sólo existen cuando hay temporal, y un test que dependa de que hoy haya viento en
Cádiz no prueba nada el resto del año.

---

## 6. Configuración

```env
AEMET_API_KEY="<el JWT>"
AEMET_API_KEY_EXPIRES_AT="2026-11-30"   # se apunta al renovar la clave
AEMET_BASE_URL="https://opendata.aemet.es/opendata/api"
AEMET_DEFAULT_MUNICIPIO="11015"          # Chipiona
AEMET_DEFAULT_PLAYA="1101501"
AEMET_DEFAULT_COSTA="42"                 # Andalucía Occidental y Ceuta
AEMET_DEFAULT_AREA="61"                  # Andalucía
AEMET_AVISOS_AREA="61"
AEMET_CHIPIONA_COAST_SUBZONE_ID="8011111" # "Del Guadalquivir al Cabo Roche"
```

`config/aemet.php` lleva el porqué de cada número al lado del número. Los
valores de cuota, TTL y frescura **no son estimaciones**: salen de las medidas de
`docs/apis/aemet/LIMITACIONES.md`. Antes de cambiar uno, léelo.

⚠️ `default_playa` y `default_area` siguen **sin verificar con una petición
real**. Que un endpoint funcione con un valor no dice nada de los demás.

✅ `default_coast` **sí está verificado** (2026-09-16, petición real a
`/prediccion/maritima/costera/costa/42`): el valor anterior, `11`, no es un
código de costa válido — son 8 códigos del `40` al `47` (ver
`docs/apis/aemet/07-maritima.md`), y `11` es el código de provincia de Cádiz
colado por reusar el mismo nombre de parámetro. Con `11` la tabla
`meteorology_aemet_prediction_coasts` llevaba desde siempre vacía o con
basura, sin que nada lo señalara: un 200 con datos de otra zona (o ninguno) no
se distingue de "hoy no hay predicción" en el log. `42` es la costa de
Andalucía Occidental y Ceuta; su subzona `8011111` ("Del Guadalquivir al Cabo
Roche") es la que cubre Chipiona, y es de donde
`App\Support\WeatherStation\SeaStateExtractor` saca el estado de la mar que se
muestra en `/weatherstation` — ver [`weather-station.md`](../weather-station.md).

---

## 7. Cuando algo falla

| Síntoma | Dónde mirar |
|---|---|
| «payload vacío» en el log | ¿Ha caducado la clave? El propio log lo dice ahora. `php artisan aemet:check-api-key` |
| Acentos rotos | El charset. AEMET responde en ISO-8859-15 salvo los CAP. `JSON_INVALID_UTF8_SUBSTITUTE` **no** es la solución: parsea sin fallar y destruye los acentos |
| 429 | Cuota agotada del endpoint. Tarda **más de una hora** en recuperarse; reintentar no ayuda |
| Datos de hace años con un 200 impecable | Pasa. `max_dias_de_antiguedad` lo detecta y lo registra |
| Los avisos no traen nada | Puede que no haya. Comprueba `config('aemet.warnings.zones')` y que el paquete sea un `tar` de verdad |

```bash
php artisan aemet:check-api-key      # lo primero, siempre
php artisan config:clear
php artisan aemet:adverse-events -v
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i aemet
```

---

## 8. Lo que queda

- Los comandos siguen llamando a `\AEMETHelper::*` para **parsear**. Las
  peticiones ya salen todas por `AEMETService`; falta mover el parseo de cada
  producto a su sitio, como se ha hecho con los avisos CAP.
- `default_playa` y `default_area`: verificar con petición real (`default_costa` ya está verificado, ver punto 6).
- Los avisos no se exponen todavía por la API v2 ni en el frontal. **Cuando se
  expongan, la atribución del punto 2 es obligatoria.**

---

## Referencias

- Documentación de la API: [`docs/apis/aemet/`](../../apis/aemet/README.md)
- Avisos y CAP: [`04-avisos-y-riesgos.md`](../../apis/aemet/04-avisos-y-riesgos.md)
- Uso legal: [`12-uso-legal-y-atribucion.md`](../../apis/aemet/12-uso-legal-y-atribucion.md)
- Límites medidos: [`LIMITACIONES.md`](../../apis/aemet/LIMITACIONES.md)
- Modelos y tablas: [weather-station.md](../weather-station.md)
- Comandos: [commands.md](../commands.md)

---

> Creado: 2026-05-26 · Última revisión: 2026-09-14
