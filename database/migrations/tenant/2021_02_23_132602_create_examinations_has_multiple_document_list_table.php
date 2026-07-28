<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateExaminationsHasMultipleDocumentListTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('examinations_has_multiple_document_list', function(Blueprint $table)
		{
			// $table->integer('id', true); //commented on 11-april-23
			$table->increments('id', true); // added on 11-april-23
			$table->integer('fk_examinations_id');
			$table->integer('fk_document_list_id');
			$table->integer('fk_specialist_id')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->index('fk_document_list_id', 'fk_document_list_id'); // added on 11-april-23
			$table->index('fk_examinations_id', 'fk_examinations_id'); // added on 11-april-23
			$table->index('fk_specialist_id', 'fk_specialist_id'); // added on 11-april-23

		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('examinations_has_multiple_document_list');
	}

}
