<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenstruationCycleHasCyclesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('menstruation_cycle_has_cycles', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('menstruation_cycle_id')->comment('FK menstruation_cycle');
			$table->date('date')->nullable();
			$table->integer('length')->default(0);
			$table->string('cycle', 50)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('menstruation_cycle_has_cycles');
	}

}
