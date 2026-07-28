<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProfileHasExaminationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('profile_has_examinations', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('profile_id')->comment('FK profiles_templates');
			$table->integer('examination_id');
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
		Schema::drop('profile_has_examinations');
	}

}
