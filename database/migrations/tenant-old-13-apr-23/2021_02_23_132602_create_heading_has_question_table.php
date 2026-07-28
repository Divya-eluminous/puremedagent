<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHeadingHasQuestionTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('heading_has_question', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('fk_check_list_heading_section_id');
			$table->string('question');
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
		Schema::drop('heading_has_question');
	}

}
