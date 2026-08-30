# Catálogo de zonas de aviso meteorológico (Meteoalerta)

Extraído de `src/documentos/AEMET-meteoalerta-delimitacion-zonas.zip` (shapefiles oficiales de AEMET,
**versión 6 del Plan**, febrero de 2018). 🟢 **182 zonas terrestres + 51 costeras = 233.**

⚠️ El Plan vigente es la **v9 (ene-2025)**; este shapefile es de la **v6**. El registro de cambios del
Plan documenta modificaciones de zonas en v6 y v8, así que **puede haber divergencias**. El código de
zona que devuelve la API es la fuente de verdad; esta tabla sirve para resolver nombre y provincia.

| Atributo | Significado |
|---|---|
| `COD_Z` | Código de zona, 6 dígitos (+ `C` final en las costeras) |
| `NOM_Z` | Nombre (las costeras empiezan por `Costa - `) |
| `COD_PROV` | **4 dígitos** = CCAA(2) + provincia INE(2) |
| `COD_CCAA` | 2 dígitos, igual que el código de área de avisos |

Sistema de referencia **EPSG:32630** (UTM 30N), codificación **ISO-8859-15**.
Las zonas costeras llegan hasta **20 millas náuticas**.


> **Fuentes de este archivo:** `src/documentos/AEMET-meteoalerta-delimitacion-zonas.zip` — **extracción directa de los `.dbf` de los dos shapefiles oficiales** (`AEMET-meteoalerta-v6-zonas` y `-zonas-costeras`), más su `Leeme` para el significado de los atributos. Contrastado 🟢 contra el campo `zona_comarcal` de los 8.122 municipios de `GET /api/maestro/municipios`: las 182 zonas terrestres coinciden, sin huérfanas.
---


## `61` Andalucía — 37 zonas

| Código | Zona | Provincia |
|---|---|---|
| `610401` | Valle del Almanzora y Los Vélez | `6104` Almería |
| `610402` | Nacimiento y Campo de Tabernas | `6104` Almería |
| `610403` | Poniente y Almería Capital | `6104` Almería |
| `610403C` | Costa - Poniente y Almería Capital | `6104` Almería |
| `610404` | Levante almeriense | `6104` Almería |
| `610404C` | Costa - Levante almeriense | `6104` Almería |
| `611101` | Grazalema | `6111` Cádiz |
| `611102` | Campiña gaditana | `6111` Cádiz |
| `611103` | Litoral gaditano | `6111` Cádiz |
| `611103C` | Costa - Litoral gaditano | `6111` Cádiz |
| `611104` | Estrecho | `6111` Cádiz |
| `611104C` | Costa - Estrecho | `6111` Cádiz |
| `611401` | Sierra y Pedroches | `6114` Córdoba |
| `611402` | Campiña cordobesa | `6114` Córdoba |
| `611403` | Subbética cordobesa | `6114` Córdoba |
| `611801` | Cuenca del Genil | `6118` Granada |
| `611802` | Guadix y Baza | `6118` Granada |
| `611803` | Nevada y Alpujarras | `6118` Granada |
| `611804` | Costa granadina | `6118` Granada |
| `611804C` | Costa - Costa granadina | `6118` Granada |
| `612101` | Aracena | `6121` Huelva |
| `612102` | Andévalo y Condado | `6121` Huelva |
| `612103` | Litoral de Huelva | `6121` Huelva |
| `612103C` | Costa - Litoral de Huelva | `6121` Huelva |
| `612301` | Morena y Condado | `6123` Jaén |
| `612302` | Cazorla y Segura | `6123` Jaén |
| `612303` | Valle del Guadalquivir de Jaén | `6123` Jaén |
| `612304` | Capital y Montes de Jaén | `6123` Jaén |
| `612901` | Antequera | `6129` Málaga |
| `612902` | Ronda | `6129` Málaga |
| `612903` | Sol y Guadalhorce | `6129` Málaga |
| `612903C` | Costa - Sol y Guadalhorce | `6129` Málaga |
| `612904` | Axarquía | `6129` Málaga |
| `612904C` | Costa - Axarquía | `6129` Málaga |
| `614101` | Sierra norte de Sevilla | `6141` Sevilla |
| `614102` | Campiña sevillana | `6141` Sevilla |
| `614103` | Sierra sur de Sevilla | `6141` Sevilla |

