<?php
namespace App\Console\Commands\Puremed; 

use Illuminate\Console\Command;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Database\Connection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Console\Exception\RuntimeException;
use App\Models\AdminUserModel;
use App\Models\RosterModel; 
use Illuminate\Support\Facades\Log;
use App\Traits\GeneralTrait; 
use Carbon;
use DB;
use PDF;
use File;
class PatientHasDocument extends Command
{
    use GeneralTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient_has_documents:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all appointment data';

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
        $lastPatientId = DB::connection('puremed_puregyn')->table('patient_has_documents')->orderBy('migration_id', 'desc')->pluck('migration_id')->first();
        if(!empty($lastPatientId))
        {
            $rows = DB::connection('migration')->table('patient_has_documents')->where('id', ">", $lastPatientId)->orderBy('id', 'ASC')->get();
        }
        else {
            $rows = DB::connection('migration')->table('patient_has_documents')->orderBy('id', 'ASC')->get();
        }
        if(!empty($rows) && count($rows) > 0)
        {
            foreach($rows as $key=>$value)
            {
                if($value->record_type == 1)
                {
                    $patient_id = DB::connection('puremed_puregyn')->table('patients')->where('migration_id',$value->patient_id)->pluck('id')->first();
                    $appointment_id = DB::connection('puremed_puregyn')->table('appointment')->where('migration_id',$value->appointment_id)->pluck('id')->first();
                    $appointment_type_id = DB::connection('puremed_puregyn')->table('appointment_types')->where('migration_id',$value->exam_app_type_id)->pluck('id')->first();
                    $exam_id = DB::connection('puremed_puregyn')
                                ->table('appoinment_type_has_examinations')
                                ->leftjoin('examinations','examinations.id','appoinment_type_has_examinations.examination_id')
                                ->where('appoinment_type_has_examinations.appoinment_id',$appointment_type_id)
                                ->where('examinations.default_service','1')->pluck('examinations.id')->first();
                    $document = DB::connection('puremed_puregyn')
                                    ->table('examinations_has_multiple_document_list')
                                    ->where('fk_examinations_id',$exam_id)
                                    ->first();
                    // dd($patient_id,$appointment_id,$appointment_type_id,$exam_id,$document);
                    $outer_data = [];
                    $outer_data['migration_id'] = $value->id;
                    $outer_data['patient_id'] = $patient_id ?? '';
                    $outer_data['appointment_id'] = $appointment_id ?? '';
                    $outer_data['exam_app_type_id'] = $appointment_type_id ?? '';
                    $outer_data['fk_examinations_id'] = $exam_id ?? '';
                    $outer_data['fk_document_id'] = $document_id ?? '';
                    $outer_data['record_type'] = 1;
                    // if($value->doc_status == 2)
                    // {
                    //     $outer_data['doc_status'] = '1,2';
                    // }
                    // else  {
                        $outer_data['doc_status'] = $value->doc_status;
                    // }
                    $outer_data['remarks']  = $value->remarks;
                    $document_pdf = 'storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/document_pdf';
                    if(!File::isDirectory($document_pdf))
                    {
                        File::makeDirectory($document_pdf, 0777, true, true);
                    }
                    if(!empty($document))
                    {
                        $document = DB::connection('puremed_puregyn')
                                    ->table('specialist_has_documents')
                                    ->where('id',$document->fk_document_list_id)
                                    ->first();
                        $data = [];
                        $data['doc_id']            = $document->id;
                        $data['name']              = $document->name;
                        $data['html_text']         = $document->html_text;
                        $data['background_color']  = $document->background_color;
                        $data['header_image']      = $document->header_image;
                        //$data['header_image_path'] = $document->header_image_path;
                        $data['header_image_path'] = url('storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/'.$document->header_image_path);
                        $data['footer_image']      = $document->footer_image;
                        $data['footer_image_path'] = $document->footer_image_path;
                        $data['footer_image_path'] = url('storage/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/'.$document->footer_image_path);
                        $data['background_color']  = $document->background_color;
                        if($value->doc_status == '2' && !empty($doc_details->remarks))
                        {
                            $data['signature'] = $doc_details->remarks;
                        }
                        else {
                            $data['signature'] = '';
                        }
                        // $PdfPath = self::StorePath('document_pdf/');
                        // $PdfPath = 'storage/app/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/document_pdf/';
                        // $PdfPath = 'public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/document_pdf/';
                        $PdfPath   = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/document_pdf/';
                        $PDFname   = $document->name.'_'.time().'.pdf';
                        // Invoice full path
                        $StorePath = $PdfPath.$PDFname;
                        //$StorePath = url('storage/'.$PDFname);
                        $accessPath = '/document_pdf/'.$PDFname;
                        $PDFPath = 'admin.pdf.documentLists';
                        PDF::loadView($PDFPath,compact('data'))->save($StorePath);
                        $outer_data['pdf_name']   = $PDFname;
                        $outer_data['pdf_path']    = $accessPath;
                    }
                    $outer_data['type'] = 'general';
                    $outer_data['created_at']  = $value->created_at;
                    $outer_data['updated_at'] = $value->updated_at;
                    $tenant_patinet_id = DB::connection('puremed_puregyn')->table('patient_has_documents')->insertGetId($outer_data);
                }
            }
        }
    }
    
}
