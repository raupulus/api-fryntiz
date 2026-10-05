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

**Hay un test que lo fija** (`FileUploadTest::test_accepts_an_arbitrary_type_when_validation_is_disabled`).
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

**Cloudflare Turnstile sigue el mismo criterio** (`TurnstileService`, desde 2026-10-05; el formulario de
contacto acepta el token de cualquiera de los dos proveedores): excepción de red o 5xx → pasa, con su
`Log::warning`. Un 4xx **no** pasa: es Cloudflare rechazando nuestra petición, no un fallo suyo. Lo fijan
los tests de `TurnstileServiceTest`.

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

### D23 · Lectura y escritura son abilities distintas

Cada módulo IoT tiene `:read` y `:write`. A un dispositivo se le emite **sólo**
la de escritura de su módulo.

**Por qué.** Sólo Hardware tenía `:read`, así que las GET de KeyCounter y
SmartPlant se protegían con la ability de escritura: el token que se graba en un
teclado —cuyo único trabajo es hacer POST— también listaba todas las sesiones y
todas las plantas de su dueño. Eso es lo contrario de lo que el catálogo dice de
sí mismo: «un token robado sólo puede hacer aquello para lo que se emitió».

**Se aplicó en seco, sin ventana de compatibilidad**, y los tokens existentes se
borraron. La API no está desplegada y no hay ningún dispositivo real
consumiéndola: montar una doble aceptación temporal habría sido trabajo para
proteger a clientes que no existen.

*Origen: AR-S02 de la auditoría 2026-09-02.*

### D24 · El número de serie sale sólo en el detalle

`HardwareDeviceResource` no incluye `serial_number` salvo que se pida con
`->detailed()`, cosa que sólo hace `GET /hardware/devices/{id}`.

**Por qué.** El listado devolvía el número de serie de todo el parque a
cualquier token con `hardware:read`, incluido el de un cacharro, y además no
aplicaba el ligado `device:{id}` que `show()` sí comprobaba por policy. O sea que
un token de dispositivo podía barrer los números de serie iterando páginas —el
mismo dato que motivó cerrar el endpoint en la auditoría A3—. El listado aplica
ahora el ligado igual que el detalle.

*Origen: AR-S03 de la auditoría 2026-09-02.*

### D25 · Las cabeceras de seguridad las pone la aplicación, no el virtualhost

`App\Http\Middleware\SecurityHeaders` añade `X-Content-Type-Options`,
`X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` y —sólo sobre
HTTPS— `Strict-Transport-Security`.

**Por qué en la aplicación.** Había **cuatro** configuraciones de servidor con
contenidos distintos y ninguna declarada como la buena; las dos que estaban en
la raíz del repositorio —las más fáciles de copiar, justo por estar ahí— eran
las que **no tenían ninguna cabecera**. Desplegar con ellas dejaba el panel de
Filament clickjackeable. En la aplicación viajan con el código, se despliegan
solas y valen igual con Apache, con nginx o con Docker.

Los virtualhosts de `docs/deploys/vhosts/` las repiten. No sobra: así las manda
el servidor aunque PHP no llegue a ejecutarse (un 502, el modo mantenimiento, un
fichero estático).

**HSTS sólo con `$request->secure()`.** Mandarla por HTTP no sirve —el navegador
la ignora— y en desarrollo es peor que inútil: deja `localhost` marcado como
«sólo HTTPS» durante un año. Sin `preload` ni `includeSubDomains`: de la lista de
preload se sale con meses de trámite, y los subdominios no son cosa de esta
aplicación.

**Y los cuatro ficheros pasan a uno por servidor, todos en `docs/deploys/vhosts/`
y ninguno en la raíz.** Se mantienen los tres —Apache, nginx y Docker— a
propósito: hoy el VPS va con Apache, y tener los otros escritos y al día es lo
que permite cambiar sin improvisar. De paso se arregló un `Redirect permanent`
que el `apache.conf` tenía **dentro de su propio vhost `:443`**, o sea un bucle
de redirección: por HTTPS no habría respondido nada.

*Origen: AR-D01 de la auditoría 2026-09-02.*

### D26 · reCAPTCHA v3 aplica umbral, y el del login es más permisivo

