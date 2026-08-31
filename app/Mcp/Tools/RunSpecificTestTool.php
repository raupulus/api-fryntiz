<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Process;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Run a specific PHPUnit test or test suite using artisan test --filter.')]
class RunSpecificTestTool extends Tool
{
    /**
     * Solo se admiten los caracteres que puede tener un nombre de clase, método
     * o suite PHPUnit: letras, dígitos, `_`, `:`, `\`, `.` y espacios. Acota la
     * superficie de esta tool antes de pasarla a un `Process::run`.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'testName' => $schema->string()
                ->description('The name of the test method, class, or suite to filter.')
                ->pattern('^[\w:\\\\. ]+$')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $testName = (string) $request->string('testName');

        if (preg_match('/^[\w:\\\\. ]+$/', $testName) !== 1) {
            return Response::error("Error: '{$testName}' no es un filtro de test válido.");
        }

        $command = ['php', 'artisan', 'test', '--filter', $testName];
        $result = Process::path(base_path())->run($command);

        return Response::text($result->output()."\n".$result->errorOutput());
    }
}
