<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRosterHasWeeksHasTimeFramesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('roster_has_weeks_has_time_frames', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('roster_id')->comment('FK roster');
			$table->integer('week_day_id')->comment('FK week_days');
			$table->time('time_frame');
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
		Schema::drop('roster_has_weeks_has_time_frames');
	}

}