`RECAPTCHA_MIN_SCORE` (0.5) para los formularios públicos y
`RECAPTCHA_MIN_SCORE_LOGIN` (0.3) para el login de los paneles.

**Por qué.** v3 **no dice «humano» o «bot»**: devuelve una puntuación de 0.0 a
1.0. Sólo se miraba `success`, que es cierto para cualquier token bien formado y
sin caducar, también el que se saca un bot con 0.1. El captcha estaba puesto y no
filtraba nada.

**Por qué dos umbrales.** Son dos riesgos distintos. Cortar de más en un
formulario público hace que un mensaje no llegue, y queda registrado en el panel
con su puntuación. Cortar de más en el login te deja fuera de tu propio panel un
mal día de red o con un navegador lleno de extensiones, y contra la fuerza bruta
ya está el límite de Filament (5 intentos) y el de `api-auth`.

**Esto NO toca D10.** Aquel es el fallo en abierto cuando Google **no responde**,
y sigue igual. Esto es el caso en que Google responde correctamente y se estaba
ignorando lo que dice. Si Google contesta sin puntuación —no debería en v3— se
deja pasar, con el mismo criterio de D10.

*Origen: AR-S04 de la auditoría 2026-09-02.*

### D27 · `project:check-config` avisa de lo que no da error por sí solo

Comando que se ejecuta después de desplegar y comprueba los ajustes que, mal
puestos, **no producen ningún error**: `FRONTEND_URLS` vacío, `APP_DEBUG` en
producción, `APP_KEY` sin poner, captcha sin claves, `TRUSTED_PROXIES` en `*`,
la cookie de sesión sin `Secure`, la cola en `sync`.

**Por qué.** El peor de todos es `FRONTEND_URLS`: la API responde 200 con todo
correcto, no escribe nada en el log, y el navegador bloquea todas las respuestas.
Desde el servidor parece que funciona y desde las ocho webs no funciona nada.

Devuelve código 1 si hay fallos, para poder encadenarlo en el script de
despliegue con `&&`. Con `--strict` los avisos también cortan.

⚠️ **Lee de `config`, nunca de `env()`.** En el servidor el despliegue hace
`config:cache` y a partir de ahí Laravel no carga el `.env`: un `env()` aquí
devolvería null y el comando avisaría de problemas inventados. Es el mismo
despiste que tuvo `TRUSTED_PROXIES` en su día, y PHPStan lo cazó al escribirlo.

*Origen: AR-D03 de la auditoría 2026-09-02.*

### D22 · Los índices de las series temporales van en su migración de creación

Cada tabla de serie temporal crea su índice `(hardware_device_id, created_at)`
junto con la tabla, en su única migración.

**Un índice nuevo sobre una tabla con datos va aparte y con `CONCURRENTLY`.** Un
`CREATE INDEX` normal bloquea la escritura mientras se construye, y sobre
`meteorology_*` eso para la ingesta de los cacharros: un microcontrolador que
recibe un error no reintenta indefinidamente, pierde la lectura. `CONCURRENTLY`
no puede ir dentro de una transacción, así que esa migración lleva
`public $withinTransaction = false` y `IF NOT EXISTS` para poder relanzarse.
Cuando ya se ha desplegado, el índice se pasa a la migración de creación de su
tabla (2026-09-14: una migración por tabla).

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

## Base de datos

### D28 · Las tablas de series temporales de IoT no se particionan por ahora

Los datos IoT (meteorología, energía, KeyCounter, SmartPlant, AirFlight) siguen creciendo en
tablas únicas, sin partición ni tablas por año.

**Por qué.** La tabla más grande ronda hoy los 300.000 registros y PostgreSQL lo lleva sin
esfuerzo: no se ha observado ninguna lentitud ni limitación atribuible al volumen. Que el
histórico crezca año a año **no implica por sí solo** un problema de rendimiento en
PostgreSQL, y hoy no hay ninguna consulta ni backup que lo esté sufriendo. Diseñar ahora un
particionado para un problema que no existe es trabajo especulativo.

**Cuándo se revisa.** No es un "nunca": se retoma en cuanto aparezca uno de estos
disparadores, y no antes:

