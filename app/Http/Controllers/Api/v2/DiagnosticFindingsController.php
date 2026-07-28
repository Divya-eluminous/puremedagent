<?php

namespace App\Http\Controllers\Api\v2;

use Illuminate\Http\Request;  
use App\Http\Controllers\Controller; 
use App\Models\DiagnosticFindingsTypesModel; 
use App\Models\PatientsHasDiagnosticFindingsModel;
use App\Models\PatientHasDiagnosticFindingsHasDocumentsModel;
use App\Models\ActivityLogModel;
use App\Models\FindingServicesModel;
use App\Models\AppointmentModel;
use App\Models\PatientsModel;
use App\Models\SettingsModel;
use App\Models\PatientsHasOldFindingModel;
use App\Models\CheckListHasSelectedQuestionModel;
use App\Models\ExaminationsModel;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Traits\GeneralTrait;
// TODO: Replace with Stancl tenancy equivalent
// use Hyn\Tenancy\Models\Website;

use Validator;
use Carbon\Carbon; 
use App\dicom_convert;
use DB; 
use Storage;
use Dompdf\Dompdf;
use Mail;
use PDF;
use Image;
//mail
use App\Mail\SendRquiredforadminmail;
 use Illuminate\Support\Facades\Log;

class DiagnosticFindingsController extends BaseController
{ 
    use GeneralTrait;
    public function __construct(
        DiagnosticFindingsTypesModel $DiagnosticFindingsTypesModel,
        PatientsHasDiagnosticFindingsModel $PatientsHasDiagnosticFindingsModel,
        PatientHasDiagnosticFindingsHasDocumentsModel $PatientHasDiagnosticFindingsHasDocumentsModel,
        ActivityLogModel $ActivityLogModel,
        dicom_convert $dicom_convert,
        FindingServicesModel $FindingServicesModel,
        AppointmentModel $AppointmentModel,
        PatientsModel $PatientsModel,
        SettingsModel $SettingsModel,
        PatientsHasOldFindingModel $PatientsHasOldFindingModel,
        CheckListHasSelectedQuestionModel $CheckListHasSelectedQuestionModel,
        ExaminationsModel $ExaminationsModel
        // Website $website
    )
    {
        $this->BaseModel            = $DiagnosticFindingsTypesModel; 
        $this->PatientsHasDiagnosticFindingsModel  = $PatientsHasDiagnosticFindingsModel;
        $this->PatientHasDiagnosticFindingsHasDocumentsModel  = $PatientHasDiagnosticFindingsHasDocumentsModel;
        $this->ActivityLogModel     = $ActivityLogModel;
        $this->dicom_convert        = $dicom_convert;
        $this->FindingServicesModel = $FindingServicesModel;
        $this->AppointmentModel = $AppointmentModel;
        $this->PatientsModel = $PatientsModel;
        $this->SettingsModel = $SettingsModel;
        $this->PatientsHasOldFindingModel = $PatientsHasOldFindingModel;
        $this->CheckListHasSelectedQuestionModel = $CheckListHasSelectedQuestionModel;
        $this->ExaminationsModel = $ExaminationsModel;
        // $this->website  = $website; 

        // $this->ViewData = [];
        // $this->JsonData = []; 

        // $this->ModuleTitle = 'Patients';
        // $this->ModuleView  = 'admin.patients.';
        // $this->ModulePath = 'admin.patients.';
    } 

