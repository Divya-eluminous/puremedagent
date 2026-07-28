<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientHasServiceControlReminderSettingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patient_has_service_control_reminder_setting', function(Blueprint $table)
		{
			$table->increments('id', true); // added increments on 11-april-23
			$table->integer('patient_id');
			$table->integer('appointment_id');
			$table->integer('service_id');
			$table->integer('control_interval');
			$table->string('control_frequency');
			$table->smallInteger('status')->default(1);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['patient_id','appointment_id','service_id','control_interval','control_frequency'], 'patient_id');// added on 11-april-23

		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patient_has_service_control_reminder_setting');
	}

}