- Alguna tabla de sensores supera los **5-10 millones** de registros.
- Una consulta del panel o de la API empieza a tardar más de ~1 s por volumen.
- El tamaño de la base de datos se acerca al límite del disco del VPS.
- Los backups tardan tanto que dejan de ser prácticos.

**La idea preferida, si llega el momento:** tablas por año con el año en el nombre
(`temperatures_2027`, `temperatures_2028`…), no purgar datos — para meteorología y energía el
histórico largo tiene valor por sí mismo. Las alternativas valoradas (particionado nativo de
PostgreSQL 17, agregados + purga del detalle) y las consultas SQL de medición previa quedaron
archivadas en [`docs/future/archived/retencion-datos-iot.md`](../future/archived/retencion-datos-iot.md).

**Lo único que sí conviene hacer ya, con coste bajo:** verificar que existe el índice
`(hardware_device_id, created_at)` en cada tabla de sensor (ver **D22**) y vigilar el tamaño
total de la base desde el chequeo de salud del sistema, para enterarse del crecimiento antes
de que sea un problema.

*Origen: nota de futuro anotada el 2026-08-19, decidida y archivada el 2026-09-15.*

---

### D30 · Los estados de contenido van por id, con el orden de la v1

`content_available_status` tiene 1 borrador, **2 programado, 3 publicado**, 4 no publicado,
5 copyright y 6 para eliminar: el orden de la base de producción, que viene de la v1. Parece
más natural «publicado = 2», y así lo puso la v2 en su seeder en mayo de 2026; todo el código
se escribió sobre ese orden y la API buscaba los publicados con el id de «programado».

**No se reordena la base** para que case con un enum más bonito: son ids que ya están en las
filas de producción. Manda la base; `ContentStatusEnum` y el seeder la copian.

*Fijado por `ContentStatusOrderTest` (enum, seeder y producción iguales) y por
`project:check-config`, que falla si la base desplegada no casa con el enum.*

---

### D31 · Un contenido publicado no vuelve a borrador ni a programado

Una vez publicado, el estado no cambia: se retira de las webs desmarcando «Activo», o se
elimina. Publicar, desde donde sea, marca «Activo»; a las webs sólo va lo publicado y activo.

**Por qué.** Lo que se ha publicado ya lo han visto las webs, los buscadores y quien lo
enlazara, y tiene fecha de publicación. Devolverlo a borrador la borraría y lo sacaría de las
webs sin dejar rastro de que existió; ocultarlo con «Activo» conserva su historia y deja
volver a enseñarlo tal cual. Las reglas están en `Content::applyPublicationRules()` (evento
`saving`), así que valen igual desde el panel, la acción masiva y el cron.

*Fijado por `ContentPublicationTest` y `ContentPublicationPanelTest`.*

---

### D32 · El panel de contenidos no lleva cabecera CSP

Una CSP (lista de orígenes desde los que el navegador puede ejecutar código) sería una tercera
capa contra el código escondido en las páginas. **No se pone.** El panel carga cosas de fuera
(vídeos de YouTube, fuentes, miniaturas) y Filament, Livewire y Alpine evalúan código en línea:
una CSP mal ajustada rompe pantallas en silencio, y ajustarla bien exige revisarlas una a una.

Las dos capas que sí hay: el HTML de los textos de los bloques se limpia al guardar
(`ContentHtmlSanitizer`, para todo el mundo) y el HTML libre (bloque `raw`, formato HTML, «JSON en
crudo») es sólo de administradores, comprobado también en el servidor. Los vídeos incrustados
tampoco se restringen a servicios conocidos por ahora.

*Fijado por `ContentHtmlSanitizerTest` (batería de ataques de OWASP) y `ContentPageSavingTest`
(HTML libre sólo de administradores, también el que se escribe dentro de un Markdown).*

---

### D33 · Los PDF se guardan con sus metadatos

Las imágenes pierden todos sus metadatos al subirlas (GPS, modelo del móvil…). Los PDF **no**:
quitarlos bien exige programas externos en el servidor (`exiftool` para borrarlos y `qpdf` para
reescribir el fichero y que no se puedan recuperar), y los de estos PDF suelen ser la autoría
puesta a propósito. Se suben tal cual.

