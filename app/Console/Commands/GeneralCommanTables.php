<?php
namespace App\Console\Commands;
ini_set('memory_limit', '-1');

use Illuminate\Console\Command;
use artisan;
use Illuminate\Support\Facades\Log;
use DB;


class GeneralCommanTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'allTables:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       
        // dump("Started : activity_log table command ");
        // Artisan::call("activitylog:get");
        // dump("Ended table command ");

        // //Log::info('working');
        // dump("Started : patients table command ");
        // Artisan::call("patients:get");
        // dump("Ended table command ");

        // dump("Started : patient_has_device table command ");
        // Artisan::call("patients_has_devices:get");
        // dump("Ended table command ");

        // dump("Started : user command ");
        // Artisan::call("users:get");
        // dump("Ended table command ");

        // dump("Started : roster command ");
        // Artisan::call("roster:get");
        // dump("Ended table command ");

        // dump("Started : roster has weeks has time frames command ");
        // Artisan::call("roster_has_weeks_has_time_frames:get");
        // dump("Ended table command ");

        // dump("Started : roster has dates command ");
        // Artisan::call("roster_has_dates:get");
        // dump("Ended table command ");

        // dump("Started : patients has diagnostic findings command ");
        // Artisan::call("patients_has_diagnostic_findings:get");
        // dump("Ended table command ");

        // dump("Started : patients has diagnostic findings command ");
        // Artisan::call("patient_has_diagnostic_findings_has_documents:get");
        // dump("Ended table command ");

        // dump("Started : menstruation cycle command ");
        // Artisan::call("menstruation_cycle:get");
        // dump("Ended table command ");    

        // dump("Started : menstruation cycle has cycles command ");
        // Artisan::call("menstruation_cycle_has_cycles:get");
        // dump("Ended table command ");

        // dump("Started : menstruation cycle has calendar command ");
        // Artisan::call("menstruation_cycle_has_calendar:get");
        // dump("Ended table command ");

        // dump("Started : appointment command ");
        // Artisan::call("appointment:get");
        // dump("Ended table command ");

        // dump("Started : patients_has_dismissal command ");
        // Artisan::call("patients_has_dismissal:get");
        // dump("Ended table command ");
        
        // dump("Started : appointment_has_queue_number command ");
        // Artisan::call("appointment_has_queue_number:get");
        // dump("Ended table command ");
       
        // dump("Started : appointment_has_notification command ");
        // Artisan::call("appointment_has_notification:get");
        // dump("Ended table command ");


        // dump("Started : appointment has examinations command ");
        // Artisan::call("appointment_has_examinations:get");
        // dump("Ended table command ");

        // dump("Started : check list has selected questions command ");
        // Artisan::call("check_list_has_selected_questions:get");
        // dump("Ended table command ");    

        // dump("Started : patient has documents command ");
        // Artisan::call("patient_has_documents:get");
        // dump("Ended table command ");
        // dd('done');

        // dump("Started : patients table command ");
        // Artisan::call("patients:get");
        // dump("Ended table command ");

        // dump("Started : patient_has_device table command ");
        // Artisan::call("patients_has_devices:get");
        // dump("Ended table command ");

        // dump("Started : appointment command ");
        // Artisan::call("appointment:get");
        // dump("Ended table command ");

        // dump("Started : appointment_has_notification command ");
        // Artisan::call("appointment_has_notification:get");
        // dump("Ended table command ");

        // dump("Started : patient has documents command ");
        // Artisan::call("patient_has_documents:get");
        // dump("Ended table command ");
        // dd('done');


    }
}


