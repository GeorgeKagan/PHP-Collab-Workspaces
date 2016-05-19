<?php

use Illuminate\Database\Migrations\Migration;

class CreateUserNotificationTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_notifications', function ($t) {
            $t->integer('user_id')->unique();
            $t->boolean('comments')->default(0);
            $t->boolean('posts')->default(0);
            $t->boolean('post_comments')->default(0);
            $t->tinyInteger('digest')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_notifications');
    }

}