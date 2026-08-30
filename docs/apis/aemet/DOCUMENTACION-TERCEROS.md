# 🔍 Documentación de terceros: qué acierta y qué no

Registro de afirmaciones sobre AEMET OpenData procedentes de **fuentes que no son AEMET** (informes
generados por IA, documentación de otros proyectos, artículos), **contrastadas con peticiones
reales**.

Existe por una razón práctica: estos documentos circulan, se pegan en prompts y se copian entre
proyectos. Sin un registro, **los mismos errores vuelven a entrar cada vez**.

Leyenda: ✅ confirmado con petición real · ❌ desmentido con petición real · ⚠️ peligroso · 🔴 sin comprobar

> **Fuentes de este archivo:** documentos aportados por el equipo, **no oficiales de AEMET y por tanto NO guardados en `src/`**: un informe generado por IA sobre predicción provincial y avisos (2026-08-26), la documentación de integración del proyecto `api-fryntiz`, y su helper `support/helpers/AEMETHelper.php` (~1.900 líneas, en producción). Todas las afirmaciones se contrastaron con peticiones reales del 2026-08-26.

---

## Regla general

> **Ninguna documentación de terceros sobre esta API es fiable sin verificar**, ni siquiera la de
> otro proyecto propio. Las dos fuentes analizadas contenían endpoints inexistentes, nombres de
> campo equivocados y límites inventados — mezclados con información correcta y valiosa.
>
> El patrón se repite: **la arquitectura la aciertan, los detalles los inventan.** Con una excepción
> importante: el **código en producción** (Fuente C) es mucho más fiable que los informes generados,
> porque cada rareza que sortea corresponde a un problema real que alguien sufrió. Sus comentarios
> del tipo "chapuza de los ID duplicados" señalan erratas auténticas de la API.

---

## Fuente A — Informe sobre predicción provincial y avisos CAP (2026-08-26)

Informe generado por IA para un bot en producción.

### Lo que acierta ✅

| Afirmación | Verificación |
|---|---|
| Patrón de dos pasos (endpoint → URL `datos` → contenido) | ✅ Correcto |
| El paso 2 no requiere API Key | ✅ Confirmado |
| Los avisos vienen en un contenedor comprimido con decenas de XML | ✅ `tar` con 56 XML |
| **Hay que descartar los avisos de nivel `verde`** | ✅ **20 de 112 son verde.** Consejo valioso |
| Valores de probabilidad `40%-70%` | ✅ Exactos |
| **Elegir el bloque `<info>` con `language` español** | ✅ **Imprescindible**: cada XML trae `es-ES` **y** `en-GB`, y sin filtrar se duplica todo |
| **El código de zona son 6 dígitos: CCAA + provincia INE + comarca** | ✅ **Correcto y muy útil.** Verificado en las 22 zonas de Galicia. Resolvió el cruce municipio → aviso |
| Tabla de códigos de CCAA para avisos (`61`–`79`) | ✅ Coincide con la especificación |
| Los avisos incluyen `areaDesc` con nombres de comarca | ✅ 22 nombres legibles |
| Descompresión recursiva defensiva (plano / gz / tar) | ✅ Buen enfoque: los formatos **sí varían** entre endpoints |
| Cachear y usar timeouts generosos | ✅ Sensato |

### Lo que falla ❌

| Afirmación | Realidad verificada |
|---|---|
| `valueName` = **`EMMA_ID`** | ❌ Es **`AEMET-Meteoalerta zona`**. Buscar `EMMA_ID` no encuentra nada |
| `valueName` = **`Nivel`** / **`Probabilidad`** | ❌ Son **`AEMET-Meteoalerta nivel`** y **`AEMET-Meteoalerta probabilidad`**. **Su filtro de verde nunca se activa** |
| `GET /prediccion/provincia/pasadomanana/{prov}` | ❌ **404.** Provincia solo tiene `hoy` y `manana` |
| `{codigo_provincia}` es "el código INE de **2 dígitos**" | ❌ Las islas usan **3 dígitos**: `07` → `estado: 404`, `071` → ✅. `35` → `estado: 404`, `353` → ✅ |
| Tabla: "Baleares `07`", "Las Palmas `35`", "S.C. Tenerife `38`" | ❌ Esos códigos no existen en este endpoint. AEMET desglosa por isla: `071`, `072`, `073`, `351`–`353`, `381`–`384` |
| `"descripcion": "éxito"` (con tilde) | ❌ La API devuelve **`"exito"`**, sin tilde. Comparar por esa cadena falla |
| Cabecera `Content-Type: charset=ISO-8859-1` | ❌ Declara **`ISO-8859-15`** explícitamente. Y **hay productos en UTF-8 real** (ozono, radiación) que se corrompen si se fuerza ISO |
| "Forzar `ISO-8859-15`" como solución universal | ❌ Corrompe los productos que ya vienen en UTF-8. Lo correcto es **leer el `charset` de la cabecera** |
| Archivo de avisos: "máx. 2 días" | 🔴 Sin confirmar el límite. Sí medido: **3,8 MB por un solo día** |
| "La URL temporal caduca a los pocos minutos" | 🔴 Sin verificar. Plausible, pero no comprobado |
| Límite: "cuotas estrictas por minuto" (sin cifra) | Incompleto: son **40 por plantilla de endpoint**, y la API lo dice en `Remaining-request-endpoint` — cabecera que el informe no menciona |

