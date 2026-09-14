<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lotes de trabajos (`Bus::batch`) del driver de cola `database`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Las bases que ya existían la crearon con la migración que juntaba
        // `jobs` y `job_batches`; ahí esta no tiene nada que hacer.
        if (Schema::hasTable('job_batches')) {
            return;
        }

        Schema::create('job_batches', function (Blueprint $table) {
            $table->comment('Lotes de trabajos (Bus::batch).');
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches');
    }
};