*Fijado por `EditorJsTest::a_pdf_is_stored_untouched_with_its_metadata` (mismo hash que el
original). Decidido en la DUDA-5 del plan de contenidos del 2026-09-24.*

---

### D34 · Lo que no es imagen ni PDF se sirve como descarga

El editor acepta cualquier fichero (D13), así que puede haber un `.html` o un `.svg` en el módulo
de ficheros. Servidos en línea desde el dominio de la API, ejecutarían su código con ese origen.
`FileController` sólo los enseña en el navegador si son JPEG, PNG, WebP, GIF o PDF
(`File::INLINE_MIMES`); el resto sale con `Content-Disposition: attachment`. Las miniaturas son
siempre imágenes generadas aquí y se enseñan.

*Fijado por `EditorJsTest::only_images_and_pdfs_are_shown_in_the_browser_and_the_rest_is_downloaded`.*

---

### D35 · Las plataformas de un Editor dicen dónde crea, no qué edita

Un Editor edita los contenidos que escribe y aquellos donde es colaborador, en cualquier
plataforma. Las plataformas que tiene asignadas sólo deciden **dónde puede crear**. Para que
entre en todo lo de una plataforma está el interruptor «Colaborador automático», que lo hace
colaborador de cada contenido, uno a uno.

**Por qué.** Si la plataforma diese acceso a todo lo que hay en ella, no se podría quitar a
nadie de un contenido concreto sin quitarle la plataforma entera, y tampoco se podría dejar
a alguien colaborar en un contenido suelto de otra plataforma. Con colaboradores por
contenido las dos cosas son una fila.

**Quitar a un colaborador es borrar (lógicamente) su fila**, no hacerla desaparecer: esa fila
es la marca de «quitado a mano», y el colaborador automático no lo vuelve a meter. Por eso
todas las relaciones con pivote de `Content` filtran las filas borradas.

*Fijado por `ContentPolicyTest` (matriz de permisos) y `ContentContributorsTest`. Decidido en
la DUDA-1 y la DUDA-2 del plan de contenidos del 2026-09-24.*

---

### D36 · Los ficheros de contenido se borran a los 30 días sin usar, no al quitarlos

Quitar una imagen de una página no borra el fichero. Al guardar, se marca como «sin usar» si
no aparece en ninguna página (tampoco en la papelera), versión del historial, borrador ni
portada, y una tarea diaria lo borra a los 30 días, después de comprobar en toda la base que
nada lo usa.

**Por qué.** `main` lo borraba en el acto y al cortar y pegar un bloque para moverlo la imagen
se perdía. Con el historial de versiones, además, una imagen quitada puede volver al recuperar
una versión: borrarla antes de que caduque la versión dejaría la página rota. Un contenido o una
página en la papelera tampoco marcan nada, porque se pueden restaurar.

Para que eliminar un contenido definitivamente no deje ficheros huérfanos, la clave
`content_files.content_id` pasa de CASCADE a SET NULL: las filas se quedan, marcadas, hasta que
la tarea borra sus ficheros.

*Fijado por `ContentFileUsageTest`, con ficheros de verdad en el disco. C2 de la auditoría de
contenidos del 2026-09-24.*

---

### D37 · La vista previa del contenido enseña el HTML guardado tal cual

La vista previa del panel (`PreviewContent`) pinta el `content` de cada página sin volver a
limpiarlo. La vista previa del modal de páginas, en cambio, sí pasa por `Str::sanitizeHtml()`.

**Por qué.** Lo del modal es lo que se está escribiendo, sin guardar, y todavía no ha pasado
por nada. Lo guardado ya pasó por las capas de D32: el HTML de los bloques se limpia al
guardar, el HTML libre es sólo de administradores y el Markdown de un Editor se limpia. Volver
a limpiarlo en la vista previa quitaría vídeos incrustados e iconos SVG, y dejaría de ser «lo
que sirve la API», que es para lo que está.

*Fijado por `ContentSectionsTest::the_preview_shows_every_page_in_order_with_the_served_html`.
C7 de la auditoría de contenidos del 2026-09-24.*

---

