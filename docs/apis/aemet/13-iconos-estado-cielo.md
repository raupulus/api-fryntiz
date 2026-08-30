# 🎨 Iconos de estado del cielo

`estadoCielo.value` de las predicciones por municipio es un **código** (`"43n"`), no un texto.
AEMET **no publica la tabla de códigos** en ninguna fuente: no está en la especificación, ni en las
FAQs, ni en la página de ayuda de su web.

Este archivo resuelve el problema de tres formas, de menos a más dependencia externa.

Requisitos previos: [`01-predicciones-municipios.md`](01-predicciones-municipios.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** **payloads reales** del endpoint agregado de predicción municipal (8.124 municipios cosechados el 2026-08-26 → 25 de los 35 códigos con su `descripcion` oficial) · **servidor de imágenes de AEMET** `https://www.aemet.es/imagenes/png/estado_cielo/{codigo}_g.png` (sondeo que reveló el juego completo de 35 códigos × día/noche). ⚠️ AEMET **no publica** esta tabla en ningún documento: no está en la especificación, ni en las FAQs, ni en la página de ayuda.

---

## Cómo se ha obtenido esta tabla

Dos vías independientes que se corroboran:

1. **De los propios payloads** 🟢 — cada objeto de estado del cielo trae el código **y** su
   `descripcion` juntos, así que las respuestas reales son un diccionario gratuito. Cosechando el
   endpoint agregado (**8.124 municipios en una sola petición**) salen **46 variantes = 25 de los 35
   códigos**. ⚠️ El campo se llama `value` en el endpoint individual y **`valor`** en el agregado.
2. **Del servidor de imágenes de AEMET** 🟢 — sondeando
   `https://www.aemet.es/imagenes/png/estado_cielo/{codigo}_g.png` responden **70 iconos**:
   **35 códigos × 2 variantes** (día y noche). Eso da el juego completo de códigos.

Cruzando ambas se deduce el sistema de numeración. ⚠️ **Y hay dos convenciones distintas**, que solo
se descubren cosechando muchos municipios.

#### Primer dígito: la familia de precipitación

| Dígito | Familia |
|---|---|
| `1` | *(sin precipitación)* |
| `2` | con lluvia |
| `3` | con nieve |
| `4` | con lluvia escasa |
| `5` | con tormenta |
| `6` | con tormenta y lluvia escasa |
| `7` | con nieve escasa |
| `8` | fenómenos de visibilidad (`81` niebla · `82` bruma · `83` calima) |

#### Segundo dígito: ⚠️ significa cosas distintas según la familia

| Dígito | En familias `1` `2` `3` `4` | En familias `5` `6` `7` |
|---|---|---|
| `1` | Despejado | **Intervalos nubosos** |
| `2` | Poco nuboso | **Nuboso** |
| `3` | Intervalos nubosos | **Muy nuboso** |
| `4` | Nuboso | **Cubierto** |
| `5` | Muy nuboso | — |
| `6` | Cubierto | — |
| `7` | Nubes altas | — |

Así, `53` es "**Muy** nuboso con tormenta" mientras `43` es "**Intervalos** nubosos con lluvia
escasa": **el mismo dígito `3` significa dos cosas.** Las familias de tormenta y nieve escasa
comprimen la escala a cuatro valores empezando por `1`.

> ⚠️ **Corrección de una versión anterior de este archivo:** se documentó una convención única
> (la de las familias 1-4) extrapolada a todas. Era **incorrecta** para `5x`, `6x` y `7x`. Se detectó
> al cosechar los 8.124 municipios del endpoint agregado, que aportó **25 códigos verificados** en vez
> de 12.

**El sufijo `n` significa nocturno.** No todos los códigos existen: no hay `21`, `22`, `31`, `32`,
`41`, `42`, ni la serie `9x`.

---

## Solución recomendada: mapeo a emoji

**Peso cero, sin fuentes, sin dependencias externas y puro Unicode.** Es la vía independiente que
pedimos: si AEMET cambia sus URLs de imágenes o deja de servirlas, esto sigue funcionando.

| Código | Fiab. | Descripción de AEMET | ☀ Día | 🌙 Noche | Codepoints (día / noche) |
|---|---|---|---|---|---|
| `11` / `11n` | 🟢 | Despejado | 🌞 | 🌙 | `U+1F31E` / `U+1F319` |
| `12` / `12n` | 🟢 | Poco nuboso | 🌤️ | ☁️ | `U+1F324 U+FE0F` / `U+2601 U+FE0F` |
| `13` / `13n` | 🟢 | Intervalos nubosos | ⛅ | ☁️ | `U+26C5` / `U+2601 U+FE0F` |
| `14` / `14n` | 🟢 | Nuboso | 🌥️ | ☁️ | `U+1F325 U+FE0F` / `U+2601 U+FE0F` |
| `15` / `15n` | 🟢 | Muy nuboso | ☁️ | ☁️ | `U+2601 U+FE0F` / `U+2601 U+FE0F` |
| `16` / `16n` | 🟢 | Cubierto | ☁️ | ☁️ | `U+2601 U+FE0F` / `U+2601 U+FE0F` |
| `17` / `17n` | 🟢 | Nubes altas | 🌤️ | ☁️ | `U+1F324 U+FE0F` / `U+2601 U+FE0F` |
| `23` / `23n` | 🟢 | Intervalos nubosos con lluvia | 🌧️ | 🌧️ | `U+1F327 U+FE0F` / `U+1F327 U+FE0F` |
| `24` / `24n` | 🟢 | Nuboso con lluvia | 🌧️ | 🌧️ | `U+1F327 U+FE0F` / `U+1F327 U+FE0F` |
| `25` / `25n` | 🟢 | Muy nuboso con lluvia | 🌧️ | 🌧️ | `U+1F327 U+FE0F` / `U+1F327 U+FE0F` |
| `26` / `26n` | 🟢 | Cubierto con lluvia | 🌧️ | 🌧️ | `U+1F327 U+FE0F` / `U+1F327 U+FE0F` |
| `27` / `27n` | 🟡 | Nubes altas con lluvia | 🌧️ | 🌧️ | `U+1F327 U+FE0F` / `U+1F327 U+FE0F` |
| `33` / `33n` | 🟡 | Intervalos nubosos con nieve | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `34` / `34n` | 🟡 | Nuboso con nieve | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `35` / `35n` | 🟡 | Muy nuboso con nieve | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `36` / `36n` | 🟡 | Cubierto con nieve | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `43` / `43n` | 🟢 | Intervalos nubosos con lluvia escasa | 🌦️ | 🌧️ | `U+1F326 U+FE0F` / `U+1F327 U+FE0F` |
| `44` / `44n` | 🟢 | Nuboso con lluvia escasa | 🌦️ | 🌧️ | `U+1F326 U+FE0F` / `U+1F327 U+FE0F` |
| `45` / `45n` | 🟢 | Muy nuboso con lluvia escasa | 🌦️ | 🌧️ | `U+1F326 U+FE0F` / `U+1F327 U+FE0F` |
| `46` / `46n` | 🟢 | Cubierto con lluvia escasa | 🌦️ | 🌧️ | `U+1F326 U+FE0F` / `U+1F327 U+FE0F` |
| `51` / `51n` | 🟢 | Intervalos nubosos con tormenta | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `52` / `52n` | 🟢 | Nuboso con tormenta | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `53` / `53n` | 🟢 | Muy nuboso con tormenta | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `54` / `54n` | 🟢 | Cubierto con tormenta | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `61` / `61n` | 🟢 | Intervalos nubosos con tormenta y lluvia escasa | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `62` / `62n` | 🟢 | Nuboso con tormenta y lluvia escasa | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `63` / `63n` | 🟢 | Muy nuboso con tormenta y lluvia escasa | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `64` / `64n` | 🟢 | Cubierto con tormenta y lluvia escasa | ⛈️ | ⛈️ | `U+26C8 U+FE0F` / `U+26C8 U+FE0F` |
| `71` / `71n` | 🟡 | Intervalos nubosos con nieve escasa | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `72` / `72n` | 🟡 | Nuboso con nieve escasa | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `73` / `73n` | 🟡 | Muy nuboso con nieve escasa | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `74` / `74n` | 🟡 | Cubierto con nieve escasa | 🌨️ | 🌨️ | `U+1F328 U+FE0F` / `U+1F328 U+FE0F` |
| `81` / `81n` | 🟢 | Niebla | 🌫️ | 🌫️ | `U+1F32B U+FE0F` / `U+1F32B U+FE0F` |
| `82` / `82n` | 🟢 | Bruma | 🌫️ | 🌫️ | `U+1F32B U+FE0F` / `U+1F32B U+FE0F` |
| `83` / `83n` | 🟡 | Calima | 🌫️ | 🌫️ | `U+1F32B U+FE0F` / `U+1F32B U+FE0F` |
### Sobre los selectores de variación

La mayoría de estos emoji son **secuencias de dos puntos de código**: el símbolo base más
`U+FE0F` (*variation selector-16*), que pide presentación en color. Están todos en la columna de
codepoints para que no haya ambigüedad al copiarlos.

⚠️ **Cuidado con `U+FE0F` en contextos que generan identificadores** (slugs, anclas, nombres de
fichero, claves de caché): es un carácter invisible que sobrevive a los `trim()` y provoca
comparaciones que fallan sin motivo aparente. Si eso te preocupa, usa el subconjunto sin selector:

| Emoji | Codepoint | Uso |
|---|---|---|
| 🌞 | `U+1F31E` | Despejado (día) |
| 🌙 | `U+1F319` | Despejado (noche) |
| ⛅ | `U+26C5` | Nubosidad parcial |

Los demás requieren `U+FE0F` para renderizarse en color. Sin él siguen siendo Unicode válido, pero
se dibujan en blanco y negro como glifos de texto.

### Por qué emoji y no una tipografía de iconos

- **Peso cero.** No hay `.woff2` que descargar ni CSS que mantener.
- **Sin petición extra.** Una fuente de iconos añade una descarga bloqueante.
- **Accesible por defecto.** Son texto: los lee un lector de pantalla (conviene añadir de todos
  modos el `descripcion` de AEMET como `aria-label` o `title`).
- **Escalan solos** con el `font-size`, sin `srcset` ni versiones `@2x`.

Contrapartida honesta: **el dibujo concreto depende del sistema del visitante** (Apple, Google,
Microsoft y Twemoji dibujan distinto). Si hace falta control visual exacto, hay que ir a SVG.

---

## Alternativa: los PNG oficiales de AEMET 🟢

```
https://www.aemet.es/imagenes/png/estado_cielo/{codigo}_g.png
```

Verificado el 2026-08-26: responden `200 image/png` los 70 códigos (`11`–`83` y sus variantes `n`).
Tamaños medidos entre ~3,6 KB y ~6,7 KB.

| Ventaja | Inconveniente |
|---|---|
| Iconografía oficial, idéntica a la web de AEMET | **Dependencia de un dominio ajeno**; si cambia la ruta, se rompen |
| Coherencia visual con la fuente | Son PNG rasterizados: no escalan bien |
| Sin trabajo de diseño | Requiere descargarlos y servirlos localmente para no depender de aemet.es |
| | 🔴 Sin verificar su licencia de uso específica: la [nota legal](12-uso-legal-y-atribucion.md) cubre *información*, y no dice nada explícito sobre iconos |

🟡 Si se usan, lo prudente es **descargar los 70 una vez** (≈350 KB en total) y servirlos desde el
proyecto, en lugar de enlazar a aemet.es en caliente. El sufijo `_g` es el único verificado; 🔴 no
sabemos si existen otros tamaños.

---

## Alternativa: SVG propio

Si hace falta control visual total, el juego mínimo son **8 formas** combinables, no 70 iconos:

| Forma | Cubre |
|---|---|
| Sol | `11` |
| Luna | `11n` |
| Nube (3 densidades) | `12`–`17` |
| Gotas | familias `2` y `4` |
| Copo | familias `3` y `7` |
| Rayo | familias `5` y `6` |
| Niebla | `81`–`83` |

🟡 Componiendo nube + modificador se cubren los 35 códigos con 8 primitivas. Es más trabajo que los
emoji, pero da control absoluto y sigue siendo ligero. **No implementado.**

---

## Reglas de uso, sea cual sea la opción

1. **Muestra siempre el texto además del icono.** El payload trae `descripcion` ya redactado en
   español por AEMET: úsalo como `title`/`aria-label`. Es lo correcto por accesibilidad y además no
   dependes de que el mapeo esté completo.
2. **Ten un icono por defecto.** Si aparece un código que no está en la tabla, no falles: muestra
   el genérico y **registra el código** para añadirlo aquí.
3. **No deduzcas día/noche por tu cuenta.** El sufijo `n` ya lo dice. Y el payload trae `orto` y
   `ocaso` si hace falta afinar.
4. **El código es una cadena.** `"11n"` no es numérico, y `"11"` con ceros o sin ellos importa.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Descripción real de los 10 códigos 🟡 (nieve, calima, nubes altas con lluvia) | Baja — **volver a cosechar en invierno** con el endpoint agregado |
| 2 | Si existe algún código fuera de los 35 detectados | Baja — el sondeo cubrió `10`–`89` |
| 3 | Licencia de uso de los PNG de AEMET | Media si se van a usar |
| 4 | Si hay otros sufijos de tamaño además de `_g` | Baja |
| 5 | Si `estadoCielo` de la predicción de **playa** usa este mismo juego (allí el campo es `f1`/`f2`, con códigos como `-116`: parece **otro** sistema) | Alta si se usa playa |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
