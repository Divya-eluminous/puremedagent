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
			$table->increments('id', true); // added increments on 11-april-23
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
			$table->string('general_new_frequency_type',125);
			$table->integer('general_first_frequency');
			$table->string('general_first_frequency_type',125);
			$table->integer('general_time_interval');
			$table->string('general_time_interval_frequency_type',125);
			$table->integer('general_number_of_interval');
			$table->integer('age_from')->nullable();
			$table->integer('age_to')->nullable();
			$table->integer('age_period_controls');
			$table->string('age_period_frequency_type',125);
			$table->integer('age_new_frequency');
			$table->string('age_new_frequency_type',125);
			$table->integer('age_first_frequency');
			$table->string('age_first_frequency_type',125);
			$table->integer('age_time_interval');
			$table->string('age_time_interval_frequency_type',125);
			$table->integer('age_number_of_interval');
			$table->integer('checkup_number_of_interval');
			$table->integer('checkup_time_interval');
			$table->integer('checkup_first_frequency');
			$table->integer('checkup_new_frequency');
			$table->integer('checkup_period_controls');
			$table->string('checkup_time_interval_frequency_type',125)->nullable();
			$table->string('checkup_first_frequency_type',125)->nullable();
			$table->string('checkup_new_frequency_type',125)->nullable();
			$table->string('checkup_period_frequency_type',125)->nullable();
			$table->string('notify_time',250)->nullable(); // Added
			$table->integer('is_reminder_updated')->default(0); //Added

			$table->integer('recommanded_service_id')->nullable(); // Added on 11-april-23

			$table->integer('general_end_cycle')->default(0);  // New added on 9-feb-24
			$table->string('general_end_cycle_frequency_type',125)->nullable(); // New added on 9-feb-24
			$table->integer('age_end_cycle')->default(0);  // New added on 9-feb-24
			$table->string('age_end_cycle_frequency_type',125)->nullable(); // New added on 9-feb-24
			$table->integer('checkup_end_cycle')->default(0);   // New added on 9-feb-24
			$table->string('checkup_end_cycle_frequency_type',125)->nullable(); // New added on 9-feb-24


			$table->timestamps();
			$table->softDeletes();
		}); 

		   $reminderSetting = DB::connection('system')->table('preferred_channels_for_reminders_setting')
			    ->where('type', 'global')
			    ->limit(1)
			    ->first();

			if ($reminderSetting) {
			    $tmp = [
			        'choice_of_channels' => $reminderSetting->choice_of_channels,
			        'notify_time' => $reminderSetting->notify_time,
			        'holiday_reminder' => $reminderSetting->holiday_reminder,
			        'reminder_push_notification_text' => $reminderSetting->reminder_push_notification_text,
			        'reminder_sms_notification_text' => $reminderSetting->reminder_sms_notification_text,
			        'reminder_mail_notification_text' => $reminderSetting->reminder_mail_notification_text,
			        'type' => $reminderSetting->type,
			        'general_period' => $reminderSetting->general_period,
			        'general_period_frequency_type' => $reminderSetting->general_period_frequency_type,
			        'general_new_frequency' => $reminderSetting->general_new_frequency,
			        'general_new_frequency_type' => $reminderSetting->general_new_frequency_type,
			        'general_first_frequency' => $reminderSetting->general_first_frequency,
			        'general_first_frequency_type' => $reminderSetting->general_first_frequency_type,
			        'general_time_interval' => $reminderSetting->general_time_interval,
			        'general_time_interval_frequency_type' => $reminderSetting->general_time_interval_frequency_type,
			        'general_number_of_interval' => $reminderSetting->general_number_of_interval,

			        /*********************************/
			        'general_end_cycle' => $reminderSetting->general_end_cycle,
			        'general_end_cycle_frequency_type' => $reminderSetting->general_end_cycle_frequency_type,
			        /*********************************/


			        'checkup_period_controls' => $reminderSetting->checkup_period_controls,
			        'checkup_period_frequency_type' => $reminderSetting->checkup_period_frequency_type,
			        'checkup_new_frequency' => $reminderSetting->checkup_new_frequency,
			        'checkup_new_frequency_type' => $reminderSetting->checkup_new_frequency_type,
			        'checkup_first_frequency' => $reminderSetting->checkup_first_frequency,
			        'checkup_first_frequency_type' => $reminderSetting->checkup_first_frequency_type,
			        'checkup_time_interval' => $reminderSetting->checkup_time_interval,
			        'checkup_time_interval_frequency_type' => $reminderSetting->checkup_time_interval_frequency_type,
			        'checkup_number_of_interval' => $reminderSetting->checkup_number_of_interval,

			        /*********************************/
			        'checkup_end_cycle' => $reminderSetting->checkup_end_cycle,
			        'checkup_end_cycle_frequency_type' => $reminderSetting->checkup_end_cycle_frequency_type,
			        /*********************************/
			        
			    ];

			    DB::table("preferred_channels_for_reminders_setting")->insert($tmp);
			}//if reminderSetting

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
