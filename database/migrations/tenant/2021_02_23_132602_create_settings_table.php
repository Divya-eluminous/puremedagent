<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$tableNames = config('settings.table_names');
        $columnNames = config('settings.column_names');
        
        \Log::info("Settings migration: Starting migration execution");
        
		Schema::create('settings', function(Blueprint $table)
		{
			$table->increments('id', true);
			$table->string('setting_key', 100);
			$table->text('setting_value', 65535);
			$table->string('description',125)->nullable(); // Added 125
			$table->smallInteger('status')->default(1);
			$table->timestamps();
			$table->softDeletes();
		});
		
		\Log::info("Settings migration: Table created successfully");
		
		// $settings = DB::connection('system')->table('settings')->get();
		try {
			\Log::info("Settings migration: Attempting to connect to system database");
			
			// Use helper to ensure correct system connection
			$settings = \App\Helpers\MigrationHelper::getFromSystem('settings', ['deleted_at' => null]);
			
			\Log::info("Settings migration: Successfully connected to system database");
		} catch (Exception $e) {
			\Log::error("Settings migration: Failed to connect to system database - " . $e->getMessage());
			return;
		}
		
		// Debug: Log the settings count
		\Log::info("Settings migration: Found " . count($settings) . " settings in system database");

		//Roshani hidden the below code for CR #70 on 4-nov-24
		// foreach ($settings as $key => $value) {
        //     $tmp = [];
        //     $tmp['setting_key'] = $value->setting_key;
        //     $tmp['setting_value'] = $value->setting_value;
        //     $tmp['description'] = $value->description;
        //     $tmp['status'] = $value->status;           
        //     DB::table("settings")->insert($tmp);
        // } 
		//Roshani hidden the below code for CR #70 on 4-nov-24

		//Roshani added the below code for CR #70 on 4-nov-24
		$copiedCount = 0;
		\Log::info("Settings migration: Starting data copy loop with " . count($settings) . " settings");
        foreach ($settings as $key => $value) {
		    // Insert only if the setting_key is NOT 'FORCED UPDATE FOR SMARTPHONE APPS'
        //This code added by roshani for CR #126 on 6-nov-24
		    $skipKeys = [
			    'FORCED UPDATE FOR SMARTPHONE APPS',
			    'ORDINATION_EMAIL',
			    'ORDINATION_MOBILE',
			    'APP_TEXT_STARTSEITE_LOGOUT',
			    'APP_LOGGED_MINS',
				'FINDING_KEYWORDS',//Roshani made the changes for point 309 on 6-aug-2025
				'ADMINISTRATOR_EMAIL'//Roshani made the changes for point 309 on 6-aug-2025
			];
		    // if ($value->setting_key != 'FORCED UPDATE FOR SMARTPHONE APPS' || $value->setting_key != 'ORDINATION_EMAIL' || $value->setting_key != 'ORDINATION_MOBILE' || $value->setting_key != 'APP_TEXT_STARTSEITE_LOGOUT' || $value->setting_key != 'APP_LOGGED_MINS') {  //Roshani added this code for CR #325 (n and o) on 16-april-25
			if (!in_array($value->setting_key, $skipKeys)) {
		        $tmp = [];
		        $tmp['setting_key']    = $value->setting_key;
		        $tmp['setting_value']  = $value->setting_value;
		        $tmp['description']    = $value->description;
		        $tmp['status']         = $value->status;           

		                // Insert the record into the 'settings' table
        try {
            DB::connection('tenant')->table("settings")->insert($tmp);
            $copiedCount++;
            \Log::info("Settings migration: Successfully copied setting: " . $value->setting_key);
        } catch (Exception $e) {
            \Log::error("Settings migration: Failed to copy setting {$value->setting_key} - " . $e->getMessage());
        }

		                //Roshani added this code for CR #213 on 6-nov-24
        // if ($value->setting_key == 'EMERGENCY_BUTTON_EMAIL_ADDRESS') {
        //     $getOrdinationEmail = \App\Helpers\MigrationHelper::getFirstFromSystem('settings', ['setting_key' => 'ORDINATION_EMAIL']);
            
        //     if ($getOrdinationEmail) { // Check if the record was found
        //         DB::connection('tenant')->table("settings")
        //             ->where('setting_key', 'EMERGENCY_BUTTON_EMAIL_ADDRESS')
        //             ->update(['setting_value' => $getOrdinationEmail->setting_value]);
        //     }
        // }
		        //Roshani added this code for CR #213 on 6-nov-24

				//Roshani added this code for 325 (k) on 10-april-25
		        // if ($value->setting_key == 'ORDINATION_EMAIL_ADDRESS') {
				//     $getOrdinationEmail = DB::connection('system')->table('settings')->where('setting_key', 'ORDINATION_EMAIL')->first();
				    
				//     if ($getOrdinationEmail) { // Check if the record was found
				//         DB::table("settings")
				//             ->where('setting_key', 'ORDINATION_EMAIL_ADDRESS')
				//             ->update(['setting_value' => $getOrdinationEmail->setting_value]);
				//     }
				// }
		        //Roshani added this code for 325 (k) on 10-april-25

		                //Roshani added this code for CR #325 (l) on 16-april-25
        // if ($value->setting_key == 'EMERGENCY_BUTTON_EMAIL_ADDRESS') {
        //     $getOrdinationEmail = \App\Helpers\MigrationHelper::getFirstFromSystem('settings', ['setting_key' => 'ORDINATION_EMAIL_ADDRESS']);
            
        //     if ($getOrdinationEmail) { // Check if the record was found
        //         DB::connection('tenant')->table("settings")
        //             ->where('setting_key', 'EMERGENCY_BUTTON_EMAIL_ADDRESS')
        //             ->update(['setting_value' => $getOrdinationEmail->setting_value]);
        //     }
        // }
		        //Roshani added this code for CR #325 (l) on 16-april-25
		/* added on 03-march-26 for #325 peter told to set the ORDINATION_EMAIL_ADDRESS and EMERGENCY_BUTTON_EMAIL_ADDRESS to null while creating new ordination */

			if (in_array($value->setting_key, [
				'EMERGENCY_BUTTON_EMAIL_ADDRESS',
				'ORDINATION_EMAIL_ADDRESS'
			])) {
				DB::connection('tenant')->table("settings")
					->where('setting_key', $value->setting_key)
					->update(['setting_value' => NULL]);
			}
		    }
		}
		
		// Debug: Log the final count
		\Log::info("Settings migration: Copied {$copiedCount} settings to tenant database");
		\Log::info("Settings migration: Migration completed successfully");
		
		//Roshani added the below code for CR #70 on 4-nov-24


		    //Add notification setting here on 11-nov-25

		    $record_exist = DB::connection('tenant')->table("appointment_has_notification")
                                 ->whereStatus(3)
                                 ->first(['id']);

            if(isset($record_exist)){

            }
            else
            {
            	$content = 'Hallo ##PATIENT_NAME##, Ihr Termin mit Dr. ##DOCTOR_SURNAME##(##APPOINTMENT_TYPE##) ist am ##DATE_TIME##';
	            $current_date = date("Y-m-d",time());
	            $notify_time = "09:00";
	            $title = 'Erinnerung an Ihren Termin';

				$tmp_setting = [];
		        $tmp_setting['patient_id']     = 0;
		        $tmp_setting['appointment_id'] = 0;
		        $tmp_setting['notify_time']    = $current_date." ".$notify_time;
		        $tmp_setting['day']            = 0;           
		        $tmp_setting['content']        = $content;  		     
		        $tmp_setting['title']          = $title;           
	     		$tmp_setting['status']         = 3;           

	     		Log::info($tmp_setting);
		        try {
		            DB::connection('tenant')->table("appointment_has_notification")->insert($tmp_setting);
		             \Log::info("Notification Settings added Successfully");
		        } catch (Exception $e) {
		            \Log::error("Notification Settings failed");
		        }
            }//else

		   


	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$tableNames = config('settings.table_names');

		Schema::drop('settings');
	}

}
