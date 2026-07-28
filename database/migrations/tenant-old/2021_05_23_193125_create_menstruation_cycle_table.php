<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenstruationCycleTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('menstruation_cycle', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('patient_id');
			$table->date('latest_date')->nullable()->comment('LetzteRegel(Last menstruation 1)');
			$table->integer('latest_length');
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
		Schema::drop('menstruation_cycle');
	}

}
