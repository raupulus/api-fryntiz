# 🧩 Cómo usar esta plantilla

Plantilla para documentar una **API externa de terceros** en `docs/apis/`. Nace de la experiencia
documentando AEMET OpenData, donde se descubrió que **la especificación oficial no describía
fielmente el comportamiento real de la API**: el formato de respuesta, la codificación y la forma de
los errores estaban mal documentados, y había endpoints devolviendo datos de hacía cuatro años con
un `200 OK`.

La plantilla está diseñada para que eso se detecte **antes** de escribir código, no después.

> [!IMPORTANT]
> Esta carpeta **no** documenta ninguna API. Es el molde. No la edites: cópiala.

---

## Procedimiento

### 1. Copiar la plantilla

```bash
cp -r docs/apis/_PLANTILLA_API docs/apis/<nombre-api>
rm docs/apis/<nombre-api>/COMO_USAR_LA_PLANTILLA.md
```

### 2. Recopilar las fuentes originales en `src/`

**Antes de escribir una sola línea de documentación.** En `src/` va todo lo oficial, tal cual se
publicó, sin editar nunca:

- La especificación (OpenAPI / Swagger / Postman), si existe.
- Las FAQs, changelog y páginas de condiciones legales.
- Catálogos de códigos que la API referencie pero no incluya.
- READMEs de clientes oficiales.

Descárgalas con `curl` y anota **la URL y la fecha** de cada una en `src/_MANIFEST.md`.

Consejos aprendidos con AEMET:
- **Muchas páginas cargan el contenido por JavaScript.** El HTML descargado sale vacío y los datos
  reales están en un `.json` aparte. Busca en el HTML rutas a `.json`.
- **Guarda el original y una transcripción a texto.** El original da fidelidad; la transcripción
  permite hacer `grep` sin lidiar con codificaciones mixtas.
- **Las FAQs suelen ser la fuente más valiosa**, más que la especificación: es donde aparecen los
  límites de uso, las caducidades y los retrasos de publicación.
- **El changelog revela cambios de política** que ninguna otra fuente menciona.

### 3. Verificar contra la API real — el paso que no se puede saltar

> [!CAUTION]
> **Nunca documentes a partir de la especificación sin comprobarlo.** La especificación declara
> intenciones; la API devuelve realidades.

Por cada familia de endpoints, comprueba **como mínimo**:

| Qué | Por qué |
|---|---|
| **`Content-Type` real** de la respuesta | Puede no ser el declarado (en AEMET, 34 % de los endpoints) |
| **Codificación real** del cuerpo | En AEMET era ISO-8859-15: `json_decode` devolvía `null` en silencio |
| **Forma del cuerpo** en éxito | Raíz, claves, tipos, si los números vienen como cadenas |
| **Forma del cuerpo en error** | Puede ser HTML en vez de JSON |
| **Qué pasa sin credenciales** | En AEMET: `200` con cuerpo vacío, no `401` |
| **Códigos de error dentro de un `200`** | En AEMET: `estado: 404` con HTTP 200 |
| **Frescura del contenido** | En AEMET había datos de hacía 4 años con `200 OK` |
| **Tamaño de la respuesta** | Antes de llamar a cualquier endpoint "todos" |
| **Cabeceras del 429** | ¿Hay `Retry-After`? En AEMET no |

**Espacia las peticiones** (5 s funcionó bien con AEMET) y **rota entre familias de endpoints**.
Nunca sondees a propósito el umbral de un límite: es abusar del servicio. Mídelo por observación.

### 4. Marcar la fiabilidad de todo

Escala común a todas las APIs de `docs/apis/`:

| Marca | Significado |
|---|---|
| 🟢 **Verificado** | Comprobado con petición real. Indica **fecha y parámetros** |
| 🔵 **Oficial** | Lo afirma el proveedor pero **no lo hemos comprobado** |
| 🟡 **Inferido** | Deducción nuestra, con el razonamiento explicado |
| 🔴 **Sin verificar** | Pendiente. **No implementar sobre esto** |
| ⚠️ **Errata** | La fuente oficial está mal. Detalle en `ERRATAS.md` |

