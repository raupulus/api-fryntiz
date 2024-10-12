<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class KeyCounterRemoveDuplicate
 *
 * @package App\Console\Commands
 */
class KeyCounterRemoveDuplicate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keycounter:remove_duplicate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina de la base de datos todos los registros duplicados que pudieran haber';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->removeDuplicates('keycounter_keyboard', ['start_at', 'end_at', 'pulsations', 'hardware_device_id']);
        $this->removeDuplicates('keycounter_mouse', ['start_at', 'end_at', 'total_clicks', 'hardware_device_id']);
    }

    /**
     * Remove duplicates accordingly
     *
     * @param string $table
     * @param array $groupByColumns
     *
     * @return void
     */
    private function removeDuplicates(string $table, array $groupByColumns): void
    {
        $groupByCols = implode(', ', $groupByColumns);

        // CTE (Common Table Expression) to identify duplicates
        $query = "
            DELETE FROM $table
            WHERE id NOT IN (
                SELECT min(id) FROM $table
                GROUP BY $groupByCols
            );
        ";

        DB::statement($query);
    }
}
