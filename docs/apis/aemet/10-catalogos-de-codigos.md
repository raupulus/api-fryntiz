# 🗂️ Catálogos de códigos

Todas las tablas de códigos de AEMET OpenData en un solo sitio, **con las erratas de la
especificación corregidas y señaladas**.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` — **todas las tablas de códigos salen de las descripciones markdown embutidas en el campo `description` de cada parámetro**, con sus erratas corregidas y señaladas · `src/documentos/AEMET-meteoalerta-delimitacion-zonas.zip` (zonas de aviso) · `src/catalogos/Playas_codigos.csv` y `src/catalogos/diccionario_municipios_INE.xlsx` (catálogos externos) · **verificación en vivo del 2026-08-26** (códigos de isla probados uno a uno).

---

## ⚠️ Cuatro reglas antes de usar cualquier tabla

1. **Todos los códigos son cadenas, nunca números.** `01` (Álava), `09` (Campisábalos), `071`
   (Menorca): convertirlos a entero destruye el cero inicial.
2. **`area` y `dia` significan cosas distintas según el endpoint.** Cinco dominios incompatibles para
   `area`, cuatro escalas para `dia`. **No los modeles como un tipo único**
   ([`ERRATAS.md` C3 y C4](ERRATAS.md#c3-area-reutiliza-el-mismo-nombre-para-5-dominios-incompatibles-)).
3. **Ningún parámetro declara `enum` en la especificación.** Los 39 son `type: string` libre; estos
   códigos vienen de tablas markdown embutidas en las descripciones. Ningún generador de clientes
   los valida ([`ERRATAS.md` C10](ERRATAS.md#c10-ningún-parámetro-declara-enum-)).
4. **La misma región tiene códigos distintos según el producto.** Galicia es `gal` en predicciones de
   texto, `71` en avisos y `40` en marítima costera. **Nunca reutilices un código entre grupos.**

### Galicia como ejemplo de la regla 4

| Producto | Código |
|---|---|
| Predicción CCAA en texto | `gal` |
| Avisos CAP | `71` |
| Marítima costera | `40` |
| Ámbito de mapas significativos | `gal` |
| Provincia (Pontevedra) | `36` |
| Municipio (Vigo) | `36057` / `id36057` |
| Radar (A Coruña) | `co` |

---

## CCAA (predicciones en texto)

Parámetro `{ccaa}` — 17 códigos. Usado en 8 endpoints de
[`02-predicciones-texto.md`](02-predicciones-texto.md).

| Código | CCAA | | Código | CCAA |
|---|---|---|---|---|
| `and` | Andalucía | | `ext` | Extremadura |
| `arn` | Aragón | | `gal` | Galicia |
| `ast` | Asturias ⚠️ | | `mad` | Madrid, Comunidad de |
| `bal` | Illes Balears | | `mur` | Murcia, Región de |
| `can` | Cantabria | | `nav` | Navarra, Comunidad Foral de |
| `cat` | Cataluña | | `pva` | País Vasco |
| `cle` | Castilla y León | | `rio` | Rioja, La |
| `clm` | Castilla - La Mancha | | `val` | Comunitat Valenciana |
| `coo` | Canarias | | | |

> ⚠️ **La especificación escribe "Astrrias"** en 7 de los 8 endpoints. El código `ast` es correcto;
> solo la etiqueta está mal ([`ERRATAS.md` C1](ERRATAS.md#c1-ccaa-asturias-está-escrito-astrrias-)).
>
> ⚠️ `coo` para Canarias no sigue ninguna lógica evidente (no es `can`, que es Cantabria).
> ⚠️ El spec escribe "Ballears, Illes"; el nombre oficial es "Illes Balears".
>
> **No incluye Ceuta ni Melilla.** Sí aparecen en los códigos de avisos (`78`, `79`).

---

## Ámbitos para mapas significativos

Parámetro `{ambito}` — 18 códigos: los 17 de CCAA **más `esp`**. Un único endpoint, en
[`08-imagenes-y-mapas.md`](08-imagenes-y-mapas.md).

| Código | Ámbito |
|---|---|
| `esp` | **España** (solo disponible aquí) |
| *resto* | Idénticos a la tabla de CCAA de arriba |

⚠️ En esta tabla el spec **sí** escribe "Asturias" correctamente. Mismas letras, dominio distinto.

---

## Provincias e islas

Parámetro `{provincia}` — **59 códigos**. Usado en 4 endpoints de
[`02-predicciones-texto.md`](02-predicciones-texto.md).

| Código | Provincia | | Código | Provincia |
|---|---|---|---|---|
| `01` | Araba/Álava ⚠️ | | `28` | Madrid |
| `02` | Albacete | | `29` | Málaga |
| `03` | Alacant/Alicante | | `30` | Murcia |
| `04` | Almería | | `31` | Navarra |
| `05` | Ávila | | `32` | Ourense |
| `06` | Badajoz | | `33` | Asturias |
| `08` | Barcelona | | `34` | Palencia |
| `09` | Burgos | | `36` | Pontevedra |
| `10` | Cáceres | | `37` | Salamanca |
| `11` | Cádiz | | `39` | Cantabria |
| `12` | Castelló/Castellón | | `40` | Segovia |
| `13` | Ciudad Real | | `41` | Sevilla |
| `14` | Córdoba | | `42` | Soria |
| `15` | A Coruña | | `43` | Tarragona |
| `16` | Cuenca | | `44` | Teruel |
| `17` | Girona | | `45` | Toledo |
| `18` | Granada | | `46` | València/Valencia |
| `19` | Guadalajara | | `47` | Valladolid |
| `20` | Gipuzkoa | | `48` | Bizkaia |
| `21` | Huelva | | `49` | Zamora |
| `22` | Huesca | | `50` | Zaragoza |
| `23` | Jaén | | `51` | Ceuta |
| `24` | León | | `52` | Melilla |
| `25` | Lleida | | `26` | La Rioja |
| `27` | Lugo | | | |

### Islas (códigos de 3 dígitos)

| Código | Isla | | Código | Isla |
|---|---|---|---|---|
| `071` | Isla de Menorca | | `353` | Isla de Gran Canaria |
| `072` | Isla de Mallorca | | `381` | Isla de Tenerife |
| `073` | Islas de Ibiza y Formentera | | `382` | Isla de La Gomera |
| `351` | Isla de Lanzarote | | `383` | Isla de La Palma |
| `352` | Isla de Fuerteventura | | `384` | Isla de El Hierro |

> ⚠️ **El spec lista el código `01` dos veces**: "Araba/Álaba" (errata) y "Araba/Álava" (correcto).
> 60 filas para 59 códigos ([`ERRATAS.md` C2](ERRATAS.md#c2-provincia-el-código-01-aparece-duplicado-)).
>
> 🟢 **Verificado el 2026-08-26**: `07` (Illes Balears) y `35` (Las Palmas) devuelven
> `estado: 404`; `071` (Menorca) y `353` (Gran Canaria) devuelven `200`.
> **Baleares y Canarias se desglosan por isla con códigos de 3 dígitos** y AEMET **no acepta** los
> códigos provinciales del INE. Cualquier tabla que diga "Baleares = 07" es incorrecta para este
> endpoint.
>
> ⚠️ **Mezcla de 2 y 3 dígitos.** Tratar siempre como cadena.

---

## Áreas para avisos

Parámetro `{area}` en `avisos_cap` — 20 valores. Ver [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md).

| Código | Área | | Código | Área |
|---|---|---|---|---|
| `esp` | **España** (3,4 MB 🟢) | | `70` | Extremadura |
| `61` | Andalucía | | `71` | Galicia 🟢 |
| `62` | Aragón | | `72` | Madrid, Comunidad de |
| `63` | Asturias, Principado de | | `73` | Murcia, Región de |
| `64` | Illes Balears | | `74` | Navarra, Comunidad Foral de |
| `65` | Canarias | | `75` | País Vasco |
| `66` | Cantabria | | `76` | Rioja, La |
| `67` | Castilla y León | | `77` | Comunitat Valenciana |
| `68` | Castilla - La Mancha | | `78` | **Ceuta** |
| `69` | Cataluña | | `79` | **Melilla** |

> ⚠️ **Códigos numéricos, no las letras de CCAA.** Galicia es `71` aquí y `gal` en las predicciones
> de texto.
>
> ⚠️ La numeración **no es alfabética ni geográfica**: `77` (Comunitat Valenciana) va después de
> `76` (La Rioja), y Ceuta y Melilla (`78`, `79`) se añadieron al final. No la deduzcas.
>
> ✅ A diferencia de `{ccaa}`, **sí incluye Ceuta y Melilla**.

---

## Costas marítimas

Parámetro `{costa}` — 8 códigos. Ver [`07-maritima.md`](07-maritima.md).

| Código | Área costera |
|---|---|
| `40` | Costa de Galicia 🟢 |
| `41` | Costa de Asturias, Cantabria y País Vasco |
| `42` | Costa de Andalucía Occidental y Ceuta |
| `43` | Costa de las Islas Canarias |
| `44` | Costa de Illes Balears |
| `45` | Costa de Cataluña |
| `46` | Costa de Valencia y Murcia |
| `47` | Costa de Andalucía Oriental y Melilla |

⚠️ Rango `40`–`47`. **Se solapan con los códigos de provincia** (`40` es Segovia como provincia y
Galicia como costa) y con los de avisos. Nunca los intercambies.

---

## Áreas de alta mar

Parámetro `{area}` en marítima de alta mar — 3 códigos.

| Código | Área |
|---|---|
| `0` | Océano Atlántico al sur de 35º N |
| `1` | Océano Atlántico al norte de 30º N 🟢 |
| `2` | Mar Mediterráneo |

🟡 Las áreas `0` y `1` **se solapan** entre 30º N y 35º N: no son una partición del Atlántico.

---

## Áreas montañosas

Parámetro `{area}` en montaña — 9 códigos. Ver [`03-predicciones-especificas.md`](03-predicciones-especificas.md).

| Código | Área |
|---|---|
| `peu1` | Picos de Europa |
| `nav1` | Pirineo Navarro |
| `arn1` | Pirineo Aragonés |
| `arn2` | Ibérica Aragonesa |
| `cat1` | Pirineo Catalán |
| `rio1` | Ibérica Riojana |
| `mad2` | Sierras de Guadarrama y Somosierra |
| `gre1` | Sierra de Gredos |
| `nev1` | Sierra Nevada |

⚠️ El sufijo numérico distingue áreas de la misma comunidad (`arn1` Pirineo vs `arn2` Ibérica). No
existen `mad1` ni `gre2`: **la secuencia tiene huecos.**

---

## Áreas nivológicas

Parámetro `{area}` en nivológica — 2 códigos.

| Código | Área |
|---|---|
| `0` | Pirineo Catalán |
| `1` | Pirineo Navarro y Aragonés |

⚠️ **Mismos valores `0` y `1` que las áreas de alta mar, con significados sin relación.** Es el caso
más claro de por qué `area` no puede ser un tipo compartido.

---

## Áreas de incendios

Parámetro `{area}` en índices de incendios — 2 códigos.

| Código | Área |
|---|---|
| `p` | Península y Baleares |
| `c` | Canarias |

---

## Escalas del parámetro `dia`

**Cuatro escalas incompatibles** con el mismo nombre de parámetro.

| Endpoint | Valores | Semántica |
|---|---|---|
| `/api/prediccion/especifica/uvi/{dia}` | `0`–`4` | `0` = hoy |
| `/api/prediccion/especifica/montaña/…/dia/{dia}` | `0`–`3` | `0` = hoy |
| `/api/incendios/mapasriesgo/previsto/dia/{dia}/…` | `1`–`7` | **`1` = mañana** (no hay `0`) |
| `/api/mapasygraficos/mapassignificativos/…/{dia}` | `a`–`f` | Tramos de 12 h |

⚠️ **Un `1` significa "mañana" en incendios y "mañana" en UVI, pero en incendios no existe el `0`**
porque el día actual se pide a otro endpoint. Revisa siempre la tabla del endpoint concreto.

### Tramos de mapas significativos

| Código | Tramo | | Código | Tramo |
|---|---|---|---|---|
| `a` | D+0 (00-12) | | `d` | D+1 (12-24) |
| `b` | D+0 (12-24) | | `e` | D+2 (00-12) |
| `c` | D+1 (00-12) | | `f` | D+2 (12-24) |

---

## Radares

Parámetro `{radar}` — 15 códigos de 2 letras. Ver [`08-imagenes-y-mapas.md`](08-imagenes-y-mapas.md).

| Código | Radar | | Código | Radar |
|---|---|---|---|---|
| `am` | Almería | | `ml` | Málaga |
| `ba` | Barcelona | | `mu` | Murcia |
| `ca` | Las Palmas | | `pm` | Illes Balears |
| `cc` | Cáceres | | `sa` | Asturias |
| `co` | A Coruña | | `se` | Sevilla |
| `ma` | Madrid | | `ss` | Vizcaya |
| `va` | Valencia | | `vd` | Palencia |
| `za` | Zaragoza | | | |

⚠️ **Sin regla deducible**: Asturias es `sa`, Vizcaya `ss`, Las Palmas `ca`, Palencia `vd`.

---

## Estaciones EMEP (contaminación de fondo)

Parámetro `{nombre_estacion}` — 13 códigos (que son números, no nombres). Ver
[`09-redes-especiales.md`](09-redes-especiales.md).

| Código | Estación | | Código | Estación |
|---|---|---|---|---|
| `01` | San Pablo de los Montes (Toledo) | | `11` | Barcarrota (Badajoz) |
| `05` | Noia (A Coruña) | | `12` | Zarra (Valencia) |
| `06` | Mahón (Illes Balears) | | `13` | Peñausende (Zamora) |
| `07` | Víznar (Granada) | | `14` | Els Torms (Lleida) |
| `08` | Niembro-Llanes (Asturias) | | `16` | O Saviñao (Lugo) |
| `09` | Campisábalos (Guadalajara) 🟢 | | `17` | Doñana (Huelva) |
| `10` | Cabo de Creus (Girona) | | | |

⚠️ **Faltan `02`, `03`, `04` y `15`.** No iteres la secuencia.

---

## Estaciones de perfil de ozono

Parámetro `{estacion}` — 2 valores, **palabras** en vez de códigos.

| Código | Ubicación |
|---|---|
| `canarias` | Izaña |
| `peninsula` | Madrid |

---

## Estaciones antárticas

Parámetro `{identificacion}` — 4 valores.

| Código | Estación |
|---|---|
| `89064` | Estación Meteorológica Juan Carlos I |
| `89064R` | Estación Radiométrica Juan Carlos I |
| `89064RA` | Estación Radiométrica Juan Carlos I (histórica, hasta 08/03/2007) |
| `89070` | Estación Meteorológica Gabriel de Castilla |

---

## Tipos de mensaje de observación

Parámetro `{tipomensaje}` — 3 valores.

| Código | Mensaje |
|---|---|
| `synop` | Observación de superficie |
| `temp` | Radiosondeos |
| `climat` | Resúmenes climáticos mensuales |

---

## Tipos de estación (capas SHAPE)

Parámetro `{tipoestacion}` — 4 valores.

| Código | Tipo |
|---|---|
| `completas` | Estaciones climatológicas completas |
| `termometricas` | Solo termométricas |
| `pluviometricas` | Solo pluviométricas |
| `automaticas` | Automáticas |

---

## Parámetros de valores extremos

Parámetro `{parametro}` — 3 valores, en **mayúscula**.

| Código | Variable |
|---|---|
| `P` | Precipitación |
| `T` | Temperatura |
| `V` | Viento |

---

## Zonas de aviso meteorológico (`AEMET-Meteoalerta zona`) 🟢

No están en la especificación. Aparecen en el `geocode` de los XML CAP y en el campo
`zona_comarcal` del maestro de municipios. **Son la unidad geográfica de los avisos.**

### Estructura del código 🟢

```
   71     15     02    [C]
   └┬┘    └┬┘    └┬┘    └┬┘
  CCAA  provincia comarca  opcional: zona COSTERA
        (INE)
