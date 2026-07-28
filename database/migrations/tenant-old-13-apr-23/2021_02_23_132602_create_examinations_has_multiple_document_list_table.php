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
			$table->integer('id', true);
			$table->integer('fk_examinations_id');
			$table->integer('fk_document_list_id');
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
		Schema::drop('examinations_has_multiple_document_list');
	}

}
