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
			$table->integer('id', true);
			$table->integer('fk_specialist_id')->nullable();
			$table->string('check_list_name');
			$table->string('type_of_checklist')->nullable()->comment('"General","Performance"');
			$table->text('introduction_text', 65535);
			$table->text('final_name', 65535);
			$table->string('frequency')->nullable();
			$table->string('frequency_type')->nullable();
			$table->dateTime('date_of_last_activation')->nullable();
			$table->enum('signDoc', array('read','sign'))->default('read'); 
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
		Schema::drop('examinations_check_list');
	}

}
