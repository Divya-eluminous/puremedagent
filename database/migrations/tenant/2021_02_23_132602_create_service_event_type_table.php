<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateServiceEventTypeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('service_event_type', function(Blueprint $table)
		{
			// $table->integer('id', true);
			$table->increments('id', true); // added on 11-april-23
			$table->integer('patient_id');
			$table->integer('appoinment_id');
			$table->integer('service_id');
			$table->enum('event_type', array('admin','web','smart_phone','tablet'))->default('admin');  
			$table->enum('status', array('displayed','booked'))->default('displayed');
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
		Schema::drop('service_event_type');
	}

}
