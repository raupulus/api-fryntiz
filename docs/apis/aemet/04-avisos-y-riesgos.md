# 🚨 Avisos de fenómenos adversos e índices de incendios

**4 endpoints.** Es el grupo más relevante para alertar y el que tiene el formato más
inesperado de toda la API.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md) · [`LIMITACIONES.md`](LIMITACIONES.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/documentos/plan_meteoalerta.pdf` (cuerpo del Plan, v9) · `src/documentos/METEOALERTA_ANX1_Umbrales_y_niveles_de_aviso.pdf` (umbrales) · `src/documentos/METEOALERTA_ANX3_CAP.pdf` (**la especificación CAP autoritativa**) · `src/documentos/METEOALERTA_ANX4_Boletin_avisos.pdf` (boletines) · `src/documentos/AEMET-meteoalerta-delimitacion-zonas.zip` (geometría de zonas) · `src/especificacion/AEMET_OpenData_specification.json` · **verificación en vivo del 2026-08-26** (56 XML de una comunidad y 252 del paquete nacional).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET /api/avisos_cap/ultimoelaborado/area/{area}` | 🟢 | **`tar` sin comprimir** con XML CAP 1.2 |
| `GET /api/avisos_cap/archivo/fechaini/{fechaIniStr}/fechafin/{fechaFinStr}` | 🟢 | **`tar` sin comprimir**, 3,8 MB (1 día) |
| `GET /api/incendios/mapasriesgo/previsto/dia/{dia}/area/{area}` | 🟢 | **PNG**, 501 KB |
| `GET /api/incendios/mapasriesgo/estimado/area/{area}` | 🟢 | ⚠️ **`estado: 404`** |

---

## Avisos de fenómenos meteorológicos adversos 🟢

```
GET /api/avisos_cap/ultimoelaborado/area/{area}
```

> [!CAUTION]
> **No devuelve JSON.** Devuelve un **`tar` SIN comprimir** que contiene ficheros XML en formato
> **CAP 1.2 de OASIS**, y el `Content-Type` es `application/x-gtar` (que sugiere gzip, pero no lo
> está). Ver [`ERRATAS.md` B2](ERRATAS.md#b2-los-avisos-cap-vienen-en-un-tar-sin-comprimir-pese-al-content-type-).

| | |
|---|---|
| `Content-Type` paso 2 🟢 | `application/x-gtar;charset=ISO-8859-15` |
| Compresión real 🟢 | **Ninguna.** Sin magic gzip (`1f8b`); `ustar` en el offset 257 |
| Tamaño (Galicia, `71`) 🟢 | 490 KB, **56 ficheros XML** |
| Tamaño (España, `esp`) 🟢 | **3,4 MB** |
| Periodicidad 🟢 | "Disponible en cualquier momento en el que se emite un fenómeno meteorológico adverso, con horas preferentes de emisión: 09:00, 11:…" |
| RSS 🔵 | Sí — **un canal por comunidad autónoma** más uno de España |
| TTL 🟡 | 15–30 min (emisión impredecible y valor que caduca rápido) |

Verificado con `71` (Galicia) y `esp`.

### Nombres de los ficheros dentro del `tar` 🟢

```
Z_CAP_C_LEMM_20260825090336_AFAZ711502PRP12616.xml
                └─ AAAAMMDDHHMMSS ─┘    └ zona ┘└fenómeno┘
```

| Fragmento | Significado |
|---|---|
| `Z_CAP_C_LEMM_` | Prefijo fijo. `LEMM` es el indicativo OACI de AEMET |
| `20260825090336` | Fecha y hora de emisión |
| `AFAZ` | Prefijo fijo |
| `711502` | 🟢 **Código de zona**: `71` CCAA + `15` provincia INE + `02` comarca. Coincide con el `geocode` interno y con el `zona_comarcal` del maestro |
| `PRP1`, `PRP2`, `TOTO` | 🟢 Fenómeno: `PR`+`P1`/`P2` = precipitación acumulada en 1 h / 12 h; `TO` = tormentas. Coincide con el código del parámetro `AEMET-Meteoalerta parametro` |
| `2616` | 🔴 Sin identificar |

🟡 **Varios ficheros por zona y fenómeno**: en Galicia, 56 XML. Hay que recorrer todos, no leer solo
el primero.

### Contenido de un XML 🟢

CAP 1.2 estándar (`urn:oasis:names:tc:emergency:cap:1.2`), **declarado en UTF-8** (a diferencia del
resto de la API, que es ISO-8859-15 —
[`ERRATAS.md` A1](ERRATAS.md#a1-la-api-responde-en-iso-8859-15-no-en-utf-8-)).

```xml
<?xml version="1.0" encoding="UTF-8"?>
<alert xmlns="urn:oasis:names:tc:emergency:cap:1.2">
  <identifier>2.49.0.0.724.0.ES.20260825090336.711502PRP126161787648616</identifier>
  <sender>http://www.aemet.es</sender>
  <sent>2026-08-25T09:03:36-00:00</sent>
  <status>Actual</status>
  <msgType>Update</msgType>
  <scope>Public</scope>
  <references>http://www.aemet.es,2.49.0.0.724.0.ES.20260824165049.…,2026-08-24T16:50:49-00:00</references>
  <info>
    <language>es-ES</language>
    <category>Met</category>
    <event>Aviso de lluvias de nivel amarillo</event>
    <responseType>Monitor</responseType>
    <urgency>Future</urgency>
    <severity>Moderate</severity>
    <certainty>Likely</certainty>
    <eventCode>
      <valueName>AEMET-Meteoalerta fenomeno</valueName>
      <value>PR;Lluvias</value>
    </eventCode>
    <effective>2026-08-25T11:03:36+02:00</effective>
    <onset>2026-08-26T06:00:00+02:00</onset>
    <expires>2026-08-26T17:59:59+02:00</expires>
    <senderName>AEMET. Agencia Estatal de Meteorología</senderName>
    <headline>Aviso de lluvias de nivel amarillo. Oeste de A Coruña</headline>
    <description>Precipitación acumulada en una hora: 15 mm.</description>
    <instruction>Esté atento. Manténgase informado de la predicción meteorológica…</instruction>
    <parameter>
      <valueName>AEMET-Meteoalerta nivel</valueName>
      <value>amarillo</value>
    </parameter>
    <parameter>
      <valueName>AEMET-Meteoalerta probabilidad</valueName>
      <value>40%-70%</value>
    </parameter>
    <area>
      <areaDesc>Oeste de A Coruña</areaDesc>
      <geocode>
        <valueName>AEMET-Meteoalerta zona</valueName>
        <value>711502</value>
      </geocode>
    </area>
  </info>
  <info>
    <language>en-GB</language>   <!-- ⚠️ el MISMO aviso, duplicado en inglés -->
    …
```

### La fuente oficial: el Plan Meteoalerta 🔵

`src/documentos/plan_meteoalerta.pdf` — **versión 9, de 10-ene-2025**, descargado el 2026-08-26.
Es el documento normativo de los avisos. Lo que sigue sale de ahí.

#### ⚠️ El nivel `verde` NO es un nivel de aviso

El Plan define **tres niveles: amarillo, naranja y rojo**. El registro de cambios de la versión 8
(26-may-2022) dice literalmente: *"Se suprime el nivel verde de aviso"*.

**Es la justificación normativa de que haya que descartarlos**: los `verde` que la API sigue
emitiendo no son avisos, son la ausencia de aviso.

| Nivel | Peligro | Recomendación oficial | Impactos |
|---|---|---|---|
| **Amarillo** | Bajo | **ESTÉ ATENTO** | Daños moderados a personas y bienes vulnerables o en zonas expuestas |
| **Naranja** | Importante | **ESTÉ PREPARADO** | Daños graves |
| **Rojo** | Extraordinario | **ACTÚE** según las autoridades. *No viaje salvo que sea estrictamente necesario* | Muy graves o catastróficos |

🟡 Esos textos son los que conviene mostrar al usuario: son los oficiales.

#### Los 15 fenómenos del Plan (Tabla 1)

Más de los 6 que aparecieron en la muestra de un día:

| Fenómeno | Variable | Restricción |
|---|---|---|
| Lluvias | Precipitación acumulada en 1 h y en 12 h (mm) | — |
| Nevadas | Nieve acumulada en 24 h (cm) | — |
| Vientos | Rachas máximas (km/h) | — |
| Tormentas | Múltiples | — |
| Temperaturas máximas | Temperatura (°C) | — |
| Temperaturas mínimas | Temperatura (°C) | — |
| Fenómenos costeros | Viento medio (km/h) y altura de mar combinada (m) | **solo zonas costeras** |
| Polvo en suspensión | Visibilidad (m) | — |
| Nieblas | Visibilidad (m) | — |
| Galernas | No aplica | **solo Cantábrico y norte de Galicia** |
| Rissagues | Oscilación del nivel del mar (m) | **solo Illes Balears** |
| Deshielos | No aplica | solo zonas acordadas con las Confederaciones Hidrográficas |
| Aludes | Escala europea de peligro de aludes | solo macizos con boletín de aludes |
| Olas de calor / Olas de frío / Tormentas tropicales | — | **avisos especiales** |

🟡 Esto explica por qué los códigos observados (`P1`, `P2`, `TA`, `TO`, `RM`, `RI`) son solo una
parte: en un día de agosto no hay nevadas ni aludes. `RI` = Rissagues, exclusivo de Baleares.

#### Periodos preferentes de emisión 🔵

Hora oficial peninsular:

| Franja | Qué se emite |
|---|---|
| **07:30 – 09:00** | Avisos para hoy (D) |
| **10:30 – 11:30** | Avisos para mañana (D+1) y pasado mañana (D+2) |
| **17:00 – 19:00** | Revisión de todos los avisos |
| **23:50** | Avance para D+3 |

⚠️ *"Son solo periodos preferentes: si se producen cambios significativos, podrán emitirse a
cualquier otra hora."* Y la antelación máxima del Plan es de **72 horas**.

🟡 Es la base para programar la sincronización: comprobar el RSS con más frecuencia en esas cuatro
franjas y relajarlo fuera de ellas.

#### Contenido mínimo de un aviso 🔵

El Plan lo fija en 11 campos, que se corresponden con lo observado en los XML: código único, código
de zona, fecha-hora de inicio y de fin, valor más probable de la variable, nivel, fenómeno y
variable, probabilidad de ocurrencia, fecha-hora de generación, **indicador de previsto/observado** y
comentarios libres del predictor.

#### La zonificación viene del Anexo 2 🔵

*"La zonificación de los avisos se ha elaborado mediante la **agregación de municipios adyacentes con
similares climatologías** de FMA"*. Confirma por qué un municipio pertenece a una zona y por qué el
maestro expone `zona_comarcal`.

#### Otros datos útiles 🔵

- Los umbrales por fenómeno y zona están en el **Anexo 1**, y **han cambiado varias veces** (el
  registro de cambios documenta ajustes por comunidad en 2013, 2015 y 2022). **No los codifiques
  como constantes.**
- Existen **avisos especiales** (varios FMA de nivel naranja/rojo a la vez, o un área extensa que
  abarque varias comunidades) y **avisos específicos** (criterios o zonas distintos a petición de
  usuarios). Ambos en formato boletín, no CAP.
- Los avisos españoles se agregan con los del resto de Europa en <https://meteoalarm.org/>.

> ✅ **Los cuatro anexos están en `src/documentos/`**, obtenidos de
> <https://www.aemet.es/es/eltiempo/prediccion/avisos/ayuda>:
> `METEOALERTA_ANX1_Umbrales_y_niveles_de_aviso.pdf` (umbrales por zona),
> `METEOALERTA_ANX2_Zonas_aviso.pdf`, `METEOALERTA_ANX3_CAP.pdf` (destilado más abajo) y
> `METEOALERTA_ANX4_Boletin_avisos.pdf` (destilado más abajo). Más el **shapefile de delimitación de
> zonas** y el **detalle municipio→zona** (221 páginas).

### Los umbrales de aviso (Anexo 1) 🔵

`src/documentos/METEOALERTA_ANX1_Umbrales_y_niveles_de_aviso.pdf` — **versión 1, 31-may-2022**,
20 páginas. Define **cuándo** se emite cada nivel.

#### Fenómenos con criterio cualitativo (no zonificados)

| Fenómeno | Amarillo | Naranja | Rojo |
|---|---|---|---|
| **Tormentas** | Tormentas *fuertes* | Tormentas *muy fuertes* | Muy fuertes con características excepcionales de alto impacto |
| **Nieblas** | Densas, generalizadas y persistentes | — *(solo amarillo)* | — |
| **Polvo en suspensión** | Normalmente visibilidad < 3.000 m | — *(solo amarillo)* | — |
| **Costeros** (cantábricas y atlánticas) | Viento 50 km/h (F7) **o** mar combinada con oleaje de 4 m | 60 km/h (F8) **o** 5 m | 90 km/h (F10) **o** 8 m |
| **Costeros** (mediterráneas) | 50 km/h (F7) **o** 3 m | 60 km/h (F8) **o** 4 m | 90 km/h (F10) **o** 7 m |
| **Rissagues** (solo Illes Balears) | Oscilación 0,7–1 m | 1–2 m | > 2 m |
| **Galernas** (Galicia, Asturias, Cantabria, País Vasco) | Rolada al NW fuerza 7 (50 km/h) / rachas > 60 km/h en litoral | fuerza 8 (60 km/h) / > 90 km/h | fuerza 10 (90 km/h) / huracanadas > 130 km/h |
| **Aludes** | Peligro 4 con salida < 2.100 m, o peligro 5 con salida > 2.100 m | Peligro 5 con salida < 2.100 m | Riesgo generalizado de nivel naranja en zona amplia |
| **Deshielos** | Acuerdo con la Confederación Hidrográfica (Duero y Ebro) | ídem | ídem |

⚠️ **Las tormentas se adjetivan, no se miden**: *"fuertes"* = gran aparato eléctrico y/o
precipitaciones localmente fuertes y/o rachas muy fuertes y/o granizo > 1 cm; *"muy fuertes"* =
extraordinario aparato eléctrico y/o rachas > 90 km/h o huracanadas y/o granizo > 2 cm.

⚠️ **Excepción estacional en Baleares**: entre el **1 de mayo y el 30 de septiembre** el criterio de
aviso amarillo costero se relaja a **40 km/h o 2 m de oleaje**.

#### Fenómenos zonificados: 6 variables × 3 niveles, por zona

El apartado 3 del Anexo trae **una tabla por comunidad autónoma** con los umbrales de cada una de las
233 zonas, para 6 variables:

| Variable | Unidad |
|---|---|
| Temperatura máxima | °C |
| Temperatura mínima | °C |
| Racha máxima de viento | km/h |
| Precipitación en 12 h | mm |
| Precipitación en 1 h | mm |
| Acumulación de nieve en 24 h | cm |

Ejemplo real (Andalucía, zona `611104` Estrecho):

| Variable | Amarillo | Naranja | Rojo |
|---|---|---|---|
| Temp. máxima | 36 | 39 | 42 |
| Temp. mínima | −1 | −4 | −8 |
| Racha máxima | 80 | 100 | 140 |
| Precip. 12 h | 40 | 80 | 120 |
| Precip. 1 h | 15 | 30 | 60 |
| Nieve 24 h | 2 | 5 | 20 |

> [!IMPORTANT]
> **Los umbrales varían por zona.** En la misma provincia de Cádiz, `611101` Grazalema tiene umbral
> de temperatura máxima 38/40/44 y `611104` Estrecho 36/39/42. **No hay un umbral nacional.**
>
> 🔴 **Las 233 × 18 = ~4.200 cifras no se han transcrito**: están en tablas de imagen dentro del PDF y
> volcarlas a mano sería una fuente de errores. **El PDF está en `src/documentos/`.** Si en algún
> momento hacen falta programáticamente, hay que extraerlas con OCR y verificarlas.
>
> 🟡 En la práctica **no suelen hacer falta**: el aviso que devuelve la API ya trae el nivel calculado
> y el valor en `AEMET-Meteoalerta parametro`. Los umbrales solo sirven para contextualizar
> ("40 mm de 120 para el rojo").

#### Regla global de las nevadas 🔵

> Como norma general **no se da aviso de nieve por encima de 600 m en Illes Balears, 2.000 m en
> Canarias y 1.500 m en el resto**, aunque localmente puedan acordarse altitudes inferiores con
> Protección Civil.

### La especificación CAP de AEMET (Anexo 3) 🔵

`src/documentos/METEOALERTA_ANX3_CAP.pdf` — **versión 1, 31-may-2022**. Es la especificación
autoritativa. Todo lo que sigue sale de ahí y **coincide con lo observado**.

#### Los valores admitidos de cada etiqueta

| Etiqueta | Valores | Notas |
|---|---|---|
| `status` | `Actual` (operativo) · **`Test`** (pruebas) | **Filtrar `Test`** antes de publicar |
| `msgType` | `Alert` (nuevo) · `Update` (actualiza los de `references`) · `Cancel` (**no implementado**) | Ver el mecanismo de retirada abajo |
| `scope` | `Public` siempre (`Restricted` no implementado) | — |
| `category` | `Met` | — |
| `severity` | **`Minor` = SIN AVISO** · `Moderate` = amarillo · `Severe` = naranja · **`Extreme` = rojo** | ✅ Confirma el mapeo completo |
| `responseType` | `Monitor` · `None` (solo en los `Minor`) | — |
| `urgency` | `Immediate` · `Expected` (dentro de la hora siguiente) · `Future` (futuro cercano). `Past` y `Unknown` **no implementados** | — |
| `certainty` | `Observed` (**ya observado**) · `Likely` (p ≥ ~50 %) · `Possible` (p < ~50 %). `Unlikely` y `Unknown` **no implementados** | — |
| `language` | `es-ES` · `en-GB` | Un bloque `<info>` por idioma |
| Probabilidad | Solo **3 valores**: `10%-40%`, `40%-70%`, `mayor 70%` | — |

#### ⚠️ `eventCode` y `parameter` usan códigos DISTINTOS para lo mismo

Es la trampa más peligrosa del formato. **Hay dos vocabularios de códigos y no coinciden**:

| Fenómeno (`eventCode`) | Parámetro (`parameter`) | Unidad | Significado |
|---|---|---|---|
| `PR` Lluvias | `P1` | mm | Precipitación acumulada en 1 hora |
| `PR` Lluvias | `P2` | mm | Precipitación acumulada en 12 horas |
| `NE` Nevadas | `NV` | cm | Acumulación de nieve en 24 h |
| **`VI` Vientos** | **`RM`** | km/h | Rachas máximas |
| `TO` Tormentas | `TO` | — | Tormentas |
| **`AT` Temperaturas máximas** | **`TA`** | °C | Temperatura máxima |
| **`BT` Temperaturas mínimas** | **`TI`** | °C | Temperatura mínima |
| `CO` Costeros | `CO` | — | Costeros |
| **`VS` Polvo en suspensión** | **`VI`** | m | Visibilidad |
| `AL` Aludes | `AL` | — | Nivel de peligro de aludes |
| `GA` Galernas | `GA` | — | Galerna |
| `RI` Rissagues | `RI` | m | Oscilación del nivel del mar |
| `NI` Nieblas | `NI` | m | Visibilidad |
| `DH` Deshielos | `DH` | — | Deshielos |

> [!CAUTION]
> **`VI` significa dos cosas opuestas según la etiqueta**: en `eventCode` es **Vientos**, en
> `parameter` es **Visibilidad** (del polvo en suspensión). Y `RM`, `TA`, `TI` son códigos de
> parámetro que **no existen** como fenómeno. **No uses una sola tabla de códigos para las dos
> etiquetas.**
>
> Esto explica los 6 códigos que observamos (`P1`, `P2`, `TA`, `TO`, `RM`, `RI`): eran **códigos de
> parámetro**, no de fenómeno.

#### El mecanismo de retirada de avisos 🔵

`Cancel` **no está implementado**. Cuando AEMET retira un aviso sin sustituirlo:

> emite un mensaje **de nivel amarillo** para esa zona y fenómeno, **cuyo `expires` coincide con el
> momento de envío** (`expires` = `effective` = `sent`, y `onset` un segundo antes), con los
> identificadores retirados en `references`.

⚠️ **Es un aviso que nace caducado.** Si filtras por `expires > ahora` lo descartas
automáticamente — que es lo correcto. Pero si te fías de `msgType` verás un `Update` amarillo y
podrías mostrarlo. **Filtra siempre por `expires`.**

#### Los mensajes `Minor` agrupan varias zonas 🔵

> Los mensajes de nivel amarillo, naranja o rojo corresponden a **una única zona**. Los mensajes sin
> aviso (`severity = Minor`) **pueden contener varias zonas**, una por cada zona de la comunidad
> autónoma donde ese fenómeno esté definido.

✅ Explica lo observado: 364 `<area>` en 112 `<info>`. **Los avisos reales son 1 zona; los verdes son
el resto.** Otra razón más para descartar los `Minor` de entrada.

#### Nomenclatura de ficheros — descifrada por completo 🔵

**El tar:**
```
Z_CAP_C_LEMM_AAAAMMDDHHMMSS_AFAE.tar.gz
             └── emisión UTC ──┘  └─ "Avisos de Fenómenos Adversos de España"
```

**Cada XML dentro:**
```
Z_CAP_C_LEMM_20260825090336_AFAZ 711502 PR P1 2616 .xml
             └─ emisión UTC ─┘    └zona┘ └FF┘└PP┘└DDHH┘
```

| Campo | Significado |
|---|---|
| `LEMM` | Indicativo OMM de **Madrid** |
| `AFAZ` | "Aviso de Fenómeno Adverso en una Zona" |
| `zzzzzz` | **Código de zona** (6 caracteres) |
| `FF` | Código de **fenómeno** (`eventCode`) |
| `PP` | Código de **parámetro** (`parameter`) |
| `DDHH` | ✅ **Día y hora de FINALIZACIÓN del aviso** — resuelve el `2616` que no identificábamos: día 26, hora 16 |

⚠️ En los mensajes sin aviso, `zzzzzz` **no es una zona**: se construye como
**CCAA(2) + `VV` + CCAA(2)** (p. ej. `71VV71`). Un filtro por prefijo de zona los captura por
accidente.

#### Contenido del tar 🔵

> Los nuevos avisos llevan en el nombre la fecha-hora del propio tar. Los anteriores no caducados ni
> actualizados llevan la fecha en que se generaron. **Los caducados y los actualizados se eliminan.**

✅ Es decir: **el tar es el estado completo y vigente**, no un incremento. No hay que acumular
histórico: cada descarga reemplaza.

#### Otros detalles 🔵

| Etiqueta | Especificación |
|---|---|
| `identifier` | `2.49.0.0.724.0` (WMO_ID de España) + `.ES.` + `AAAAMMDDHHMMSS` + `.` + id único |
| `sent` | **UTC** (`-00:00`) |
| `effective` / `onset` / `expires` | ⚠️ **hora oficial LOCAL** (`+01:00` / `+02:00`), no UTC |
| `headline` | Construido como **`<event>.<areaDesc>`** |
| `description` | Parámetro + valor + unidad, más un comentario libre **siempre en español** aunque el bloque sea `en-GB` |
| `instruction` | Depende solo del **nivel**, no del fenómeno |
| `polygon` | **WGS84**, solo `polygon` (nunca `circle`), y **un `<area>` puede tener varios** |
| `geocode` | La zona costera es la no costera **con los mismos 6 primeros caracteres** + `C` |

> ⚠️ **`sent` va en UTC pero `onset` y `expires` en hora local.** Compararlos sin normalizar la zona
> horaria da errores de 1-2 horas.

> ⚠️ **La resolución de los polígonos del CAP es BAJA a propósito**, "para que el tamaño de los
> ficheros no sea muy grande". Para geometría precisa hay que usar el shapefile (ver abajo).

### Los boletines de avisos (Anexo 4) 🔵

`src/documentos/METEOALERTA_ANX4_Boletin_avisos.pdf` — v1, 31-may-2022, 7 páginas.

Los **boletines** son el producto **alternativo al CAP**: agregaciones de texto de los avisos, que
AEMET mantiene "para facilitar la adaptación de los usuarios". **La API OpenData sirve CAP, no
boletines** — este anexo solo hace falta si alguna vez se consumen boletines por otra vía.

#### Tipos de boletín

| Boletín | Ámbito | Niveles |
|---|---|---|
| Autonómico hoy y mañana, amarillo | CCAA | amarillo |
| Autonómico hoy y mañana, rojo/naranja | CCAA | naranja, rojo |
| Nacional hoy y mañana, amarillo | España | amarillo |
| Nacional D+2, rojo/naranja | España | naranja, rojo |
| Nacional D+3 (avance), rojo/naranja | España | naranja, rojo |
| Aviso especial | variable | — |

#### Estructura

Cabecera (`AGENCIA ESTATAL DE METEOROLOGÍA` / tipo / CCAA / número / emitido / válido hasta) y luego
dos bloques opcionales: **FENÓMENOS OBSERVADOS** y **FENÓMENOS PREVISTOS**. Cada fenómeno lleva
`Nivel`, `Ámbito geográfico`, `Hora de comienzo`, `Hora de finalización`, `Probabilidad` y
`Comentario`.

⚠️ **En los observados, `Hora de comienzo` es la cadena literal `«en curso»`**, no una hora. Y llevan
`Evolución/Comentario` como **texto obligatorio**.

#### Numeración e identificadores

Correlativos, **reiniciando cada año**, seguidos de `/` y una clave de ámbito.
⚠️ **Andalucía se parte en dos**: `61ANC` (Occidental) y `61ANR` (Oriental).

| Clave | Ámbito | | Clave | Ámbito |
|---|---|---|---|---|
| `61ANC` / `61ANR` | Andalucía Occidental / Oriental | | `72MAM` | Madrid |
| `62ARA` | Aragón | | `73MUM` | Murcia |
| `63ASA` | Asturias | | `74NAN` | Navarra |
| `64IBB` | Illes Balears | | `75PVA` | País Vasco |
| `65CCS` | Canarias | | `76RIR` | La Rioja |
| `66CAN` | Cantabria | | `77VAL` | C. Valenciana |
| `67CLE` | Castilla y León | | `78CEU` | Ceuta |
| `68CMA` | Castilla-La Mancha | | `79MEL` | Melilla |
| `69CTA` | Cataluña | | `ECA` | España hoy/mañana amarillo |
| `70EXT` | Extremadura | | `EMP` | España pasado mañana |
| `71GAL` | Galicia | | `ESP` | España avance |

Cabeceras OMM del tipo `WOSP70 LECR GALICIA`, donde la cifra codifica nivel y alcance:

| Cabecera | Significado |
|---|---|
| `WOSP6x` + centro | Amarillo autonómico |
| `WOSP70 LEMM` | Amarillo nacional |
| `WOSP7x` + centro | Rojo/naranja autonómico |
| `WOSP80 LEMM` | Nacional D+2 |
| `WOSP90 LEMM` | Nacional avance D+3 |
| `WOSP40 LEMM` | Aviso especial |

#### Reglas de emisión útiles 🔵

- **Si el boletín de la mañana no necesita cambios, no se emite otro por la noche.** El vigente sigue
  valiendo.
- **No se emite boletín al terminar un episodio**: la validez ya viene dentro del boletín.
- Si se cancela anticipadamente y desaparecen todos los fenómenos, se emite uno con el texto
  `«Se cancelan los avisos amarillos, o rojos y/o naranjas para este período de validez»`.
- Los avisos amarillos de tormenta llevan **siempre** un texto fijo advirtiendo de que puede haber
  tormentas de intensidad superior de forma puntual.

### El diccionario de campos, según los metadatos 🟢

Los metadatos del endpoint declaran:

| Campo | Valor |
|---|---|
| `formato` | **`application/x-gtar (contiene ficheros CAP v1.2)`** — AEMET **sí** documenta aquí el formato tar, aunque la especificación diga `application/json` |
| `campos` | ⚠️ No es un array de campos, sino **una cadena de texto**: `"Anexo 3 del Plan Meteoalerta (https://www.aemet.es/documentos/es/eltiempo/prediccion/avisos/plan_meteoalerta/plan_meteoalerta.pdf)"` |

Es decir: **para los avisos, AEMET no da diccionario de campos en la API** y remite al
**Anexo 3 del Plan Meteoalerta**. El cuerpo del Plan sí se ha descargado y contrastado (ver arriba);
🔴 el Anexo 3 no se ha localizado.

⚠️ Nótese la inconsistencia de tipo: `campos` es un **array de objetos** en todos los demás productos
y una **cadena** en este. Código que itere `campos[]` esperando objetos recorrerá los caracteres uno
a uno. Ver [`ERRATAS.md` E31](ERRATAS.md#e31-el-campo-campos-de-los-metadatos-cambia-de-tipo).

### ⚠️ Cada XML trae DOS bloques `<info>`: `es-ES` y `en-GB` 🟢

Verificado: 56 ficheros → **112 bloques `<info>`**, exactamente la mitad en cada idioma.

**Si recorres todos los `<info>` obtienes cada aviso duplicado.** Hay que **filtrar por
`<language>es-ES`** (o quedarte con el primero). Nada en la documentación de AEMET lo menciona.

### Campos que importan 🟢

| Campo | Uso |
|---|---|
| `status` | `Actual` = aviso real. 🟡 Puede haber `Test` o `Exercise`: **filtrarlos** antes de publicar |
| `msgType` | `Alert`, `Update`, `Cancel`. ⚠️ Un `Cancel` **anula** un aviso previo: hay que procesarlo |
| `references` | Identificador del aviso al que sustituye. Necesario para no mostrar avisos superados |
| `onset` / `expires` | Ventana de vigencia. **Filtrar por `expires`** para no mostrar caducados |
| `severity` | `Minor`, `Moderate`, `Severe`, `Extreme` — correlaciona con el nivel de color (ver abajo) |
| `language` | **`es-ES` o `en-GB`** — filtrar para no duplicar |
| `headline`, `description`, `instruction` | Textos listos para mostrar |
| `identifier` | Identificador único, para deduplicar |

### Los parámetros propietarios `AEMET-Meteoalerta` 🟢

Dentro de cada `<info>`, en bloques `<parameter>`. **Los `valueName` son literales largos**, no
nombres cortos:

```xml
<parameter>
  <valueName>AEMET-Meteoalerta nivel</valueName>
  <value>amarillo</value>
</parameter>
<parameter>
  <valueName>AEMET-Meteoalerta parametro</valueName>
  <value>P1;Precipitación acumulada en una hora;15 mm</value>
</parameter>
<parameter>
  <valueName>AEMET-Meteoalerta probabilidad</valueName>
  <value>40%-70%</value>
</parameter>
```

| `valueName` exacto | Valores observados 🟢 |
|---|---|
| `AEMET-Meteoalerta nivel` | `verde` (20), `amarillo` (92) |
| `AEMET-Meteoalerta parametro` | Formato **`código;descripción;umbral`**. Ver la tabla de fenómenos abajo |
| `AEMET-Meteoalerta probabilidad` | `40%-70%` (86), `10%-40%` (6) |

⚠️ **El `valueName` NO es `Nivel` ni `Probabilidad`.** Documentación de terceros que use esos nombres
cortos **no encontrará nunca los valores**. El literal completo (con el prefijo `AEMET-Meteoalerta `
y en minúscula) es obligatorio.

> [!IMPORTANT]
> ### Hay que descartar el nivel `verde` 🟢
>
> **20 de los 112 bloques `info` tienen `nivel: verde`.** Son avisos rutinarios de *ausencia* de
> riesgo, no alertas. Publicarlos satura la interfaz con "warnings" que no avisan de nada.
>
> Dos formas de filtrarlos, ambas verificadas:
>
> | Vía | Cómo |
> |---|---|
> | Parámetro propietario | descartar `AEMET-Meteoalerta nivel` = `verde` |
> | **Estándar CAP** (preferible 🟡) | descartar `severity` = `Minor` |
>
> 🟢 **La correlación nivel ↔ severity es exacta**, verificada sobre el paquete nacional completo
> (252 ficheros):
>
> | `AEMET-Meteoalerta nivel` | `severity` CAP | `responseType` | Observados |
> |---|---|---|---|
> | `verde` | `Minor` | **`None`** | 177 |
> | `amarillo` | `Moderate` | `Monitor` | 71 |
> | **`naranja`** | **`Severe`** | `Monitor` | **4** |
> | `rojo` | 🟡 `Extreme` | 🟡 `Monitor` | 0 — sin observar |
>
> **Tres formas equivalentes de filtrar los verdes**, todas verificadas: descartar
> `nivel == "verde"`, descartar `severity == "Minor"`, o descartar **`responseType == "None"`**.
> 🟡 La más limpia es `severity`: es del estándar CAP, no una extensión de AEMET.
>
> 🟢 Dato adicional: **los avisos `verde` no traen `parametro`, `probabilidad`, `description` ni
> `instruction`**. Si esperas esos campos, el código falla justo en los que hay que descartar.

### Los 6 códigos de fenómeno observados 🟢

Del paquete nacional completo (`area/esp`, 252 ficheros):

| Código | Fenómeno | Umbral del aviso |
|---|---|---|
| `P1` | Precipitación acumulada en una hora | 15 mm |
| `P2` | Precipitación acumulada en 12 horas | 40 mm |
| `TA` | Temperatura máxima | 34 ºC |
| `TO` | Tormentas | *(sin umbral numérico)* |
| `RM` | Rachas máximas | 80 km/h |
| `RI` | Oscilación del nivel del mar | 1,5 m |

⚠️ **El umbral varía por zona y época**: el que aparece es el aplicable a ese aviso concreto, no una
constante. Los umbrales oficiales por zona están en el Anexo 3 del Plan Meteoalerta.

🟡 Existen más fenómenos de los que aparecen en un día concreto. Los `event` observados delatan al
menos: **lluvias, tormentas, temperaturas máximas, temperaturas mínimas, nevadas, nieblas, vientos,
polvo en suspensión** y **costeros**. 🔴 Sus códigos no se han observado todos (`TO` cubre tormentas,
`TA` máximas; los de nieve, niebla, viento y polvo no aparecieron con código en esta muestra).

### Otros campos CAP verificados 🟢

| Campo | Valores observados | Uso |
|---|---|---|
| `responseType` | `Monitor` (75) · **`None` (177)** | `None` ⇔ nivel verde. Vía alternativa de filtrado |
| `urgency` | `Future` (247) · `Immediate` (5) | `Immediate` = el fenómeno ya está ocurriendo |
| `certainty` | `Likely` (249) · `Possible` (3) | Confianza en la previsión |
| `event` | `"Aviso de lluvias de nivel amarillo"`, … | **Ya incluye el nivel en el texto**, listo para mostrar |

🟡 `event` es la cadena más útil para una interfaz: trae fenómeno y nivel redactados en español.

### ✅ Cruzar avisos con un municipio — RESUELTO 🟢

Los avisos van por **zona**, no por municipio. El puente está confirmado:

```xml
<area>
  <areaDesc>Oeste de A Coruña</areaDesc>
  <geocode>
    <valueName>AEMET-Meteoalerta zona</valueName>
    <value>711502</value>
  </geocode>
</area>
```

**El código de zona son 6 dígitos con esta estructura** 🟢:

```
   71     15     02
   └┬┘    └┬┘    └┬┘
  CCAA  provincia comarca
        (INE)
```

Verificado en las 22 zonas del paquete de Galicia: `7115xx` (A Coruña), `7127xx` (Lugo),
`7132xx` (Ourense), `7136xx` (Pontevedra). Los prefijos de CCAA coinciden con los
[códigos de área de avisos](10-catalogos-de-codigos.md#áreas-para-avisos) y los de provincia con los
[códigos INE](10-catalogos-de-codigos.md#provincias-e-islas).

**Y encaja exactamente con el `zona_comarcal` del maestro de municipios** 🟢: Vigo devuelve
`zona_comarcal: "713601"`, que es una de las 22 zonas del paquete. Ese es el enlace
municipio → avisos que aplican.

⚠️ **Existe un sufijo `C` para zonas costeras** 🟢: `711501C`, `713601C`, `712701C`… (6 de las 22).
Los `areaDesc` correspondientes empiezan por `"Costa - "` (`Costa - Rias Baixas`,
`Costa - Oeste de A Coruña`). **Un filtro por prefijo de 4 dígitos los incluye**; si solo quieres
avisos terrestres, hay que excluir los que acaban en `C`.

| Filtro | Qué obtienes |
|---|---|
| `zona.startsWith("7136")` | Todos los avisos de Pontevedra, costeros incluidos |
| `zona == "713601"` | Solo la comarca exacta del municipio |
| `zona.startsWith("7136") && !zona.endsWith("C")` | Solo zonas terrestres de Pontevedra |

🟡 Para un municipio concreto, lo correcto es filtrar por su `zona_comarcal` exacta **más** la
variante costera si el municipio es costero.

⚠️ **`valueName` es `AEMET-Meteoalerta zona`, no `EMMA_ID`.** Documentación de terceros que busque
`EMMA_ID` no encontrará nada.

### 🎯 Cada `<area>` trae su POLÍGONO geográfico 🟢

**Presente en las 364 áreas de la muestra, sin excepción.** No está documentado en ninguna fuente de
AEMET.

```xml
<area>
  <areaDesc>Oeste de A Coruña</areaDesc>
  <polygon>43.18,-9.08 43.15,-9.01 43.18,-8.98 43.14,-8.85 … 43.18,-9.08</polygon>
  <geocode>
    <valueName>AEMET-Meteoalerta zona</valueName>
    <value>711502</value>
  </geocode>
</area>
```

| Detalle 🟢 | Valor |
|---|---|
| Formato | Pares `latitud,longitud` separados por **espacios** |
| Orden | **`lat,lon`** — al revés que GeoJSON, que usa `lon,lat` |
| Puntos | 31 en el ejemplo; varía por zona |
| Cierre | **Cerrado**: el primer punto coincide con el último |

**Esto permite resolver "¿me afecta este aviso?" con un point-in-polygon** sobre las coordenadas
exactas de una localización, en vez de depender del código de zona. Es bastante más preciso:

| Método | Precisión | Coste |
|---|---|---|
| Prefijo de zona (`7136`) | Provincia entera | Trivial |
| Zona exacta (`713601`) | Comarca | Trivial, requiere el `zona_comarcal` del maestro |
| **Point-in-polygon** | **Coordenada exacta** | Requiere las coordenadas del punto y un algoritmo de geometría |

🟡 Para varias localizaciones fijas, lo práctico es resolver una vez a qué zonas pertenece cada una
y guardarlo; el polígono sirve para hacer esa resolución bien.

⚠️ **Convierte el orden si vas a usar GeoJSON o una librería GIS**: `43.18,-9.08` es
latitud 43,18 / longitud −9,08, y GeoJSON espera `[-9.08, 43.18]`.

### ⚠️ Cada `<info>` tiene VARIAS `<area>`, no una 🟢

364 áreas repartidas en 112 bloques `<info>` → **una media de 3,25 áreas por aviso**. Un mismo aviso
cubre varias comarcas.

Código que haga `info.find('area')` (en singular) **procesa solo la primera y descarta el resto**.
Hay que iterar todas.

### Todos los hijos de `<info>` 🟢

Recuento sobre los 112 bloques de la muestra:

| Elemento | Apariciones | Nota |
|---|---|---|
| `area` | 364 | **varias por aviso** |
| `parameter` | 296 | 3 en los `amarillo`, 1 en los `verde` |
| `language`, `category`, `event`, `responseType`, `urgency`, `severity`, `certainty`, `eventCode`, `effective`, `onset`, `expires`, `senderName`, `headline`, `web`, `contact` | 112 | uno por bloque |
| **`description`** | **92** | ⚠️ **ausente en los 20 `verde`** |
| **`instruction`** | **92** | ⚠️ **ausente en los 20 `verde`** |

⚠️ `description` e `instruction` **solo existen en los avisos con nivel real**. Código que los lea
sin comprobar falla justo en los que hay que descartar.

### `areaDesc`: nombres de comarca 🟢

22 zonas distintas en Galicia, con nombres legibles listos para mostrar: `A Mariña`,
`Centro de Lugo`, `Interior de A Coruña`, `Rias Baixas`, `Costa - Miño de Pontevedra`… Es el texto
que conviene enseñar al usuario en vez del código numérico.

### Códigos de área 🟢

`esp` (España) y códigos numéricos `61`–`79` por comunidad. Tabla completa en
[`10-catalogos-de-codigos.md`](10-catalogos-de-codigos.md#áreas-para-avisos).

⚠️ **Son códigos distintos** de los de CCAA en texto (`gal`, `and`…) y de los de provincia. Galicia
es `71` aquí y `gal` allí.

🟡 Pedir `esp` trae 3,4 MB. Con un ámbito regional conviene pedir solo esa comunidad.

---

## Archivo de avisos 🟢

```
GET /api/avisos_cap/archivo/fechaini/{fechaIniStr}/fechafin/{fechaFinStr}
```

| | |
|---|---|
| Formato de fecha | `AAAA-MM-DDTHH:MM:SSUTC`, con los `:` como `%3A` |
| Datos disponibles 🔵 | **desde el 18/06/2018** |
| Formato de respuesta 🟢 | **`tar` sin comprimir**, igual que el de último elaborado |
| Tamaño 🟢 | **3,8 MB para UN solo día** (`2026-08-20`) |

> [!WARNING]
> 🔵 AEMET publicó el 14/05/2026 un aviso de **"laguna en la disponibilidad de los datos"** en los
> productos de avisos, "que se irá rellenando próximamente". No dar por completo el histórico.

⚠️ **No acepta área**: devuelve el rango completo para toda España. 🟢 Medido: **3,8 MB por un día**.
🟡 Extrapolando, un mes serían ~114 MB y un año ~1,4 GB. **Trocea por días** y no pidas rangos
amplios.

---

## Índices de riesgo de incendios forestales 🟢

```
GET /api/incendios/mapasriesgo/estimado/area/{area}
GET /api/incendios/mapasriesgo/previsto/dia/{dia}/area/{area}
```

🟢 Verificado el 2026-08-26:

| Endpoint | Resultado |
|---|---|
| `previsto/dia/1/area/p` | ✅ **PNG de 501,4 KB** (`image/png`, sin charset) |
| `estimado/area/p` | ⚠️ **`estado: 404`**, `"No hay datos que satisfagan esos criterios"` |

⚠️ **El `estimado` no devuelve datos** — y se comprobó en agosto, plena temporada de incendios.
🔴 Sin saber si es permanente. **Usa `previsto/dia/1` como sustituto del día actual.**

Nota: es el **único PNG** de la API; el resto de imágenes son GIF
([`08-imagenes-y-mapas.md`](08-imagenes-y-mapas.md)).

`{area}` — solo dos valores:

| Código | Área |
|---|---|
| `p` | Península y Baleares |
| `c` | Canarias |

⚠️ Tercer dominio distinto para el parámetro `area`, incompatible con los otros
([`ERRATAS.md` C3](ERRATAS.md#c3-area-reutiliza-el-mismo-nombre-para-5-dominios-incompatibles-)).

`{dia}` (solo en `previsto`): `1` (mañana) a `7`. ⚠️ Aquí **no existe el `0`**: el día actual se
obtiene con el endpoint `estimado`. Otra escala distinta de `dia`.

| Endpoint | Qué da 🔵 | Estado 🟢 |
|---|---|---|
| `estimado` | Último mapa de riesgo **estimado** (situación actual) | ⚠️ sin datos |
| `previsto` | Mapa de riesgo **previsto** para el día `1`–`7` | ✅ PNG |

---

## Pendiente de verificar

**Cobertura: 4 de 4 ✅.**

| # | Qué | Prioridad |
|---|---|---|
| 1 | ✅ *Resuelto*: cruce `zona_comarcal` ↔ `geocode` confirmado | — |
| 2 | ✅ *Resuelto*: nomenclatura completa de ficheros, incluido el `DDHH` de finalización | — |
| 3 | ✅ *Resuelto*: `rojo` → `Extreme` confirmado por la especificación (Anexo 3) | — |
| 10 | ✅ *Resuelto*: los **13 códigos de fenómeno y 14 de parámetro** están en el Anexo 3 | — |
| 8 | Si el `polygon` es siempre un anillo simple o puede traer varios | Media si se usa point-in-polygon |
| 4 | ✅ *Resuelto*: `status` solo admite `Actual` y `Test`. **Filtrar `Test`** | — |
| 5 | Si `incendios/mapasriesgo/estimado` está roto de forma permanente | Media |
| 6 | ✅ *Resuelto*: [las 233 zonas](14-zonas-de-aviso.md) desde los shapefiles oficiales | — |
| 9 | Transcribir las ~4.200 cifras de umbrales por zona del Anexo 1 (tablas en imagen) | Baja — requiere OCR verificado; el aviso ya trae el nivel calculado |
| 7 | Rango máximo aceptado por el archivo de avisos (medido: 3,8 MB por día) | Media |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
