<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\ApiRaupulusServer;
use App\Mcp\Tools\GetModelInfoTool;
use App\Mcp\Tools\GetSystemStatusTool;
use App\Mcp\Tools\InspectDatabaseSchemaTool;
use App\Mcp\Tools\RunSpecificTestTool;
use App\Models\User;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las 4 tools del servidor MCP fallaban al invocarlas: 3 declaraban su
 * parámetro directamente en la firma de `handle()` en vez de vía `schema()`,
 * así que `laravel/mcp` anunciaba al cliente que no aceptaban argumentos y el
 * contenedor no podía resolverlos (`BindingResolutionException`). Ninguna de
 * las 4 tenía test, así que la regresión pasó desapercibida.
 */
class ApiRaupulusServerTest extends TestCase
{
    #[Test]
    public function get_system_status_tool_returns_structured_status(): void
    {
        ApiRaupulusServer::tool(GetSystemStatusTool::class)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->has('app_name')
                ->has('database')
                ->etc());
    }

    #[Test]
    public function get_model_info_tool_returns_structural_data_for_a_valid_model(): void
    {
        ApiRaupulusServer::tool(GetModelInfoTool::class, ['modelClass' => User::class])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('class', User::class)
                ->has('fillable')
                ->etc());
    }

    #[Test]
    public function get_model_info_tool_reports_an_error_for_an_unknown_class(): void
    {
        ApiRaupulusServer::tool(GetModelInfoTool::class, ['modelClass' => 'App\\Models\\NoExiste'])
            ->assertHasErrors()
            ->assertSee("Class 'App\\Models\\NoExiste' does not exist.");
    }

    #[Test]
    public function inspect_database_schema_tool_returns_columns_for_a_valid_table(): void
    {
        ApiRaupulusServer::tool(InspectDatabaseSchemaTool::class, ['tableName' => 'users'])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('table', 'users')
                ->has('columns')
                ->etc());
    }

    #[Test]
    public function inspect_database_schema_tool_reports_an_error_for_an_unknown_table(): void
    {
        ApiRaupulusServer::tool(InspectDatabaseSchemaTool::class, ['tableName' => 'no_existe'])
            ->assertHasErrors()
            ->assertSee("Table 'no_existe' does not exist.");
    }

    #[Test]
    public function run_specific_test_tool_delegates_to_a_faked_process(): void
    {
        Process::fake();

        ApiRaupulusServer::tool(RunSpecificTestTool::class, ['testName' => 'ExampleTest'])
            ->assertOk();

        Process::assertRan(['php', 'artisan', 'test', '--filter', 'ExampleTest']);
    }

    #[Test]
    public function run_specific_test_tool_rejects_input_outside_the_allowed_pattern(): void
    {
        Process::fake();

        ApiRaupulusServer::tool(RunSpecificTestTool::class, ['testName' => 'Test; rm -rf /'])
            ->assertHasErrors();

        Process::assertNothingRan();
    }
}