### Lo que es peligroso ⚠️

> [!CAUTION]
> **`verify=False` para saltarse errores de certificado SSL.**
>
> El informe recomienda reintentar con la verificación de certificados desactivada y silenciar los
> avisos de `urllib3`. **No lo hagáis.**
>
> - **Desactivar la verificación TLS expone la conexión a intercepción.** Aunque los datos
>   meteorológicos sean públicos, **la API Key viaja en esa petición** (y más aún si se envía por
>   query string, como el mismo informe recomienda).
> - **En ~130 peticiones reales a `opendata.aemet.es` y a las URLs `sh/` no hubo ni un error de
>   certificado.** La cadena de AEMET valida correctamente.
> - Si algún día apareciera un `CERTIFICATE_VERIFY_FAILED`, la causa habitual es un **almacén de CA
>   local desactualizado**. La solución es actualizarlo, no desactivar la comprobación.
> - Silenciar `InsecureRequestWarning` agrava el problema: oculta el único aviso de que la conexión
>   dejó de ser segura.

> [!CAUTION]
> **Enviar la API Key "tanto en la cabecera como por parámetro de URL".**
>
> Basta la cabecera 🟢. Duplicarla en la URL no añade nada y **filtra la credencial** a los logs del
> servidor, los del proxy, el historial del navegador y la cabecera `Referer`.
> Ver [`00-fundamentos.md`](00-fundamentos.md#autenticación).

### Aviso operativo para quien use ese bot

🟢 El endpoint provincial funciona en la mayoría de España, pero **las cuatro provincias gallegas
devuelven la predicción del 3 de noviembre de 2022** con toda la apariencia de estar correcta. Si el
bot sirve alguna de ellas, está mostrando un dato de hace cuatro años.

Es un **error conocido de AEMET** que no vamos a investigar. La defensa es la misma que hace falta en
todo el grupo: **validar la fecha de elaboración del contenido** antes de publicarlo. Ver
[`02-predicciones-texto.md`](02-predicciones-texto.md#por-provincia-funciona-con-una-avería-conocida-).

---

## Fuente B — Documentación de integración de otro proyecto propio (`api-fryntiz`)

### Lo que acierta ✅

| Afirmación | Verificación |
|---|---|
| Patrón de dos saltos y que el segundo no lleva auth | ✅ Correcto |
| Nombrado de variables de entorno (`AEMET_API_KEY`, defaults por producto) | ✅ Sensato, adoptado |
| Idea de TTL de caché por producto | ✅ Buen enfoque, aunque los valores concretos estaban inventados: la periodicidad real está en los **metadatos** de cada endpoint |
| Reintentar solo en 429 y 5xx, con backoff exponencial | ✅ Correcto |
| Validar que el payload es un array no vacío antes de persistir | ✅ Necesario: la API devuelve `200` con cuerpo vacío |

### Lo que falla ❌

| Afirmación | Realidad verificada |
|---|---|
| `/red/especial/radiacionsolar` | ❌ **404.** La ruta es `/api/red/especial/radiacion` |
| `/red/especial/contaminacionfondo` (sin estación) | ❌ **404.** Exige `/estacion/{nombre_estacion}` |
| Límite "~100 peticiones/minuto" y "~3000/día" | ❌ Sin fuente. Son **40 por plantilla de endpoint** |
| `AEMET_DEFAULT_COSTA="11"` | ❌ No es un código de costa. Van del `40` al `47`; Galicia es `40` |
| `base_url` = `…/opendata/api` | ⚠️ El servidor es `…/opendata` y las rutas ya empiezan por `/api/`. Invita a un `/api/api/` |

---

## Fuente C — `AEMETHelper.php` de `api-fryntiz` (código en producción, ~1.900 líneas)

Helper de Laravel que lleva **años funcionando**. Por eso es la fuente más interesante de las tres:
lo que hace, funciona. Y lo que hace revela cosas que ninguna documentación cuenta.

### Lo que aporta, y era desconocido para nosotros ✅

| Hallazgo | Valor |
|---|---|
| **Los metadatos traen un campo `campos[]`** con el diccionario completo: `id`, `descripcion` (con unidad), `tipo_datos` y `requerido` | ✅ **El hallazgo más valioso de las tres fuentes.** Resolvió el "diccionario de campos" que estaba pendiente en 4 módulos. Ver [`00-fundamentos.md`](00-fundamentos.md#los-metadatos-son-el-diccionario-de-datos-) |
| Los metadatos traen `posicion_txt` con **posiciones de carácter** para los formatos de ancho fijo | ✅ Confirmado. Es lo que permite parsear contaminación de fondo |
| ⚠️ **La clave viene como `posición_txt` y `posicion_txt`** en el mismo documento | ✅ Confirmado. Su código ya lo sorteaba buscando cualquier clave con `_txt` |
| ⚠️ **`Codigo_validacion_O3` está duplicado** en los metadatos (el segundo es PM10) | ✅ Confirmado. Su comentario en el código lo llama "la chapuza de los ID duplicados" |
| Contaminación de fondo está en **formato FINN**, texto de ancho fijo, datos diezminutales | ✅ Confirmado por `formato: ascii/txt` y `periodicidad: Cada 1h` |
| Los avisos CAP traen **`<polygon>`** con el contorno geográfico | ✅ **Confirmado en las 364 áreas.** Permite point-in-polygon. Ver [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md#-cada-area-trae-su-polígono-geográfico-) |
| Usa `PharData` sobre un fichero `.tar` (no `.tar.gz`) | ✅ Corrobora que los avisos vienen **sin comprimir** |
| Usa el prefijo `id` para el municipio (`id11016`) | ✅ Coincide con lo verificado en el maestro |
| `CURLOPT_ENCODING => ''` para aceptar cualquier compresión de transporte | ✅ Buena práctica con esta API |
| La fecha del CSV de radiación **puede no estar en la línea 2**: la busca entre las 5 primeras | 🟡 Defensa razonable ante un formato inestable. No verificado que se mueva, pero el código lo contempla por algo |

### El atajo que explica por qué "lleva años funcionando" ⚠️

```php
json_decode($response, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
```

**Esa bandera es la razón de que la integración no falle** con la codificación ISO-8859-15: en vez de
devolver `null`, sustituye cada secuencia inválida por `U+FFFD` (`�`).

⚠️ **Funciona, pero pierde todos los acentos y las `ñ`.** Los datos llegan; el texto sale corrompido
(`Meteorolog�a`). Y es el caso más difícil de detectar de los tres, porque la integración parece sana
y solo se nota mirando una cadena con tilde.

**Lo correcto es leer el `charset` de la cabecera y convertir antes de decodificar.** Detalle en
[`ERRATAS.md` A2-bis](ERRATAS.md#a2-bis--el-atajo-json_invalid_utf8_substitute-funciona-pero-pierde-los-acentos-).

### Lo que hace de forma frágil ⚠️

| Práctica | Problema |
|---|---|
| **Filtra avisos por slug de `areaDesc`** (`litoral_gaditano`, `campina_gaditana`…) | Los nombres de comarca son texto libre y pueden cambiar. **El `geocode` es estable y jerárquico**: filtrar por prefijo de zona es más robusto |
| `foreach ($jsonFromXml['info'] as $info)` sobre todos los bloques | ⚠️ Procesa **cada aviso dos veces**: los XML traen `es-ES` **y** `en-GB`. Hay que filtrar por idioma |
| `foreach ($info['area'] as $area)` con SimpleXML | 🟡 Correcto aquí (hay varias áreas), pero SimpleXML colapsa un elemento único en objeto y varios en array: hay que normalizar o falla cuando solo hay una |
| Descarga el `tar` a `/tmp` y extrae a disco | 🟡 Funciona, pero deja ficheros si algo falla a medias. Se puede hacer en memoria |
| `$PATHS` con los parámetros **incrustados en la ruta** (`.../diaria/11016`) | 🟡 Obliga a duplicar la entrada por cada municipio o playa. Mejor plantilla con marcador, como sí hace en `periodClimatologiaPasada` |
| No lee `Remaining-request-endpoint` | 🟡 Se queda sin la única señal fiable de cuota. Ver [`LIMITACIONES.md`](LIMITACIONES.md#-la-api-expone-un-contador-de-cuota-que-no-documenta-) |
| Etiqueta `'ozono' => 'red/especial/perfilozono/...'` | 🟡 Confuso: son dos endpoints distintos (`ozono` y `perfilozono`) |

### Códigos que usa, por si sirven de referencia

Municipio `11016` (Chipiona) · playas `1101604` y `1101602` · estación de observación `5906X` ·
estación climatológica `5910` · contaminación `17` (Doñana) · costa `42` (Andalucía Occidental) ·
alta mar `1` · avisos área `61` (Andalucía). 🔴 No verificados por nosotros, pero coherentes con los
catálogos.

---

## Cómo añadir una fuente aquí

1. **Verifica cada afirmación con una petición real** antes de clasificarla. Una sospecha no es un
   desmentido.
2. Separa en tres bloques: **acierta**, **falla** y **es peligroso**. El tercero merece un aviso
   destacado.
3. **Incluye la evidencia**: código HTTP, cabecera o fragmento de respuesta.
4. Si el error también afecta a nuestra documentación, **corrígela ahí** y anótalo en
   [`ERRATAS.md`](ERRATAS.md).
5. Si la fuente aporta algo correcto y útil, **incorpóralo** al módulo correspondiente citando que
   vino de fuera y que se verificó.

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
