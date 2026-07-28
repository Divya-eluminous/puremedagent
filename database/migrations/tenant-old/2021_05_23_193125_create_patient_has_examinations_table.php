<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientHasExaminationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patient_has_examinations', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('patient_id')->comment('FK-patient');
			$table->integer('examination_id')->comment('FK-examinations');
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patient_has_examinations');
	}

}
