---
name: mcp-server
description: >-
  Integración Model Context Protocol (MCP) de Api Raupulus con el paquete
  laravel/mcp. Cárgala SIEMPRE que trabajes en app/Mcp/ (Servers, Tools,
  Resources, Prompts), en routes/ai.php, en el servidor ApiRaupulusServer, o
  cuando crees/edites herramientas MCP, registres nuevas tools, uses los comandos
  mcp:start / mcp:inspector / make:mcp-*, o quieras exponer contexto o
  funcionalidad del proyecto a un LLM o asistente de IA (Claude Desktop, Cursor,
  etc.). Úsala ante "MCP", "servidor MCP", "tool de IA", "exponer a un LLM",
  "Model Context Protocol" o "conector para Claude/Cursor", aunque no se nombre el
  paquete. Para la lógica de negocio que consuma la tool usa laravel-backend.
---

# MCP — Servidor de IA del proyecto (`laravel/mcp`)

Paquete: **`laravel/mcp` ^0.8.1**. Expone el contexto/funcionalidad del proyecto
a LLMs vía Model Context Protocol. Documentación viva del módulo en
`docs/info/mcp.md` (mantenla actualizada al cambiar algo aquí).

## Estructura

```
app/Mcp/
├── Servers/ApiRaupulusServer.php   # Servidor (registra tools/resources/prompts)
└── Tools/GetSystemStatusTool.php   # Herramientas expuestas
app/Console/Commands/Mcp/InspectorCommand.php   # mcp:inspector sobrescrito (pnpm)
routes/ai.php                                    # Registro de transportes
```

## Registro (routes/ai.php)

El servidor se publica en dos transportes; al añadir un servidor nuevo, regístralo
en ambos si procede:

```php
use App\Mcp\Servers\ApiRaupulusServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/api-raupulus', ApiRaupulusServer::class);   // HTTP (GET/POST/DELETE)
Mcp::local('api-raupulus', ApiRaupulusServer::class);      // Stdio/CLI
```

## Servidor

Atributos `#[Name]`, `#[Version]`, `#[Instructions]` y arrays de capacidades.
**Para exponer una herramienta nueva, añádela al array `$tools`** (no basta con
crear la clase):

```php
#[Name('Api Raupulus Server')]
#[Version('0.0.1')]
#[Instructions('...')]
class ApiRaupulusServer extends Server
{
    protected array $tools = [ GetSystemStatusTool::class ];
    protected array $resources = [];
    protected array $prompts = [];
}
```

## Tools

Una tool: `extends Laravel\Mcp\Server\Tool`, `#[Description('...')]` (en inglés,
clara y orientada al LLM), `handle(Request): Response`, y `schema(JsonSchema)`
para los argumentos. Devuelve datos con `Response::structured($array)`.

```php
#[Description('Get basic status, environment, and database connection details.')]
class GetSystemStatusTool extends Tool
{
    public function handle(Request $request): Response
    {
        // ... recopila datos ...
        return Response::structured($status);
    }

    public function schema(JsonSchema $schema): array
    {
        return []; // sin argumentos
    }
}
```

Convenciones de seguridad para tools en este proyecto:

- Las tools exponen el sistema a un LLM externo: trata cada `handle()` como un
  endpoint público. **Valida los argumentos** vía `schema()` y **no expongas
  secretos** (claves, `.env` sensible, credenciales) en la respuesta.
- Las tools de **lectura** son la norma. Si una tool **escribe**, protégela con
  la autorización del dominio (Policies de `app/Policies/`) igual que harías en la
  API; no confíes en el cliente MCP.
- Reutiliza Services (`app/Services/<Modulo>/`) para la lógica; la tool es una
  fachada fina, como un controlador (ver skill `laravel-backend`).

## Comandos

```bash
php artisan mcp:start api-raupulus       # Arranca el servidor local (Stdio)
php artisan mcp:inspector api-raupulus   # Inspector oficial (sobrescrito: prioriza pnpm dlx, fallback npx)
php artisan make:mcp-server NombreServer
php artisan make:mcp-tool NombreTool
php artisan make:mcp-resource NombreResource
php artisan make:mcp-prompt NombrePrompt
```

Nota: `mcp:inspector` está **sobrescrito** en `App\Console\Commands\Mcp\InspectorCommand`
para usar `pnpm dlx @modelcontextprotocol/inspector` (coherente con el resto del
proyecto, que prefiere pnpm) y caer a `npx` solo si pnpm no está disponible.

## Al terminar

1. Registra la tool/resource/prompt en el array correspondiente del servidor.
2. `./vendor/bin/pint`.
3. Actualiza `docs/info/mcp.md` (lista de tools, argumentos y respuesta).
