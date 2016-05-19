<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LoginBrootforceTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('brootforce', function($t)
        {
            $t->increments('id')->unique();
            $t->string('email',100);
            $t->string('ip',15);
            $t->integer('retries');
            $t->timestamp('first_attempt');
            $t->integer('timeout')->nullable();
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('brootforce');
	}

}
