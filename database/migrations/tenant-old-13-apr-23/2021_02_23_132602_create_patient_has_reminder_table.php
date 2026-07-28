<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientHasReminderTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patient_has_reminder', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('patient_id');
			$table->integer('service_reminder_id');
			$table->dateTime('last_reminder_date')->nullable();
			$table->dateTime('next_reminder_date')->nullable();
			$table->enum('status', array('activate','deactivate'))->default('activate');
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
		Schema::drop('patient_has_reminder');
	}

}
