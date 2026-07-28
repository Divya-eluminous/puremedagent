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
			$table->integer('id')->primary();
			$table->string('name');
			$table->string('url');
			$table->smallInteger('status')->default(1)->comment('1=>Display,0=>Hide	');
			$table->integer('user_id')->comment('FK users');
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
		Schema::drop('menus_settings');
	}

}
