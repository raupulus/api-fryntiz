# Decisiones técnicas

Registro de decisiones tomadas **a conciencia** sobre este proyecto, con su motivo.

> **Para qué sirve esto.** Casi todas las entradas de aquí son cosas que una auditoría —humana o
> automática— señala como problema. Y en su mayoría lo parecen: reCAPTCHA que deja pasar cuando
> falla, relaciones sin `whenLoaded`, un método de subida que acepta cualquier tipo de archivo.
> No son descuidos: se decidieron así, por los motivos que están escritos abajo.
>
> Antes de "arreglar" cualquiera de estas cosas, léelo aquí. Y si aun así hay que cambiarlo, que
> sea porque han cambiado las circunstancias, **no porque una herramienta lo haya vuelto a marcar**.

---

## Archivos y subidas

### D13 · La validación de subida es opcional y viene activada

`File::addFile()` recibe `bool $validate = true` como último parámetro:

- **`true`** (por defecto): el MIME real tiene que estar en `File::SAFE_MIMES` y el tamaño por
  debajo de `File::MAX_FILE_SIZE`. Es lo que usan los campos que esperan una imagen o un documento
  concreto: avatar, portada de contenido, foto de producto.
- **`false`**: entra cualquier cosa, sin límite de tipo. Es lo que usan el **editor de contenido** y
  los **archivos adjuntos**.

**Por qué.** Esta es una intranet privada y de un solo dueño. Seguridad sí, **capar no**: por el
editor y los adjuntos se sube lo que haga falta —modelos de impresión 3D, vectores, proyectos de
software de edición, documentos— y no hay nada que censurar ahí. Lo que se protege son los campos
que esperan una imagen, donde recibir otra cosa es un error de todos modos.

El parámetro va el último de la firma para que ninguna llamada existente cambie de comportamiento:
todas quedan validadas por defecto sin tocarlas.

**Hay un test que lo fija** (`FileUploadTest::test_acepta_un_tipo_arbitrario_cuando_la_validacion_esta_desactivada`).
Si se cae porque alguien ha "endurecido" el modelo, lo que se ha roto es el editor.

### D2 · `file_types` NO es fuente de validación, nunca

`SAFE_MIMES` es una constante del modelo `File` y se amplía **ahí, a mano**.

**Por qué.** `file_types` es un **catálogo de metadatos** —icono, extensión, tipo legible— que se
rellena desde el panel con toda clase de formatos. Es entrada de usuario. Usarla como lista de tipos
seguros sería validar el input contra el propio input.

Es exactamente lo contrario de una lista blanca, y por eso queda dicho aquí y en el propio código:
es la clase de cosa que alguien conecta a la tabla dentro de seis meses pensando que la mejora.

### D12 · Tope de subida: 20 MB

`File::MAX_FILE_SIZE`, y sólo cuando la validación está activa.

No es el límite de experiencia de usuario, que lo pone cada campo de Filament y es más estricto
(`ImageCropperUpload` está en 4 MB). Una foto de alta calidad entra grande y **el cropper la deja en
un megabyte o menos** según para qué se vaya a usar. El tope del modelo sólo corta lo absurdo.

### D3 · Los metadatos EXIF/GPS se limpian SIEMPRE y de forma explícita

`File::stripMetadata()` vacía el EXIF y quita el perfil ICC, y además se guarda con `strip` en el
encoder. **Dos capas para lo mismo, a propósito.**

**Por qué, aunque la librería ya lo haga.** Hoy el driver GD de Intervention no propaga metadatos al
reescribir una imagen, así que técnicamente la limpieza es redundante — y por eso mismo está escrita.
Esa garantía es un **accidente de la implementación**, no una decisión del proyecto: el día que se
cambie a Imagick, o que la librería suba de major, nadie va a volver a mirar esa línea y las
coordenadas GPS de una foto empezarían a viajar otra vez sin que nada avise.

Hay dos tests: uno comprueba el resultado (el archivo sale sin GPS) y otro la intención
(`stripMetadata()` deja la instancia sin EXIF). El primero solo no bastaría, porque pasaría en verde
aunque la limpieza se hubiera borrado del código.

### D4 · La limpieza aplica a todas las imágenes, privadas y públicas

Una foto pública con las coordenadas de casa dentro es el mismo problema que una privada, sólo que
con más gente mirándola. El coste es el mismo reprocesado que ya hace falta para acotar el ancho.

### D5 · La rotación se conserva rotando los píxeles de verdad

