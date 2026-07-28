<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDeletedAppointmentTrackTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('deleted_appointment_track', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); //New added
			$table->string('google_event_id')->nullable();
			$table->dateTime('start_date');
			$table->dateTime('end_date');
			$table->integer('patient_id');
			$table->integer('doctor_id');
			$table->integer('appointment_type_id');
			$table->text('notes', 65535)->nullable();
			$table->smallInteger('status')->default(1)->comment('0 >> Pending, 1 >> Confirmed, 2 >> Reject');
			$table->enum('reminder_status', array('0','1'))->default('0');
			$table->string('appointment_status', 100);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['google_event_id','start_date','patient_id','doctor_id','appointment_type_id'], 'google_event_id'); // added on 11-april-23
			$table->index('appointment_status', 'appointment_status'); // added on 11-april-23
			
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('deleted_appointment_track');
	}

}
