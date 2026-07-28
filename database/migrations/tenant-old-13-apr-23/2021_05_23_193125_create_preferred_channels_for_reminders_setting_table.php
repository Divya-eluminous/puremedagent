<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePreferredChannelsForRemindersSettingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('preferred_channels_for_reminders_setting', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('choice_of_channels')->nullable();
			$table->string('holiday_reminder', 11);
			$table->string('reminder_push_notification_text')->nullable();
			$table->string('reminder_sms_notification_text');
			$table->string('reminder_mail_notification_text');
			$table->enum('type', array('global','service'))->default('service');
			$table->integer('service_id');
			$table->enum('activated_reminder', array('general','age','checkup'))->default('general');
			$table->integer('general_period');
			$table->string('general_period_frequency_type');
			$table->integer('general_new_frequency');
			$table->string('general_new_frequency_type');
			$table->integer('general_first_frequency');
			$table->string('general_first_frequency_type');
			$table->integer('general_time_interval');
			$table->string('general_time_interval_frequency_type');
			$table->integer('general_number_of_interval');
			$table->integer('age_from')->nullable();
			$table->integer('age_to')->nullable();
			$table->integer('age_period_controls');
			$table->string('age_period_frequency_type');
			$table->integer('age_new_frequency');
			$table->string('age_new_frequency_type');
			$table->integer('age_first_frequency');
			$table->string('age_first_frequency_type');
			$table->integer('age_time_interval');
			$table->string('age_time_interval_frequency_type');
			$table->integer('age_number_of_interval');
			$table->integer('checkup_number_of_interval');
			$table->integer('checkup_time_interval');
			$table->integer('checkup_first_frequency');
			$table->integer('checkup_new_frequency');
			$table->integer('checkup_period_controls');
			$table->string('checkup_time_interval_frequency_type')->nullable();
			$table->string('checkup_first_frequency_type')->nullable();
			$table->string('checkup_new_frequency_type')->nullable();
			$table->string('checkup_period_frequency_type')->nullable();
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
		Schema::drop('preferred_channels_for_reminders_setting');
	}

}
