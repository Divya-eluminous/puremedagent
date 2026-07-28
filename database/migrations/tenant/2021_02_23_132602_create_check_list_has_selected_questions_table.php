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
			// $table->integer('id', true); //Commented on 11-april-23
			$table->increments('id', true); // Added increments on 11-april-23
			$table->integer('migration_id'); // Added at 1-aug-22
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
			$table->string('export_status',50)->nullable()->default('0'); // Added 50
			$table->enum('type', array('general','performance'))->nullable();
			$table->dateTime('activation_start_date')->nullable();
			$table->dateTime('activation_last_date')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->index(['fk_patient_id','fk_appointment_id','fk_examination_id','fk_check_list_id','export_status'], 'fk_patient_id'); // added on 11-april-23
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
