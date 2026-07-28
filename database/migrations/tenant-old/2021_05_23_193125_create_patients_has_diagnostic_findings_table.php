<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientsHasDiagnosticFindingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_has_diagnostic_findings', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('old_id');
			$table->integer('patient_id')->index('patient_id')->comment('FK patients');
			$table->integer('finding_type_id')->index('finding_type_id')->comment('FK Diagnostic Finding Types');
			$table->string('document_name');
			$table->date('date');
			$table->text('comment', 65535)->nullable();
			$table->smallInteger('status');
			$table->enum('export_status', array('0','1'))->default('0');
			$table->string('pdf_name')->nullable();
			$table->string('pdf_path')->nullable();
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
		Schema::drop('patients_has_diagnostic_findings');
	}

}
