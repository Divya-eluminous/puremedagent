<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppointmentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointment', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('google_event_id')->nullable();
			$table->dateTime('start_date');
			$table->dateTime('end_date');
			$table->integer('patient_id');
			$table->integer('doctor_id');
			$table->integer('appointment_type_id');
			$table->text('notes', 65535)->nullable();
			$table->smallInteger('status')->default(1)->comment('0 >> Pending, 1 >> Confirmed, 2 >> Reject');
			$table->enum('reminder_status', array('0','1'))->default('0');
			$table->string('appointment_status', 250);
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
		Schema::drop('appointment');
	}

}