### D38 · El bloqueo de una página no deja a nadie fuera de su propia página

El bloqueo (P4) es por usuario **y pestaña**. Eso protege de pisarse entre dos pestañas, pero
tiene dos trampas que se vieron en el navegador, y las dos se resuelven sin debilitarlo:

- **Recargar.** La petición de la página nueva llega al servidor antes que el aviso de la vieja
  soltando el bloqueo, así que la recarga se veía «abierta en otra pestaña». La pantalla en
  lectura vuelve a intentar cogerlo a los 3 s y en cada autoguardado, y si está libre pasa a
  edición releyendo la página.
- **El navegador se cierra de golpe.** La pestaña muerta no suelta nada y el bloqueo dura dos
  minutos. Con «Editar aquí» el **mismo usuario** se queda el bloqueo de su otra pestaña; la
  otra, si seguía viva, pasa a lectura con su borrador. A otro usuario no se le puede quitar
  así: eso es «Forzar desbloqueo», sólo de administradores.

*Fijado por `ContentPageLockTest::the_same_user_can_take_over_from_another_tab_but_not_from_another_user`
y `ContentPageEditorTest` (recarga y caída). F8 del plan de contenidos del 2026-09-24.*

---

### D39 · La caché de la API caduca con un contador global, y las visitas se cuentan con `defer()`

**Caché.** El plan ligaba la clave de cada respuesta al `updated_at` del contenido (y los listados
al último `updated_at` de la plataforma). Se usa en su lugar un contador en la caché,
`ApiCacheVersion`, que sube con cualquier cambio en lo que la API enseña:

- `updated_at` va al segundo: dos cambios en el mismo segundo dejaban una respuesta vieja
  guardada.
- Mucho de lo que se enseña no es del contenido (el título de un relacionado, el nombre de una
  categoría, la plataforma, el autor): con `updated_at` habría que tocar todos los contenidos
  afectados; con el contador basta subirlo.
- Los listados no necesitan una consulta de `max(updated_at)` para montar su clave.

A cambio, cualquier edición invalida todas las respuestas guardadas, no sólo las del contenido.
Con el volumen de ediciones de estas webs (unas pocas al día) no importa. El `updated_at` del
contenido se sigue poniendo al día al cambiar sus partes, porque es un dato del contrato.

**Visitas.** Se cuentan con `defer()` y no con `dispatchAfterResponse()`: las dos corren después
de responder y sin cola, pero la segunda se queda registrada en la aplicación y, cuando ésta
atiende más de una petición (los tests, Octane), la repite en cada una. Los tests contaban
visitas de más en las páginas.

*Fijado por `ContentApiCacheTest` (matriz de caducidad y visitas). F9 del plan de contenidos del
2026-09-24.*

---

### D40 · Las imágenes de los contenidos se guardan en WebP, sin el original

Las fotos que se suben a un contenido (editor, portadas del contenido y de sus páginas, imagen
social) no se guardan tal cual: pasan a WebP a calidad 85, giradas según su orientación, sin
metadatos y a 2560 px como mucho (`File::addFile(..., webpOriginal: true)`). No se conserva el
original.

- Las fotos del móvil llegan a 5–10 MB con el GPS dentro; una web no necesita más que la copia de
  2560 px, que ocupa una décima parte.
- HEIC, HEIF y AVIF, que un navegador no siempre pinta, salen servibles desde el primer momento.
- Los GIF se quedan como están (perderían la animación), y el resto de módulos sigue con su
  formato hasta que se decida para cada uno (`docs/future/`).

*Fijado por `EditorJsTest::a_phone_photo_is_stored_as_webp_rotated_and_without_its_metadata`,
`EditorJsTest::a_heic_photo_becomes_webp` y
`ContentSectionsTest::the_seo_section_saves_its_fields_and_the_social_image_as_webp`. DUDA-6 y F4
del plan de contenidos del 2026-09-24.*

---

### D41 · Borradores en el servidor, e historial de 50 versiones y 30 días

- **Borradores en la base de datos**, uno por usuario y página, y no en el navegador: se recuperan
  desde otro equipo o tras perder la sesión, y cada uno es sólo de quien lo escribió. Los de más
  de 30 días sin tocar se borran.
