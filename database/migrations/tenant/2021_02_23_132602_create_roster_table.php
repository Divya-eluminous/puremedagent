<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRosterTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('roster', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); //Added
			$table->integer('user_id')->comment('FK users who inserted record');
			$table->integer('doctor_id')->comment('FK users->role->doctor');
			$table->integer('appointment_type_id')->default(0);
			$table->smallInteger('status')->default(1);
			$table->timestamps();
			$table->softDeletes();

			$table->index(['user_id','doctor_id','appointment_type_id'], 'user_id'); // added on 11-april-23

		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('roster');
	}

}
