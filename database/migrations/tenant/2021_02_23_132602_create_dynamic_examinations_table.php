<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDynamicExaminationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dynamic_examinations', function(Blueprint $table)
		{
			// $table->integer('id', true);
			$table->increments('id', true); // added on 11-april-23
			$table->integer('migration_id')->nullable(); 
			$table->integer('fk_specialist_id')->nullable();
			$table->string('name',125);
			$table->text('description')->nullable();
			$table->string('url',125);
			$table->string('document_name',125)->nullable();
			$table->string('document_path',125)->nullable();
			$table->smallInteger('document_status')->default(0)->nullable()->comment('0=>Unread,1=>Read,2=>Sign');
			$table->smallInteger('default_service')->nullable();
			$table->smallInteger('status');
			$table->smallInteger('show_as_control')->default(0)->nullable()->comment('1=show on doctoer dashboard,0=not show');
			$table->string('check_list_pdf_name',125)->nullable();
			$table->string('check_list_pdf_path',125)->nullable();
			$table->enum('check_list_status', array('0','1','2'))->comment('0=> pending,1=>unread,2=>signed'); 
			$table->string('signature',125)->nullable();
			$table->smallInteger('trigger_exam_flag')->default(0)->nullable();
			$table->enum('show_as_reminder', array('0','1'))->default(0)->comment('0=>not set,1=>set reminder');
			$table->enum('show_as_recommended', array('0','1'))->default(0)->nullable();
			$table->enum('on_dashboard', array('0','1'))->default(0)->nullable();  
			$table->integer('sequence_no')->nullable();

			$table->timestamps();
			$table->softDeletes();

			$table->index('show_as_control', 'show_as_control'); // added on 11-april-23
			$table->index('status', 'status'); // added on 11-april-23
			$table->index('name', 'name'); // added on 11-april-23
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('dynamic_examinations');
	}

}
