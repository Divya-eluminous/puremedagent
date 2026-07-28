<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOldPatientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('old_patients', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('old_id')->default(0);
			$table->integer('pat_nr')->nullable()->default(0);
			$table->string('family_name', 50)->nullable();
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('email')->nullable();
			$table->string('country_code', 20)->nullable();
			$table->string('mobile_no', 50)->nullable()->comment('phone number 1 from Ganymed DB');
			$table->string('ganymed_mobile_no', 50)->nullable();
			$table->date('birth_date')->nullable();
			$table->string('age', 100)->default('0');
			$table->string('password')->nullable();
			// $table->string('str_password')->nullable();
			$table->string('login_otp', 50)->nullable();
			$table->timestamp('otp_created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->text('api_access_token', 65535)->nullable();
			$table->timestamp('last_login_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->enum('login_type', array('app','google'))->default('app');
			$table->smallInteger('is_blocked')->nullable()->default(0)->comment('1=>Blocked,0=>UnBlock');
			$table->smallInteger('status')->nullable()->default(1);
			$table->string('mobile_token')->nullable();
			$table->string('token', 100)->nullable();
			$table->string('road')->nullable();
			$table->string('street_no')->nullable();
			$table->string('place')->nullable();
			$table->integer('postal_code')->nullable()->default(0);
			$table->char('gender', 1)->nullable();
			$table->smallInteger('size')->nullable()->default(0);
			$table->smallInteger('weight')->nullable()->default(0);
			$table->string('title', 20)->nullable();
			$table->string('salutation', 100)->nullable();
			$table->string('family_doctor', 50)->nullable();
			$table->string('insurance_number')->nullable();
			$table->string('additional_insurance', 50)->nullable();
			$table->smallInteger('gdpr')->default(0)->comment('0=>Not Selected,1=>Selected');
			$table->smallInteger('update_ganydb')->default(0)->comment('0=>Dont update,1=>update to ganymed,2=>insert to ganymed db');
			$table->enum('patient_status_flag', array('0','1'))->default('0');
			$table->enum('reminder_active', array('0','1'))->default('1');
			$table->text('note_report_request', 65535)->nullable();
			$table->enum('note_report_request_flag', array('1','0'))->default('0');
			$table->string('social_security_number')->nullable();
			$table->timestamps();
			$table->softDeletes();
			$table->index(['family_name','first_name','mobile_no'], 'family_name');
			$table->index(['first_name','family_name'], 'first_name_2');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patients');
	}

}
