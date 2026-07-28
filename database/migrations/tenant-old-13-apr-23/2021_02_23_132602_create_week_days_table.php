<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreateWeekDaysTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tableNames = config('week_days.table_names');
        $columnNames = config('week_days.column_names');

		Schema::create('week_days', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('day', 100)->nullable();
			$table->smallInteger('status')->default(1);
			$table->timestamps();
		});

		$week_days = DB::connection('system')->table('week_days')->get();

		foreach ($week_days as $key => $value) {
            $tmp = [];
            $tmp['day'] = $value->day;
            $tmp['status'] = $value->status;
            DB::table("week_days")->insert($tmp);
        }
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$tableNames = config('week_days.table_names');
		Schema::drop('week_days');
	}

}
