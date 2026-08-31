<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Inspects a specific database table and returns its columns and their data types.')]
class InspectDatabaseSchemaTool extends Tool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tableName' => $schema->string()
                ->description('The name of the table to inspect.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $tableName = (string) $request->string('tableName');

        if (! Schema::hasTable($tableName)) {
            return Response::error("Error: Table '{$tableName}' does not exist.");
        }

        $columns = Schema::getColumnListing($tableName);
        $schema = [];

        foreach ($columns as $column) {
            $schema[] = [
                'name' => $column,
                'type' => Schema::getColumnType($tableName, $column),
            ];
        }

        $indexes = array_map(fn ($index) => $index['name'].' ('.implode(', ', $index['columns']).')', Schema::getIndexes($tableName));
        $foreignKeys = array_map(fn ($fk) => $fk['columns'][0].' -> '.$fk['foreign_table'].'.'.$fk['foreign_columns'][0], Schema::getForeignKeys($tableName));

        return Response::structured([
            'table' => $tableName,
            'columns' => $schema,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ]);
    }
}
