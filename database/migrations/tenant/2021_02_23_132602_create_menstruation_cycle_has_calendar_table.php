<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenstruationCycleHasCalendarTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('menstruation_cycle_has_calendar', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable();
			$table->integer('patient_id');
			$table->timestamp('start_date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->integer('length');
			$table->date('ovulation')->nullable();
			$table->string('implantation')->nullable();
			$table->date('blood_test_possible')->nullable();
			$table->date('urine_test_possible')->nullable();
			$table->string('menstruation')->nullable();
			$table->string('fertile')->nullable();
			$table->string('very_fertile')->nullable();
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
		Schema::drop('menstruation_cycle_has_calendar');
	}

}
