<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessContentViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $contentId;
    public $viewedAt;

    public function __construct($contentId, $viewedAt)
    {
        $this->contentId = $contentId;
        $this->viewedAt = $viewedAt;
    }

    public function handle()
    {
        $date = $this->viewedAt->format('Y-m-d');

        try {
            // Intento actualizar primero
            $updated = DB::table('content_daily_views')
                ->where('content_id', $this->contentId)
                ->where('date', $date)
                ->update([
                    'views' => DB::raw('views + 1'),
                    'updated_at' => now()
                ]);

            // Si no se actualizó ninguna fila, creo nueva
            if ($updated === 0) {
                DB::table('content_daily_views')->insert([
                    'content_id' => $this->contentId,
                    'date' => $date,
                    'views' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            // Si hay un error de duplicado (race condition), intento update de nuevo
            if (str_contains($e->getMessage(), 'duplicate key')) {
                DB::table('content_daily_views')
                    ->where('content_id', $this->contentId)
                    ->where('date', $date)
                    ->update([
                        'views' => DB::raw('views + 1'),
                        'updated_at' => now()
                    ]);
            } else {
                \Log::error('Error processing view: ' . $e->getMessage());
            }
        }
    }
}
