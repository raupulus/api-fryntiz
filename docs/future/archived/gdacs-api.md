# API del sistema GDACS (alertas de desastres)

> **Resuelto e implementado (2026-09-15):** módulo propio (`gdacs:sync` cada
> 10 min, tabla `gdacs_events`, panel de solo lectura), con histórico en base
> de datos, los seis tipos de desastre, y sin página pública propia todavía
> (sólo panel Admin) — ver «Qué se decidió» más abajo. Este documento se
> conserva como la referencia de la API de GDACS (endpoints, parámetros, el
> descubrimiento de que `alertlevel` excluye el verde por defecto, la
> valoración frente a otras APIs gratuitas) — el mismo papel que cumple
> `docs/apis/aemet/` para AEMET. Cómo lo usa esta plataforma, en
> [`docs/info/apis/gdacs.md`](../../info/apis/gdacs.md).

## Qué es GDACS

GDACS (acrónimo histórico de *Global Disaster Alert and Coordination System*;
su documentación de 2025 lo expande como *Global Disaster Awareness and
Coordination System* — el acrónimo no ha cambiado, la explicación del nombre
sí) es un marco de cooperación entre la Comisión Europea (JRC, ECHO) y
Naciones Unidas (OCHA, UNITAR/UNOSAT). Publica alertas casi en tiempo real de
seis tipos de desastre repentino, cada uno con su código corto:

| Código | Desastre |
|--------|----------|
| `EQ` | Terremoto (incluye estimación de riesgo de tsunami) |
| `TC` | Ciclón tropical |
| `FL` | Inundación |
| `VO` | Volcán |
| `DR` | Sequía |
| `WF` | Incendio forestal |

Cada evento lleva un **nivel de alerta** — `green` / `orange` / `red` — que
estima el impacto humanitario esperado (verde: mínimo; naranja: requiere
atención; rojo: respuesta internacional urgente). Para terremoto, tsunami y
ciclón tropical la alerta la genera un algoritmo, **sin revisión humana antes
de publicarse**.

## Cómo es la API

Fuentes consultadas: los PDF oficiales *API quick start* (v1 y v2, 2025) y
*Terms of use* (marzo 2025), publicados en gdacs.org.

- **Sin API key, sin registro, gratuita.** No hay cabecera de autenticación
  que mandar.
- **Endpoint principal** (GeoJSON):
  `https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH`
  Parámetros: `eventlist` (uno o varios códigos separados por `;`, ej.
  `EQ;TC`), `fromdate`/`todate` (`YYYY-MM-DD`), `alertlevel` (`green`,
  `orange`, `red`, combinables con `;`).
  ⚠️ **Comprobado en directo (2026-09-15): si se omite `alertlevel`, la API
  excluye los eventos en verde por defecto.** No está documentado en los PDF
  oficiales. Verde es el nivel más frecuente en zonas sin desastres a escala
  internacional (España incluida — ver más abajo), así que omitir el
  parámetro deja fuera la mayoría de lo que hay cerca de aquí. Pedir siempre
  `alertlevel=green;orange;red` explícito salvo que de verdad sólo interese lo
  grave.
- **Paginación obligatoria de facto**: la API **no devuelve más de 100
  registros por llamada**, ordenados por fecha. Para más, hay que iterar con
  el parámetro `pagenumber` (y opcionalmente `pagesize`).
- **Endpoint pensado para sondeo periódico**, ya cacheado en su lado:
  `https://www.gdacs.org/gdacsapi/api/events/geteventlist/events4app`. La
  propia guía recomienda guardar la colección localmente y comparar el campo
  `datetime` antes de volver a pedir, en vez de repetir `SEARCH` sin
  necesidad.
- **Otros formatos**: XML/RSS (`feed_reference.aspx`), CAP, y KML — en bloque
  sólo para los últimos 4 días, o por evento individual
  (`resources.aspx?eventid=...&eventtype=...&filter=kml`).
- **Ningún límite de peticiones por minuto documentado explícitamente** (a
  diferencia de AEMET o de USGS, ver tabla). La única guía formal es la del
  punto anterior: cachear y no repetir sondeos innecesarios.
- **Atribución obligatoria**: citar la fuente como *"Global Disaster
  Awareness and Coordination System, GDACS"*.
