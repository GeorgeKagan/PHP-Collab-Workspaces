<?php

use Illuminate\Database\Migrations\Migration;

class ChangeUserPasswordFieldType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        DB::statement("ALTER TABLE `users` CHANGE `password` `password` VARCHAR(100) NOT NULL;");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        DB::statement("ALTER TABLE `users` CHANGE `password` `password` INT(10) UNSIGNED NULL DEFAULT NULL;");
	}

}