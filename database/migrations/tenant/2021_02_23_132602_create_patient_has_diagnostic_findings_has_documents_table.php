<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientHasDiagnosticFindingsHasDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patient_has_diagnostic_findings_has_documents', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable();
			$table->integer('finding_id');
			$table->integer('patient_id')->nullable();
			$table->string('text', 25)->nullable();
			$table->text('original_name', 65535)->nullable();
			$table->text('file', 65535)->nullable();
			$table->string('jpg_file')->nullable();
			$table->string('pdf_file')->nullable();
			$table->timestamps();

			$table->index(['finding_id','patient_id'], 'finding_id'); // added on 11-april-23
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patient_has_diagnostic_findings_has_documents');
	}

}
