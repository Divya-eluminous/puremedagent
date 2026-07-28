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
			$table->increments('id', true);
			$table->integer('migration_id')->nullable();
			$table->integer('appointment_id')->comment('FK appointment');
			$table->integer('patient_id')->comment('FK patient');
			$table->integer('examination_id')->comment('FK examinations');
			// $table->enum('dismissal_flag', array('0','1','2'))->default('0');
			$table->integer('dismissal_flag')->default(0)->nullable();
			$table->string('create_from',20)->nullable(); // Added on 10-april-23
			$table->timestamps();
			$table->index(['appointment_id','patient_id','examination_id'], 'appointment_id');
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
