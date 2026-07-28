<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBeaconsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('beacons', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->string('beacon_identifier')->nullable();
			$table->string('beacon_UUID', 250);
			$table->string('beacon_major', 250);
			$table->string('beacon_minor', 250);
			$table->integer('status')->nullable();
			$table->string('device', 250)->nullable();
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
		Schema::drop('beacons');
	}

}
