<?php

namespace App\Console\Commands;

use App\Models\KeyCounter\Keyboard;
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
     * @return mixed
     */
    public function handle()
    {
        ## Obtiene todos los id únicos en un array.
        $keyboardUniques_ids = Keyboard::select(
            DB::raw('min(id) as id1'), 'start_at')
            ->groupBy('start_at', 'end_at', 'pulsations', 'device_id', 'device_name')
            ->get()
            ->pluck('id1');

        $keycounter = Keyboard::whereNotIn('id', $keyboardUniques_ids)->delete();
    }
}
