<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGoogleColorsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('google_colors', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->string('name', 100);
			$table->string('code', 50);
			$table->smallInteger('status');
		});

		$googleColors = \App\Helpers\MigrationHelper::getFromSystem('google_colors');

        foreach ($googleColors as $key => $value) {
            $tmp = [];          
            $tmp['name'] = $value->name;
            $tmp['code'] = $value->code;
            $tmp['status'] = $value->status;                   
            DB::connection('tenant')->table("google_colors")->insert($tmp);
        }   
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('google_colors');
	}

}
