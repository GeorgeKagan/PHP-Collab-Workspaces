<?php

use Illuminate\Database\Migrations\Migration;

class UpdatePostsComments extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('post_comments', function($table)
        {
            $table->string('img',20)->nullable();
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('post_comments', function($table)
        {
            $table->dropColumn('img');
        });
	}

}