<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDynamicAppointmentTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dynamic_appointment_types', function(Blueprint $table)
		{
			//$table->integer('id', true); // commented on 11-april-23
			$table->increments('id', true);// added on 11-april-23
			$table->integer('migration_id')->nullable();
			$table->integer('fk_specialist_id')->nullable();
			$table->string('name',500)->nullable();
			$table->integer('duration')->default(0);
			$table->text('description', 65535);
			$table->smallInteger('status')->default(1);
			$table->smallInteger('recommend_exams')->default(0)->comment('0=>No(Dont show recommended examinations),1=>Show Recommended Exams');
			$table->string('patient_document',125)->nullable();
			$table->string('patient_document_path',125)->nullable();
			$table->smallInteger('patient_document_status')->default(0)->comment('0=>Unread,1=>Read,2=>Sign');
			$table->enum('on_dashboard', array('0','1'))->default('0')->nullable();
			$table->enum('is_dynamic', array('0','1'))->default('0');
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
		Schema::drop('dynamic_appointment_types');
	}

}
