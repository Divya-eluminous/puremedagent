<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateActivityLogsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('activity_logs', function(Blueprint $table)
		{
			$table->integer('id');
			$table->string('module', 100)->nullable();
			$table->string('action', 100)->nullable();
			$table->text('old_data', 65535)->nullable();
			$table->text('new_data', 65535)->nullable();
			$table->string('message', 100)->nullable();
			$table->string('method', 100)->nullable();
			$table->string('url', 100)->nullable();
			$table->string('ip', 100)->nullable();
			$table->text('agent', 65535)->nullable();
			$table->integer('user_id')->default(0);
			$table->integer('patient_id')->nullable()->default(0);
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
		Schema::drop('activity_logs');
	}

}
