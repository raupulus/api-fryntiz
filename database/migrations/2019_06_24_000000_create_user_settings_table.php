<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateUsersConfigurationTable
 */
class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->boolean('send_email')
                ->nullable()
                ->default(true)
                ->comment('Indica si permite el envío de emails con información no prioritaria');
            $table->boolean('send_notification')
                ->nullable()
                ->default(true)
                ->comment('Indica si quiere notificaciones.');
            $table->boolean('send_notification_push')
                ->nullable()
                ->default(true)
                ->comment('Indica si permite notificaciones push');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_settings');
    }
}
