<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrdinationHasSpecialityTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ordination_has_specialist', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('ordination_id')->nullable();
			$table->integer('specialist_id')->nullable();
			$table->smallInteger('status');
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
		Schema::drop('ordination_has_specialist');
	}

}
