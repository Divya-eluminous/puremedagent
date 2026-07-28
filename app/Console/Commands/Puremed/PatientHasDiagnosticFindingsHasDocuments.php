<?php

namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AdminUserModel;
use Illuminate\Support\Facades\Log;

use Carbon;
use DB;
class PatientHasDiagnosticFindingsHasDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient_has_diagnostic_findings_has_documents:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all  patient_has_diagnostic_findings_has_documents data';

     /**
     * @var Connection
     */
    private $connection;

    /**
     * @var WebsiteRepository
     */
    private $websites;

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
        $lastPatientId = DB::connection('puremed_puregyn')
        ->table('patients_has_diagnostic_findings')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();

        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')
            ->table('patient_has_diagnostic_findings_has_documents')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else
        {
            $rows = DB::connection('migration')
            ->table('patient_has_diagnostic_findings_has_documents')->orderBy('id', 'ASC')->get();
        }

        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                //insert into tenant table
                $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();

                $finding_id = DB::connection('puremed_puregyn')
                ->table('patients_has_diagnostic_findings')->where('migration_id',$value->finding_id)->pluck('id')->first();

                $data = [];
                $data['migration_id'] = $value->id;
                $data['finding_id'] = $finding_id ?? '';
                $data['patient_id'] = $patient_id;                
                $data['text'] = $value->text;               
                $data['original_name'] = $value->original_name;
                $data['file'] = $value->file;               
                $data['jpg_file'] = $value->jpg_file;
                $data['pdf_file'] = $value->pdf_file;  
                $data['created_at']  = $value->created_at;
                $data['updated_at'] = $value->updated_at; 

                $tenant_patinet_id = DB::connection('puremed_puregyn')
                ->table('patient_has_diagnostic_findings_has_documents')->insertGetId($data);
                

                // add into the master table
                //add into the patinet has ordination tabel
            }
        }
        
    }

    
}
