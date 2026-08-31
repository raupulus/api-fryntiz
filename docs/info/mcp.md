# Integración con Model Context Protocol (MCP)

Integración que expone el framework Laravel y el contexto del proyecto a modelos de lenguaje (LLM) y asistentes de IA a través del protocolo MCP oficial.

## Archivos Principales

- **Rutas de IA:** [routes/ai.php](../../routes/ai.php)
- **Servidor MCP:** [app/Mcp/Servers/ApiRaupulusServer.php](../../app/Mcp/Servers/ApiRaupulusServer.php)
- **Herramientas (Tools):**
  - [app/Mcp/Tools/GetSystemStatusTool.php](../../app/Mcp/Tools/GetSystemStatusTool.php): Obtiene información sobre el estado de la aplicación, variables de entorno y base de datos.

## Rutas y Endpoints

El servidor está registrado y expuesto de dos formas:

1. **Interfaz Web (HTTP):**
   - **URL:** `/mcp/api-raupulus`
   - **Métodos:** `GET` / `POST` / `DELETE`
   - **Uso:** Utilizado por clientes MCP que se conectan a través de transporte HTTP.

2. **Interfaz Local (CLI/Stdio):**
   - **Handle:** `api-raupulus`
   - **Uso:** Arrancado mediante línea de comandos (Stdio) con `php artisan mcp:start api-raupulus`.

## Herramientas Registradas (Tools)

### GetSystemStatusTool
- **Clase:** `App\Mcp\Tools\GetSystemStatusTool`
- **Descripción:** Obtiene un resumen del estado de la aplicación.
- **Argumentos:** Ninguno.
- **Respuesta:** JSON estructurado con:
  - `app_name`: Nombre de la aplicación.
  - `env`: Entorno actual (`local`, `production`, etc.).
  - `debug`: Si el modo debug está activo (`true`/`false`).
  - `laravel_version`: Versión de Laravel instalada.
  - `php_version`: Versión de PHP.
  - `database`: Estado de conexión, driver configurado y posible error de conexión.

## Comandos Útiles de Desarrollo

```bash
# Iniciar el Inspector oficial de MCP para depurar y probar servidores
php artisan mcp:inspector api-raupulus

# Arrancar el servidor MCP local para clientes vía Stdio (e.g., Cursor, Claude Desktop)
php artisan mcp:start api-raupulus

# Generar nuevos elementos MCP
php artisan make:mcp-server NombreServer
php artisan make:mcp-tool NombreTool
php artisan make:mcp-resource NombreResource
php artisan make:mcp-prompt NombrePrompt
```

## Personalizaciones del Proyecto

### Priorización de `pnpm` en `mcp:inspector`
El comando original `mcp:inspector` provisto por el paquete `laravel/mcp` ejecuta el inspector de MCP utilizando `npx` por defecto. 

Para mejorar la seguridad y consistencia con el ecosistema de este proyecto (que prefiere `pnpm`), hemos sobrescrito el comando creando la clase `App\Console\Commands\Mcp\InspectorCommand`. Este comando personalizado:
1. Comprueba en tiempo de ejecución si el comando `pnpm` está instalado en el sistema.
2. Si existe, ejecuta el inspector usando `pnpm dlx @modelcontextprotocol/inspector`.
3. Si no existe, realiza un *fallback* automático a `npx @modelcontextprotocol/inspector`.

---

> Creado: 2026-06-17 · Última revisión: 2026-08-19