`orient()` **antes** de limpiar. La orientación viaja como un flag EXIF y se va con el resto de
metadatos: descartarla sin rotar dejaría tumbadas todas las fotos hechas con el móvil en vertical.

### D6 · El TODO de `createThumbnails()` se queda hasta que se implemente

Escribir en las miniaturas los metadatos **de plataforma** (datos de la web y autoría) es una
funcionalidad querida, no un residuo. El TODO no se borra: sólo desaparece cuando esté hecho.

No está hecho porque no es una línea: GD no escribe EXIF, la librería no expone API para ello, haría
falta Imagick (no instalado) o una dependencia tipo `lsolesen/pel`, y las miniaturas se guardan en
**WebP**, donde los metadatos van en un chunk XMP con soporte pobre en PHP. Análisis completo en
[`docs/future/metadatos-imagenes.md`](../future/metadatos-imagenes.md).

Ojo al orden: **primero se limpia lo ajeno** (D3), **después** se escribe lo nuestro. Son dos cosas
distintas.

### D11 · `addFileFromBase64()` se mantiene

Con las mismas reglas de tamaño y MIME que `addFile()`, y comprobando el tamaño **sobre la cadena
base64 antes de decodificar**: una cadena de 500 MB no debe materializarse en memoria ni en disco
sólo para descubrir después que sobraba.

---

## Seguridad

### D10 · reCAPTCHA falla en abierto

Si Google no responde —excepción de red o status que no sea 2xx—, `RecaptchaService::verify()`
devuelve `valid: true` y el envío se acepta.

**Por qué.** Si Google no responde no se puede afirmar que quien envía sea un bot, y no se va a
cerrar el acceso al sitio porque un tercero se caiga. En principio no debería ocurrir; si ocurre y
resulta ser un problema de verdad, la salida es **buscar otro proveedor**, no dejar a la gente fuera
mientras tanto.

**Cómo se vigila.** Los dos `Log::warning` de `RecaptchaService` son la señal de alerta: si aparecen
a ráfagas, alguien está provocando el fallo para saltarse la comprobación.

Hay dos tests que lo fijan (`RecaptchaServiceTest`), precisamente para que la próxima auditoría no lo
marque como bug y alguien lo "arregle" cerrando el paso.

*Origen: SEC-05 de la auditoría 2026-09-01.*

### D1 · El webhook de GitLab está eliminado, no desactivado

Se fueron el controlador, los dos modelos, las rutas, el script de despliegue y la documentación.

**Por qué.** Era código de hace años de la rama `main` para desplegar desde GitHub. El despliegue va
por **GO-CD** desde hace unos seis años. Además la validación estaba rota de raíz: leía
`config('app.gitlab_token_deploy_api')`, una clave que no existe en `config/app.php`, así que
`isValidHash()` devolvía `false` siempre.

No se conserva nada "por si acaso". **El día que haga falta un webhook se plantea de cero y bien**,
con firma HMAC, no reactivando aquello.

*Origen: SEC-07 y CAL-01.*

---

## API

### D22 · Los índices de las series temporales van con `CONCURRENTLY`

La migración `2026_09_02_000001_add_indexes_to_time_series_tables` declara
`public $withinTransaction = false` y crea los veinte índices con
`CREATE INDEX CONCURRENTLY IF NOT EXISTS`.

**Por qué.** Un `CREATE INDEX` normal bloquea la tabla para escritura mientras
se construye. Sobre `meteorology_*` eso significa parar la ingesta de los
cacharros durante el despliegue, y un microcontrolador que recibe un error no
reintenta indefinidamente: pierde la lectura.

`CONCURRENTLY` no puede ejecutarse dentro de una transacción, y PostgreSQL sí
soporta DDL transaccional, así que Laravel envuelve la migración por defecto. De
ahí el `$withinTransaction = false`. El precio es que un fallo a mitad deja los
índices ya creados; por eso todo va con `IF NOT EXISTS` y la migración se puede
relanzar sin limpiar nada a mano.

**El orden de las columnas no es cosmético.** `(hardware_device_id, created_at)`
sirve para las tres cosas de la misma consulta: acota por dispositivo, acota el
rango de fechas dentro de ese dispositivo, y devuelve las filas ya ordenadas por
fecha, así que PostgreSQL se ahorra el `sort`. Al revés no serviría para lo
primero, que es lo que más filas descarta.

**Qué lo fija.** `tests/Feature/Database/TimeSeriesIndexesTest.php` comprueba que
cada tabla tiene su índice y con las columnas en ese orden. No mide rendimiento
—eso no se mide en una suite—: comprueba que el índice existe, que es lo que se
pierde con un `dropIndex` de más o con una tabla nueva creada copiando una vieja.

