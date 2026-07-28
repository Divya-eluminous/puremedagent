<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSupportSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('support_settings', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('name');
			$table->string('url');
			$table->smallInteger('status')->default(1)->comment('1=>Display,0=>Hide	');
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
		Schema::drop('support_settings');
	}

}
