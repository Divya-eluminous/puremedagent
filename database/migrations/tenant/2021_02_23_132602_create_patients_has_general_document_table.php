<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientsHasGeneralDocumentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_has_general_document', function(Blueprint $table)
		{
			$table->increments('id', true); // added increments on 11-april-23
			$table->integer('patient_id');
			$table->integer('document_id')->comment('check_list_id and document_id');
			$table->smallInteger('type')->comment('\'check_list\',\'document\'');
			$table->dateTime('activation_start_date')->nullable();
			$table->dateTime('activation_last_date')->nullable();
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
		Schema::drop('patients_has_general_document');
	}

}
