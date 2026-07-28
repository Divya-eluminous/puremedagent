<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tableNames = config('settings.table_names');
        $columnNames = config('settings.column_names');
        
		Schema::create('settings', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->string('setting_key', 100);
			$table->text('setting_value', 65535);
			$table->string('description')->nullable();
			$table->smallInteger('status')->default(1);
			$table->timestamps();
			$table->softDeletes();
		});
		$settings = DB::connection('system')->table('settings')->get();

		foreach ($settings as $key => $value) {
            $tmp = [];
            $tmp['setting_key'] = $value->setting_key;
            $tmp['setting_value'] = $value->setting_value;
            $tmp['description'] = $value->description;
            $tmp['status'] = $value->status;           
            DB::table("settings")->insert($tmp);
        } 
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$tableNames = config('settings.table_names');

		Schema::drop('settings');
	}

}
