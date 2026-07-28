<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppoinmentTypeHasExaminationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appoinment_type_has_examinations', function(Blueprint $table)
		{
			// $table->integer('id', true);
			$table->increments('id', true);
			$table->integer('appoinment_id');
			$table->integer('examination_id');
			$table->integer('fk_specialist_id')->nullable();
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
		Schema::drop('appoinment_type_has_examinations');
	}

}
