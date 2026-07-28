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
			$table->increments('id', true); // added increments on 11-april-23
			$table->integer('patient_id');
			$table->integer('service_reminder_id');
			$table->dateTime('last_reminder_date')->nullable();
			$table->dateTime('next_reminder_date')->nullable();
			$table->enum('status', array('activate','deactivate'))->default('activate');
			$table->integer('cycle_no')->default(1);//added on 21-apr-25
			$table->integer('is_deleted_through_cycle')->default(0);//added on 21-apr-25 
			$table->timestamps();
			$table->softDeletes();

		    $table->index(['patient_id','service_reminder_id'], 'patient_id');// added on 11-april-23
		    $table->index(['last_reminder_date','next_reminder_date'], 'last_reminder_date');// added on 11-april-23

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
