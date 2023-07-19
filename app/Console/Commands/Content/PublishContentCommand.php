<?php

namespace App\Console\Commands\Content;

use App\Models\Content\Content;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PublishContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publica el contenido que ha sido programado para la fecha actual o posterior';

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
     * @return int
     */
    public function handle()
    {
        echo "\n\n Comenzando publicación de contenido \n\n";

        $now = Carbon::now();


        $contents = Content::whereNull('published_at')
            ->where(function ($q) use ($now) {
                $q->where('scheduled_at', '<=', $now)
                    ->orWhereNull('scheduled_at');
            })
            ->get();


        $ids = [];

        $contents->each(function ($content) use ($now, &$ids) {
            $content->published_at = $now;
            $content->save();

            $ids[] = $content->id;
        });


        echo "\n\n Se han publicado " . count($ids) . " páginas de contenido \n\n";
        var_dump($ids);


        echo "\n\n Fin actualización de páginas de contenido \n\n";
    }
}
