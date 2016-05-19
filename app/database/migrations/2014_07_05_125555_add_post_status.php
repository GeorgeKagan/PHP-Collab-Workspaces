<?php

use Illuminate\Database\Migrations\Migration;

class AddPostStatus extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('posts', function ($table) {
            $table->boolean('locked')->after('likes')->default(false);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('posts', function ($table) {
            $table->dropColumn('locked');
        });
	}

}