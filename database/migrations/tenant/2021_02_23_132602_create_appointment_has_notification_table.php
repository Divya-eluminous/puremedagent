<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppointmentHasNotificationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointment_has_notification', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); // Added at 1-aug-22
			$table->integer('patient_id')->comment('FK patients');
			$table->integer('appointment_id')->comment('FK appointment');
			$table->dateTime('notify_time')->nullable();
			$table->string('title')->nullable();
			$table->string('day')->nullable();
			$table->text('content', 65535)->nullable();
			$table->smallInteger('status')->default(0)->comment('0=>Added, 1=>Notified/Unread,2=>Read,3=>Notify all patients');
			$table->text('one_signal_response', 65535)->nullable();
			$table->timestamps();


			$table->index(['patient_id','appointment_id','notify_time'], 'patient_id'); // added on 11-april-23

		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('appointment_has_notification');
	}

}
