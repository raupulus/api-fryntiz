<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id - name - translation_display_name_token - created_at - updated_at
        Schema::create('users_roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->string('name', '255');
            $table->unsignedBigInteger('translation_display_name_token');
            /*
            $table->foreign('translation_display_name_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('set null');
            */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_roles', function (Blueprint $table) {
            $table->dropForeign(['translation_display_name_token']);
        });
    }
}
