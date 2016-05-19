<?php

use Illuminate\Database\Migrations\Migration;

class ChangeChatGroupIdType extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chat', function($table)
        {
            $table->dropColumn('group_id');
        });
        Schema::table('chat', function($table)
        {
            $table->string('group_id')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat', function($table)
        {
            $table->dropColumn('group_id');
        });
        Schema::table('chat', function($table)
        {
            $table->integer('group_id')->after('user_id');
        });
    }

}