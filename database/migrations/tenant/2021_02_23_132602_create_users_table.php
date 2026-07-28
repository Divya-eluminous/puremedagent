<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('migration_id')->nullable(); //Added
			$table->integer('doctor_id');
			$table->string('first_name',125);
			$table->string('last_name', 100)->nullable();
			$table->string('email',125);
			$table->string('profile_img')->nullable();
			$table->string('img_path')->nullable();
			$table->string('password');
			$table->string('str_password', 100)->nullable();
			$table->string('country_code', 20)->nullable();
			$table->string('mobile_number', 20)->nullable();
			$table->string('remember_token', 100)->nullable();
			$table->smallInteger('google_color_id')->nullable()->comment('FK google_colors');
			$table->string('color',125)->nullable();
			$table->string('doctor_speciality',125)->nullable();
			$table->smallInteger('status')->default(1);
			// $table->string('str_password', 100)->nullable();
			// $table->string('login_otp', 255)->nullable();
			$table->string('login_otp', 255); // Remove nullable here
			// $table->timestamp('otp_created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamp('otp_created_at')->default(DB::raw('CURRENT_TIMESTAMP')); // Remove nullable 
			$table->enum('is_updated', array('0','1'))->nullable()->default('0');
			// $table->text('message', 65535)->nullable();
			$table->text('message', 65535); // Remove nullable 
			$table->timestamps();

			$table->index('doctor_id', 'doctor_id'); // added on 11-april-23
			$table->index('google_color_id', 'google_color_id'); // added on 11-april-23


		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
