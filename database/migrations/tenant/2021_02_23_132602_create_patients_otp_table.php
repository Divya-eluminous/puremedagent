<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePatientsOtpTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('patients_otp', function(Blueprint $table)
		{
			  $table->increments('id', true);
		      $table->string('email');
		      $table->string('mobile_no', 50);
		      $table->date('birth_date');
		      $table->string('login_otp', 50)->nullable();
		      $table->timestamp('otp_created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
		      $table->timestamps();     
			
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('patients_otp');
	}

}
