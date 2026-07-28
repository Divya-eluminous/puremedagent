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
			$table->increments('id', true);
			$table->integer('migration_id')->nullable();//Added
			$table->integer('roster_id')->comment('FK roster');
			$table->integer('week_day_id')->comment('FK week_days');
			$table->time('time_frame');
			$table->enum('time_frame_flag', array('0','1','2'))->default('0')->nullable()->comment('0=>free,1=selected,2=used'); // Added nullable
			$table->dateTime('time_frame_flag_date')->nullable();
			$table->text('comment', 65535)->nullable();

			$table->date('start_date')->nullable(); //Added
			$table->date('end_date')->nullable();  //Added
			$table->timestamps();

			$table->index(['roster_id','week_day_id'], 'roster_id');

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
