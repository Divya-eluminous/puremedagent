<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDynamicAppointmentTypesHasExaminationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dynamic_appointment_types_has_examinations', function(Blueprint $table)
		{
			// $table->integer('id', true);
			$table->increments('id', true); // added on 11-april-23
			$table->integer('dynamic_appointment_type_id');
			$table->integer('examination_id');
			$table->integer('fk_specialist_id')->nullable();
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
		Schema::drop('dynamic_appointment_types_has_examinations');
	}

}
