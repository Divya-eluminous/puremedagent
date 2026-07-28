<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientHasDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patient_has_documents', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('appointment_id')->default(0);
			$table->integer('patient_id');
			$table->integer('exam_app_type_id')->nullable();
			$table->integer('fk_examinations_id')->nullable();
			$table->integer('fk_document_id')->nullable();
			$table->smallInteger('record_type')->default(0)->comment('0=>Exam,1=>Appointment Type');
			$table->string('doc_status', 266)->default('0')->comment('0=> unread,1=>read,2=>signed,3=>print,4=>mail');
			$table->text('remarks', 65535)->nullable();
			$table->string('pdf_name')->nullable();
			$table->string('pdf_path')->nullable();
			$table->enum('type', array('general','service'))->nullable();
			$table->dateTime('activation_start_date')->nullable();
			$table->dateTime('activation_last_date')->nullable();
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
		Schema::drop('patient_has_documents');
	}

}
