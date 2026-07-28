<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSpecialistHasDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('specialist_has_documents', function(Blueprint $table)
		{
			$table->increments('id', true);// added increments on 11-april-23
			$table->integer('fk_specialist_id');
			$table->string('type_of_document',125); // Added 125
			$table->text('name', 65535);
			$table->text('html_text', 65535);
			$table->string('header_image')->nullable();
			$table->string('header_image_path')->nullable();
			$table->string('footer_image')->nullable();
			$table->string('footer_image_path')->nullable();
			$table->string('background_color');
			$table->string('frequency')->nullable();
			$table->string('frequency_type')->nullable();
			$table->timestamp('date_of_last_activation')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
			$table->enum('status', array('0','1','2'))->comment('0=>pending,1=>active,2=>inactive');
			$table->enum('signDoc', array('read','sign'))->default('read'); // Added

			$table->timestamps();
			$table->softDeletes();

			$table->index('type_of_document', 'type_of_document');


		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('specialist_has_documents');
	}

}
