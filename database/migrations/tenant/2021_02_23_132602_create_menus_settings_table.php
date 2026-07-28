<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenusSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('menus_settings', function(Blueprint $table)
		{
			// $table->integer('id')->primary();
			$table->increments('id',true); // added on 11-april-23
			$table->string('name');
			$table->string('url');
			$table->smallInteger('status')->default(1)->comment('1=>Display,0=>Hide	');
			$table->integer('user_id')->comment('FK users');
			$table->timestamps();
			$table->softDeletes();
		});


		//start Added below code on 10-sept-24
		$menus_settings = \App\Helpers\MigrationHelper::getFromSystem('menus_settings');

		foreach ($menus_settings as $key => $value) {
            $tmp = [];
            $tmp['name'] = $value->name;
            $tmp['url'] = $value->url;
            $tmp['status'] = $value->status;
            $tmp['user_id'] = $value->user_id;           
            DB::connection('tenant')->table("menus_settings")->insert($tmp);
        }
        //end Added below code on 10-sept-24

	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('menus_settings');
	}

}