- **Historial** con lo que había antes de cada cambio real del contenido (la huella no cuenta el
  `time` de Editor.js ni la indentación): **50 versiones por página y 30 días**. Es un colchón
  para deshacer, no un archivo: con más, la tabla crecería con cada autoguardado que se convierta
  en guardado, y nadie recupera versiones de hace meses.

*Fijado por `ContentPageDraftTest` (entre ellos `drafts_older_than_thirty_days_are_pruned`) y
`ContentPageHistoryTest::sixty_different_saves_leave_the_fifty_most_recent` y
`::versions_older_than_thirty_days_are_pruned_by_the_daily_task`. F6 del plan de contenidos del
2026-09-24.*

---

### D42 · Las páginas antiguas se convierten al pedirlas, sin comando de conversión

Las páginas de la v1 sólo tienen su JSON de Editor.js y el HTML servido; no sus versiones en
Markdown. La auditoría (su F4) proponía un comando que las convirtiera todas de una vez;
siguiendo el criterio de G1 (nada de comandos para una sola vez), se convierten **la primera vez
que alguien las pide en otro formato** y la conversión se guarda en la caché una semana, ligada a
la fecha de la página (`content-page-format:{id}:{updated_at}:{formato}`):

- no hay que acordarse de lanzar nada al desplegar;
- una página que se vuelve a guardar desde el panel ya guarda sus versiones y deja de necesitarlo;
- si la conversión mejora, las páginas antiguas lo notan al caducar la caché, sin volver a
  convertir la base.

*Fijado por `ContentDetailApiTest::an_old_page_without_its_other_formats_is_converted_once_and_kept_in_the_cache`.
F9 del plan de contenidos del 2026-09-24.*

---

### D43 · El editor de páginas no tiene atajos de teclado propios

Los cuatro que venían de `main` chocaban con el navegador: `Cmd/Ctrl+Mayús+W` (aviso) **cierra la
ventana** en Chrome y en Firefox, y la página no puede impedirlo (en Firefox la tecla es
`reserved`; en Chrome, cerrar ventana es un comando reservado); `+O` (cita) abre los marcadores,
`+M` (resaltar) cambia de perfil en Chrome y abre el modo adaptable en Firefox, y `+C` (código en
línea) abre el inspector. No queda ninguna `Cmd/Ctrl+Mayús+letra` que no use Chrome, Firefox o sus
herramientas de desarrollo, y las combinaciones con `Alt` son `AltGr` en los teclados europeos de
Windows y Linux (`Ctrl+Alt+E` es «€»).

Así que las herramientas no llevan atajo: los bloques se insertan escribiendo `/` en una línea vacía
y filtrando por su nombre (`/cita`, `/aviso`), y resaltar y código en línea se aplican desde la
barra que aparece al seleccionar texto. Quedan los de Editor.js (negrita, cursiva, enlace).

Antes de añadir un atajo, se comprueba contra las listas de Chrome, Firefox (incluidas sus
herramientas de desarrollo) y Safari, y contra el sistema (macOS y los métodos de entrada de Linux,
`Ctrl+Mayús+U`).

*Fijado por `EditorJsAssetsTest::the_editor_tools_have_no_keyboard_shortcuts_of_their_own`. DUDA-7 del
plan de contenidos del 2026-09-24.*

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

### D29 · Los paquetes de Editor.js van con versión exacta

En `package.json`, `@editorjs/*`, `@calumk/editorjs-codecup`, `editorjs-alert` y `prismjs` llevan
la versión exacta, sin `^`. No es una excepción a D7: se actualizan igual, pero **a propósito**,
porque una versión nueva de una herramienta puede cambiar el formato de lo que se guarda. Pasó con
`@editorjs/list` 2.x (listas en otro formato) y con el núcleo 2.30 (`data-empty` dentro del HTML
de la alerta), y el HTML que ven las webs sale de esos datos. El procedimiento está en
[`content.md`](content.md), «Actualizar el editor».

Tampoco se deja al bloque de código bajar los lenguajes de resaltado de cdnjs, como hace por
defecto: vienen de `prismjs` por npm, para que el panel no cargue scripts de fuera.

