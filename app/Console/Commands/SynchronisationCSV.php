<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
use App\Models\SettingsModel;
use App\Models\PatientsModel;
use App\Exports\PatientCollectionExport;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use Storage;
use Mail;
use Maatwebsite\Excel\Facades\Excel;


class SynchronisationCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SynchronisationCSV';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check folder file access';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SettingsModel $SettingsModel,PatientsModel $PatientsModel)
    {
        parent::__construct();       
        $this->SettingsModel = $SettingsModel;
        $this->PatientsModel = $PatientsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {  
        //Log::info("called csv cron");
        $admin_email = $this->SettingsModel
                        ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                        ->whereStatus(1)
                        ->first();
        $report_title = date('Y-m-d').'-Patient-record.xls';

        $PatientsModel =  $this->PatientsModel
                        ->where('old_id','=','99999')
                        ->get();

        $a = array('Id','Previous Appoitment Date','Next Appoitment Date','First Name','Family Name');

        $file = Excel::store(new PatientCollectionExport($PatientsModel,$a), 'mail_files/'.$report_title);

        $file_path = storage_path().'/app/mail_files/'.$report_title;

        $data = array('content'=> 'Bitte überprüfe den Anhang.');

        $sendFile = Mail::send('admin.mail.app-patient-email', $data, function($message) use($admin_email,$file_path){

        $message->to($admin_email->setting_value);

        $message->subject(date('Y-m-d').'-Patient-record');

        $message->from($admin_email->setting_value,'Puregyn');

        $message->attach($file_path);

        });
        
    }

   
}
