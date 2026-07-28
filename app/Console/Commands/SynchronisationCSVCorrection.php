<?php

namespace App\Console\Commands; 

use Illuminate\Console\Command;
use App\Models\SettingsModel;
use App\Models\PatientsModel;
use App\Exports\PatientCollectionExportNew;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
use Storage;
use Mail;
use Maatwebsite\Excel\Facades\Excel;


class SynchronisationCSVCorrection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SynchronisationCSVNew';

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
        //Log::info("called correction csv cron");
        $admin_email = $this->SettingsModel
                        ->where('setting_key','=','ADMINISTRATOR_EMAIL')
                        ->whereStatus(1)
                        ->first();
        $report_title = date('Y-m-d').'-correction-patient-record.xls';

        $PatientsModel =  DB::table('patients')
                        ->where('old_id','=','99999')
                        //->where('id','24989')
                        ->orderby('first_name','ASC')
                       // ->where('first_name','Hester')
                        ->get();



        $a = array('ganymed_id',
            'pat_nr',
            'previous_appoitment_date',
            'next_appoitment_date',
            'family_name',          
            'first_name',
            'birth_date',
            'mobile_no',
            'road',
            'postal_code',
            'place',
            'mobile_no',
            'insurance_number',
            'name_matches',
            'maching_info',
            '99999 removed',
            'match',
            'result',
            'app_id'
           );

        $data = array('step1');
        $file = Excel::store(new PatientCollectionExportNew($PatientsModel,$a), 'mail_files/'.$report_title);

        $file_path = storage_path().'/app/mail_files/'.$report_title;

        $data = array('content'=> 'Bitte überprüfe den Anhang.');

        $sendFile = Mail::send('admin.mail.app-patient-email', $data, function($message) use($admin_email,$file_path){

        $message->to('eluminous_se41@eluminoustechnologies.com');

        $message->subject(date('Y-m-d').'-correction-Patient-record');

        $message->from($admin_email->setting_value,'Puregyn');

        $message->attach($file_path);

        });
        
    }

   
}
