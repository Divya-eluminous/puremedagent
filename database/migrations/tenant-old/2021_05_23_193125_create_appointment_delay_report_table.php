<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppointmentDelayReportTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointment_delay_report', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('patient_id')->comment('FK Patients');
			$table->integer('appointment_id')->comment('FK appointment');
			$table->string('delay_time');
			$table->string('custome_message');
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
		Schema::drop('appointment_delay_report');
	}

}