## `62` Aragón — 9 zonas

| Código | Zona | Provincia |
|---|---|---|
| `622201` | Pirineo oscense | `6222` Huesca |
| `622202` | Centro de Huesca | `6222` Huesca |
| `622203` | Sur de Huesca | `6222` Huesca |
| `624401` | Albarracín y Jiloca | `6244` Teruel |
| `624402` | Gúdar y Maestrazgo | `6244` Teruel |
| `624403` | Bajo Aragón de Teruel | `6244` Teruel |
| `625001` | Cinco Villas de Zaragoza | `6250` Zaragoza |
| `625002` | Ibérica zaragozana | `6250` Zaragoza |
| `625003` | Ribera del Ebro de Zaragoza | `6250` Zaragoza |

## `63` Principado de Asturias — 7 zonas

| Código | Zona | Provincia |
|---|---|---|
| `633301` | Litoral occidental asturiano | `6333` Asturias |
| `633301C` | Costa - Litoral occidental asturiano | `6333` Asturias |
| `633302` | Litoral oriental asturiano | `6333` Asturias |
| `633302C` | Costa - Litoral oriental asturiano | `6333` Asturias |
| `633303` | Suroccidental asturiana | `6333` Asturias |
| `633304` | Central y Valles Mineros | `6333` Asturias |
| `633305` | Cordillera y Picos de Europa | `6333` Asturias |

## `64` Illes Balears — 13 zonas

| Código | Zona | Provincia |
|---|---|---|
| `645301` | Ibiza y Formentera | `6453` Ibiza y Formentera |
| `645301C` | Costa - Ibiza y Formentera | `6453` Ibiza y Formentera |
| `645401` | Sierra Tramontana | `6454` Mallorca |
| `645401C` | Costa - Sierra Tramontana | `6454` Mallorca |
| `645402` | Norte y nordeste de Mallorca | `6454` Mallorca |
| `645402C` | Costa - Norte y nordeste de Mallorca | `6454` Mallorca |
| `645403` | Interior de Mallorca | `6454` Mallorca |
| `645404` | Sur de Mallorca | `6454` Mallorca |
| `645404C` | Costa - Sur de Mallorca | `6454` Mallorca |
| `645405` | Levante mallorquín | `6454` Mallorca |
| `645405C` | Costa - Levante mallorquín | `6454` Mallorca |
| `645501` | Menorca | `6455` Menorca |
| `645501C` | Costa - Menorca | `6455` Menorca |

## `65` Canarias — 24 zonas

| Código | Zona | Provincia |
|---|---|---|
| `659001` | Norte de Gran Canaria | `6590` Gran Canaria |
| `659001C` | Costa - Norte de Gran Canaria | `6590` Gran Canaria |
| `659003` | Cumbres de Gran Canaria | `6590` Gran Canaria |
| `659004` | Este, sur y oeste de Gran Canaria | `6590` Gran Canaria |
| `659004C` | Costa - Este, sur y oeste de Gran Canaria | `6590` Gran Canaria |
| `659101` | Lanzarote | `6591` Lanzarote |
| `659101C` | Costa - Lanzarote | `6591` Lanzarote |
| `659201` | Fuerteventura | `6592` Fuerteventura |
| `659201C` | Costa - Fuerteventura | `6592` Fuerteventura |
| `659302` | Cumbres de La Palma | `6593` La Palma |
| `659303` | Este de La Palma | `6593` La Palma |
| `659303C` | Costa - Este de La Palma | `6593` La Palma |
| `659304` | Oeste de La Palma | `6593` La Palma |
| `659304C` | Costa - Oeste de La Palma | `6593` La Palma |
| `659401` | La Gomera | `6594` La Gomera |
| `659401C` | Costa - La Gomera | `6594` La Gomera |
| `659501` | El Hierro | `6595` El Hierro |
| `659501C` | Costa - El Hierro | `6595` El Hierro |
| `659601` | Norte de Tenerife | `6596` Tenerife |
| `659601C` | Costa - Norte de Tenerife | `6596` Tenerife |
| `659602` | Área metropolitana de Tenerife | `6596` Tenerife |
| `659602C` | Costa - Área metropolitana de Tenerife | `6596` Tenerife |
| `659603` | Este, sur y oeste de Tenerife | `6596` Tenerife |
| `659603C` | Costa - Este, sur y oeste de Tenerife | `6596` Tenerife |

