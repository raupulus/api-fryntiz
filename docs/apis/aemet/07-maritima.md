# 🌊 Predicción marítima

**2 endpoints.** Boletines marítimos para zonas costeras y de alta mar. Ambos 🟢 verificados.

Requisitos previos: [`00-fundamentos.md`](00-fundamentos.md)

Leyenda: 🟢 verificado (2026-08-26) · 🔵 oficial sin comprobar · 🟡 inferido · 🔴 sin verificar

> **Fuentes de este archivo:** `src/especificacion/AEMET_OpenData_specification.json` (rutas y códigos de costa y área) · **metadatos de los dos endpoints** (periodicidad e indicativos de boletín) · **verificación en vivo del 2026-08-26** (estructura anidada y tamaños).

---

## Resumen

| Endpoint | Estado | Formato real |
|---|---|---|
| `GET /api/prediccion/maritima/costera/costa/{costa}` | 🟢 | JSON `list[1]`, 3,0 KB |
| `GET /api/prediccion/maritima/altamar/area/{area}` | 🟢 | JSON `list[1]`, 5,1 KB |

Este grupo es de los mejor construidos de la API: estructura JSON coherente, con avisos, situación
general, predicción por zonas y tendencia, y **fechas de validez explícitas**.

---

## Predicción marítima costera 🟢

```
GET /api/prediccion/maritima/costera/costa/{costa}
```

| | |
|---|---|
| Periodicidad 🟢 | "Dos veces al día (12:00 y 20:00) **h.o.p**" (hora oficial peninsular) |
| Tamaño 🟢 | ~3,0 KB |
| TTL 🟡 | 6 h |

Verificado con `40` (Galicia).

### Códigos de costa 🟢

| Código | Área costera |
|---|---|
| `40` | Costa de Galicia |
| `41` | Costa de Asturias, Cantabria y País Vasco |
| `42` | Costa de Andalucía Occidental y Ceuta |
| `43` | Costa de las Islas Canarias |
| `44` | Costa de Illes Balears |
| `45` | Costa de Cataluña |
| `46` | Costa de Valencia y Murcia |
| `47` | Costa de Andalucía Oriental y Melilla |

⚠️ Son **8 códigos del `40` al `47`**, y **no** coinciden con los códigos de provincia. Un `11`
(Cádiz como provincia) **no es un código de costa válido**.

### Estructura 🟢

```
[0]
├── id: "FQXX40"          ← identificador del boletín OMM
├── nombre: "Boletín meteorológico y marino para las zonas costeras de …"
├── origen: { productor, web, language, copyright, notaLegal,
│             elaborado: "2026-08-25T20:00:00",
│             inicio:    "2026-08-25T20:00:00",
│             fin:       "2026-08-26T20:00:00" }
├── aviso:     { inicio, fin, texto: "No hay avisos.", id: "A802", nombre: "Avisos para Galicia" }
├── situacion: { analisis: "2026-08-25T12:00:00", inicio, fin, texto: "Sistema de bajas presiones…",
│                id: "S802", nombre: "Situación General Galicia" }
├── prediccion: { inicio, fin,
│                 zona[3]: { id: 8112710, nombre: "Aguas costeras de Lugo",
│                            subzona[1]: { texto: "Variable 1 a 4 excepto W 3 o 4 …",
│                                          id: 8112710, nombre: "Aguas costeras de Lugo" } } }
└── tendencia: { inicio: "2026-08-26T20:00:00", fin: "2026-08-27T20:00:00",
                 texto: "No se esperan condiciones de aviso en ninguna zona" }
```

### Detalles que importan 🟢

- **`origen` trae aquí las fechas de validez** (`elaborado`, `inicio`, `fin`), a diferencia de otros
  productos donde `elaborado` está en la raíz. **Es la vía para validar la frescura**: si `fin` ya
  ha pasado, el boletín está caducado.
- **`aviso.texto` puede ser `"No hay avisos."`** — cadena literal, no vacío ni `null`. No lo trates
  como "hay aviso" solo porque el campo exista.
- **`zona[]` y `subzona[]` anidan y a veces se repiten**: en Galicia, `zona.id` y `subzona.id` son
  ambos `8112710` con el mismo nombre. 🟡 Cuando una zona no se subdivide, la subzona se duplica.
  **Hay que recorrer siempre hasta `subzona`** para obtener los textos.
- **Los textos son prosa meteorológica marina** ("Variable 1 a 4 excepto W 3 o 4"): números de
  escala Beaufort y direcciones abreviadas. Se muestran tal cual, no se parsean.
- ⚠️ `origen.web` viene como `" http://www.aemet.es"` — **con un espacio inicial** y en `http`, no
  `https`. Otros productos usan `https` sin espacio. Hay que limpiarlo antes de usarlo como enlace.
- `id: "FQXX40"` es el identificador de boletín de la OMM; el `40` final coincide con el código de
  costa.

---

## Predicción marítima de alta mar 🟢

```
GET /api/prediccion/maritima/altamar/area/{area}
```

| | |
|---|---|
| Periodicidad 🟢 | "Dos veces al día (08:00 y 20:00) **UTC**" |
| Tamaño 🟢 | ~5,1 KB |
| TTL 🟡 | 6 h |

Verificado con `1`.

⚠️ **Ojo con las zonas horarias:** este producto se elabora en **UTC** y el costero en **hora
oficial peninsular**. Al comparar o programar tareas hay que tenerlo en cuenta.

### Códigos de área 🟢

| Código | Área de alta mar |
|---|---|
| `0` | Océano Atlántico al sur de 35º N |
| `1` | Océano Atlántico al norte de 30º N |
| `2` | Mar Mediterráneo |

🟢 **Indicativos de boletín** (campo `id` del payload), según los metadatos:
`FQMQ42` = zonas del Mediterráneo · `FQNT42` = Atlántico al norte de 30 N ·
`FQNT43` = Atlántico al sur.

⚠️ Solo **3** valores, numéricos. Es un dominio de `area` **incompatible** con los de avisos,
montaña, nivológica e incendios, que comparten el nombre del parámetro
([`ERRATAS.md` C3](ERRATAS.md#c3-area-reutiliza-el-mismo-nombre-para-5-dominios-incompatibles-)).
Nótese que `0` y `1` también son válidos en el dominio nivológico, con significados sin relación.

🟡 Las áreas `0` y `1` **se solapan** entre 30º N y 35º N: no son una partición.

### Estructura 🟢

Igual que la costera **pero sin `aviso` ni `tendencia`**:

```
[0]
├── id, nombre
├── origen: { …, elaborado, inicio, fin }
├── situacion: { analisis, inicio, fin, texto, id, nombre }
└── prediccion: { inicio, fin, zona[]: { …, subzona[]: { texto, id, nombre } } }
```

**No asumas que los dos endpoints tienen la misma forma**: código que lea `aviso` o `tendencia`
directamente falla aquí.

---

## Pendiente de verificar

| # | Qué | Prioridad |
|---|---|---|
| 1 | Forma de `aviso` cuando **sí** hay aviso activo (solo se ha visto "No hay avisos.") | 🔥 Alta — es el caso que importa |
| 2 | Si `zona[]` puede traer varias `subzona[]` distintas | Alta — condiciona el recorrido |
| 3 | Catálogo de códigos de zona/subzona (`8112710`) — **los metadatos no lo traen**: solo documentan `id` y `nombre`, el resto de sus 7 campos van vacíos | Media |
| 4 | Si alta mar alguna vez incluye `aviso` o `tendencia` | Media |
| 5 | Comportamiento cuando el boletín aún no se ha elaborado en la ventana | Media |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
