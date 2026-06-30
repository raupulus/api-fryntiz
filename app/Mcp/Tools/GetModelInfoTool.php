<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use ReflectionClass;
use ReflectionMethod;

class GetModelInfoTool extends Tool
{
    /**
     * @return non-empty-string
     */
    public function description(): string
    {
        return 'Gets structural information about an Eloquent model (fillable fields, relationships, scopes) using Reflection.';
    }

    /**
     * @param  string  $modelClass  The fully qualified class name of the model (e.g. App\\Models\\User).
     */
    public function handle(string $modelClass): string
    {
        if (! class_exists($modelClass)) {
            return "Error: Class '{$modelClass}' does not exist.";
        }

        try {
            $reflection = new ReflectionClass($modelClass);
            $model = new $modelClass;

            // Get fillable
            $fillable = $model->getFillable();

            // Get table
            $table = $model->getTable();

            // Find scopes and relations heuristically
            $scopes = [];
            $relations = [];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (str_starts_with($method->getName(), 'scope') && $method->getName() !== 'scope') {
                    $scopes[] = $method->getName();
                }

                $returnType = $method->getReturnType();
                if ($returnType && str_contains($returnType->getName(), 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                    $relations[] = [
                        'method' => $method->getName(),
                        'type' => class_basename($returnType->getName()),
                    ];
                }
            }

            return json_encode([
                'class' => $modelClass,
                'table' => $table,
                'fillable' => $fillable,
                'scopes' => $scopes,
                'relations' => $relations,
            ], JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            return 'Error analyzing model: '.$e->getMessage();
        }
    }
}
