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
        // id - name - display_name - created_at - updated_at
        Schema::create('users_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', '255');
            $table->string('translation_display_name_token', 511);
            $table->foreign('translation_display_name_token')
                ->references('token')->on('translations')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
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
