<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateExaminationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('examinations', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('fk_specialist_id')->nullable();
			$table->string('name');
			$table->text('description', 65535)->nullable();
			$table->string('url');
			$table->string('document_name')->nullable();
			$table->string('document_path')->nullable();
			$table->smallInteger('document_status')->nullable()->default(0)->comment('0=>Unread,1=>Read,2=>Sign');
			$table->smallInteger('default_service')->nullable();
			$table->smallInteger('status');
			$table->smallInteger('show_as_control')->nullable()->default(0)->comment('1=show on doctoer dashboard,0=not show');
			$table->string('check_list_pdf_name')->nullable();
			$table->string('check_list_pdf_path')->nullable();
			$table->enum('check_list_status', array('0','1','2'))->comment('0=> pending,1=>unread,2=>signed');
			$table->string('signature')->nullable();
			$table->smallInteger('trigger_exam_flag')->default(0);
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
		Schema::drop('examinations');
	}

}
