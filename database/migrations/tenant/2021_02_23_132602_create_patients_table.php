<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\DB;


class CreatePatientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->integer('migration_id')->nullable(); // Added
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
			$table->string('str_password')->nullable();
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
			// $table->integer('postal_code')->nullable()->default(0);

			$table->string('postal_code')->nullable(); //Roshani added this line chnage the datatype for germany code which start with 0 , on 27 nov 24

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
			$table->enum('note_report_request_flag', array('0','1','2'))->default('0');
			$table->string('social_security_number')->nullable();
			$table->enum('note_report_request_from', array('default','admin','app')); // Added

			$table->integer('sendSMS')->default(1); // Added
			$table->integer('sendMail')->default(1); // Added

			$table->enum('is_updated', array('0','1'))->nullable()->default('0'); // Added
			$table->enum('new_flag', array('0','1','2'))->nullable()->default('0'); // Added
			$table->tinyInteger('sendNotification')->default(0); // Added
			$table->tinyInteger('finding_request_admin_flag')->default(0); // Added

			$table->timestamps();
			$table->softDeletes();
			$table->index(['family_name','first_name','mobile_no'], 'family_name');
			$table->index(['first_name','family_name'], 'first_name_2');


			$table->index('email', 'email'); // added on 11-april-23
			$table->enum('country', ['Austria', 'Germany', 'Switzerland'])->nullable();//added on 10-dec-24
		
			// $table->fullText('family_name', 'family_name_2'); // added on 11-april-23
			// $table->fullText('first_name', 'first_name'); // added on 11-april-23

		    // $table->index(['family_name'], 'fulltext_title');
           //  $table->raw('ALTER TABLE old_patients ADD FULLTEXT fulltext_title (family_name)');

            // $table->index(['first_name'], 'fulltext_title');
            // $table->raw('ALTER TABLE old_patients ADD FULLTEXT fulltext_title (first_name)');
			
		});


		// Add FULLTEXT indexes after table creation added on 18-oct-24
        DB::statement('ALTER TABLE patients ADD FULLTEXT fulltext_index_first_name (first_name)');
        DB::statement('ALTER TABLE patients ADD FULLTEXT fulltext_index_family_name (family_name)');



	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{

		// Drop FULLTEXT indexes first added on 18-oct-24
        DB::statement('ALTER TABLE patients DROP INDEX fulltext_index_first_name');
        DB::statement('ALTER TABLE patients DROP INDEX fulltext_index_family_name');


		Schema::drop('patients');
	}

}