*Origen: AR-R01 de la auditoría 2026-09-02.*

### D18 · Hay DOS puertas de respuesta y se mantienen las dos

`JsonHelper` (estático) y `ApiResponseTrait` (trait) devuelven exactamente lo mismo y coexisten a
propósito.

**Por qué.** Un trait sólo lo puede usar una clase que lo declare, así que los handlers de
excepciones de `bootstrap/app.php`, la ruta de cierre de `routes/api/v2.php` y los `render()` de
`app/Exceptions/` no podían usarlo: **tenían el envelope copiado a mano, once veces**. Una clase
estática sí llega a esos sitios. Y el trait sigue siendo lo cómodo dentro de un controlador, donde
están las 79 llamadas.

Se valoró dejar sólo la clase estática y que el trait delegara. Se descartó: son dos maneras
legítimas de pedir lo mismo desde dos contextos distintos, y quitar una obligaría a reescribir uno
de los dos lados sin ganar nada.

**Lo que sí es único es la forma.** `App\Support\Http\ApiEnvelope` es el único sitio donde está
escrito qué claves lleva el sobre y qué entra en el bloque `debug`. Las dos puertas beben de ahí.
Una lista blanca de cabeceras escrita dos veces es una lista blanca que algún día sólo se actualiza
en una.

**Qué lo fija.** `tests/Feature/Api/V2/ApiResponseParityTest.php` compara las dos salidas método a
método —cuerpo y código HTTP— y además comprueba que ningún método de `JsonHelper` se queda sin
gemelo en el trait. Ambos ficheros llevan en su cabecera un aviso apuntando al otro.

*Origen: AR-A06 de la auditoría 2026-09-02.*

### D19 · El envelope lo lleva TODA respuesta, también las que genera Laravel

El `render()` de cierre de `bootstrap/app.php` atrapa cualquier `Throwable` de `api/*` y lo devuelve
con el sobre, respetando el código HTTP de la excepción y sus cabeceras.

**Por qué.** Hasta la revisión de 2026-09-02, el envelope sólo lo aplicaba el código propio. Todo lo
que emitía el framework salía con la forma de Laravel —`{"message": ..., "exception": ..., "trace": [...]}`—
y en **HTML** si el cliente no mandaba `Accept: application/json`, que es exactamente lo que hace un
microcontrolador. Los dos casos que se veían:

| Caso | Cómo salía antes |
|---|---|
| **429** del throttle | `{"message":"Too Many Attempts."}`, y con `APP_DEBUG` el stack trace completo con rutas absolutas del servidor |
| **500** no controlado | igual, o HTML |

Se valoró resolverlo con un middleware de normalización. **No sirve:** la excepción salta por encima
del pipeline de middleware hasta el kernel, así que un middleware nunca la ve. Tiene que ser un
`render()`.

Va registrado **el último** porque los `render()` se prueban en orden y gana el primero que devuelva
algo: así los handlers específicos (401, 403, 404, 405, 410) siguen mandando.

Las cabeceras de la excepción HTTP se conservan: sin ellas un 429 perdería su `Retry-After` y el
cliente no sabría cuánto esperar.

**Qué lo fija.** `tests/Feature/Api/V2/ErrorEnvelopeTest.php`, que prueba cada tipo de error con
`Accept: application/json`, con el comodín y sin cabecera `Accept`.

*Origen: AR-E02 de la auditoría 2026-09-02.*

### D20 · El borrado se queda en 204 sin cuerpo

Es la única respuesta de la API que no lleva envelope, y así se queda.

**Por qué.** Un 204 no lleva cuerpo por definición del protocolo, así que no hay dónde poner el
sobre. Degradarlo a un 200 con un sobre vacío daría uniformidad a costa de dejar de ser REST, y en
esta API el criterio es REST. Decisión tomada explícitamente el 2026-09-02, no heredada.

**Qué lo fija.** `ApiResponseParityTest::test_borrado_es_identico_y_sigue_siendo_204_sin_cuerpo()` y
`ErrorEnvelopeTest::test_el_borrado_sigue_siendo_204_sin_cuerpo()`.

*Origen: decisión sobre AR-A06.*

### D21 · El bloque `debug` sólo en desarrollo, con lista blanca

Las respuestas llevan una clave `debug` con el contexto de la petición, y sólo con `APP_DEBUG=true`.

**Por qué.** Es lo que la V1 hacía en `JsonHelper::siteData()` y se echaba de menos: en desarrollo
interesa ver de dónde vino la petición y con qué. En producción no sale nunca.

