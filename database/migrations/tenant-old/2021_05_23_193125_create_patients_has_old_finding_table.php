<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientsHasOldFindingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_has_old_finding', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('fk_patient_id');
			$table->timestamp('appoinmant_date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->enum('imported_flag', array('0','1','2'))->default('0')->comment('0=>pending,1=>import');
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
		Schema::drop('patients_has_old_finding');
	}

}
