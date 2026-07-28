<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppointmentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointment', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable();
			$table->string('google_event_id')->nullable();
			$table->integer('event_id')->nullable();
			$table->dateTime('start_date');
			$table->dateTime('end_date');
			$table->integer('patient_id');
			$table->integer('doctor_id');
			$table->integer('appointment_type_id');
			$table->text('notes', 65535)->nullable();
			$table->smallInteger('status')->default(1)->comment('0 >> Pending, 1 >> Confirmed, 2 >> Reject');
			$table->enum('reminder_status', array('0','1'))->default('0');
			$table->string('appointment_status', 100);
			$table->tinyInteger('qrcode_process_status')->default(0)->comment('0 >> default, 1 >> Process');  // Added by vijay
			$table->timestamps();
			$table->softDeletes();

			$table->index(['google_event_id','start_date','patient_id','doctor_id','appointment_type_id'], 'google_event_id'); // added on 11-april-23
			$table->index('appointment_status', 'appointment_status'); // added on 11-april-23
			$table->tinyInteger('assign_to_doc_dashboard')->default(0)->comment('0 >> default, 1 >> Assign');  // Added on 12-apr-24
			$table->integer('is_app_booked')->nullable()->default(1);//added by roshani on 30/04/2024
			// added by vijay 12/9/2024
			$table->tinyInteger('appointment_created_from')
				->nullable()->default(null)->comment('1- Dashboard, 2- Assistant-dashboard, 3- Appointment manage, 4- web, 5- App');
			$table->tinyInteger('appointment_updated_from')
				->nullable() ->default(null)->comment('1- Dashboard, 2- Assistant-dashboard, 3- Appointment manage, 4- web, 5- App');
			$table->tinyInteger('optimal_appointment') ->nullable()->default(null) ->comment('1 - optimal appointment');
			$table->integer('appointment_createdby')->nullable()->default(null)->comment('appointment created by user');
			$table->integer('appointment_updatedby')->nullable()->default(null)->comment('appointment updated by user');
			// end
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('appointment');
	}

}
