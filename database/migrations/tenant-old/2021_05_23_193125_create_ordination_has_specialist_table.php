<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrdinationHasSpecialistTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ordination_has_specialist', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('ordination_id');
			$table->integer('specialist_id');
			$table->enum('status', array('0','1'))->comment('0=pending,1=active');
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
