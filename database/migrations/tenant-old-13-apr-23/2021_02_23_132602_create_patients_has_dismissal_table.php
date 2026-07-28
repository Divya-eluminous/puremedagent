<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientsHasDismissalTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_has_dismissal', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('fk_patient_id');
			$table->integer('appointment_id')->nullable();;
			$table->integer('fk_dismissal_id');
			$table->enum('status', array('0','1'))->default('0');
			$table->enum('dismissal_flag', array('0','1','2'))->comment('0=>pending,1=>done');
			$table->enum('type', array('dismissal', 'examinations'))->default('dismissal');
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
		Schema::drop('patients_has_dismissal');
	}

}
