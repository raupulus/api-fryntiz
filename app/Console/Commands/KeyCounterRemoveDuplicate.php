<?php

namespace App\Console\Commands;

use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
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
        $this->removeDuplicates(Keyboard::class, ['start_at', 'end_at', 'pulsations', 'hardware_device_id']);
        $this->removeDuplicates(Mouse::class, ['start_at', 'end_at', 'total_clicks', 'hardware_device_id']);
    }

    /**
     * Remove duplicates accordingly
     *
     * @param string $model
     * @param array $groupByColumns
     *
     * @return void
     */
    private function removeDuplicates(string $model, array $groupByColumns): void
    {
        $offset = 0;
        $limit = 50;

        do {
            // Get a batch of ids of records that should be kept
            $uniqueIds = DB::table(with(new $model)->getTable())
                ->select(DB::raw('MIN(id) AS id'))
                ->groupBy($groupByColumns)
                ->offset($offset)
                ->limit($limit)
                ->pluck('id');

            if ($uniqueIds->isEmpty()) {
                break;
            }

            // Delete duplicate records but keep the ones with ids in uniqueIds
            DB::table(with(new $model)->getTable())
                ->whereNotIn('id', $uniqueIds)
                ->whereIn('id', function ($query) use ($groupByColumns, $model) {
                    $query->select('id')
                        ->from(with(new $model)->getTable())
                        ->groupBy($groupByColumns)
                        ->havingRaw('COUNT(*) > 1');
                })
                ->delete();

            $offset += $limit;
        } while (!$uniqueIds->isEmpty());
    }
}
