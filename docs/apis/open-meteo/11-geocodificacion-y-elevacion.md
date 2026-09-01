# 📍 Geocodificación y elevación

> **Última actualización:** 2026-09-01

**3 endpoints.** Resolver nombres de lugar a coordenadas, consultar una localidad por su
identificador y obtener la altitud del terreno. Son las piezas de apoyo del resto de APIs.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md) · [`ERRATAS.md`](ERRATAS.md)

Leyenda: 🟢 verificado (`2026-08-31` / `2026-09-01`) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/elevation.yml`, `src/web-texto/geocoding.txt`,
> `src/web-texto/elevation.txt` + verificación en vivo del 2026-08-31 (peticiones 08, 09 y 10).
> **La API de geocodificación no tiene especificación OpenAPI** ⚠️.

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET https://geocoding-api.open-meteo.com/v1/search` | 🟢 | JSON UTF-8 con `results[]` |
| `GET https://geocoding-api.open-meteo.com/v1/get` | 🟢 | JSON UTF-8, **objeto plano sin envoltorio** |
| `GET https://api.open-meteo.com/v1/elevation` | 🟢 | JSON UTF-8 con un único array `elevation` |

---

## Búsqueda de localidades 🟢

```
GET /v1/search?name=Sevilla&count=2&language=es
```

| Parámetro | Formato | Defecto | Notas |
|---|---|---|---|
| `name` | Cadena | — | **Obligatorio.** Nombre o código postal, con calificador opcional tras coma |
| `count` | Entero | `10` | Hasta 100 🟢: `count=101` responde `400` — «Parameter count must be between 1 and 100.» |
| `language` | Código de idioma en minúsculas | `en` | Traduce nombre, país y áreas administrativas |
| `countryCode` | ISO-3166-1 alfa-2 | — | Filtro de país inequívoco |
| `format` | `json` \| `protobuf` | `json` | `protobuf` verificado 🟢: `application/x-protobuf`, 319 B frente a 912 B del JSON. El `.proto` está en el repositorio de GitHub 🔵 |
| `apikey` | Cadena | — | Solo en el dominio `customer-` |

**La búsqueda por código postal español funciona** 🟢: `name=41001&language=es` devolvió Sevilla
(2026-08-31).

Reglas de coincidencia 🔵:

- **Dos caracteres** exigen coincidencia exacta; **tres o más** hacen coincidencia por prefijo
  normalizado, insensible a mayúsculas y a diacríticos.
- Búsquedas vacías o de un solo carácter no devuelven nada.
- El calificador tras la coma (`Los Angeles, California`) debe coincidir **exactamente**: no admite
  prefijos. Acepta nombres de país, códigos ISO de dos letras, nombres de admin1 y sus
  abreviaturas.
- Si una abreviatura es a la vez admin1 y código de país, **gana el admin1**: para filtrar por país
  sin ambigüedad, `countryCode`.
- Con varias comas solo se usa el texto entre la primera y la segunda.

Respuesta real (2026-08-31, `name=Sevilla&count=2&language=es`), recortada:

```json
{"results":[{
  "id":2510911,"name":"Sevilla","latitude":37.38283,"longitude":-5.97317,"elevation":16.0,
  "feature_code":"PPLA","country_code":"ES","admin1_id":2593109,"admin2_id":2510910,
  "admin3_id":6361046,"timezone":"Europe/Madrid","population":686741,
  "postcodes":["41015","41016", …],
  "country_id":2510769,"country":"España","admin1":"Andalucía",
  "admin2":"Provincia de Sevilla","admin3":"Sevilla"}]}
```

| Campo | Notas |
|---|---|
| `id` | Identificador GeoNames; resoluble con `/v1/get` |
| `feature_code` | Tipo de lugar según GeoNames (`PPLA` = capital de admin1) |
| `timezone` | Directamente utilizable como `timezone` en el resto de APIs 🟢 |
| `postcodes` | Array de códigos postales; puede ser largo (24 entradas para Sevilla) |
| `admin1`…`admin4` | Jerarquía administrativa. En España: comunidad, provincia, comarca, municipio 🟡 |

> [!CAUTION]
> **Sin resultados, la clave `results` no existe** 🟢. Verificado el 2026-08-31 con
> `name=Zzzxqvnoexistelugar`:
>
> ```
> HTTP/1.1 200 OK
> {"generationtime_ms":0.52297115}
> ```
>
> No es un array vacío: **no está la clave**. Leerla siempre con valor por defecto
> (`$data['results'] ?? []`). Ver
> [`A8`](ERRATAS.md#a8--una-búsqueda-sin-resultados-no-devuelve-results-).
>
> Lo mismo vale dentro de cada resultado: **los campos vacíos no se devuelven**, así que `admin4`
> falta si no existe ese nivel 🔵.

Cabecera no documentada: `X-Encoding-Time` 🟢
([`D1`](ERRATAS.md#d1--cabecera-x-encoding-time-en-la-api-de-geocodificación-)).

## Consulta por identificador 🟢

```
GET /v1/get?id=2510911
```

Devuelve **el objeto de la localidad directamente**, sin envoltorio `results`. Verificado el
2026-08-31: la misma Sevilla del ejemplo anterior llegó como `"name":"Seville"`, `"country":"Spain"`,
`"admin1":"Andalusia"` — es decir, **en inglés**, porque no se pasó `language`.

**Sí acepta `language`** 🟢, aunque la documentación solo lo describe para `/v1/search`: con
`?id=2510911&language=es` devuelve `"Sevilla"`, `"España"`, `"Andalucía"`
([`D3`](ERRATAS.md#d3--v1get-responde-en-inglés-salvo-que-se-le-pase-language-)).

Sirve para resolver los `admin1_id`, `admin2_id`, `country_id`… que devuelve `/v1/search`.

---

## Elevación 🟢

```
GET https://api.open-meteo.com/v1/elevation?latitude=40.4168&longitude=-3.7038
```

| | |
|---|---|
| Host | `api.open-meteo.com` — el mismo que la Forecast API |
| Modelo | Copernicus DEM GLO-90, 90 m de resolución, cobertura mundial 🔵 |
| Límite | **100 coordenadas** por petición 🟢: con 101 responde `400` — `{"reason":"Parameter 'latitude' and 'longitude' must not exceed 100 coordinates.","error":true}` |
| Respuesta | `{"elevation":[666.0]}` — **siempre un array**, aunque se pida un punto 🟢 |
| Tamaño 🟢 | 21 B |
| TTL 🟡 | Permanente: el terreno no cambia. Guardar en base de datos, no en caché |

`Content-Type: application/json` **sin `charset`**, a diferencia del resto de endpoints 🟢
([`B4`](ERRATAS.md#b4--content-type-sin-charset-en-v1elevation-)).

Error real con coordenada inválida 🔵:

```json
{"error":true,"reason":"Latitude must be in range of -90 to 90°. Given: 522.52."}
```

> [!NOTE]
> **Atribución adicional**: quien use la API de elevación debe citar también al programa Copernicus,
> además de a Open-Meteo 🔵. Ver [`LIMITACIONES.md`](LIMITACIONES.md#condiciones-legales).

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Si un código postal ambiguo devuelve varias localidades ordenadas por población | Baja |
| 2 | Si `/v1/search` acepta multi-idioma o solo un `language` por petición | Baja |
