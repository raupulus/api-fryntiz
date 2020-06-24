<?php

namespace App\Console\Commands;

use App\Keycounter\Keyboard;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KeyCounterGenerateDuration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keycounter:generate_duration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera la duración para los registros que no se le calculara automáticamente';

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
        $keyboard = Keyboard::whereNull('duration')->get();

        foreach ($keyboard as $k) {
            ## Calculo la duración en segundos de la racha.
            $start = new Carbon($k->start_at);
            $end = new Carbon($k->end_at);
            $duration = $start->diffInSeconds($end);

            ## Almaceno la duración en segundos de la racha.
            $k->duration = $duration;

            $k->save();
        }
    }
}
