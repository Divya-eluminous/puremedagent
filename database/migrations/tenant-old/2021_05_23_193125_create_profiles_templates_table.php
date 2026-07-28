<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProfilesTemplatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('profiles_templates', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('name');
			$table->float('age_from', 10, 0);
			$table->float('age_to', 10, 0);
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
		Schema::drop('profiles_templates');
	}

}
