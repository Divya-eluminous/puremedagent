<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientsHasOrdinationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_has_ordination', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('fk_ordination_id');
			$table->integer('fk_patient_id');
			$table->enum('status', array('0','1','2'))->comment('0=>pending,1=>active,2=>inactive');
			$table->string('social_security_number')->nullable();
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
		Schema::drop('patients_has_ordination');
	}

}
