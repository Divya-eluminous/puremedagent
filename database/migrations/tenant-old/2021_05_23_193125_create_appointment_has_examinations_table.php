<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppointmentHasExaminationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointment_has_examinations', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('appointment_id')->comment('FK appointment');
			$table->integer('patient_id')->comment('FK patient');
			$table->integer('examination_id')->comment('FK examinations');
			$table->enum('dismissal_flag', array('0','1','2'))->nullable()->default('0');
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('appointment_has_examinations');
	}

}
