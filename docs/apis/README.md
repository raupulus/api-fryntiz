# 📡 Documentación de APIs Externas

> [!IMPORTANT]
> **Consulta este directorio SOLO cuando necesites referencia de una API externa de terceros.**
> No forma parte de la documentación de módulos del proyecto. Si tu tarea no toca una API
> externa, ignóralo por completo: leerlo gasta contexto sin aportar nada.

Este directorio contiene la **documentación técnica destilada de las APIs oficiales de terceros**
que consume (o consumirá) Sansimar, para que una persona o un agente de IA pueda resolver una duda
concreta sin leer especificaciones enteras ni navegar por webs oficiales.

---

## No confundir con `docs/info/apis/`

Son dos directorios con propósitos **opuestos**. Confundirlos genera documentación duplicada y
desincronizada:

| | `docs/apis/` (este directorio) | `docs/info/apis/` |
|---|---|---|
| **Qué documenta** | La API **oficial de un tercero**: qué endpoints existen, qué devuelven, qué límites tiene | **Cómo la usamos nosotros** dentro de Sansimar: servicios, comandos, modelos, caché, configuración |
| **De quién es la verdad** | De AEMET, Brevo, Google… Nosotros solo la transcribimos y verificamos | Nuestra. Es nuestro código |
| **Cuándo cambia** | Cuando el tercero cambia su API | Cuando cambiamos nuestro código |
| **Ejemplo de contenido** | "El endpoint `X` devuelve un `tar` sin comprimir en ISO-8859-15" | "`AemetService::getAvisos()` cachea 5 min y descomprime el `tar`" |

**Regla de referencia cruzada:** cuando un documento de `docs/info/apis/` aluda a algo de la API
oficial (una ruta, un código, un límite), **debe enlazar al archivo concreto de `docs/apis/`** en
vez de repetir la información. Así el dato oficial vive en un único sitio.

Ejemplo correcto en `docs/info/apis/aemet.md`:

```markdown
El comando cachea 30 min porque AEMET actualiza este producto dos veces al día
(ver [flujo y límites](aemet/00-fundamentos.md#flujo-de-dos-pasos)).
```

---

## 📚 APIs documentadas

| API | Directorio | Estado | Descripción |
|---|---|---|---|
| **AEMET OpenData** | [aemet/](aemet/README.md) | 🟢 Documentada · **los 64 endpoints verificados (100 %)** · 2026-08-26 | Datos meteorológicos y climatológicos oficiales de España. 64 endpoints REST. |

---

## 🧭 Estructura de cada API

Cada API vive en su propio subdirectorio con esta forma:

```
docs/apis/<api>/
├── README.md              # Índice: qué hay en cada archivo y normas de uso
├── ERRATAS.md             # Errores y trampas de la documentación oficial
├── LIMITACIONES.md        # Límites de uso, cuotas, caducidades, condiciones
├── 00-fundamentos.md      # Autenticación, flujo, errores, formatos (leer siempre primero)
├── NN-<dominio>.md        # Un archivo por grupo de endpoints relacionados
└── src/                   # Fuentes originales oficiales — NUNCA se editan ni se leen de rutina
    ├── _MANIFEST.md       # Procedencia, fecha de captura y fiabilidad de cada fuente
    └── …
```

Para crear la documentación de una API nueva, parte de
**[_PLANTILLA_API/](_PLANTILLA_API/COMO_USAR_LA_PLANTILLA.md)**.

Para **llevar este directorio a otro proyecto**: se copia la carpeta de la API
entera —incluido su `src/`— y se registra `docs/apis/` en las instrucciones del
proyecto destino, con la norma de que sólo se lee al trabajar contra esa API.

---

## 📏 Normas comunes a todas las APIs de este directorio

1. **Nunca confiar en la especificación oficial sin verificar.** La especificación declara
   intenciones; la API devuelve realidades. Todo dato relevante se comprueba con una petición real
   antes de darlo por bueno. En AEMET, por ejemplo, el spec declara `application/json` en los 64
   endpoints y **34 % devuelven texto plano**.
2. **Marcar la fiabilidad de cada afirmación** con la escala común (ver más abajo) y **fechar** lo
   verificado. Una afirmación sin marca de fiabilidad es una afirmación inservible.
3. **`src/` es de solo lectura.** Contiene las fuentes originales tal cual se publicaron. No se
   edita, no se corrige y no se lee de rutina: existe para poder regenerar o auditar la
   documentación destilada.
4. **Modularidad por dominio.** Un archivo por grupo de endpoints que se usan juntos, para poder
   leer solo lo necesario. El `README.md` de cada API indexa qué hay en cada archivo.
5. **Erratas y limitaciones van a sus archivos dedicados**, no dispersas por los módulos. Los
   módulos enlazan a ellos.
6. **Nunca escribir credenciales aquí.** Las claves van en `.env`. En la documentación se referencia
   la variable de entorno (`AEMET_API_KEY`), jamás su valor.
7. **Cada archivo declara sus fuentes al principio**, con un bloque
   `> **Fuentes de este archivo:**` que nombra los ficheros concretos de `src/` de los que sale su
   contenido y, si hubo verificación en vivo, su fecha. Es lo que permite saber **qué hay que volver a
   descargar** para actualizar un dato, y **de dónde salió**: de un documento oficial, de los
   metadatos de un endpoint o de una petición real.

### Escala de fiabilidad

| Marca | Significado |
|---|---|
| 🟢 **Verificado** | Comprobado con una petición real contra la API. Se indica la fecha y con qué parámetros. |
| 🔵 **Oficial** | Afirmado por el proveedor (spec, FAQ, changelog) pero **no comprobado** por nosotros. |
| 🟡 **Inferido** | Deducción nuestra a partir de fuentes oficiales. Se explica el razonamiento. |
| 🔴 **Sin verificar** | Pendiente de comprobar. **No implementar sobre esto sin verificar antes.** |
| ⚠️ **Errata** | La fuente oficial es incorrecta o engañosa. Detalle en `ERRATAS.md`. |

---

> Creado: 2026-08-26 · Última revisión: 2026-08-26
