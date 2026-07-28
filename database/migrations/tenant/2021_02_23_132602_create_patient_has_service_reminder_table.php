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
			$table->increments('id', true); // added increments on 11-april-23
			$table->integer('patient_id');
			$table->integer('appointment_id');
			$table->integer('service_id');
			$table->integer('parent_id');
			$table->timestamp('reminder_date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->string('reminder_status', 250)->comment('set,executed,ignore');
			$table->string('type',125)->nullable();
			$table->enum('status', array('activate','deactivate'))->default('activate');
			$table->string('media',11)->nullable();
			$table->enum('read_status', array('0','1'))->default('0')->nullable()->comment('0=>unread,1=>read');
			$table->tinyInteger('notification_count')->default(0); // Added pn 28-march-24

			$table->timestamp('next_reminder_date')->nullable();
			$table->tinyInteger('is_deleted_from_ignore_state')->default(0); // Added pn 10-june-24
			$table->tinyInteger('is_added_from_age_reminder')->default(0); // Added pn 02-july-24


			$table->tinyInteger('is_sent')->default(0); // Added on 14-jan-25
			$table->timestamp('sent_date')->nullable(); // Added on 14-jan-25

			 $table->integer('cycle_no')->default(1);//added on 21-apr-25
			 $table->integer('is_deleted_through_cycle')->default(0);//added on 21-apr-25

			$table->timestamps();
			$table->softDeletes();

			//Commented below line which is added on 11-april-23 showing error of maxlength
			//$table->index(['patient_id','appointment_id','service_id','reminder_date','reminder_status'], 'patient_id');// added on 11-april-23  
			

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