## `66` Cantabria — 5 zonas

| Código | Zona | Provincia |
|---|---|---|
| `663901` | Litoral cántabro | `6639` Cantabria |
| `663901C` | Costa - Litoral cántabro | `6639` Cantabria |
| `663902` | Liébana | `6639` Cantabria |
| `663903` | Centro y valle de Villaverde | `6639` Cantabria |
| `663904` | Cantabria del Ebro | `6639` Cantabria |

## `67` Castilla y León — 24 zonas

| Código | Zona | Provincia |
|---|---|---|
| `670501` | Meseta de Ávila | `6705` Ávila |
| `670502` | Sistema Central de Ávila | `6705` Ávila |
| `670503` | Sur de Ávila | `6705` Ávila |
| `670901` | Cordillera Cantábrica de Burgos | `6709` Burgos |
| `670902` | Norte de Burgos | `6709` Burgos |
| `670903` | Condado de Treviño | `6709` Burgos |
| `670904` | Meseta de Burgos | `6709` Burgos |
| `670905` | Ibérica de Burgos | `6709` Burgos |
| `672401` | Cordillera Cantábrica de León | `6724` León |
| `672402` | Bierzo de León | `6724` León |
| `672403` | Meseta de León | `6724` León |
| `673401` | Cordillera Cantábrica de Palencia | `6734` Palencia |
| `673402` | Meseta de Palencia | `6734` Palencia |
| `673701` | Meseta de Salamanca | `6737` Salamanca |
| `673702` | Sistema Central de Salamanca | `6737` Salamanca |
| `673703` | Sur de Salamanca | `6737` Salamanca |
| `674001` | Meseta de Segovia | `6740` Segovia |
| `674002` | Sistema Central de Segovia | `6740` Segovia |
| `674201` | Ibérica de Soria | `6742` Soria |
| `674202` | Meseta de Soria | `6742` Soria |
| `674203` | Sistema Central de Soria | `6742` Soria |
| `674701` | Meseta de Valladolid | `6747` Valladolid |
| `674901` | Sanabria | `6749` Zamora |
| `674902` | Meseta de Zamora | `6749` Zamora |

## `68` Castilla - La Mancha — 17 zonas

| Código | Zona | Provincia |
|---|---|---|
| `680201` | La Mancha albaceteña | `6802` Albacete |
| `680202` | Alcaraz y Segura | `6802` Albacete |
| `680203` | Hellín y Almansa | `6802` Albacete |
| `681301` | Montes del norte y Anchuras | `6813` Ciudad Real |
| `681302` | La Mancha de Ciudad Real | `6813` Ciudad Real |
| `681303` | Valle del Guadiana | `6813` Ciudad Real |
| `681304` | Sierras de Alcudia y Madrona | `6813` Ciudad Real |
| `681601` | Alcarria conquense | `6816` Cuenca |
| `681602` | Serranía de Cuenca | `6816` Cuenca |
| `681603` | La Mancha conquense | `6816` Cuenca |
| `681901` | Serranía de Guadalajara | `6819` Guadalajara |
| `681902` | Parameras de Molina | `6819` Guadalajara |
| `681903` | Alcarria de Guadalajara | `6819` Guadalajara |
| `684501` | Sierra de San Vicente | `6845` Toledo |
| `684502` | Valle del Tajo | `6845` Toledo |
| `684503` | Montes de Toledo | `6845` Toledo |
| `684504` | La Mancha toledana | `6845` Toledo |

## `69` Cataluña — 21 zonas

