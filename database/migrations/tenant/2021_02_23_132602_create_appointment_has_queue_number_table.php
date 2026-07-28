<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppointmentHasQueueNumberTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointment_has_queue_number', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); // Added at 1-aug-22
			$table->integer('patient_id')->comment('FK Patients');
			$table->integer('appointment_id')->comment('FK appointment');
			$table->integer('symbol_id')->comment('FK waiting_number_symbols');
			$table->date('date');
			$table->string('queue_number');
			$table->smallInteger('queue_number_type')->default(0)->comment('0=>App,1=>Tablet');
			$table->integer('called_status')->default(0)->comment('0=>OPEN,1=>CALLED');
			$table->dateTime('called_time')->nullable();
			$table->smallInteger('status');
			$table->timestamps();
			$table->softDeletes();

			$table->index(['patient_id','appointment_id','symbol_id'], 'patient_id'); // added on 11-april-23
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('appointment_has_queue_number');
	}

}
