# ⚖️ Uso legal y atribución

Condiciones de reutilización de los datos de AEMET. **Relevante en el momento en que se publique
cualquier dato de AEMET en el frontal.**

Fuente: nota legal oficial (`src/web-texto/nota_legal.txt`, <https://www.aemet.es/es/nota_legal>) y
FAQs (`src/web-texto/faqs.txt`). Todo 🔵 **oficial**: transcrito de la fuente, no interpretado.

> [!NOTE]
> Este archivo resume las condiciones publicadas por AEMET. **No es asesoramiento jurídico.** Ante
> una duda con implicaciones legales reales, consultar la nota legal completa en `src/` o preguntar.


> **Fuentes de este archivo:** `src/web-texto/nota_legal.txt` (**las condiciones de reutilización**, fuente autoritativa) · `src/web-texto/info.txt` (marco legal del servicio) · `src/catalogos/faqs.json` (FAQ 1.3–1.7) · `src/web-texto/gitlab-python-README.md` (licencia MIT del cliente oficial) · **verificación en vivo del 2026-08-26** (los campos `copyright` y `notaLegal` que vienen dentro de los payloads).
---

## Resumen: qué se puede y qué hay que hacer

| | |
|---|---|
| ✅ **Uso comercial** | Permitido. La autorización cubre "fines comerciales y no comerciales" |
| ✅ **Reproducir, distribuir, transformar** | Permitido, en cualquier modalidad y formato |
| ✅ **Coste** | Gratuito. Cesión no exclusiva de derechos de propiedad intelectual |
| ⚠️ **Citar a AEMET** | **Obligatorio** |
| ⚠️ **Indicar la fecha de actualización** | **Obligatorio** cuando el documento original la incluya |
| ⚠️ **Conservar los metadatos** | **Obligatorio** (fecha y condiciones de reutilización) |
| ❌ **Insinuar patrocinio de AEMET** | Prohibido |
| ❌ **Desnaturalizar la información** | Prohibido alterar el sentido |

Marco legal: Ley 18/2015, de 9 de julio, que modifica la Ley 37/2007 sobre reutilización de la
información del sector público.

---

## Las cinco condiciones generales 🔵

Transcritas de la nota legal:

1. **No desnaturalizar el sentido de la información.**
2. **Citar a AEMET como fuente**, en una de las formas admitidas (ver abajo).
3. **Mencionar la fecha de la última actualización** de los documentos reutilizados, siempre que
   estuviera incluida en el documento original.
4. **No indicar, insinuar ni sugerir que AEMET participa, patrocina o apoya** la reutilización.
5. **Conservar la integridad de los metadatos** sobre fecha de actualización y condiciones de
   reutilización que estuvieran incluidos en el documento facilitado por AEMET.

---

## Cómo citar

### Forma corta

```
Fuente: AEMET
```

### Forma larga (alternativa admitida)

```
Información elaborada utilizando, entre otras, la obtenida de la Agencia Estatal de Meteorología
```

### Para servicios de valor añadido 🔵

Cita textual de la nota legal:

> En caso, de realizar con ella servicios de valor añadido en base a la información meteorológica y
> climatológica suministrada por AEMET para su difusión o suministro a terceros, se debe **mencionar
> explícitamente a AEMET como propietaria de dicha información**, incluyendo la referencia
> "Fuente: AEMET" o en su lugar el texto: "Información elaborada utilizando, entre otras, la obtenida
> de la Agencia Estatal de Meteorología".

🟡 Mostrar la predicción de AEMET en una web es, con toda probabilidad, un "servicio de valor
añadido". Es decir: **la atribución explícita es exigible**, no opcional.

### El aviso de copyright de AEMET

Aparece al pie de todas sus páginas:

```
© AEMET. Autorizado el uso de la información y su reproducción citando a AEMET como autora de la misma.
```

---

## La atribución viene en los propios datos 🟢

Buena noticia: **la mayoría de los productos JSON traen los textos legales en el payload**, así que
no hay que escribirlos a mano.

Verificado en predicción municipal, playa y marítima 🟢:

```json
"origen": {
  "productor": "Agencia Estatal de Meteorología - AEMET. Gobierno de España",
  "web": "https://www.aemet.es",
  "enlace": "https://www.aemet.es/es/eltiempo/prediccion/municipios/...",
  "language": "es",
  "copyright": "© AEMET. Autorizado el uso de la información y su reproducción citando a AEMET…",
  "notaLegal": "https://www.aemet.es/es/nota_legal"
}
```

**Propagar `origen.copyright` y `origen.notaLegal` hasta la vista** es la forma más segura de cumplir
las condiciones 2 y 5: se cita a AEMET con su propio texto y se conservan los metadatos.

### Precauciones 🟢

- ⚠️ **`origen.web` no siempre viene limpio.** En marítima costera llega como `" http://www.aemet.es"`
  — con espacio inicial y en `http`, no `https`. En municipio viene bien. **Limpiar y normalizar
  antes de usarlo como enlace.**
- ⚠️ **No todos los productos traen `origen`.** Los de texto plano llevan la cabecera
  "AGENCIA ESTATAL DE METEOROLOGÍA" y **las imágenes no traen nada**: en esos casos la atribución hay
  que añadirla en la plantilla.
- ⚠️ **`origen.enlace`** apunta al producto equivalente en la web de AEMET. Enlazarlo refuerza la
  atribución, pero 🔴 sin verificar que la URL sea siempre válida.

---

## La fecha de actualización es obligatoria 🔵

La condición 3 obliga a mostrar la fecha de la última actualización cuando el original la incluya.
**Y todos los productos verificados la incluyen**, así que aplica:

| Producto | Dónde está la fecha 🟢 |
|---|---|
| Municipio (diaria / horaria) | `elaborado` en la raíz |
| Playa | `elaborado` en la raíz |
| Marítima (costera / alta mar) | `origen.elaborado`, `origen.inicio`, `origen.fin` |
| Observación | `fint` en cada registro |
| Predicciones en texto | Cabecera: `DÍA … A LAS … HORA OFICIAL` |
| Avisos CAP | `<sent>`, `<onset>`, `<expires>` en el XML |

Esto **coincide** con la necesidad técnica de validar la frescura del dato
([`ERRATAS.md` A4](ERRATAS.md#a4-hay-endpoints-que-devuelven-datos-rancios-con-un-200-impecable-)):
el mismo campo sirve para cumplir la obligación legal y para no publicar una predicción de 2022.

---

## Exclusión de responsabilidad de AEMET 🔵

De la nota legal:

> AEMET no será responsable del uso o interpretación que de su información hagan los agentes
> reutilizadores ni tampoco de los daños sufridos o pérdidas económicas que, de forma directa o
> indirecta, produzcan o puedan producir perjuicios económicos, materiales o sobre datos, provocados
> por el uso de la información reutilizada.

Y tampoco garantiza continuidad del servicio ni ausencia de errores.

🟡 Consecuencia práctica: **la integración tiene que tolerar que AEMET falle, se retrase o devuelva
datos incorrectos**. Los datos rancios que hemos medido son un caso real de esto. Si en algún momento
se usaran para algo con consecuencias operativas, conviene mostrar la fecha del dato de forma visible
para que quien lo lea pueda juzgar su vigencia.

---

## Régimen sancionador 🔵

El reutilizador queda sometido a la normativa de reutilización de información del sector público,
**incluido el régimen sancionador del artículo 11 de la Ley 18/2015**. No es una recomendación de
estilo: incumplir la atribución tiene consecuencias previstas por ley.

---

## Qué NO cubre esta autorización 🔵

| No cubierto | Detalle |
|---|---|
| **Productos de pago** | Solo es libre lo del Anexo II de la resolución de precios públicos de 30/12/2015 (BOE nº 4 de 05/01/2016) |
| **Modelos numéricos** | HARMONIE-AROME, ECMWF, polvo: no son datos abiertos (FAQ 5.3) |
| **Adaptaciones a medida** | Cambios de formato o recorte los hace AEMET con coste de gestión (FAQ 1.7) |
| **Sistemas externos referenciados** | AEMET declina responsabilidad sobre contenidos fuera de sus canales |

---

## Lista de comprobación antes de publicar

- [ ] Se muestra `Fuente: AEMET` o el texto largo, de forma visible.
- [ ] Se muestra la fecha de elaboración del dato.
- [ ] Se propaga `origen.copyright` y `origen.notaLegal` cuando el producto los trae.
- [ ] Para imágenes y texto plano, se ha añadido la atribución manualmente.
- [ ] No hay nada que sugiera que AEMET patrocina o valida el sitio.
- [ ] No se ha alterado el sentido de la información.
- [ ] El dato se ha validado como fresco antes de mostrarlo.
- [ ] Se ha limpiado `origen.web` (espacios, `http` → `https`) si se usa como enlace.

---

## Referencias

| Documento | Ubicación |
|---|---|
| Nota legal completa | `src/web-texto/nota_legal.txt` · <https://www.aemet.es/es/nota_legal> |
| FAQs (condiciones y productos) | `src/web-texto/faqs.txt` |
| Descripción del servicio y marco legal | `src/web-texto/info.txt` |
| Licencia del cliente Python oficial | MIT, © 2026 AEMET (`src/web-texto/gitlab-python-README.md`) |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