*Fijado por `EditorJsAssetsTest` (versiones exactas) y `ServedHtmlRegressionTest` (las páginas
reales regrabadas por el editor sirven el mismo HTML).*

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


### D15 · `project:clear` decide por `APP_ENV`, y en desarrollo regenera la `APP_KEY`

`php artisan project:clear`, sin ningún flag, tiene que ser lo correcto en los dos lados. Ése era el
objetivo del comando —algo sencillo de recordar y de ejecutar tras cada subida— y se había perdido:
en el servidor había que acordarse de `--production --no-key --force`, y olvidar `--no-key` una sola
vez regeneraba la clave de producción.

| | Desarrollo | Producción |
|-|-|-|
| `APP_KEY` | se regenera | se conserva (hace falta `--key`) |
| Colas | `queue:clear` | `queue:restart` |
| Recacheo | no | sí |

**En desarrollo regenera la clave, y así se queda.** `project:clear` deja el proyecto **como recién
instalado**: ése es su propósito ahí. Conservar la clave sería hacer media limpieza. No es un
descuido ni un comportamiento heredado.

⚠️ **Esto lleva propuesto en varias auditorías seguidas**, siempre con el mismo argumento («el
comportamiento por defecto es destructivo»). En desarrollo **no hay que invertir la lógica**; lo que
había que arreglar era producción, y ya está arreglado. El comentario del propio comando remite aquí.

**Por qué en producción es al revés.** Allí el comando se ejecuta después de cada despliegue.
Regenerar la clave en ese momento invalida las sesiones abiertas y deja sin descifrar los
`two_factor_secret` que Fortify guarda cifrados —los tokens de Sanctum no, que se guardan hasheados—.
Rotar la clave es una operación aparte, que no tiene nada que ver con subir código: para eso está
`--key`, que además sigue pidiendo confirmación salvo `--force`.

**Y por qué las colas no se vacían en producción.** La conexión por defecto es `database`, así que
`queue:clear` borra la tabla `jobs`: correos sin enviar, PDFs de currículum sin generar. Un
despliegue no tira trabajo pendiente. Lo que sí hace falta ahí es `queue:restart`, porque un worker
lleva el código en memoria desde que arrancó y seguiría ejecutando el viejo.

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

### D18 · Las respuestas de error del cliente no van al log

Un `422` de validación y un `403` de permiso **no se registran**. Son respuestas del contrato: el
cliente manda un campo mal y se le dice. No son fallos del servidor.

La decisión vive en `bootstrap/app.php`:

```php
$exceptions->dontReport([
    JsonValidationException::class,
    JsonAuthorizationException::class,
]);
```

**Por qué está anotado aquí.** Las dos excepciones ya llevaban un método pensado para eso:

```php
public function report(): bool
{
    return false;   // ← hacía lo contrario de lo que dice
}
```

El handler de Laravel sólo deja de reportar cuando `report()` devuelve algo **distinto** de `false`
(`Handler::report()`: `... && $this->container->call($reportCallable) !== false`). Devolver `false`
significa «repórtalo tú». Con el comentario diciendo una cosa y el código haciendo otra, en
producción cada petición mal formada dejaba **una traza de setenta líneas**, y un cacharro con el
firmware desalineado llenaba el disco en un rato.

Los `report()` se han quitado: un solo sitio, explícito, sin depender de qué significa el valor de
retorno. Laravel ya ignora por defecto las suyas equivalentes (`ValidationException`,
`AuthenticationException`, `HttpException`…); estas son propias y hay que decírselo.

*Lo fija `tests/Unit/Support/NonReportableExceptionsTest.php`. Origen: log de producción del
2026-09-06.*

---

## Cómo mantener este documento

Se añade una entrada cuando se decide **no** hacer algo que parece que habría que hacer, o hacerlo de
una forma que a primera vista chirría. Cada entrada dice **qué** se decidió, **por qué**, y —cuando
existe— **qué test lo fija**.

Lo que no va aquí: decisiones que el código ya explica por sí solo, y cosas que simplemente están
pendientes (eso es `docs/future/`).

> Creado: 2026-09-01 · Última revisión: 2026-10-05