```

- Los 2 primeros dígitos son el [código de área de avisos](#áreas-para-avisos) (`61`–`79`).
- Los 2 siguientes, el [código INE de provincia](#provincias-e-islas).
- Los 2 últimos, la comarca dentro de la provincia.
- **El sufijo `C` marca zonas costeras** 🟢 (sus `areaDesc` empiezan por `"Costa - "`).

### El catálogo completo: 233 zonas 🟢

**[`14-zonas-de-aviso.md`](14-zonas-de-aviso.md)** — 182 terrestres + 51 costeras, con código, nombre,
provincia y comunidad, extraídas de los shapefiles oficiales de AEMET
(`src/documentos/AEMET-meteoalerta-delimitacion-zonas.zip`).

### Ejemplo: las 22 zonas de Galicia 🟢

| Zona | Provincia | | Zona | Provincia |
|---|---|---|---|---|
| `711501` `711501C` | A Coruña | | `713201` `713202` `713203` | Ourense |
| `711502` `711502C` | A Coruña | | `713204` `713205` | Ourense |
| `711503` | A Coruña | | `713601` `713601C` | Pontevedra |
| `711504` `711504C` | A Coruña | | `713602` | Pontevedra |
| `712701` `712701C` | Lugo | | `713603` `713603C` | Pontevedra |
| `712702` `712703` `712704` | Lugo | | | |

`areaDesc` observados: `A Mariña`, `Centro de Lugo`, `Interior de A Coruña`, `Interior de Pontevedra`,
`Costa - A Mariña`, `Costa - Miño de Pontevedra`, `Costa - Noroeste de A Coruña`,
`Costa - Oeste de A Coruña`, `Costa - Rias Baixas`, `Costa - Suroeste de A Coruña`…

### Cómo obtener la zona de un municipio 🟢

```
GET /api/maestro/municipio/id{codigo_INE}   →   campo "zona_comarcal"
```

Vigo (`id36057`) → `zona_comarcal: "713601"`, que es una de las 22 zonas del paquete de Galicia.
Ver [`04-avisos-y-riesgos.md`](04-avisos-y-riesgos.md#-cruzar-avisos-con-un-municipio--resuelto-).

✅ Catálogo completo en [`14-zonas-de-aviso.md`](14-zonas-de-aviso.md).

---

## Catálogos que NO están en la especificación

Estos códigos no vienen en tablas: hay que obtenerlos de una fuente externa o de la propia API.

| Catálogo | Dónde | Volumen |
|---|---|---|
| **Municipios** | `GET /api/maestro/municipios` (🔴 preferible) · `src/catalogos/diccionario_municipios_INE.xlsx` | >8.000 |
| **Playas** | `src/catalogos/Playas_codigos.csv` 🟢 | **590** |
| **Estaciones (idema)** | `GET /api/valores/climatologicos/inventarioestaciones/todasestaciones` 🔴 | cientos |
| **Zonas de aviso CAP** | ✅ **[Las 233 catalogadas](14-zonas-de-aviso.md)** | 233 |
| **Códigos de `estadoCielo`** | ✅ [`13-iconos-estado-cielo.md`](13-iconos-estado-cielo.md) — 35 códigos | 35 |
| **Códigos `f1`/`f2` de playa** | 🔴 Sin catálogo conocido | ? |
| **Zonas/subzonas marítimas** | 🔴 Sin catálogo. Vienen en el propio payload (`id` + `nombre`) | ? |

### Formato del CSV de playas 🟢

```
ID_PLAYA;NOMBRE_PLAYA;ID_PROVINCIA;NOMBRE_PROVINCIA;ID_MUNICIPIO;NOMBRE_MUNICIPIO;LATITUD;LONGITUD
3605706;Samil;36;Pontevedra;36057;Vigo;42º 13' 12";-08º 46' 20"
```

⚠️ **ISO-8859**, separador `;`, saltos **CRLF**, y coordenadas como **texto en grados/minutos/
segundos** (no decimales). `ID_PLAYA` = 5 dígitos de municipio + 2 de orden.

---

## Pendiente

| # | Qué | Prioridad |
|---|---|---|
| 1 | ✅ *Resuelto*: [las 233 zonas catalogadas](14-zonas-de-aviso.md) | — |
| 2 | ✅ *Resuelto*: [tabla de `estadoCielo`](13-iconos-estado-cielo.md) (12 de 35 descripciones verificadas) | — |
| 3 | Tabla de códigos `f1`/`f2` y `sTermica` de playa | Media |
| 4 | Volcar el catálogo de municipios desde la API | Media |
| 5 | Volcar el inventario de estaciones | Media |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
