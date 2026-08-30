# Aemet OpenData API

[Aemet OpenData](https://opendata.aemet.es/centrodedescargas/inicio) es una API REST desarrollada por la Agencia Estatal de Meteorología (Aemet) que permite la difusión y reutilización de información meteorolica, de acuerdo con el marco normativo de reutilización de la información del sector público.

La plataforma proporciona una API REST completa formada por numerosos endpoints que permiten acceder a datos descargables y a sus metadatos asociados. Aunque esta arquitectura ofrece una gran flexibilidad, también requiere gestionar múltiples consultas, descargas y mecanismos de actualización para explotar la información de forma eficiente

## Sobre esta librería

Esta librería proporciona un cliente Python genérico para consumir APIs REST basadas en la publicación de datasets descargables. Su diseño permite automatizar la descarga de datos, gestionar metadatos asociados y reutilizar componentes comunes, reduciendo la complejidad de integración y mantenimiento.

Aunque puede utilizarse con cualquier API que siga un patrón de acceso compatible, está especialmente optimizada para la explotación de los servicios de [AEMET OpenData](https://opendata.aemet.es/centrodedescargas/inicio), facilitando el acceso, la integración y el procesamiento de la información publicada por la Agencia.

La librería incorpora además soporte para la consulta y procesamiento de fuentes **RSS**, empleándolas como mecanismo de monitorización de cambios y publicación de actualizaciones. Gracias a ello, es posible detectar la disponibilidad de nuevos datos de forma eficiente y realizar consultas a las APIs únicamente cuando resulta necesario, minimizando el tráfico, reduciendo la carga sobre los servicios y garantizando el acceso a la información más reciente disponible.

## Flujo de trabajo

AEMET OpenData API simplifica el siguiente proceso:

```text
Endpoint REST
     ↓
Respuesta JSON
     ↓
URL de datos
     ↓
URL de metadatos
     ↓
Descarga automática
```

# Inicio rápido

- [Características](#características)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Autenticación](#autenticación)
- [Uso básico](#uso-básico)
- [Parámetros en ruta](#parámetros-en-ruta)
- [Descarga automática de datasets](#descarga-automática-de-datasets)
- [Script genérico](#script-genérico)
- [Soporte RSS](#soporte-rss)
- [Comprobación manual de actualizaciones](#comprobación-manual-de-actualizaciones)
- [Sincronización automática](#sincronización-automática)
- [Utilidades disponibles](#utilidades-disponibles)
- [Documentación](#documentación)
- [Filosofía](#filosofía)
- [Aviso](#aviso)
- [Licencia](#licencia)


---

# Características

- Cliente REST reutilizable.
- Soporte para API Key.
- Parámetros de ruta (Path Parameters).
- Descarga automática de datasets.
- Descarga automática de metadatos.
- Soporte para monitorización mediante RSS.
- Sincronización automática basada en RSS.
- Conversión entre múltiples formatos.
- Ejemplos reales de entrada y salida incluidos en el repositorio.
- Arquitectura sencilla y extensible.

← [Volver al inicio](#aemet-opendata-api)

---

# Estructura del proyecto

```text
Aemet-OpenData-API/

├── common/        Componentes reutilizables
├── config/        Configuración y definición de datasets
├── data/          Datos descargados y estado local
├── docs/          Documentación
├── examples/      Ejemplos y datasets de muestra
├── scripts/       Casos de uso y automatizaciones
├── utils/         Utilidades auxiliares
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
├── LICENSE
└── requirements.txt
```
### Directorios principales

- `common/`: cliente API, descarga de recursos y soporte RSS.
- `scripts/`: ejemplos de consumo y automatización.
- `utils/`: herramientas auxiliares para transformación e inspección de datos.
- `docs/`: documentación detallada de funcionalidades y utilidades.
- `examples/`: datasets y ejemplos de referencia.
- `data/`: descargas y estado local de sincronización.

### Ejemplos

El directorio `examples/` contiene datasets de muestra y ejemplos de salida generados por las diferentes utilidades incluidas en el proyecto.

Ejemplos disponibles:

- JSON → CSV
- JSON → XLSX
- JSON → XML
- Filtrado de datasets JSON
- Predicciones municipales
- Datos observacionales

← [Volver al inicio](#aemet-opendata-api)

---

# Instalación

Clonar el repositorio:

```bash
git clone <url-del-repositorio>
cd Aemet-API
```

Instalar dependencias:

```bash
pip install -r requirements.txt
```

---
← [Volver al inicio](#aemet-opendata-api)

# Configuración

Editar:

```python
config/settings.py
```

Ejemplo:

```python
BASE_URL = "https://opendata.aemet.es/opendata"

API_KEY = "INTRODUCE_AQUI_TU_API_KEY"

DOWNLOAD_DIR = "data"

TIMEOUT = 60
```

---
← [Volver al inicio](#aemet-opendata-api)

# Autenticación

La autenticación se realiza mediante API Key, la cual tiene un periodo de caducidad según está documentado en las [FAQs de Aemet OpenData](https://opendata.aemet.es/centrodedescargas/faqs)

La API Key configurada en:

```python
API_KEY
```

será añadida automáticamente a todas las peticiones realizadas por el cliente REST.

No es necesario añadirla manualmente a cada llamada.

---
← [Volver al inicio](#aemet-opendata-api)

# Uso básico

Consumir un endpoint:

```python
from common.api_client import ApiClient

client = ApiClient()

response = client.get(
    endpoint="/api/mapasygraficos/analisis"
)

print(response)
```

---
← [Volver al inicio](#aemet-opendata-api)

# Parámetros en ruta

Algunos endpoints utilizan parámetros integrados en la propia URL.

Endpoint:

```text
/api/prediccion/especifica/montaña/pasada/area/{area}
```

Uso:

```python
response = client.get(
    endpoint="/api/prediccion/especifica/montaña/pasada/area/{area}"
    path_params={
        "area": "cat1"
    }
)
```

URL generada:

```text
https://opendata.aemet.es/opendata/api/prediccion/especifica/montaña/pasada/area/cat1
```

---
← [Volver al inicio](#aemet-opendata-api)


# Descarga automática de datasets

Si la API devuelve una respuesta similar a:

```json
{
    "datos": "https://opendata.aemet.es/opendata/sh/restoruta",
    "metadatos": "https://opendata.aemet.es/opendata/sh/restoruta"
}
```

la descarga puede realizarse mediante:

```python
from common.downloader import Downloader

downloader = Downloader()

downloader.download(response)
```

Aemet OpenData API se encargará automáticamente de:

- Descargar los datos.
- Descargar los metadatos.
- Guardar ambos archivos localmente.

---

← [Volver al inicio](#aemet-opendata-api)

# Script genérico

El script:

```text
scripts/fetch_resource.py
```

permite probar rápidamente cualquier endpoint.

Ejemplo:

```python
response = client.get(
    endpoint="/api/observacion/convencional/datos/estacion/{idema}",
    path_params={
        "idema": "4642E"
    }
)

downloader.download(response)
```

Ejecución:

```bash
python -m scripts.fetch_resource
```

---
← [Volver al inicio](#aemet-opendata-api)


# Soporte RSS

Aemet OpenData API permite utilizar [feeds RSS](https://opendata.aemet.es/centrodedescargas/rssatom) asociados a los datasets.

[Aemet OpenData](https://opendata.aemet.es/centrodedescargas/inicio) disponde de RSS, GoeRSS y ATOM de distintos conjuntos de datos.

Cuando un dataset dispone de RSS, se recomienda utilizarlo para detectar actualizaciones antes de consultar directamente el endpoint.

Flujo recomendado:

```text
RSS
 ↓
Actualización detectada
 ↓
Invocación del endpoint
 ↓
Descarga de datos
 ↓
Descarga de metadatos
```

Este enfoque reduce significativamente el número de peticiones realizadas a la API, evitando así la aparición de respuestas con el código de estado 429 (Too Many Requests).

---
← [Volver al inicio](#aemet-opendata-api)


# Comprobación manual de actualizaciones

Consultar los feeds RSS configurados:

```bash
python -m scripts.check_updates
```

Este script muestra la última publicación disponible para cada feed RSS configurado.

---
← [Volver al inicio](#aemet-opendata-api)

# Sincronización automática

Aemet OpenData API incorpora un mecanismo de sincronización automática basado en RSS.

Ejecución:

```bash
python -m scripts.auto_sync
```

Funcionamiento:

1. Consulta los feeds RSS configurados.
2. Detecta nuevas publicaciones.
3. Invoca únicamente los endpoints afectados.
4. Descarga datos y metadatos.
5. Almacena el estado local para evitar descargas repetidas.

---

← [Volver al inicio](#aemet-opendata-api)

# Utilidades disponibles

Incluye herramientas para:

## Gestión de credenciales

- Consultar la fecha de expiración de una API Key
- Calcular días restantes hasta la expiración de una API Key

[Para más información consultar:](docs/EXPIRY.md)

- Consultar el contenido de un API Key de forma legible.

[Para más información consultar:](docs/POSTMAN.md)

## Integración y pruebas

- Generar colecciones [Postman](https://www.postman.com/es) a partir de especificaciones OpenAPI

[Para más información consultar:](docs/POSTMAN.md)

## Conversión de formatos

- JSON → CSV
- JSON → XLSX
- JSON → XML
- CSV → JSON

[Para más información consultar:](docs/UTILITIES.md)

## Manipulación de datos

- Formatear JSON
- Filtrar datasets
- Validar JSON
- Mostrar esquemas
- Unir archivos JSON

[Para más información consultar:](docs/UTILITIES.md)

---

← [Volver al inicio](#aemet-opendata-api)

# Documentación

- [Sincronización automática mediante RSS ](docs/AUTO_SYNC.md)
- [Configuración y uso de feeds RSS](docs/RSS.md)
- [Conversores y utilidades disponibles ](docs/UTILITIES.md)
- [Generador de Colecciones para Postman](docs/POSTMAN.md)
- [Ejemplos completos de uso ](docs/EXAMPLES.md)

---

← [Volver al inicio](#aemet-opendata-api)

# Filosofía

Aemet OpenData API busca minimizar el código repetitivo necesario para consumir APIs basadas en datasets descargables.

La lógica de acceso a la API, la descarga de recursos y la monitorización de actualizaciones se concentran en componentes reutilizables, permitiendo que los scripts específicos permanezcan simples, legibles y fáciles de mantener.

---

# Aviso

Las utilidades y ejemplos de código incluidos en este repositorio se proporcionan únicamente con fines informativos y de referencia.

Se distribuyen "tal cual", sin garantías de ningún tipo, expresas o implícitas. El usuario es responsable de evaluar su idoneidad para cada caso de uso y de realizar las pruebas necesarias antes de su utilización en entornos de producción.

© 2026 Aemet.

← [Volver al inicio](#aemet-opendata-api)

---
# Licencia

Este proyecto se distribuye bajo la licencia MIT.

[Licencia ](LICENSE)

---

← [Volver al inicio](#aemet-opendata-api)

