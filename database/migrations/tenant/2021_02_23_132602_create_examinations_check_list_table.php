<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateExaminationsCheckListTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('examinations_check_list', function(Blueprint $table)
		{
			// $table->integer('id', true); //commented on 11-april-23
			$table->increments('id', true); // added on 11-apil-23
			$table->integer('migration_id')->nullable();
			$table->integer('fk_specialist_id')->nullable();
			$table->string('check_list_name');
			$table->string('type_of_checklist')->nullable()->comment('"General","Performance"');
			$table->text('introduction_text', 65535);
			$table->text('final_name', 65535);
			$table->string('frequency')->nullable();
			$table->string('frequency_type')->nullable();
			$table->dateTime('date_of_last_activation')->nullable();
			$table->enum('signDoc', array('read','sign'))->default('read')->nullable(); 
			$table->smallInteger('status');
			$table->string('header_image')->nullable(); // added on 11-april-23
			$table->string('header_image_path')->nullable(); // added on 11-april-23
			$table->string('footer_image')->nullable(); // added on 11-april-23
			$table->string('footer_image_path')->nullable(); // added on 11-april-23
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
		Schema::drop('examinations_check_list');
	}

}
