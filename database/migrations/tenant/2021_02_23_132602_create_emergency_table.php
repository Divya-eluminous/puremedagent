<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEmergencyTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('emergency', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('patient_id')->comment('FK patients');
			$table->string('current_complaint')->nullable();
			$table->string('previous_treatment')->nullable();
			$table->timestamps();
			$table->dateTime('delted_at')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('emergency');
	}

}
