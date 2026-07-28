<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCheckListHasSelectedQuestionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('check_list_has_selected_questions', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('fk_patient_id');
			$table->integer('fk_appointment_id')->nullable();
			$table->integer('fk_examination_id')->nullable();
			$table->integer('fk_check_list_id')->nullable();
			$table->text('questions', 65535);
			$table->enum('check_list_flag', array('0','1'))->default('0');
			$table->string('pdf_name')->nullable();
			$table->string('pdf_path')->nullable();
			$table->string('signature')->nullable();
			$table->string('status')->nullable()->default('0')->comment('0=> unread,1=>read,2=>signed,3=>print,4=>mail');
			$table->enum('type', array('general','performance'))->nullable();
			$table->dateTime('activation_start_date')->nullable();
			$table->dateTime('activation_last_date')->nullable();
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
		Schema::drop('check_list_has_selected_questions');
	}

}