| Código | Zona | Provincia |
|---|---|---|
| `690801` | Prepirineo de Barcelona | `6908` Barcelona |
| `690802` | Depresión central de Barcelona | `6908` Barcelona |
| `690803` | Prelitoral de Barcelona | `6908` Barcelona |
| `690804` | Litoral de Barcelona | `6908` Barcelona |
| `690804C` | Costa - Litoral de Barcelona | `6908` Barcelona |
| `691701` | Pirineo de Girona | `6917` Girona |
| `691702` | Prelitoral de Girona | `6917` Girona |
| `691703` | Ampurdán | `6917` Girona |
| `691703C` | Costa - Ampurdán | `6917` Girona |
| `691704` | Litoral sur de Girona | `6917` Girona |
| `691704C` | Costa - Litoral sur de Girona | `6917` Girona |
| `692501` | Valle de Arán | `6925` Lleida |
| `692502` | Pirineo de Lleida | `6925` Lleida |
| `692503` | Depresión central de Lleida | `6925` Lleida |
| `694301` | Depresión central de Tarragona | `6943` Tarragona |
| `694302` | Prelitoral norte de Tarragona | `6943` Tarragona |
| `694303` | Litoral norte de Tarragona | `6943` Tarragona |
| `694303C` | Costa - Litoral norte de Tarragona | `6943` Tarragona |
| `694304` | Litoral sur de Tarragona | `6943` Tarragona |
| `694304C` | Costa - Litoral sur de Tarragona | `6943` Tarragona |
| `694305` | Prelitoral sur de Tarragona | `6943` Tarragona |

## `70` Extremadura — 8 zonas

| Código | Zona | Provincia |
|---|---|---|
| `700601` | Vegas del Guadiana | `7006` Badajoz |
| `700602` | La Siberia extremeña | `7006` Badajoz |
| `700603` | Barros y Serena | `7006` Badajoz |
| `700604` | Sur de Badajoz | `7006` Badajoz |
| `701001` | Norte de Cáceres | `7010` Cáceres |
| `701002` | Tajo y Alagón | `7010` Cáceres |
| `701003` | Meseta cacereña | `7010` Cáceres |
| `701004` | Villuercas y Montánchez | `7010` Cáceres |

## `71` Galicia — 22 zonas

| Código | Zona | Provincia |
|---|---|---|
| `711501` | Noroeste de A Coruña | `7115` A Coruña |
| `711501C` | Costa - Noroeste de A Coruña | `7115` A Coruña |
| `711502` | Oeste de A Coruña | `7115` A Coruña |
| `711502C` | Costa - Oeste de A Coruña | `7115` A Coruña |
| `711503` | Interior de A Coruña | `7115` A Coruña |
| `711504` | Suroeste de A Coruña | `7115` A Coruña |
| `711504C` | Costa - Suroeste de A Coruña | `7115` A Coruña |
| `712701` | A Mariña | `7127` Lugo |
| `712701C` | Costa - A Mariña | `7127` Lugo |
| `712702` | Centro de Lugo | `7127` Lugo |
| `712703` | Montaña de Lugo | `7127` Lugo |
| `712704` | Sur de Lugo | `7127` Lugo |
| `713201` | Noroeste de Ourense | `7132` Ourense |
| `713202` | Miño de Ourense | `7132` Ourense |
| `713203` | Sur de Ourense | `7132` Ourense |
| `713204` | Montaña de Ourense | `7132` Ourense |
| `713205` | Valdeorras | `7132` Ourense |
| `713601` | Rias Baixas | `7136` Pontevedra |
| `713601C` | Costa - Rias Baixas | `7136` Pontevedra |
| `713602` | Interior de Pontevedra | `7136` Pontevedra |
| `713603` | Miño de Pontevedra | `7136` Pontevedra |
| `713603C` | Costa - Miño de Pontevedra | `7136` Pontevedra |

## `72` Comunidad de Madrid — 3 zonas

| Código | Zona | Provincia |
|---|---|---|
| `722801` | Sierra de Madrid | `7228` Madrid |
| `722802` | Metropolitana y Henares | `7228` Madrid |
| `722803` | Sur, Vegas y Oeste | `7228` Madrid |