**Lo que NO se copió de la V1, y es lo importante:**

- `siteData()` volcaba `request()->headers->all()` entero, o sea `Authorization: Bearer <token>` y
  las cookies de sesión. Ahora las cabeceras pasan por **lista blanca**
  (`ApiEnvelope::SAFE_HEADERS`). Con lista negra, la cabecera que se invente mañana entraría sola.
- `parameters` viene de `$request->all()`, así que el `debug` de `POST /auth/tokens` habría enseñado
  la contraseña en claro. Los campos de `ApiEnvelope::REDACTED_INPUT` salen como `[oculto]`.
- `prepareError()` metía el objeto `Exception` entero en la respuesta. Ahora sólo van clase,
  mensaje, fichero y línea, y dentro de `debug`.

«Es sólo desarrollo» no es excusa: en desarrollo es donde se pegan respuestas en capturas y en
tickets.

**Qué lo fija.** Cuatro tests en `ApiResponseParityTest`, incluidos uno que manda un `Authorization`
real y otro que manda una contraseña, y comprueban que no aparecen en el cuerpo.

*Origen: AR-A06 de la auditoría 2026-09-02.*

### D9 · Los resources NO usan `whenLoaded`

`AirFlightResource::latestRoute`, `ContentResource::type/status`,
`ContentRelatedResource::image/type` y `HardwareDeviceResource::type` leen sus relaciones
directamente. Quien use estos resources tiene que cargarlas con su `with()`.

**Por qué.** `whenLoaded()` haría **desaparecer la clave del JSON** cuando la relación no viene
cargada. Eso cambia un fallo ruidoso —`preventLazyLoading` revienta en local, que es justo su
función— por uno silencioso en el cliente, que recibe una respuesta incompleta sin que nada avise.
Es peor que el problema que evita.

Hoy todos los llamantes cargan lo que hace falta y **no hay N+1 real**: el hallazgo era de robustez
teórica, no de un problema medido.

*Origen: API-05.*

### D8 · Las colecciones se paginan con los valores por defecto de `CollectionQuery`

25 por página, orden descendente por `created_at`. No se añaden parámetros a `CollectionQuery` para
que un endpoint concreto pagine de otra manera: la clase la comparten todos los módulos y no merece
un parámetro nuevo por una diferencia de cinco elementos.

*Origen: API-03.*

---

## Dependencias

### D7 · Las dependencias se mantienen al día, incluidos los majors

Sin dejar saltos pendientes "para más adelante" salvo que haya un bloqueo real.

**`intervention/image` + `intervention/image-laravel` se actualizan juntas y en un commit propio.**
Son la única pareja que toca el código de imágenes, así que aislarlas permite ver el efecto sin
mezclarlo con nada. Fue necesario adelantar ese salto: Laravel 13 trae su propio `Illuminate\Image`,
cuyo `GdDriver` llama a `ImageManager::usingDriver()`, un método que **sólo existe en la versión 4**.
Con la 3 instalada, cualquier `Image::read()` reventaba y las miniaturas y `/file/resize` no
funcionaban.

**La excepción es `guzzlehttp/guzzle`**, bloqueado por una incompatibilidad de dependencias. Ver
**D16**: asunto cerrado, no hace falta volver a levantarlo.

*Origen: DEP-01, DEP-02, DEP-03.*

---

---

## Calidad

### D14 · El baseline de PHPStan se revisa, no se hereda

`phpstan-baseline.neon` silencia errores para que la suite quede en verde, y eso lo convierte en
un sitio perfecto donde esconder bugs de verdad. En la resolución de la auditoría de 2026-09-01,
**cinco fallos reales estaban ahí dentro**, señalados por PHPStan y silenciados:

| Silenciado como | Era en realidad |
|---|---|
| `SmartPlantPlant::$hardware_device_id` en la policy | `GET /smartplant/plants/{id}/readings` devolvía 404 a todo el mundo |
| `Content::$user_id` en la policy | Un autor no alcanzaba su propio contenido; nadie salvo admin podía borrar lo suyo |
| `SmartPlantPlant::$hardware_device_id` en la regla | El ligado por dispositivo no se comprobaba nunca |
| `File::$type` en el controlador | `/file/resize` devolvía siempre «no es una imagen» |
| `ContentSeo::$twitter_title` y `Content::$seo_*` | Las tarjetas de X salían sin título y la API devolvía el SEO a null |

De las 45 entradas `property.notFound` que había, **16 se resolvieron** (5 bugs + tipado que
faltaba). Las **29 restantes están revisadas una a una** y son tipado dinámico legítimo:

