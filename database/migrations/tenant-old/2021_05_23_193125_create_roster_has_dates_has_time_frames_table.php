<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRosterHasDatesHasTimeFramesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('roster_has_dates_has_time_frames', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('roster_id')->comment('FK roster');
			$table->integer('date_id')->comment('FK roster_has_dates');
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
		Schema::drop('roster_has_dates_has_time_frames');
	}

}