## `73` Región de Murcia — 7 zonas

| Código | Zona | Provincia |
|---|---|---|
| `733001` | Altiplano de Murcia | `7330` Murcia |
| `733002` | Noroeste de Murcia | `7330` Murcia |
| `733003` | Vega del Segura | `7330` Murcia |
| `733004` | Valle del Guadalentín, Lorca y Águilas | `7330` Murcia |
| `733004C` | Costa - Valle del Guadalentín, Lorca y Águilas | `7330` Murcia |
| `733005` | Campo de Cartagena y Mazarrón | `7330` Murcia |
| `733005C` | Costa - Campo de Cartagena y Mazarrón | `7330` Murcia |

## `74` Comunidad Foral de Navarra — 4 zonas

| Código | Zona | Provincia |
|---|---|---|
| `743101` | Vertiente cantábrica de Navarra | `7431` Navarra |
| `743102` | Centro de Navarra | `7431` Navarra |
| `743103` | Pirineo navarro | `7431` Navarra |
| `743104` | Ribera del Ebro de Navarra | `7431` Navarra |

## `75` País Vasco — 9 zonas

| Código | Zona | Provincia |
|---|---|---|
| `750101` | Cuenca del Nervión | `7501` Araba/Álava |
| `750102` | Llanada alavesa | `7501` Araba/Álava |
| `750103` | Rioja alavesa | `7501` Araba/Álava |
| `752001` | Gipuzkoa litoral | `7520` Gipuzkoa |
| `752001C` | Costa - Gipuzkoa litoral | `7520` Gipuzkoa |
| `752002` | Gipuzkoa interior | `7520` Gipuzkoa |
| `754801` | Bizkaia litoral | `7548` Bizkaia |
| `754801C` | Costa - Bizkaia litoral | `7548` Bizkaia |
| `754802` | Bizkaia interior | `7548` Bizkaia |

## `76` La Rioja — 2 zonas

| Código | Zona | Provincia |
|---|---|---|
| `762601` | Ribera del Ebro de La Rioja | `7626` La Rioja |
| `762602` | Ibérica riojana | `7626` La Rioja |

## `77` Comunitat Valenciana — 17 zonas

| Código | Zona | Provincia |
|---|---|---|
| `770301` | Litoral norte de Alicante | `7703` Alacant/Alicante |
| `770301C` | Costa - Litoral norte de Alicante | `7703` Alacant/Alicante |
| `770302` | Interior de Alicante | `7703` Alacant/Alicante |
| `770303` | Litoral sur de Alicante | `7703` Alacant/Alicante |
| `770303C` | Costa - Litoral sur de Alicante | `7703` Alacant/Alicante |
| `771201` | Interior norte de Castellón | `7712` Castelló/Castellón |
| `771202` | Litoral norte de Castellón | `7712` Castelló/Castellón |
| `771202C` | Costa - Litoral norte de Castellón | `7712` Castelló/Castellón |
| `771203` | Interior sur de Castellón | `7712` Castelló/Castellón |
| `771204` | Litoral sur de Castellón | `7712` Castelló/Castellón |
| `771204C` | Costa - Litoral sur de Castellón | `7712` Castelló/Castellón |
| `774601` | Interior norte de Valencia | `7746` València/Valencia |
| `774602` | Litoral norte de Valencia | `7746` València/Valencia |
| `774602C` | Costa - Litoral norte de Valencia | `7746` València/Valencia |
| `774603` | Interior sur de Valencia | `7746` València/Valencia |
| `774604` | Litoral sur de Valencia | `7746` València/Valencia |
| `774604C` | Costa - Litoral sur de Valencia | `7746` València/Valencia |

## `78` Ciudad de Ceuta — 2 zonas

| Código | Zona | Provincia |
|---|---|---|
| `785101` | Ceuta | `7851` Ceuta |
| `785101C` | Costa - Ceuta | `7851` Ceuta |

## `79` Ciudad de Melilla — 2 zonas

| Código | Zona | Provincia |
|---|---|---|
| `795201` | Melilla | `7952` Melilla |
| `795201C` | Costa - Melilla | `7952` Melilla |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
