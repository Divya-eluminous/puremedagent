<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSpecialistTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('specialist', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->text('name', 65535);
			$table->enum('status', array('0','1'))->default('0')->comment('0=inactive,1=active');
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
		Schema::drop('specialist');
	}

}
