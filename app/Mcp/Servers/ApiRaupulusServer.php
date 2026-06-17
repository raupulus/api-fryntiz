<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetModelInfoTool;
use App\Mcp\Tools\GetSystemStatusTool;
use App\Mcp\Tools\InspectDatabaseSchemaTool;
use App\Mcp\Tools\RunSpecificTestTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Api Raupulus Server')]
#[Version('0.0.1')]
#[Instructions('Instructions describing how to use the server and its features.')]
class ApiRaupulusServer extends Server
{
    protected array $tools = [
        GetSystemStatusTool::class,
        RunSpecificTestTool::class,
        InspectDatabaseSchemaTool::class,
        GetModelInfoTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
