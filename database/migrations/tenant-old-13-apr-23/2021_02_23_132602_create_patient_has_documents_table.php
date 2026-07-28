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
			$table->increments('id', true);
			$table->integer('appointment_id')->default(0);
			$table->integer('patient_id');
			$table->integer('fk_examinations_id')->nullable();
			$table->integer('fk_document_id')->nullable();
			$table->string('pdf_name', 255)->nullable();
			$table->string('pdf_path', 255)->nullable();
			$table->enum('type', array('general','service'))->default('service');
			$table->timestamp('activation_start_date')->nullable();
			$table->timestamp('activation_last_date')->nullable();
			$table->integer('exam_app_type_id');
			$table->smallInteger('record_type')->default(0)->comment('0=>Exam,1=>Appointment Type');
			$table->smallInteger('doc_status')->default(0)->comment('0=>Unread,1=>Read,2=>Sign');
			$table->text('remarks', 65535)->nullable();
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
