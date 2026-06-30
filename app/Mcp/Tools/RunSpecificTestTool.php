<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Support\Facades\Process;
use Laravel\Mcp\Server\Tool;

class RunSpecificTestTool extends Tool
{
    /**
     * @return non-empty-string
     */
    public function description(): string
    {
        return 'Run a specific PHPUnit test or test suite using artisan test --filter.';
    }

    /**
     * @param  string  $testName  The name of the test method, class, or suite to filter.
     */
    public function handle(string $testName): string
    {
        $command = ['php', 'artisan', 'test', '--filter', $testName];
        $result = Process::path(base_path())->run($command);

        return $result->output()."\n".$result->errorOutput();
    }
}
