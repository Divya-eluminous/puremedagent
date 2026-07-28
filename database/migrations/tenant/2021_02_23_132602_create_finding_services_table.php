<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFindingServicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('finding_services', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->string('name');
			$table->string('type')->nullable();
			$table->string('web_link');
			$table->smallInteger('status');
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
		Schema::drop('finding_services');
	}

}
