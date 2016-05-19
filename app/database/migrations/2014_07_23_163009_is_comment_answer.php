<?php

use Illuminate\Database\Migrations\Migration;

class IsCommentAnswer extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('post_comments', function ($table) {
            $table->boolean('is_answer')->after('likes')->default(false);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('post_comments', function ($table) {
            $table->dropColumn('is_answer');
        });
	}

}