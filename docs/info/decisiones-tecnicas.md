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

**`guzzlehttp/guzzle` 7 → 8 está bloqueado, y no por decisión.** Guzzle 8 requiere
`guzzlehttp/psr7 ^3`, mientras `laravel/reverb` —en su última versión, v1.11.1— sigue exigiendo
`psr7 ^2.6`. Subir Guzzle obligaría a quitar Reverb, que es el WebSocket del proyecto. Guzzle 7.15.5
está al día dentro de su rama y `roave/security-advisories` no reporta avisos para ella, así que no
hay urgencia. **Se aplicará cuando Reverb admita `psr7` 3.**

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

---

## Cómo mantener este documento

Se añade una entrada cuando se decide **no** hacer algo que parece que habría que hacer, o hacerlo de
una forma que a primera vista chirría. Cada entrada dice **qué** se decidió, **por qué**, y —cuando
existe— **qué test lo fija**.

Lo que no va aquí: decisiones que el código ya explica por sí solo, y cosas que simplemente están
pendientes (eso es `docs/future/`).

> Creado: 2026-09-01 · Última revisión: 2026-09-01
