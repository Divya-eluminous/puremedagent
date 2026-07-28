<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientHasServiceReminderTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patient_has_service_reminder', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('patient_id');
			$table->integer('appointment_id');
			$table->integer('service_id');
			$table->integer('parent_id');
			$table->timestamp('reminder_date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->string('reminder_status', 250)->comment('set,executed,ignore');
			$table->string('type')->nullable();
			$table->enum('status', array('activate','deactivate'))->default('activate');
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
		Schema::drop('patient_has_service_reminder');
	}

}