    /*---------------------------------
    |   Diagnostic Finding Types Listing 
    ------------------------------------------*/
    public function getDiagnosticFindingsTypes(){
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collection = collect([]); 
            $collection = $this->BaseModel->whereStatus(1)->get();

             if(!empty($collection) && sizeof($collection) > 0){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    // dd($data);
                    self::_createLog('getDiagnosticFindingsTypes',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('getDiagnosticFindingsTypes',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
            }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getDiagnosticFindingsTypes',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    /*---------------------------------
    |   Create Diagnostic Finding
    ------------------------------------------*/
    public function createDiagnosticFindings(Request $request)
    {
        Log::info('in createDiagnosticFindings api...');

        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_INVALID_DATA');
        $status     = false; 
        $findingDocumentObj = '';
        $findingDocumentRecords = [];
        $findingImage = [];
        try {  
            // DB::beginTransaction(); 
            $inputdata = $request->all();

             Log::info($inputdata);

            $validator = Validator::make($inputdata,[
                              'patient_id'      => 'required',
                              'finding_type_id' => 'required',
                              'document_name'   => 'required',
                              'file'   => 'required',
                              'date'            => 'required',
                              //'comment'         => 'required',
                              'status'          => 'required',
                            ],  
                            [
                              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
                              'finding_type_id.required' => __('api.ERR_FINDINGD_TYPE_REQ'),
                              'document_name.required' => __('api.ERR_DOCUMENT_NAME_REQ'),
                              'file.required'    => __('api.ERR_FINDING_FILE_REQ'),
                              'date.required'    => __('api.ERR_FINDING_DATE_REQ'),
                             // 'comment.required' => __('api.ERR_FINDING_COMMENT_REQ'),
                              'status.required'  => __('api.ERR_FINDING_STATUS_REQ'),         
                            ]); 

            if ($validator->fails()) {            
              $errors[] = $validator->errors(); 
            }else{

                $status = true; 
                // File Uploading
                DB::beginTransaction();

                $collection                 = new $this->PatientsHasDiagnosticFindingsModel;
                $collection->patient_id     = $request->patient_id;
                $collection->finding_type_id= $request->finding_type_id;
                $collection->document_name  = $request->document_name;
                $date                       = strtotime($request->date);
                $collection->date           = date('Y-m-d', $date);  
                $collection->comment        = $request->comment;
                $collection->status         = $request->status;

                if($collection->save())
                {

                    $all_transactions = [];
                  
                    if($request->hasfile('file'))
                    { 
                         
                        $finding_cnt = 0;
                       
                        foreach($request->file('file') as $file)
                        {
                            $path = 'diagnostic_findings';
                            $original_file  = strtolower($file->getClientOriginalName());
                            $original_file  = str_replace(' ', '%20', $original_file);
                            $extension      = strtolower($file->getClientOriginalExtension()); 
                            $f_name = str_replace('%20', '', $original_file);
                    
                            $fileName    = date('YmdHis').'-'.trim($f_name);
                            //$fileName   = $request->document_name.'_'.$request->patient_id;
                            
                           
                            // $storefilePath  = '/diagnostic_findings/'.date('YmdHis').'-'.$original_file; 
                            
                            //$filePath  = date('YmdHis').'-'.$original_file;
                            $img_original_name = explode('.',$original_file);

                            $img_find_name = explode('/',$request->document_name);
                            if(count($img_find_name)>1)
                            {
                                $imgFindingName = trim($img_find_name[0]).'_'. trim($img_find_name[1]);
                            }
                            else
                            {
                                $imgFindingName = $request->document_name;
                            }
                            $digits   = 3;
                            $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
                            $fileName = $imgFindingName.'_'.$request->patient_id.'_'.$randomNo.'.'.$img_original_name[1];
                            // $fileName = self::createPdfFileName($request->patient_id,$imgFindingName);
                            $fileName = self::createIMGFileName($request->patient_id,$imgFindingName,$original_file);

                            // $storefilePath  = '/diagnostic_findings/'.$fileName; 
                            // //$fileStorePath = Storage::putFileAs($path, $file, $fileName);
                            // //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
                            // //dd($path, $file, $fileName);
                            // $fileStorePath = self::putFilePath($path, $file, $fileName);
                            // // $findingDocumentObj->finding_id   = $collection->id;
                            // // $findingDocumentObj->original_name   = $fileName;    
                            // // $findingDocumentObj->file           = $filePath;

                            // // // Create Array for finding image name file_path
                            // $newFileName = self::getFilePath($storefilePath);

                            // $findingImage[$finding_cnt] = $newFileName;
                            // $finding_cnt++;
                            // // // End
                            // // //Save data

                              $filePath  = $path.'/'.$fileName;                          
                            $fileStorePath = self::putFilePath($path, $file, $fileName);                         

                            $new_img_path = self::StorePath($path.'/crop/');                          
                            if(!file_exists($new_img_path)){                               
                                Storage::makeDirectory($path.'/crop',0755);
                            }                            
                            $fileCropPath  = $path;                           
                            $new_fileName = self::StorePath($path);                            
                            
                            $new_fileCropPath = self::StorePath($fileCropPath);                            
                            $croppath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/crop/'.$fileName; 
                            $getSizepath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/'.$fileName; 

                            $image = getimagesize($getSizepath);
                            $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/'.$fileName;
                            $width = $image[0];
                            $height = $image[1];                            
                           
                            $a= Image::make($PdfPath)->resize($width,$height ,function($constraint){
                                    $constraint->upsize();
                                    $constraint->aspectRatio();
                                    })
                                   ->save($croppath);
                   
                            $new_fileCropPath = self::StorePath($fileCropPath);
                           
                            $newFileName = self::getFilePath('/diagnostic_findings/crop/'.$fileName);
                            $findingImage[$finding_cnt]['path'] = $newFileName;
                            $findingImage[$finding_cnt]['name'] = $fileName;
                            $findingImage[$finding_cnt]['width'] = $width;
                            $findingImage[$finding_cnt]['height'] = $height;
                            $finding_cnt++;
                            // // End
                            // //Save data
                        }

                        // Generate PDF for Upload Finding 
                        // if(!empty($findingImage) && sizeof($findingImage)>1)
                        if(!empty($findingImage))
                        {
                            //$PdfPath   = self::StorePath('diagnostic_findings/');
                            //$PdfPath   = storage_path().'/app/diagnostic_findings/';
                            //dd($PdfPath); $request->patient_id
                            // $PDFname   = $request->document_name.'_'.time().'.pdf';
                            $doc_find_name = explode('/',$request->document_name);
                            if(count($doc_find_name)>1)
                            {
                                $PDFFindingName = trim($doc_find_name[0]).'_'. trim($doc_find_name[1]);
                            }
                            else
                            {
                                $PDFFindingName = $request->document_name;
                            }
                            $digits   = 3;
                            $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
                            //$PDFname = $PDFFindingName.'_'.$request->patient_id.'_'.$randomNo.'.pdf';
                            $PDFname = self::createPdfFileName($request->patient_id,$PDFFindingName);
                            //$PDFname = $PDFFindingName.'_'.$request->patient_id.'.pdf';
                            //$PDFname   = $request->document_name.'_'.$request->patient_id.'.pdf';
                            //dd($PDFname);
                            // Invoice full path
                            if(!empty(Config('ordination_id')))
                            {
                                $getDatabaseName = DB::connection('system')
                                            ->table("tenants")
                                            ->where('ordination_id',Config('ordination_id'))
                                            ->first(['uuid']);

                                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/diagnostic_findings/';
                            }
                            else
                            {
                                $PdfPath = '/opt/app-shared/php/data/storage/app/public/diagnostic_findings/';
                            }

                            $StorePath = $PdfPath.$PDFname; 
                            $accessPath = '/diagnostic_findings/'.$PDFname;

                            $PDFPath = 'admin.pdf.finding-image';  
                            //added by swati 19-Jul-23 to work image if ssl is changed=====================
                             $pdf = app('dompdf.wrapper');
                             //############ Permitir ver imagenes si falla ################################
                              $contxt = stream_context_create([
                                'ssl' => [
                                    'verify_peer' => FALSE,
                                    'verify_peer_name' => FALSE,
                                    'allow_self_signed' => TRUE,
                                ]
                            ]);

                            $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
                            $pdf->getDomPDF()->setHttpContext($contxt);
                            $pdf->loadView($PDFPath,compact('findingImage'))->save($StorePath);
                            
                            // PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView($PDFPath,compact('findingImage'))->save($StorePath);
                           
                            //$AccessPath = '/storage/Invoice/'.$PDFname;

                            // Save PDF name and pdfPath
                            $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                            $findingDocumentObj->finding_id    = $collection->id;
                            $findingDocumentObj->original_name = $PDFname;    
                            $findingDocumentObj->file          = '/diagnostic_findings/'.$PDFname;

                            if ($findingDocumentObj->save()) 
                            { 
                                // dd('if');
                                $findingDocumentRecords[] = $findingDocumentObj;
                                $all_transactions[] = 1;
                            }
                            else
                            {
                                // dd('else');
                                $all_transactions[] = 0;
                            }
                        }
                        // else
                        // {
                        //     $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                        //     $findingDocumentObj->finding_id   = $collection->id;
                        //     $findingDocumentObj->original_name   = $fileName;    
                        //     $findingDocumentObj->file           = $storefilePath;
                        //     if ($findingDocumentObj->save()) 
                        //     { 
                        //         // dd('if');
                        //         $findingDocumentRecords[] = $findingDocumentObj;
                        //         $all_transactions[] = 1;
                        //     }
                        //     else
                        //     {
                        //         // dd('else');
                        //         $all_transactions[] = 0;
                        //     }
                        // }

                        
                       //dd($findingDocumentRecords);
                        // End
                    }                
                }else{
                     $all_transactions[] = 0;
                }
                // dd($all_transactions);
                if (!in_array(0,$all_transactions)) 
                {
                    $status  = true;
                    $message = __('api.DATA_INSERTED');
                    // $data = $this->PatientsHasDiagnosticFindingsModel
                    //             ->with(['hasFindingDocument'])
                    //             ->get();
                   
                    $data = $collection; 
                    $data['has_document'] = $findingDocumentRecords;
                     // dd($findingDocumentRecords->id); 
                    $newData = [];
                    $newData['patient_id'] = $collection->patient_id; 
                    $newData['finding_type_id'] = $collection->finding_type_id;
                    $newData['document_name'] = $collection->document_name;
                    $newData['date'] = $collection->date;
                    $newData['comment'] = $collection->comment;
                    $newData['status'] = $collection->status;
                    $newData['id'] = $collection->id;
                    // dd($newData);
                    // dd($findingDocumentObj->original_name);
                    // $document = [];
                    // dd($findingDocumentRecords);
                    // foreach ($findingDocumentRecords as $value) {
                    //     $newData['doc_name'] = explode(" ",$value->original_name);
                    // }
                    
                    // $newData['document'] = implode(",",$document);
                    // dd($document);
                   
                    self::_createLog('createDiagnosticFindings',array($data),'info');
                    $this->ActivityLogModel->addApiLog('Create Diagnostic Findings','has create diagnostic finding','Create',null,$newData); 
                    DB::commit();  
                }else
                {
                    DB::rollback(); 
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            DB::rollback();
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('createDiagnosticFindings',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

        return self::_sendResult($message,$data,$errors,$status);
    } 


    /*---------------------------------
    |   scan findings for tablet
    ------------------------------------------*/
    public function createDiagnosticFindingsQrcode(Request $request)
    {

          $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_INVALID_DATA');
        $status     = false; 
        $findingDocumentObj = '';
        $findingDocumentRecords = [];
        $findingImage = [];
        try {  
            // DB::beginTransaction(); 
            $inputdata = $request->all();

            $validator = Validator::make($inputdata,[
                              'patient_id'      => 'required',
                              'finding_type_id' => 'required',
                              'document_name'   => 'required',
                              'file'   => 'required',
                              'date'            => 'required',
                              //'comment'         => 'required',
                              'status'          => 'required',
                            ],  
                            [
                              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
                              'finding_type_id.required' => __('api.ERR_FINDINGD_TYPE_REQ'),
                              'document_name.required' => __('api.ERR_DOCUMENT_NAME_REQ'),
                              'file.required'    => __('api.ERR_FINDING_FILE_REQ'),
                              'date.required'    => __('api.ERR_FINDING_DATE_REQ'),
                             // 'comment.required' => __('api.ERR_FINDING_COMMENT_REQ'),
                              'status.required'  => __('api.ERR_FINDING_STATUS_REQ'),         
                            ]); 

            if ($validator->fails()) {            
              $errors[] = $validator->errors(); 
            }else{

                $status = true; 
                // File Uploading
                DB::beginTransaction();

                $collection                 = new $this->PatientsHasDiagnosticFindingsModel;
                $collection->patient_id     = $request->patient_id;
                $collection->finding_type_id= $request->finding_type_id;
                $collection->document_name  = $request->document_name;
                $date                       = strtotime($request->date);
                $collection->date           = date('Y-m-d', $date);  
                $collection->comment        = $request->comment;
                $collection->status         = $request->status;

                if($collection->save())
                {

                    $all_transactions = [];
                  
                    if($request->hasfile('file'))
                    { 
                         
                        $finding_cnt = 0;
                       
                        foreach($request->file('file') as $file)
                        {
                            $path = 'diagnostic_findings';
                            $original_file  = strtolower($file->getClientOriginalName());
                            $original_file  = str_replace(' ', '%20', $original_file);
                            $extension      = strtolower($file->getClientOriginalExtension()); 
                            $f_name = str_replace('%20', '', $original_file);
                    
                            $fileName    = date('YmdHis').'-'.trim($f_name);
                            //$fileName   = $request->document_name.'_'.$request->patient_id;
                            
                           
                            // $storefilePath  = '/diagnostic_findings/'.date('YmdHis').'-'.$original_file; 
                            
                            //$filePath  = date('YmdHis').'-'.$original_file;
                            $img_original_name = explode('.',$original_file);

                            $img_find_name = explode('/',$request->document_name);
                            if(count($img_find_name)>1)
                            {
                                $imgFindingName = trim($img_find_name[0]).'_'. trim($img_find_name[1]);
                            }
                            else
                            {
                                $imgFindingName = $request->document_name;
                            }
                            $digits   = 3;
                            $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
                            $fileName = $imgFindingName.'_'.$request->patient_id.'_'.$randomNo.'.'.$img_original_name[1];
                            // $fileName = self::createPdfFileName($request->patient_id,$imgFindingName);
                            $fileName = self::createIMGFileName($request->patient_id,$imgFindingName,$original_file);

                            // $storefilePath  = '/diagnostic_findings/'.$fileName; 
                            // //$fileStorePath = Storage::putFileAs($path, $file, $fileName);
                            // //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
                            // //dd($path, $file, $fileName);
                            // $fileStorePath = self::putFilePath($path, $file, $fileName);
                            // // $findingDocumentObj->finding_id   = $collection->id;
                            // // $findingDocumentObj->original_name   = $fileName;    
                            // // $findingDocumentObj->file           = $filePath;

                            // // // Create Array for finding image name file_path
                            // $newFileName = self::getFilePath($storefilePath);

                            // $findingImage[$finding_cnt] = $newFileName;
                            // $finding_cnt++;
                            // // // End
                            // // //Save data

                              $filePath  = $path.'/'.$fileName;                          
                            $fileStorePath = self::putFilePath($path, $file, $fileName);                         

                            $new_img_path = self::StorePath($path.'/crop/');                          
                            if(!file_exists($new_img_path)){                               
                                Storage::makeDirectory($path.'/crop',0755);
                            }                            
                            $fileCropPath  = $path;                           
                            $new_fileName = self::StorePath($path);                            
                            
                            $new_fileCropPath = self::StorePath($fileCropPath);                            
                            $croppath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/crop/'.$fileName; 
                            $getSizepath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/'.$fileName; 

                            $image = getimagesize($getSizepath);
                            $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/'.$fileName;
                            $width = $image[0];
                            $height = $image[1];                            
                           
                            $a= Image::make($PdfPath)->resize($width,$height ,function($constraint){
                                    $constraint->upsize();
                                    $constraint->aspectRatio();
                                    })
                                   ->save($croppath);
                   
                            $new_fileCropPath = self::StorePath($fileCropPath);
                           
                            $newFileName = self::getFilePath('/diagnostic_findings/crop/'.$fileName);
                            $findingImage[$finding_cnt]['path'] = $newFileName;
                            $findingImage[$finding_cnt]['name'] = $fileName;
                            $findingImage[$finding_cnt]['width'] = $width;
                            $findingImage[$finding_cnt]['height'] = $height;
                            $finding_cnt++;
                            // // End
                            // //Save data
                        }

                        // Generate PDF for Upload Finding 
                        // if(!empty($findingImage) && sizeof($findingImage)>1)
                        if(!empty($findingImage))
                        {
                            //$PdfPath   = self::StorePath('diagnostic_findings/');
                            //$PdfPath   = storage_path().'/app/diagnostic_findings/';
                            //dd($PdfPath); $request->patient_id
                            // $PDFname   = $request->document_name.'_'.time().'.pdf';
                            $doc_find_name = explode('/',$request->document_name);
                            if(count($doc_find_name)>1)
                            {
                                $PDFFindingName = trim($doc_find_name[0]).'_'. trim($doc_find_name[1]);
                            }
                            else
                            {
                                $PDFFindingName = $request->document_name;
                            }
                            $digits   = 3;
                            $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
                            //$PDFname = $PDFFindingName.'_'.$request->patient_id.'_'.$randomNo.'.pdf';
                            $PDFname = self::createPdfFileName($request->patient_id,$PDFFindingName);
                            //$PDFname = $PDFFindingName.'_'.$request->patient_id.'.pdf';
                            //$PDFname   = $request->document_name.'_'.$request->patient_id.'.pdf';
                            //dd($PDFname);
                            // Invoice full path
                            if(!empty(Config('ordination_id')))
                            {
                                $getDatabaseName = DB::connection('system')
                                            ->table("tenants")
                                            ->where('ordination_id',Config('ordination_id'))
                                            ->first(['uuid']);

                                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/diagnostic_findings/';
                            }
                            else
                            {
                                $PdfPath = '/opt/app-shared/php/data/storage/app/public/diagnostic_findings/';
                            }

                            $StorePath = $PdfPath.$PDFname; 
                            $accessPath = '/diagnostic_findings/'.$PDFname;

                            $PDFPath = 'admin.pdf.finding-image';  
                            //added by swati 19-Jul-23 to work image if ssl is changed=====================
                             $pdf = app('dompdf.wrapper');
                             //############ Permitir ver imagenes si falla ################################
                              $contxt = stream_context_create([
                                'ssl' => [
                                    'verify_peer' => FALSE,
                                    'verify_peer_name' => FALSE,
                                    'allow_self_signed' => TRUE,
                                ]
                            ]);

                            $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
                            $pdf->getDomPDF()->setHttpContext($contxt);
                            $pdf->loadView($PDFPath,compact('findingImage'))->save($StorePath);

                            // PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView($PDFPath,compact('findingImage'))->save($StorePath);
                           
                            //$AccessPath = '/storage/Invoice/'.$PDFname;

                            // Save PDF name and pdfPath
                            $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                            $findingDocumentObj->finding_id    = $collection->id;
                            $findingDocumentObj->original_name = $PDFname;    
                            $findingDocumentObj->file          = '/diagnostic_findings/'.$PDFname;

                            if ($findingDocumentObj->save()) 
                            { 
                                // dd('if');
                                $findingDocumentRecords[] = $findingDocumentObj;
                                $all_transactions[] = 1;
                            }
                            else
                            {
                                // dd('else');
                                $all_transactions[] = 0;
                            }
                        }
                        // else
                        // {
                        //     $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                        //     $findingDocumentObj->finding_id   = $collection->id;
                        //     $findingDocumentObj->original_name   = $fileName;    
                        //     $findingDocumentObj->file           = $storefilePath;
                        //     if ($findingDocumentObj->save()) 
                        //     { 
                        //         // dd('if');
                        //         $findingDocumentRecords[] = $findingDocumentObj;
                        //         $all_transactions[] = 1;
                        //     }
                        //     else
                        //     {
                        //         // dd('else');
                        //         $all_transactions[] = 0;
                        //     }
                        // }

                        
                       //dd($findingDocumentRecords);
                        // End
                    }                
                }else{
                     $all_transactions[] = 0;
                }
                // dd($all_transactions);
                if (!in_array(0,$all_transactions)) 
                {
                    $status  = true;
                    $message = __('api.DATA_INSERTED');
                    // $data = $this->PatientsHasDiagnosticFindingsModel
                    //             ->with(['hasFindingDocument'])
                    //             ->get();
                   
                    $data = $collection; 
                    $data['has_document'] = $findingDocumentRecords;
                     // dd($findingDocumentRecords->id); 
                    $newData = [];
                    $newData['patient_id'] = $collection->patient_id; 
                    $newData['finding_type_id'] = $collection->finding_type_id;
                    $newData['document_name'] = $collection->document_name;
                    $newData['date'] = $collection->date;
                    $newData['comment'] = $collection->comment;
                    $newData['status'] = $collection->status;
                    $newData['id'] = $collection->id;
                    // dd($newData);
                    // dd($findingDocumentObj->original_name);
                    // $document = [];
                    // dd($findingDocumentRecords);
                    // foreach ($findingDocumentRecords as $value) {
                    //     $newData['doc_name'] = explode(" ",$value->original_name);
                    // }
                    
                    // $newData['document'] = implode(",",$document);
                    // dd($document);
                   
                    self::_createLog('createDiagnosticFindings',array($data),'info');
                    $this->ActivityLogModel->addApiLog('Create Diagnostic Findings','has create diagnostic finding','Create',null,$newData); 
                    DB::commit();  
                }else
                {
                    DB::rollback(); 
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            DB::rollback();
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('createDiagnosticFindings',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

        return self::_sendResult($message,$data,$errors,$status);
    } 

    /*---------------------------------
    |   Diagnostic Findings Listing 
    ------------------------------------------*/
    public function getDiagnosticFindings(Request $request){
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $patientId   = $request->patient_id;
        // dd($patientId);
        $inputdata  = $request->all();
        
        try
        {
            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                            ], 
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),  
                            ]
                            ); 

            if ($validator->fails())  
            {           
              $errors[] = $validator->errors(); 
            }else
            {
                $collection = collect([]);  
               
                $collection = $this->PatientsHasDiagnosticFindingsModel
                ->with('hasFindingDocument')
                                    ->leftjoin('diagnostic_findings_types', 'diagnostic_findings_types.id' , '=', 'patients_has_diagnostic_findings.finding_type_id')
                                    ->leftjoin('patients', 'patients.id' , '=', 'patients_has_diagnostic_findings.patient_id')
                                    ->where('patients_has_diagnostic_findings.patient_id', $patientId)
                                    ->orderBy('patients_has_diagnostic_findings.id','DESC')
                                    ->get([
                                        'diagnostic_findings_types.name as finding_type',
                                        'diagnostic_findings_types.colour as finding_colour',
                                        'patients.first_name as patient_fname',
                                        'patients.family_name as patient_lname',
                                        'patients_has_diagnostic_findings.id',
                                        'patients_has_diagnostic_findings.document_name',
                                        'patients_has_diagnostic_findings.date',
                                        'patients_has_diagnostic_findings.comment',
                                        'patients_has_diagnostic_findings.status',
                                        'patients_has_diagnostic_findings.old_id',
    
                                    ]); 
                                
                if(!empty($collection) && sizeof($collection) > 0)
                {
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS'); 

                    $collection = $collection->map(function($item)
                    {       

                       $item->comment = $item->comment ?? '';
                        if($item->old_id != '-1' && $item->old_id != '0')
                        {
                            $item->finding_type = 'Externer Befund';
                            $item->finding_colour = '#000000';
                            $item->comment = '';
                        }               
                        if($item->hasFindingDocument)
                        {
                            $documents = $item->hasFindingDocument;
                            $filterDocuments = array();
                            foreach ($documents as $document) 
                            {       
                                if($document->deleted_at=='')                
                                {
                                    $findingDocument = '';
                                    $file_path = str_replace('\\', '/', $document->file);

                                    $new_path = $str = ltrim($file_path, '/');
                                    $new_file_path = self::StorePath($new_path.'/');

                                    if(!empty($file_path) && $document->original_name == 'create_pdf' )
                                    {                                    
                                        if(empty($document->pdf_file))
                                        {

                                            $file_path = str_replace("\r\n", "<br/>", $file_path);

                                            $dompdf = new Dompdf();
                                           
                                            $data = '<!doctype html>
                                                        <html lang="de">
                                                            <head>
                                                                <meta charset="UTF-8">
                                                                <meta name="viewport"
                                                                      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                                                                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                                                                <title>Document</title>
                                                                <style>
                                                                    body {
                                                                        font-size:24px;
                                                                    }
                                                                </style>
                                                            </head>

                                                            <body style="width: 100%;">'.$file_path.'
                                                            </body>
                                                        </html>';
                                                         $dompdf->loadHTML($data);
                                           // $dompdf->setPaper('A4', 'landscape');

                                            // Render the HTML as PDF
                                            $dompdf->render();
                                          
                                            $output = $dompdf->output();
                                            $path   = self::StorePath('/diagnostic_findings/pdf/');
                                            //$path = storage_path().'/app';
                                            $file_name = uniqid().'.pdf';
                                          
                                            file_put_contents($path.$file_name, $output);
                                           
                                            $this->PatientHasDiagnosticFindingsHasDocumentsModel->where('id',$document->id)->update(['pdf_file'=>$file_name]);   
                                             $findingDocument = $file_name; 
                                             $filterDocuments[] = $document;

                                        }else
                                        {
                                            $findingDocument = $document->pdf_file;
                                            $filterDocuments[] = $document;
                                        }
                                    }
                                    elseif(!empty($file_path)) 
                                    {
                                        $ext = pathinfo($file_path, PATHINFO_EXTENSION);

                                         if($ext != 'dcm'  )
                                         {
                                                $filterDocuments[] = $document;
                                                                            
                                        // {
                                            //     if(empty($document->jpg_file))
                                            //     {
                                            //         $withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file_path);

                                            //         $jpg_file = $withoutExt.".jpg";

                                            //         $file =  storage_path().'/app'.$file_path;

                                            //         $job_start = time();
                                            //         // $d = new dicom_convert;
                                            //         $this->dicom_convert->file = $file;
                                            //         $this->dicom_convert->dcm_to_jpg();

                                            //         $this->PatientHasDiagnosticFindingsHasDocumentsModel->where('id',$document->id)->update(['jpg_file'=>$jpg_file]);
                                            //         $findingDocument = $jpg_file;
                                            //     }else
                                            //     {
                                            //         $findingDocument = $document->jpg_file;
                                            //     }
                                            // }
                                            // else
                                            // {
                                            $findingDocument = $file_path;
                                        }  
                                        $document->file_path = url($findingDocument);                                         
                                    }
                                    $new_doc_path = self::getFilePath($findingDocument);

                                    $document->file_path = $new_doc_path;
                                    $findingDocument = url('/storage/app/');
                                    $item->path = $findingDocument;
                                }
                            }
                            unset($item['hasFindingDocument']);
                            $item['hasFindingDocument'] = $filterDocuments;
                            return $item;
                        }   
                    });                        
                  
                  

                    $data  = $collection;
                    self::_createLog('getDiagnosticFindings',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }else
                {
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getDiagnosticFindings',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


/*---------------------------------
    |   Diagnostic Findings Listing For Android
    ------------------------------------------*/
    public function getDiagnosticFindingsAndroid(Request $request){
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false;
        $patientId   = $request->patient_id;
        // dd($patientId);
        $inputdata  = $request->all();
        
        try
        {
            $validator  = Validator::make($inputdata,[
                              'patient_id'  => 'required',
                            ], 
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),  
                            ]
                            ); 

            if ($validator->fails())  
            {           
              $errors[] = $validator->errors(); 
            }else
            {
                $collection = collect([]);  
               
                $collection = $this->PatientsHasDiagnosticFindingsModel
                ->with('hasFindingDocument')
                                    ->leftjoin('diagnostic_findings_types', 'diagnostic_findings_types.id' , '=', 'patients_has_diagnostic_findings.finding_type_id')
                                    ->leftjoin('patients', 'patients.id' , '=', 'patients_has_diagnostic_findings.patient_id')
                                    ->where('patients_has_diagnostic_findings.patient_id', $patientId)
                                    ->orderBy('patients_has_diagnostic_findings.date','DESC')
                                    ->get([
                                        'diagnostic_findings_types.name as finding_type',
                                        'diagnostic_findings_types.colour as finding_colour',
                                        'patients.first_name as patient_fname',
                                        'patients.family_name as patient_lname',
                                        'patients_has_diagnostic_findings.id',
                                        'patients_has_diagnostic_findings.document_name',
                                        'patients_has_diagnostic_findings.date',
                                        'patients_has_diagnostic_findings.comment',
                                        'patients_has_diagnostic_findings.status',
                                        'patients_has_diagnostic_findings.old_id',
                                    ]); 
                                
                if(!empty($collection) && sizeof($collection) > 0)
                {
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS'); 

                    $collection = $collection->map(function($item)
                    {       
                        $item->pdf_icon = asset('assets/admin-lte/dist/img/pdf.png');
                       $item->comment = $item->comment ?? '';
                        if($item->old_id != '-1' && $item->old_id != '0')
                        {
                            $item->finding_type = 'Externer Befund';
                            $item->finding_colour = '#000000';
                            $item->comment = '';
                        }               
                        if($item->hasFindingDocument)
                        {
                            $documents = $item->hasFindingDocument;
                            $filterDocuments = array();
                            foreach ($documents as $document) 
                            {                               
                                $findingDocument = '';
                                $file_path = str_replace('\\', '/', $document->file);



                                if(!empty($file_path) && $document->original_name == 'create_pdf' )
                                {                                    
                                    if(empty($document->pdf_file))
                                    {

                                        $file_path = str_replace("\r\n", "<br/>", $file_path);

                                        $dompdf = new Dompdf();
                                       
                                        $data = '<!doctype html>
                                                    <html lang="de">
                                                        <head>
                                                            <meta charset="UTF-8">
                                                            <meta name="viewport"
                                                                  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                                                            <meta http-equiv="X-UA-Compatible" content="ie=edge">
                                                            <title>Document</title>
                                                            <style>
                                                                body {
                                                                    font-size:24px;
                                                                }
                                                            </style>
                                                        </head>

                                                        <body style="width: 100%;">'.$file_path.'
                                                        </body>
                                                    </html>';
                                                     $dompdf->loadHTML($data);
                                       // $dompdf->setPaper('A4', 'landscape');

                                        // Render the HTML as PDF
                                        $dompdf->render();
                                      
                                        $output = $dompdf->output();
                                        $path = self::StorePath('/diagnostic_findings/pdf/');

                                        //$path = storage_path().'/app';
                                        $file_name = uniqid().'.pdf';
                                      
                                        file_put_contents($path.$file_name, $output);
                                       
                                        $this->PatientHasDiagnosticFindingsHasDocumentsModel->where('id',$document->id)->update(['pdf_file'=>$file_name]);   
                                         $findingDocument = $file_name; 
                                         $filterDocuments[] = $document;

                                    }else
                                    {
                                        $findingDocument = $document->pdf_file;
                                        $filterDocuments[] = $document;
                                    }
                                }
                                elseif(!empty($file_path)) 
                                {
                                    $ext = pathinfo($file_path, PATHINFO_EXTENSION);

                                     if($ext != 'dcm'  )
                                     {
                                            $filterDocuments[] = $document;
                                                                        
                                    // {
                                        //     if(empty($document->jpg_file))
                                        //     {
                                        //         $withoutExt = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file_path);

                                        //         $jpg_file = $withoutExt.".jpg";

                                        //         $file =  storage_path().'/app'.$file_path;

                                        //         $job_start = time();
                                        //         // $d = new dicom_convert;
                                        //         $this->dicom_convert->file = $file;
                                        //         $this->dicom_convert->dcm_to_jpg();

                                        //         $this->PatientHasDiagnosticFindingsHasDocumentsModel->where('id',$document->id)->update(['jpg_file'=>$jpg_file]);
                                        //         $findingDocument = $jpg_file;
                                        //     }else
                                        //     {
                                        //         $findingDocument = $document->jpg_file;
                                        //     }
                                        // }
                                        // else
                                        // {
                                        $findingDocument = $file_path;
                                    }                                         
                                }
                                $document->file_path = url('/storage/app'.$findingDocument);
                                $findingDocument = url('/storage/app/');
                                $item->path = $findingDocument;
                            }
                            unset($item['hasFindingDocument']);
                            $item['hasFindingDocument'] = $filterDocuments;
                            return $item;
                        }   
                    }); 

                    $data  = $collection;
                    self::_createLog('getDiagnosticFindings',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }else
                {
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                }
            }
        }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getDiagnosticFindings',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


    /*---------------------------------
    |   Get Signle Diagnostic Finding
    ------------------------------------------*/
    public function getSingleDiagnosticFinding(Request $request){
        $errors     = [];   
        $data       = []; 
        $message    = __('api.ERR_NOT_FOUND'); 
        $status     = false; 

        $findingId  = $request->finding_id;
        $patientId   = $request->patient_id;

        $inputdata  = $request->all();
        try{
            $validator  = Validator::make($inputdata,[
                              'finding_id'  => 'required',
                              'patient_id'  => 'required',
                            ], 
                            [
                              'finding_id.required'    => __('api.ERR_FINDING_ID_REQ'),
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),  
                            ]
                            ); 

             if ($validator->fails())  
            {           
              $errors[] = $validator->errors(); 
            }else
            {
                $collection = collect([]); 
                $collection = $this->PatientsHasDiagnosticFindingsModel
                                ->leftjoin('diagnostic_findings_types', 'diagnostic_findings_types.id' , '=', 'patients_has_diagnostic_findings.finding_type_id')
                                ->leftjoin('patients', 'patients.id' , '=', 'patients_has_diagnostic_findings.patient_id')
                                ->where('patients_has_diagnostic_findings.id', $findingId)
                                ->where('patients_has_diagnostic_findings.patient_id', $patientId)
                                ->with('hasFindingDocument')
                                ->get([
                                    'diagnostic_findings_types.name as finding_type',
                                    'diagnostic_findings_types.colour as finding_colour',
                                    'patients.first_name as patient_fname',
                                    'patients.family_name as patient_lname',
                                    'patients_has_diagnostic_findings.id',
                                    'patients_has_diagnostic_findings.document_name',
                                    'patients_has_diagnostic_findings.date',
                                    'patients_has_diagnostic_findings.comment',
                                    'patients_has_diagnostic_findings.status',
                                    'patients_has_diagnostic_findings.old_id',
                                ]);  
            }                       

             if(!empty($collection) && sizeof($collection) > 0){
                
                $collection = $collection->map(function($item)
                {
                    if($item->old_id != '-1' && $item->old_id != '0')
                    {
                        $item->finding_type = 'Externer Befund';
                        $item->finding_colour = '#000000';
                          $item->comment = '';
                    } 
                    if($item->hasFindingDocument)
                    {
                        $filterDocuments = array();
                        $documents = $item->hasFindingDocument;
                        foreach ($documents as $document) 
                        {
                           if($document->deleted_at=='')                
                           {
                                $findingDocument = '';

                                $file_path = str_replace('\\', '/', $document->file); //commented below code on 6-oct-23
                              

                                $ext = pathinfo($file_path, PATHINFO_EXTENSION);

                                $new_file_path = self::StorePath($file_path.'/');

                                if (!empty($file_path)) 
                                {
                                    $findingDocument = self::getFilePath($file_path);
                                    //$findingDocument = url('/storage/app'.$file_path); 
                                }

                                if(!empty($document->pdf_file))
                                {
                                    $document->file_path = self::getFilePath($document->pdf_file);
                                    // $document->file_path = url('/storage/app'.$document->pdf_file);
                                }else
                                {
                                $document->file_path = $findingDocument;
                                }

                               

                                if($ext != 'dcm'  )
                                {
                                // dump('in');
                                $filterDocuments[] = $document;
                                }                           
                                $findingDocument = url('/storage/app/');
                                $item->path = $findingDocument;                            
                            }
                        }
                        unset($item['hasFindingDocument']);
                        $item['hasFindingDocument'] = $filterDocuments;
                        return $item;
                    }   
                });
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    self::_createLog('getSingleDiagnosticFinding',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }else{
                     $message  = __('api.ERR_NOT_FOUND');   
                }
            }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
             self::_createLog('getSingleDiagnosticFinding',$errors,'error');
             // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }
       return self::_sendResult($message,$data,$errors,$status);
    }


    public function filterByDate(Request $request)
    {
        $errors   = [];  
        $data     = [];
        $message  = __('api.ERR_INVALID_DATA');
        $status   = false;
        $patientId   = $request->patient_id;

        // $fromDate   = trim($request->from_date);
        // $toDate     = trim($request->to_date);
        $start  = date('Y-m-d', strtotime($request->from_date));
        $end    = date('Y-m-d', strtotime($request->to_date));
        // $start = Carbon::parse($request->from_date);
        // $end = Carbon::parse($request->to_date);
        // dd($start);
        // dd($end);
        // dd($fromDate);
        // dd(trim($toDate));
        $inputdata  = $request->all();
        try{
        $validator  = Validator::make($inputdata,[
                          'patient_id'  => 'required',
                          'from_date'  => 'required',
                          'to_date'    => 'required',
                        ], 
                        [
                          'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),  
                          'from_date.required'    => __('api.ERR_START_DATE_REQ'),
                          'to_date.required'    => __('api.ERR_END_DATE_REQ'),   
                        ]
                        ); 

         if ($validator->fails())  
        {           
          $errors[] = $validator->errors(); 
        }else
        {
                $collection = $this->PatientsHasDiagnosticFindingsModel
                        ->leftjoin('diagnostic_findings_types', 'diagnostic_findings_types.id' , '=', 'patients_has_diagnostic_findings.finding_type_id')
                        ->leftjoin('patients', 'patients.id' , '=', 'patients_has_diagnostic_findings.patient_id')
                        ->whereDate('date','<=',$end)
                        ->whereDate('date','>=',$start)
                        ->where('patients_has_diagnostic_findings.patient_id', $patientId)
                        ->with('hasFindingDocument')
                        ->get([
                                'diagnostic_findings_types.name as finding_type',
                                'diagnostic_findings_types.colour as finding_colour',
                                'patients.first_name as patient_fname',
                                'patients.family_name as patient_lname',
                                'patients_has_diagnostic_findings.id',
                                'patients_has_diagnostic_findings.document_name',
                                'patients_has_diagnostic_findings.date',
                                'patients_has_diagnostic_findings.comment',
                                'patients_has_diagnostic_findings.status',

                            ]);             
                if((!empty($collection) && sizeof($collection) > 0)){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    self::_createLog('filterByDate',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('filterByDate',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
            }
        }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('filterByDate',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        
       return self::_sendResult($message,$data,$errors,$status);
    } 

    public function filterByType(Request $request)
    {
        $errors   = [];  
        $data     = [];
        $message  = __('api.ERR_INVALID_DATA');
        $status   = false;
        $patientId   = trim($request->patient_id);
        $type   = trim($request->finding_type_id); 

        // dd($type);
        $inputdata  = $request->all();
        try{
        $validator  = Validator::make($inputdata,[
                          'patient_id'      => 'required',
                          'finding_type_id'=> 'required'
                        ], 
                        [
                          'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                          'finding_type_id.required'=>__('api.FINDING_TYPE_ID_REQ'),     
                        ]
                        ); 

        if ($validator->fails()) 
        {           
          $errors[] = $validator->errors(); 
        }else
        {
                $collection = $this->PatientsHasDiagnosticFindingsModel
                        ->leftjoin('diagnostic_findings_types', 'diagnostic_findings_types.id' , '=', 'patients_has_diagnostic_findings.finding_type_id')
                        ->leftjoin('patients', 'patients.id' , '=', 'patients_has_diagnostic_findings.patient_id')
                        ->where('finding_type_id',$type)
                        ->where('patients_has_diagnostic_findings.patient_id', $patientId)
                        ->with('hasFindingDocument')
                        ->get([
                                'diagnostic_findings_types.name as finding_type',
                                'diagnostic_findings_types.colour as finding_colour',
                                'patients.first_name as patient_fname',
                                'patients.family_name as patient_lname',
                                'patients_has_diagnostic_findings.id',
                                'patients_has_diagnostic_findings.document_name',
                                'patients_has_diagnostic_findings.date',
                                'patients_has_diagnostic_findings.comment',
                                'patients_has_diagnostic_findings.status',
                            ]);             
         
                if((!empty($collection) && sizeof($collection) > 0)){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                    self::_createLog('filterByType',array($data),'info');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');

                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('filterByType',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                }
             }
         }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
                self::_createLog('filterByType',$errors,'error');
                // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
            }
        
       return self::_sendResult($message,$data,$errors,$status);
    } 

    public function downloadFinding(Request $request)
    {
        $errors    = []; 
        $data       = [];
        $message    = __('api.ERR_INVALID_DATA');
        $status     = false;

        $inputdata = $request->all();

        $validator = Validator::make($inputdata,[
                          'doc_id'      => 'required',
                          // 'finding_id' => 'required',
                        ],  
                        [
                          'doc_id.required' => __('api.ERR_DOCUMENT_ID_REQ'),
                          // 'finding_id.required' => __('api.ERR_FINDINGD_TYPE_REQ'),
                        ]
                        ); 

        if ($validator->fails()) {           
          $errors[] = $validator->errors(); 
        }else{
            try{

                $collection     = $this->PatientHasDiagnosticFindingsHasDocumentsModel
                        ->find($request->doc_id); 
                // dd($collection->hasFindingDocument->id);

                // $collection     = $this->PatientHasDiagnosticFindingsHasDocumentsModel
                //                 ->where('id', $Id)
                //                 ->get();
                // dd($collection->toArray());
                // $data  = [];
                // foreach ($collections as $key => $collection)
                //         {
                            
                //             $data[$key]['file']  = $collection->hasFindingDocument->file;
                //         }
                      // dd($data);  
                // $collection     = $this->PatientsHasDiagnosticFindingsModel
                //         ->join('patient_has_diagnostic_findings_has_documents', 'patient_has_diagnostic_findings_has_documents.finding_id', '=', 'patients_has_diagnostic_findings.id')
                //         ->where('patients_has_diagnostic_findings.patient_id', $patientId)
                //         ->get([
                //                 'patients_has_diagnostic_findings.id',
                //                 'patient_has_diagnostic_findings_has_documents.file',
                //             ]); 
                        // dd($collection);

                // echo "<pre>";
                // print_r($collection);
                // die;
                // $fileArray = '';
                // if($collection->hasFindingDocument){
                //     $documents = $collection->hasFindingDocument;
                //     foreach ($documents as $document) {
                //         $id=$document->id; 
                //     } 
                //     // echo "<pre>";
                //     // print_r($fileArray);
                //     // die;
                //     if($fileArray){
                //         $status = true; 
                //         return response()->download(storage_path('app/'.$fileArray));
                //     }else{
                //     $errors[] = __('api.ERR_DIAGNOSTIC_FILE');
                //     self::_createLog('downloadFinding',$errors,'error'); 
                //     }
                // }  
                // $data = $collection;

                $new_file = self::StorePath($collection->file.'/');

                if(!empty($collection->file)){
                    $status = true;
                    return response()->download($new_file);
                 }else{
                    $errors[] = __('api.ERR_DIAGNOSTIC_FILE');
                    self::_createLog('downloadFinding',$errors,'error');
                    // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
                 }
               
            }
            catch(\Exception $e) {
                $message = __('api.ERR_SOMETHING_WRONG');
                $errors[] = [
                      "error" => $e->getMessage(), 
                  ];
            }
        }
        return self::_sendResult($message,$data,$errors,$status);
    }


    //FINDING SERVISEC LIST
    public function getFindingServices(Request $request)
    {        
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collection = collect([]); 
            $collection = $this->FindingServicesModel->getFindingServices();

             if(!empty($collection) && sizeof($collection) > 0){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                   
                    self::_createLog('getFindingsSrvices',array($data),'info');

                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('getFindingsSrvices',$errors,'error');
                }
            }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getFindingsSrvices',$errors,'error');
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    //FINDING SERVISEC TYPE for Qrcode
    public function getFindingServicesQrcode(Request $request)
    {        
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;

        try{
            $collection = collect([]); 

            $collection = $this->BaseModel->get();
       
             if(!empty($collection) && sizeof($collection) > 0){
                    $status  = true;
                    $message = __('api.DATA_FOUND_SUCCESS');
                    $data  = $collection;
                   
                    self::_createLog('getFindingsSrvices',array($data),'info');

                }else{
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('getFindingsSrvices',$errors,'error');
                }
            }
        catch(\Exception $e) {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getFindingsSrvices',$errors,'error');
        }
        return self::_sendResult($message,$data,$errors,$status);
    }


    //GET ALL APPOINMANTS
    public function getAllAppoinmant(Request $request)
    {
       
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $input   = $request->all();

        try
        {
            $collection = collect([]);
            /* 
            $collection = $this->AppointmentModel
                              // ->where('patient_id',$input['patient_has_diagnostic_findings_has_documents'])
                              ->where('patient_id',$input['patient_id'])
                              ->whereDate('start_date','<',date('Y-m-d'))
                              ->get();
                              */
            //Updated
            $collection = $this->AppointmentModel
                              // ->where('patient_id',$input['patient_has_diagnostic_findings_has_documents'])
                              ->where('patient_id',$input['patient_id'])
                              // ->whereDate('start_date','<',date('Y-m-d')) // commented on 20-nov-23
                              ->whereDate('start_date','<=',date('Y-m-d')) //added on 20-nov-23
                              ->where('appointment_status','Fertig')
                              ->where('is_app_booked', 1) // added by vijay 16/4/2024
                              ->orderBy('start_date', 'DESC')
                              ->get();                      
            //dd($collection);               
            if(!empty($collection) && sizeof($collection) > 0)
            {
                $status  = true;
                $message = __('api.DATA_FOUND_SUCCESS');
                $data    = $collection;
               
                self::_createLog('getAllAppoinmant',array($data),'info');
            }
            else
            {
                $message  = __('api.ERR_NOT_FOUND');
                $errors[] = [
                      "error" => __('api.DATA_NOT_FOUND'),
                  ];
                self::_createLog('getAllAppoinmant',$errors,'error');
            }
        }
        catch(\Exception $e) 
        {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getAllAppoinmant',$errors,'error');
        }
        return self::_sendResult($message,$data,$errors,$status);
    }

    //send requirest for admin get old findings.
    public function SendRequiredAdminGetOldFindings(Request $request)
    {
        //dd($request->all());
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_NOT_FOUND');  
        $status     = false;
        $input   = $request->all();
        
        try
        {
            $validator  = Validator::make($input,[
                              'patient_id'  => 'required',
                              'appoinmant_date'  => 'required',
                            ], 
                            [
                              'patient_id.required'    => __('api.ERR_PATIENT_ID_REQ'),
                              'appoinmant_date.required' => __('api.ERR_APPOINTMENT_REQ'),  
                            ]
                            ); 

            if ($validator->fails())  
            {     

              $errors[] = $validator->errors(); 
            }else
            {
               
                $collection = collect([]);
                $admin_email = $this->SettingsModel
                               ->where('setting_key','=','ORDINATION_EMAIL_ADDRESS')
                               ->whereStatus(1)
                               ->first();
                 $admin_email = $this->SettingsModel
                            ->where('setting_key','=','ORDINATION_EMAIL_ADDRESS')
                            ->whereStatus(1)
                            ->first();

                if($admin_email)
                {
                    $AdminEmail = $admin_email->setting_value;
                }               
               
                $patient_details  = $this->PatientsModel
                                    ->where('id',$input['patient_id']) 
                                    ->first();
                $patient_details['note_report_request_flag'] = '2';    
                $patient_details['patient_status_flag'] = '0';                    

                $bdate = '';
                if(!empty($patient_details))
                {
                    if(!empty($patient_details['birth_date']))
                    {
                        $bdate = Date('d-m-Y',strtotime($patient_details['birth_date'])).',';
                    }

                    $collection['body'] = ucfirst($patient_details['first_name']).' '.$patient_details['family_name'] .' '.$bdate.' hat um die Übermittlung der Befunde folgender Untersuchung in die Smartphone App gebeten:';

                    $collection['patients_name'] = ucfirst($patient_details['first_name']).' '.$patient_details['family_name'];
                }
                else
                {
                    $collection['body'] = "";
                } 
                if(!empty($input['notes']))
                {
                    $collection['note'] = $input['notes'];
                    $patient_details['note_report_request'] = $input['notes'];
                    $patient_details['note_report_request_from'] = 'app';
                }
                else
                {
                    $collection['note'] = "";
                }        
                $patient_details->save();
                //dd($patient_details);
                //GET APPOINMANT DATES
                $ap_date = [];
                $cnt = 0;
                //dd($input['appoinmant_date']);

               
                if(!empty($input['appoinmant_date']) && sizeof($input['appoinmant_date'])>0)
                {

                    foreach ($input['appoinmant_date'] as $key_1 => $a_value) 
                    {
                        //dd($a_value);
                       
                        $appoinmant_date = $this->AppointmentModel->where('id',$a_value)->first();
                        //dd($appoinmant_date,$a_value);
                        // INSERT RECORD
                     
                       
                        if(!empty($appoinmant_date))
                        {
                            //uncommented below exists condition on 24-may-23 for exists issue
                            $OldFindingModel = $this->PatientsHasOldFindingModel
                                           ->where('fk_patient_id',$input['patient_id'])
                                           ->where('appointment_id',$a_value)
                                           ->where('appoinmant_date',$appoinmant_date['start_date'])
                                           ->where('imported_flag','0')
                                           ->first();
                              
                                            
                            if(empty($OldFindingModel))
                            {
                                $OldFindingModel = new $this->PatientsHasOldFindingModel;
                                $OldFindingModel->fk_patient_id   = $input['patient_id'];

                                $OldFindingModel->appointment_id  = $a_value;
                                if(!empty($appoinmant_date['start_date']))
                                {
                                    $OldFindingModel->appoinmant_date = date('Y-m-d H:i:s',strtotime($appoinmant_date['start_date']));
                                    $ap_date[$cnt] =  date('d-m-Y H:i:s',strtotime($appoinmant_date['start_date']));    
                                }else
                                {
                                    $OldFindingModel->appoinmant_date = '';
                                    $ap_date[$cnt] = '';
                                }
                                $OldFindingModel->imported_flag = '0';
                                $OldFindingModel->created_at      = Date('Y-m-d');
                                $OldFindingModel->save();
                                $cnt++; 
                            } 
                        }
                        

                        
                    }
                }
                // GET APPOINMANT DATES End
               
                $collection['appoinmant_date']= $ap_date;

                 /**************update old patient table data on 20dec22******/
                 if(isset($input['patient_id']) && !empty($input['patient_id'])){
                    $patientCollection = $this->PatientsModel->find($input['patient_id']); 
                    if(isset($patientCollection)){
                       $old_patient_update = self::_oldPatient($patientCollection);  
                   }//if                   
                 }//if isset   
               /*********update old patient table data on 20dec22***********/

                $result = Mail::to($AdminEmail)->send(new SendRquiredforadminmail($collection));
                               
                if(!empty($collection) && sizeof($collection) > 0)
                {
                    $status  = true;
                    $message = 'Vielen Dank! Ihre Anfrage wird bearbeitet';
                    $data    = $collection;
                   
                    self::_createLog('SendRequiredAdminGetOldFindings',array($data),'info');
                }
                else
                {
                    $message  = __('api.ERR_NOT_FOUND');
                    $errors[] = [
                          "error" => __('api.DATA_NOT_FOUND'),
                      ];
                    self::_createLog('SendRequiredAdminGetOldFindings',$errors,'error');
                }
            }    
       }
        catch(\Exception $e) 
        {
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('getAllAppoinmant',$errors,'error');
        }

        return self::_sendResult($message,$data,$errors,$status);
    }
     
    public function createIMGFileName($patient_id,$name,$original_file)
    {
        $extensionForImg =explode('.', $original_file);
        $fileName = '';
        $digits = 3;
        $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
        //dd($randomNo);
        $patientDetails = $this->PatientsModel->find($patient_id);
        if(!empty($patientDetails))
        {
            if(!empty($name))
            {
                $name = substr($name,0,3);
                $name = strtoupper($name);
                $bod = Date('m-d',strtotime($patientDetails->birth_date));
                $bod = str_replace('','-', $bod);
            }
            else
            {
                $name = "";
            }

            
            $fileName = $patientDetails->family_name.'_'.$randomNo.'_'.$name.'_'.$bod;
        }
      
        return $fileName.'.'.$extensionForImg[1];
    }
    
   public function createDiagnosticFindingsTest(Request $request)
    {
        $errors     = [];  
        $data       = [];
        $message    = __('api.ERR_INVALID_DATA');
        $status     = false; 
        $findingDocumentObj = '';
        $findingDocumentRecords = [];
        $findingImage = [];
        try {  
            // DB::beginTransaction(); 
            $inputdata = $request->all();

            $validator = Validator::make($inputdata,[
                              'patient_id'      => 'required',
                              'finding_type_id' => 'required',
                              'document_name'   => 'required',
                              'file'   => 'required',
                              'date'            => 'required',
                              //'comment'         => 'required',
                              'status'          => 'required',
                            ],  
                            [
                              'patient_id.required' => __('api.ERR_PATIENT_ID_REQ'),
                              'finding_type_id.required' => __('api.ERR_FINDINGD_TYPE_REQ'),
                              'document_name.required' => __('api.ERR_DOCUMENT_NAME_REQ'),
                              'file.required'    => __('api.ERR_FINDING_FILE_REQ'),
                              'date.required'    => __('api.ERR_FINDING_DATE_REQ'),
                             // 'comment.required' => __('api.ERR_FINDING_COMMENT_REQ'),
                              'status.required'  => __('api.ERR_FINDING_STATUS_REQ'),         
                            ]); 

            if ($validator->fails()) {            
              $errors[] = $validator->errors(); 
            }else{

                $status = true; 
                // File Uploading
                DB::beginTransaction();

                $collection                 = new $this->PatientsHasDiagnosticFindingsModel;
                $collection->patient_id     = $request->patient_id;
                $collection->finding_type_id= $request->finding_type_id;
                $collection->document_name  = $request->document_name;
                $date                       = strtotime($request->date);
                $collection->date           = date('Y-m-d', $date);  
                $collection->comment        = $request->comment;
                $collection->status         = $request->status;

                if($collection->save())
                {

                    $all_transactions = [];
                  
                    if($request->hasfile('file'))
                    { 
                         
                        $finding_cnt = 0;
                       
                        foreach($request->file('file') as $file)
                        {
                            $path = 'diagnostic_findings';
                            $original_file  = strtolower($file->getClientOriginalName());
                            $original_file  = str_replace(' ', '%20', $original_file);
                            $extension      = strtolower($file->getClientOriginalExtension()); 
                            $f_name = str_replace('%20', '', $original_file);
                    
                            $fileName    = date('YmdHis').'-'.trim($f_name);
                            //$fileName   = $request->document_name.'_'.$request->patient_id;
                            
                           
                            // $storefilePath  = '/diagnostic_findings/'.date('YmdHis').'-'.$original_file; 
                            
                            //$filePath  = date('YmdHis').'-'.$original_file;
                            $img_original_name = explode('.',$original_file);

                            $img_find_name = explode('/',$request->document_name);
                            if(count($img_find_name)>1)
                            {
                                $imgFindingName = trim($img_find_name[0]).'_'. trim($img_find_name[1]);
                            }
                            else
                            {
                                $imgFindingName = $request->document_name;
                            }
                            $digits   = 3;
                            $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
                            $fileName = $imgFindingName.'_'.$request->patient_id.'_'.$randomNo.'.'.$img_original_name[1];
                            // $fileName = self::createPdfFileName($request->patient_id,$imgFindingName);
                            $fileName = self::createIMGFileName($request->patient_id,$imgFindingName,$original_file);

                            // $storefilePath  = '/diagnostic_findings/'.$fileName; 
                            // //$fileStorePath = Storage::putFileAs($path, $file, $fileName);
                            // //$fileStorePath = Storage::disk('tenant')->putFileAs($path, $file, $fileName);
                            // //dd($path, $file, $fileName);
                            // $fileStorePath = self::putFilePath($path, $file, $fileName);
                            // // $findingDocumentObj->finding_id   = $collection->id;
                            // // $findingDocumentObj->original_name   = $fileName;    
                            // // $findingDocumentObj->file           = $filePath;

                            // // // Create Array for finding image name file_path
                            // $newFileName = self::getFilePath($storefilePath);

                            // $findingImage[$finding_cnt] = $newFileName;
                            // $finding_cnt++;
                            // // // End
                            // // //Save data

                              $filePath  = $path.'/'.$fileName;                          
                            $fileStorePath = self::putFilePath($path, $file, $fileName);                         

                            $new_img_path = self::StorePath($path.'/crop/');                          
                            if(!file_exists($new_img_path)){                               
                                Storage::makeDirectory($path.'/crop',0755);
                            }                            
                            $fileCropPath  = $path;                           
                            $new_fileName = self::StorePath($path);                            
                            
                            $new_fileCropPath = self::StorePath($fileCropPath);                            
                            $croppath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/crop/'.$fileName; 
                            $getSizepath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/'.$fileName; 

                            $image = getimagesize($getSizepath);
                            $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9b15a68114c94800aa29355b4d3c9944/diagnostic_findings/'.$fileName;
                            $width = $image[0];
                            $height = $image[1];                            
                           
                            $a= Image::make($PdfPath)->resize($width,$height ,function($constraint){
                                    $constraint->upsize();
                                    $constraint->aspectRatio();
                                    })
                                   ->save($croppath);
                   
                            $new_fileCropPath = self::StorePath($fileCropPath);
                           
                            $newFileName = self::getFilePath('/diagnostic_findings/crop/'.$fileName);
                            $findingImage[$finding_cnt]['path'] = $newFileName;
                            $findingImage[$finding_cnt]['name'] = $fileName;
                            $findingImage[$finding_cnt]['width'] = $width;
                            $findingImage[$finding_cnt]['height'] = $height;
                            $finding_cnt++;
                            // // End
                            // //Save data
                        }

                        // Generate PDF for Upload Finding 
                        // if(!empty($findingImage) && sizeof($findingImage)>1)
                        if(!empty($findingImage))
                        {
                            //$PdfPath   = self::StorePath('diagnostic_findings/');
                            //$PdfPath   = storage_path().'/app/diagnostic_findings/';
                            //dd($PdfPath); $request->patient_id
                            // $PDFname   = $request->document_name.'_'.time().'.pdf';
                            $doc_find_name = explode('/',$request->document_name);
                            if(count($doc_find_name)>1)
                            {
                                $PDFFindingName = trim($doc_find_name[0]).'_'. trim($doc_find_name[1]);
                            }
                            else
                            {
                                $PDFFindingName = $request->document_name;
                            }
                            $digits   = 3;
                            $randomNo = rand(pow(10, $digits-1), pow(10, $digits)-1);
                            //$PDFname = $PDFFindingName.'_'.$request->patient_id.'_'.$randomNo.'.pdf';
                            $PDFname = self::createPdfFileName($request->patient_id,$PDFFindingName);
                            //$PDFname = $PDFFindingName.'_'.$request->patient_id.'.pdf';
                            //$PDFname   = $request->document_name.'_'.$request->patient_id.'.pdf';
                            //dd($PDFname);
                            // Invoice full path
                            if(!empty(Config('ordination_id')))
                            {
                                $getDatabaseName = DB::connection('system')
                                            ->table("tenants")
                                            ->where('ordination_id',Config('ordination_id'))
                                            ->first(['uuid']);

                                $PdfPath = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/diagnostic_findings/';
                            }
                            else
                            {
                                $PdfPath = '/opt/app-shared/php/data/storage/app/public/diagnostic_findings/';
                            }

                            $StorePath = $PdfPath.$PDFname; 
                            $accessPath = '/diagnostic_findings/'.$PDFname;

                            $PDFPath = 'admin.pdf.finding-image';  
                            //added by swati 19-Jul-23 to work image if ssl is changed=====================
                             $pdf = app('dompdf.wrapper');
                             //############ Permitir ver imagenes si falla ################################
                              $contxt = stream_context_create([
                                'ssl' => [
                                    'verify_peer' => FALSE,
                                    'verify_peer_name' => FALSE,
                                    'allow_self_signed' => TRUE,
                                ]
                            ]);

                            $pdf = \PDF::setOptions(['isHTML5ParserEnabled' => true, 'isRemoteEnabled' => true]);
                            $pdf->getDomPDF()->setHttpContext($contxt);
                            $pdf->loadView($PDFPath,compact('findingImage'))->save($StorePath);
                            // PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView($PDFPath,compact('findingImage'))->save($StorePath);
                           
                            //$AccessPath = '/storage/Invoice/'.$PDFname;

                            // Save PDF name and pdfPath
                            $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                            $findingDocumentObj->finding_id    = $collection->id;
                            $findingDocumentObj->original_name = $PDFname;    
                            $findingDocumentObj->file          = '/diagnostic_findings/'.$PDFname;

                            if ($findingDocumentObj->save()) 
                            { 
                                // dd('if');
                                $findingDocumentRecords[] = $findingDocumentObj;
                                $all_transactions[] = 1;
                            }
                            else
                            {
                                // dd('else');
                                $all_transactions[] = 0;
                            }
                        }
                        // else
                        // {
                        //     $findingDocumentObj     = new $this->PatientHasDiagnosticFindingsHasDocumentsModel;
                        //     $findingDocumentObj->finding_id   = $collection->id;
                        //     $findingDocumentObj->original_name   = $fileName;    
                        //     $findingDocumentObj->file           = $storefilePath;
                        //     if ($findingDocumentObj->save()) 
                        //     { 
                        //         // dd('if');
                        //         $findingDocumentRecords[] = $findingDocumentObj;
                        //         $all_transactions[] = 1;
                        //     }
                        //     else
                        //     {
                        //         // dd('else');
                        //         $all_transactions[] = 0;
                        //     }
                        // }

                        
                       //dd($findingDocumentRecords);
                        // End
                    }                
                }else{
                     $all_transactions[] = 0;
                }
                // dd($all_transactions);
                if (!in_array(0,$all_transactions)) 
                {
                    $status  = true;
                    $message = __('api.DATA_INSERTED');
                    // $data = $this->PatientsHasDiagnosticFindingsModel
                    //             ->with(['hasFindingDocument'])
                    //             ->get();
                   
                    $data = $collection; 
                    $data['has_document'] = $findingDocumentRecords;
                     // dd($findingDocumentRecords->id); 
                    $newData = [];
                    $newData['patient_id'] = $collection->patient_id; 
                    $newData['finding_type_id'] = $collection->finding_type_id;
                    $newData['document_name'] = $collection->document_name;
                    $newData['date'] = $collection->date;
                    $newData['comment'] = $collection->comment;
                    $newData['status'] = $collection->status;
                    $newData['id'] = $collection->id;
                    // dd($newData);
                    // dd($findingDocumentObj->original_name);
                    // $document = [];
                    // dd($findingDocumentRecords);
                    // foreach ($findingDocumentRecords as $value) {
                    //     $newData['doc_name'] = explode(" ",$value->original_name);
                    // }
                    
                    // $newData['document'] = implode(",",$document);
                    // dd($document);
                   
                    self::_createLog('createDiagnosticFindings',array($data),'info');
                    $this->ActivityLogModel->addApiLog('Create Diagnostic Findings','has create diagnostic finding','Create',null,$newData); 
                    DB::commit();  
                }else
                {
                    DB::rollback(); 
                    $message = __('api.ERR_SOMETHING_WRONG');
                }
            }
        }
        catch(\Exception $e) {
            DB::rollback();
            $message = __('api.ERR_SOMETHING_WRONG');
            $errors[] = [
                  "error" => $e->getMessage(), 
              ];
            self::_createLog('createDiagnosticFindings',$errors,'error');
            // $this->ActivityLogModel->addApiLog('SignupSendOtp','send otp for login','Get');
        }

        return self::_sendResult($message,$data,$errors,$status);
    } 
    
}