**Una afirmación sin marca de fiabilidad es inservible**: quien la lea no sabrá si puede confiar en
ella. Es preferible un 🔴 honesto que un dato que parece firme y no lo es.

### 5. Rellenar los archivos

| Archivo | Qué poner |
|---|---|
| `README.md` | Índice, normas de uso y la tabla "qué leer según la tarea" |
| `00-fundamentos.md` | Autenticación, flujo, errores, codificación, formatos. **Lo transversal** |
| `ERRATAS.md` | Todo lo que la documentación oficial dice mal, agrupado por gravedad |
| `LIMITACIONES.md` | Límites, cuotas, caducidades, retrasos, lo que la API no ofrece |
| `NN-<dominio>.md` | Un archivo por grupo de endpoints que se usan juntos |

### 6. Enlazarla desde los índices

1. Añadir una fila en la tabla de [`docs/apis/README.md`](../README.md#-apis-documentadas).
2. Si va a usarse en el proyecto, crear su documento en `docs/info/apis/` describiendo **nuestra
   integración**, enlazando aquí para lo oficial.

---

## Cómo dividir en módulos

Regla: **un archivo por grupo de endpoints que se consultan juntos al resolver una tarea.**

- ✅ **Bien**: "predicciones por municipio" (incluyendo el maestro de municipios, que hace falta para
  obtener el código).
- ❌ **Mal**: un archivo por tag de la especificación, si eso separa endpoints que siempre se usan
  juntos o junta 22 que no tienen nada que ver.
- ❌ **Mal**: un archivo por endpoint. Multiplica los saltos entre ficheros.

Criterio de tamaño 🟡: si un archivo pasa de ~400 líneas, probablemente mezcla dos dominios. Si baja
de ~60, probablemente debería estar unido a otro.

**Comprueba la suma.** Si la API tiene N endpoints, los módulos deben sumar N. Es la forma más simple
de detectar que te has dejado alguno.

---

## Reglas que no se negocian

1. **`src/` nunca se edita.** Ni para corregir una errata evidente. Las correcciones van en la
   documentación destilada, señalando la errata.
2. **Ninguna credencial en la documentación.** Van en `.env`; aquí se cita la variable.
3. **Fechar lo verificado.** Una API cambia; "verificado" sin fecha no dice nada.
4. **Las erratas y limitaciones van a sus archivos**, no dispersas. Los módulos enlazan a ellos.
5. **Documentar lo que falta.** Cada módulo termina con su tabla de "pendiente de verificar". Un
   hueco reconocido es información; un hueco disimulado es una trampa.
6. **Cada archivo declara sus fuentes al principio.** Un bloque `> **Fuentes de este archivo:**` con
   los ficheros concretos de `src/` de los que sale su contenido, más la fecha de la verificación en
   vivo si la hubo. Sin eso, dentro de seis meses nadie sabrá qué hay que volver a descargar para
   actualizar un dato — ni si ese dato salió de un documento oficial o de una petición real.

---

## Errores a evitar (todos cometidos de verdad)

| Error | Qué pasó |
|---|---|
| Confiar en un MD de otro proyecto | Tenía dos endpoints inexistentes (404 real) y límites de uso inventados |
| Deducir la codificación de un endpoint | Dos endpoints de AEMET *parecen* UTF-8 porque son numéricos; el resto no lo es |
| Confiar en `$response->successful()` | Un `200` puede traer `estado: 404`, cuerpo vacío o datos de hace años |
| Inventar TTL de caché | Los metadatos de cada producto traen la periodicidad real. Úsala |
| Suponer el caso de uso | Prioricé por un negocio que había deducido del repo, y era falso. Pregunta o cubre todo |
| Fiarse de un campo `total` | El JSON de canales RSS declaraba `total: 800`; eran 41 |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
