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
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); // Added
			$table->integer('old_finding_id')->nullable(); // Added
			$table->integer('old_id');
			$table->integer('patient_id')->comment('FK patients');
			$table->integer('finding_type_id')->comment('FK Diagnostic Finding Types');
			$table->string('document_name');
			$table->date('date');
			$table->text('comment', 65535)->nullable();
			$table->smallInteger('status');
			$table->enum('export_status', array('0','1'))->default('0');
			$table->timestamps();
			$table->softDeletes();

			$table->index(['patient_id','finding_type_id'], 'patient_id'); // added on 11-april-23


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