- **`AirFlightAirPlane`** (13): un `select()` con alias trae columnas de `airflight_routes` sobre
  el modelo del avión, y después se mutan. Las columnas existen; expresar ese tipo obligaría a
  reescribir el método entero con un DTO.
- **`BaseWeatherStation`, `CurriculumBaseSection`, `BaseModel`** : clases base que leen propiedades
  que definen sus hijos. `BaseModel::$image` es defensivo a propósito — comprueba si el hijo tiene
  imagen antes de borrarla.
- **`Platform`, `User`, `ContentPage`, `KeyCounterController`, `FileThumbnailController`,
  `TokensRelationManager`**: accessors y agregaciones resueltas en tiempo de ejecución.

**Criterio para la próxima vez.** Ante una entrada `property.notFound`, la pregunta es: *¿esa
propiedad existe en algún sitio —columna, accessor, alias de un select— o no existe en absoluto?*
Si no existe en absoluto, **es un bug**, no ruido de tipado: el valor será `null` siempre y la
condición que lo use dará siempre el mismo resultado. Los cinco de arriba eran de ese tipo.


### D15 · `project:clear` regenera la `APP_KEY` por defecto, y así se queda

El comando regenera la clave salvo que se pase `--no-key`.

**Por qué.** `project:clear` deja el proyecto **como recién instalado**: ése es su propósito.
Conservar la clave sería hacer media limpieza. No es un descuido ni un comportamiento heredado.

**Las salvaguardas ya están donde deben.** En producción pide confirmación explícita, avisa de
cuántos usuarios tienen 2FA activo —Fortify cifra `two_factor_secret` con la `APP_KEY`, así que esos
usuarios tendrán que volver a darlo de alta— y `--no-key` existe precisamente para limpiar sin tocar
la clave.

⚠️ **Esto lleva propuesto en varias auditorías seguidas**, siempre con el mismo argumento («el
comportamiento por defecto es destructivo»). Está decidido, revisado y confirmado más de una vez.
**No hay que invertir la lógica.** El comentario del propio comando remite aquí.

### D16 · `guzzlehttp/guzzle` se queda en 7 — asunto cerrado

No es una preferencia ni algo por decidir: **es una incompatibilidad**. Guzzle 8 requiere
`guzzlehttp/psr7 ^3`, y `laravel/reverb` —en su última versión— exige `psr7 ^2.6`. Subir Guzzle
obligaría a quitar Reverb, que es el WebSocket del proyecto.

Guzzle 7 está al día dentro de su rama y `roave/security-advisories` no reporta nada para ella.

**No hace falta volver a levantarlo.** Se aplicará solo, sin discusión, el día que Reverb admita
`psr7` 3 — y hasta entonces `composer outdated` lo seguirá listando, que no significa nada.


### D17 · La escritura de sensores se queda en el controlador

`SensorReadingController::store()` y `storeReadings()` arman las filas, abren la transacción, hacen
el `insert` y disparan el evento **dentro del controlador**, en lugar de delegarlo en
`WeatherStationService` como manda la convención de AGENTS.md §14. Lo mismo, en menor medida, en
algún fragmento de `ContentController`, `AirFlightController` y `TokenController`.

**No está roto.** Es una cuestión de dónde vive el código, no de qué hace: son las rutas más
cubiertas por tests de todo el proyecto (`WeatherStationPersistenceTest`, 25 casos), están
comentadas y funcionan.

**Por qué no se mueve.** Es la ruta más caliente del proyecto —lecturas IoT cada pocos segundos, con
un `insert` por lote pensado a propósito para no multiplicar el trabajo del servidor—, y moverla es
puro refactor de organización: cero cambio funcional a cambio de tocar el camino por el que entra
todo lo que miden los cacharros. El riesgo no lo paga la mejora.

Se hará, si se hace, en un refactor de consolidación con calma, nunca como parte de otra tarea ni
antes de un despliegue.

*Origen: API-01 y API-04 de la auditoría 2026-09-01.*

---

## Cómo mantener este documento

Se añade una entrada cuando se decide **no** hacer algo que parece que habría que hacer, o hacerlo de
una forma que a primera vista chirría. Cada entrada dice **qué** se decidió, **por qué**, y —cuando
existe— **qué test lo fija**.

Lo que no va aquí: decisiones que el código ya explica por sí solo, y cosas que simplemente están
pendientes (eso es `docs/future/`).

> Creado: 2026-09-01 · Última revisión: 2026-09-02