- **El "terms of use" es sobre todo un descargo de responsabilidad**, no una
  licencia restrictiva: los datos son automáticos y "as is", no sustituyen ni
  anulan las alertas oficiales de las autoridades locales/nacionales, pueden
  tener caídas o cambios sin aviso previo, y no hay garantía de completitud.
  Nada en el documento prohíbe uso comercial ni pide licencia previa.

## Valoración frente a otras opciones gratuitas

| API | Cobertura | Key/registro | Límite documentado | Formato | Nota |
|-----|-----------|---------------|---------------------|---------|------|
| **GDACS** | 6 desastres (terremoto, ciclón, inundación, volcán, sequía, incendio), global | Ninguno | Sin límite de tasa explícito; 100 registros/página | GeoJSON, XML/RSS, CAP, KML | El más completo para "un solo desastre grande a la vez"; pensado para ayuda humanitaria, no para series de baja magnitud |
| **USGS Earthquake API** | Solo terremotos, global | Ninguno | 429 al pasarte; caché de 60 s en los feeds; tope duro de 20.000 resultados por consulta a la de búsqueda | GeoJSON, CSV, KML | El más fiable y documentado para terremotos en concreto — feeds pregenerados (última hora, semana, mes) que USGS recomienda usar en vez de la consulta libre para no cargar su base de datos |
| **NASA EONET** | 8 categorías (incendios, tormentas severas, volcanes, inundaciones, hielo marino/lacustre, sequía, terremotos, deslizamientos), global | Ninguno | No documentado | JSON, GeoJSON | Seguimiento por observación satelital, no por alerta de impacto humanitario — eventos más numerosos y de menor magnitud que GDACS |
| **ReliefWeb API (OCHA)** | Informes y metadatos de desastres (no alertas en vivo) | Sin key, pero exige parámetro `appname`; **desde el 1 de noviembre de 2025 sólo se aceptan `appname` preaprobados** | 1.000 resultados por petición, 1.000 peticiones/día | JSON | Es catálogo de informes humanitarios, no un feed de alertas — complementa a GDACS, no lo sustituye. El requisito de aprobación desde nov-2025 lo saca de "uso libre sin fricción" |

**Para lo que probablemente interesa aquí** (un módulo más de datos públicos,
en la línea de AirFlight/WeatherStation): **GDACS es la mejor opción**, y de
lejos la de mayor cobertura sin ningún registro ni clave. Si en algún momento
sólo interesaran terremotos, USGS es más estricto en sus límites mismos pero
mejor documentado y con feeds pregenerados listos para sondeo barato.

## Cobertura geográfica real: sí sirve para ~150-200 km de Chipiona, con una trampa

Probado en directo contra la API real (2026-09-15), no fiado de lo que dicen
wrappers de terceros. **Primera pasada, incorrecta:** sin pasar `alertlevel`
explícito, `country=Spain` sólo devolvió eventos en naranja/rojo, todos a
400-1000 km de Chipiona, y `eventlist=EQ&country=Spain` desde 2015 dio 204
(sin resultados) — de ahí salió una primera versión de esta nota que decía
"GDACS no sirve para esto". Era una conclusión sacada de una petición
incompleta, no un límite real de la API.

**La trampa: `alertlevel` filtra por defecto.** Si no se manda ese parámetro,
la API **excluye los eventos en verde**. Verde es justo el nivel que
predomina en zonas de baja-media actividad sísmica/incendios como España, así
que omitir `alertlevel` deja fuera casi todo lo que hay cerca de casa. Hay que
pedir siempre `alertlevel=green;orange;red` explícito.

Repitiendo la consulta así:

- `eventlist=WF&country=Spain&alertlevel=green;orange;red` → 10 incendios en
  vez de 5, y entre los nuevos (todos verdes) hay tres dentro de 125 km de
  Chipiona: **89,0 km** (Huelva, 2026-08-07), **95,7 km** (frontera con
  Portugal, 2026-06-08) y **124,9 km** (Málaga, 2026-09-13 — el mismo que
  llegó por Telegram vía el plugin de Home Assistant, `WF1031998`, que cuadra
  con los 124,8 km que reporta el propio plugin).
- `eventlist=EQ&country=Spain&alertlevel=green;orange;red` desde 2015 → **16
  terremotos**, no 0. El más cercano a Chipiona está a 193 km (2026-03-17); hay
  varios más entre 244 y 254 km (una serie de agosto de 2026). Ninguno cae
  dentro de 150 km exactos en esta ventana, pero la zona sí tiene actividad
  real que GDACS sí registra — sólo que en verde, y verde es lo que se estaba
  perdiendo.

