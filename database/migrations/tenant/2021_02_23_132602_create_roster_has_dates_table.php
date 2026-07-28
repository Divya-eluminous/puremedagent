<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRosterHasDatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('roster_has_dates', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); //Added
			$table->integer('roster_id')->comment('FK roster');
			$table->integer('week_day_id')->comment('FK week_days');
			$table->date('start_date')->nullable();
			$table->date('end_date')->nullable();
			$table->date('date')->nullable();
			$table->time('from_time')->nullable();
			$table->time('to_time')->nullable();
			$table->smallInteger('date_index');
			$table->smallInteger('is_excluded')->default(0)->comment('0=>Included(Not Excluded), 1 =>Excluded');
			$table->timestamps();
			$table->index(['roster_id','week_day_id','date'], 'roster_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('roster_has_dates');
	}

}