**Conclusión corregida:** para ~150-200 km alrededor de Chipiona, GDACS **sí
aporta datos reales**, sobre todo incendios (y en menor medida terremotos,
más escasos y algo más lejos). Sigue sin haber filtro por radio ni por
bounding box en la API — `bbox=`/`boundingBox=` los ignora en silencio, la
respuesta sale idéntica a la de no mandar ningún filtro geográfico — así que
el radio hay que aplicarlo **en cliente**, con las coordenadas que sí trae
cada evento en GeoJSON. Y el único filtro geográfico real de la API es
`country` (nombre en inglés: `country=Spain`, no `ESP` — con ISO-3 da 204).

Sigue siendo cierto que GDACS **no sustituye a una fuente local dedicada**: es
más disperso (por desastre "grande" a nivel de la fuente, no de sensor local)
y no va a bajar a nivel de finca o barrio. Para lo que sí sea puramente local:

- **Terremotos con magnitud y coordenadas exactas, sin filtrar por escala:**
  el catálogo del [IGN](https://www.ign.es/web/ultimos-terremotos) (Instituto
  Geográfico Nacional), que detecta sismos desde magnitud 1.5 — muchos más de
  los que GDACS jamás mostrará —, con servicio WMS/INSPIRE y catálogo abierto
  en [datos.gob.es](https://datos.gob.es/en/catalogo/e00125901-spaigncatalogosismiconacional).
- **Incendios/avisos meteorológicos por provincia:** AEMET, ya integrado en
  este proyecto (`docs/info/apis/aemet.md`), con avisos por zona.

Las dos cosas no son excluyentes: GDACS aporta el contexto "esto ha saltado
como noticiable a nivel internacional", el IGN/AEMET aportan el detalle fino
local. El plugin de Home Assistant que ya usa el usuario (Telegram) es prueba
de que, con `alertlevel` bien puesto, GDACS sí produce avisos útiles a esta
distancia.

## Qué se decidió

- **Módulo propio** (`Gdacs`, no encajaba en ninguno existente): modelos,
  enums, servicio, comando y recurso Filament bajo ese namespace.
- **Sí se guarda histórico** en `gdacs_events`, con `gdacs:sync` cada 10 min
  (`Schedule::command`, igual que AEMET/AirFlight) — upsert por
  `(event_type, event_id)`, no una fila por episodio.
- **Los seis tipos de desastre**, sin acotar a un subconjunto.
- **Sin página pública propia todavía** — sólo el recurso de solo lectura en
  el panel Admin (bajo "Módulos", debajo de AEMET), con `GdacsEventPolicy`
  cerrando create/update/delete a todo el mundo, admin incluido. Si en algún
  momento se expone en `/algo` público con JSON consumido desde el navegador,
  aplican los mismos criterios ya fijados para `weatherstation`/`airflight`:
  `same-origin` + `throttle:public-widget` — ver `docs/info/weather-station.md`.
- **Atribución**: `config('gdacs.attribution')`, pendiente de mostrarse en
  algún sitio visible el día que haya página pública (hoy sólo vive en config
  y en esta documentación).

Detalle completo de la implementación en
[`docs/info/apis/gdacs.md`](../../info/apis/gdacs.md).

## Fuentes

- [GDACS API quick start v2 (PDF, 2025)](https://www.gdacs.org/Documents/2025/GDACS_API_quickstart_v2.pdf)
- [GDACS API quick start v1 (PDF, 2025)](https://www.gdacs.org/Documents/2025/GDACS_API_quickstart_v1.pdf) — incluye el límite de 100 registros/página y el aviso de caché
- [GDACS Terms of use (PDF, marzo 2025)](https://www.gdacs.org/documents/2025/GDACS_Terms_of_use_Mar_25.pdf)
- [GDACS Swagger](https://www.gdacs.org/gdacsapi/swagger/index.html) · [Feed reference](https://www.gdacs.org/feed_reference.aspx) · [KML](http://www.gdacs.org/kml.aspx)
- [USGS Earthquake feeds — GeoJSON summary format](https://earthquake.usgs.gov/earthquakes/feed/v1.0/geojson.php)
- [NASA EONET](https://eonet.gsfc.nasa.gov/api/v3/categories)
- [ReliefWeb API doc](https://apidoc.reliefweb.int/)
