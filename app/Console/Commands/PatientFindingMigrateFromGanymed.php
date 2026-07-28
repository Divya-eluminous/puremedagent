<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\UserHasRetrievedCertificateModel;
use App\Models\MigrationTables;
use App\Models\FindingsModel;
use Orchestra\Parser\Xml\Facade as XmlParser;

use App\Models\FremdbefundeSqlSrvModel;

use DB;
use Storage;


class PatientFindingMigrateFromGanymed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patient-migrate-finding:add';

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
    public function __construct(FindingsModel $FindingsModel)
    {
        parent::__construct();
        $this->FindingsModel = $FindingsModel;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

       // dd(\Carbon\Carbon::parse('2020-10-30')->format('Y-m-d H:i:s'));
        // dump('start');

        // $patientGanyFindings = FremdbefundeSqlSrvModel::where('pat_nr','1')->latest('id')->get();
        // dd($patientGanyFindings,'lisitng');

      

        // $sql = "select * from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='fremdbefunde'";
        // dd(DB::connection('sqlsrv')->select($sql)); 

        $txt = '';
        $blacklist_keywords = [];
        $whitelist_keywords = [];

        $whitelist_findings = $this->FindingsModel
                        ->select('keyword')
                        ->where('status','W')
                        ->get()
                        ->toArray();
        if(!empty($whitelist_findings))
        {
            $whitelist_keywords = array_column($whitelist_findings,'keyword');
        }else
        {
            $blacklist_findings = $this->FindingsModel
                        ->select('keyword')
                        ->where('status','B')
                        ->get()
                        ->toArray();
            $blacklist_keywords = array_column($blacklist_findings,'keyword');
        }


        $txt = '';
        $blacklist_keywords = [];
        $whitelist_keywords = [];

        $whitelist_findings = $this->FindingsModel
                        ->select('keyword')
                        ->where('status','W')
                        ->get()
                        ->toArray();
        if(!empty($whitelist_findings))
        {
            $whitelist_keywords = array_column($whitelist_findings,'keyword');
        }else
        {
            $blacklist_findings = $this->FindingsModel
                        ->select('keyword')
                        ->where('status','B')
                        ->get()
                        ->toArray();
            $blacklist_keywords = array_column($blacklist_findings,'keyword');
        }


        $lastUserId = DB::table('finding_last_inserted')->where('id','1')->pluck('last_id')->first();

        //dd($lastUserId);

        if(!empty($lastUserId))
        {
            $patientGanyFindings = DB::connection('sqlsrv')
                                    ->table('fremdbefunde')
                                    ->where('ID', ">", $lastUserId)
                                    ->orderBy('ID','ASC')
                                    ->get();

            // $patientGanyFindings = DB::table('fremdbefunde')
            //                     ->where('id', ">", $lastUserId)
            //                     ->where('document_from_app_server', "=", '0')
            //                     ->orderBy('id','ASC')
            //                     ->get();
        }
        else
        {
            $patientGanyFindings = DB::connection('sqlsrv')
                                    ->table('fremdbefunde')
                                    ->orderBy('ID','ASC')
                                    ->get();
            // $patientGanyFindings = DB::table('fremdbefunde')
            //                         ->orderBy('id','ASC')
            //                         ->paginate(50);
        }         
        //dd(count( $patientGanyFindings));
        $all_records = [];
        $index_key = 0;
        if(!empty($patientGanyFindings) && count($patientGanyFindings)>0)
        {
            foreach ($patientGanyFindings as $patientGanyFinding) 
            {          

                DB::table('finding_last_inserted')->where('id',1)->update(['last_id'=>$patientGanyFinding->ID]);  
                              

                $patientRecord =  DB::table("patients")//_bk_06102020
                                     ->where('pat_nr','=',$patientGanyFinding->pat_nr)
                                     ->first(['id']);
                
                if(!empty($patientRecord))
                {  
                    //$finding_date = trim(date("Y-m-d",strtotime($patientGanyFinding->dat)));

                    $finding_date = trim(date("Y-m-d",strtotime($patientGanyFinding->dat)))."-".trim($patientGanyFinding->text);

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['patient_id']      = trim($patientRecord->id);

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['old_id']      = trim($patientGanyFinding->ID);

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_type_id'] = trim($patientGanyFinding->von_fremd);

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['document_name']   = trim($patientGanyFinding->text);

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['date']            = trim(date("Y-m-d",strtotime($patientGanyFinding->dat)));

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['comment']         = trim($patientGanyFinding->langtext);

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['status']          = 2;

                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['finding_id'] = 'inserted_main_id';

                    if(!empty($patientGanyFinding->datei)){
                        $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['original_name'] = trim($patientGanyFinding->datei);
                        $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['file'] = '/diagnostic_findings/'.trim($patientGanyFinding->datei);
                        $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['text'] = trim($patientGanyFinding->text);
                    }else{
                        //create file and store it in pdf format
                        //$patientGanyFinding->langtext
                        $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['original_name'] = 'create_pdf';
                        $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['file'] = trim($patientGanyFinding->langtext);
                        $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['text'] = trim($patientGanyFinding->text);
                    }
                    
                    $all_records[$patientGanyFinding->pat_nr][$finding_date]['finding_has_documents'][$index_key]['created_at'] = trim(date("Y-m-d",strtotime($patientGanyFinding->dat)));
                    $index_key++;

                    dump("created array up to ".$patientGanyFinding->ID);
                }
            }
        }
        else
        {
             echo "no record found";
        }
     
        foreach ($all_records as $all_record) 
        {
            foreach ($all_record as $patientData)
            {

               // $validHeader = ((strpos($patientData['document_name'], 'LABADOLF') !== false) || (strpos($patientData['document_name'], 'MEADOLF2') !== false)  || (strpos($patientData['document_name'], 'SPEWIE1') !== false)  || (strpos($patientData['document_name'], 'MEIMCLPF') !== false));

                $searchword = $patientData['document_name'];
                if(!empty($whitelist_keywords))
                {
                   // echo "if";
                    $validHeader =  array_filter($whitelist_keywords, function($var) use ($searchword) 
                    { 
                        return strpos($searchword,$var) !== false;
                    });                    
                }
                if(!empty($blacklist_keywords))
                { 
                    $validHeader =  array_filter($blacklist_keywords, function($var) use ($searchword) 
                    { 
                         return strpos($searchword,$var) !== false;
                    });
                    if(!empty($validHeader) && sizeof($validHeader) >0)
                    {
                        continue;
                    }else
                    {
                        $validHeader = true;
                    }                
                }
                if ($validHeader) 
                { 
                    $tmp = [];
                    $tmp['patient_id']      = $patientData['patient_id'];
                    $tmp['old_id']          = $patientData['old_id'];
                    $tmp['finding_type_id'] = $patientData['finding_type_id'];
                    $tmp['document_name']   = $patientData['document_name'];
                    $tmp['date']            = $patientData['date'];
                    $tmp['comment']         = $patientData['comment'];
                    $tmp['status']          = $patientData['status'];
                    //dd($tmp);
                    $lastInsertId = DB::table('patients_has_diagnostic_findings')->insertGetId($tmp);

                    if($lastInsertId)
                    {
                        // dd($patientKey,$patientData);
                        $doc_recs = [];
                        $j = 0;
                        foreach ($patientData['finding_has_documents'] as $doc_index=>$finding_has_document)
                        {
                            // $validInnerHeader = ((strpos($finding_has_document['text'], 'LABADOLF') !== false) || (strpos($finding_has_document['text'], 'MEADOLF2') !== false)  || (strpos($finding_has_document['text'], 'SPEWIE1') !== false)  || (strpos($finding_has_document['text'], 'MEIMCLPF') !== false));
                            // if ($validInnerHeader)
                            // { 
                            if ($finding_has_document['text'] == $patientData['document_name'])
                            {
                                $doc_recs[$j]['finding_id']      = $lastInsertId;
                                $doc_recs[$j]['text']            = $finding_has_document['text'];
                                $doc_recs[$j]['patient_id']      = $patientData['patient_id'];
                                $doc_recs[$j]['original_name']   = $finding_has_document['original_name'];
                                $doc_recs[$j]['file']            = $finding_has_document['file'];
                                $j++;
                            }
                        }
                    }
                    else
                    {
                        echo "fail to insert:".$patientData['patient_id'];
                        $txt .= "\n fail to insert:".$patientData['patient_id'];
                    }

                    if(!empty($doc_recs) && sizeof($doc_recs)>0)
                    {
                        DB::table("patient_has_diagnostic_findings_has_documents")
                                ->insert($doc_recs);
                    }
                }
            }
        }

        
        
       // dd('all inserted');
        $app_server_data = DB::table('patients_has_diagnostic_findings')
        ->where('old_id','0')
        ->whereNull('deleted_at')
        ->get();
       // dd(count($app_server_data));

        if(!empty($app_server_data) && count($app_server_data) > 0)
        {            
            foreach($app_server_data as $key=>$value)
            {
                $document_data = DB::table('patient_has_diagnostic_findings_has_documents')->where('finding_id',$value->id)->get();

                $gynamade_array = array();
                foreach($document_data as $new_entry)
                {
                    $gynamade_array = [];
                    $pat_nr_id = DB::table("patients")->select('pat_nr')
                                         ->where('id','=',$value->patient_id)
                                         ->first();
                    
                  //  $gynamade_array['text'] = DB::raw("CAST('".trim($value->document_name)."' as char)");
                    //  $gynamade_array['datei'] =  DB::raw("CAST('".trim($new_entry->file)."' as char)");  
                   // $gynamade_array['dat']      =  \Carbon\Carbon::parse($value->date)->format('Y-m-d H:i:s');
                    // $gynamade_array['sysdat'] = \Carbon\Carbon::parse($value->date)->format('Y-m-d H:i:s');
                    $date  = $value->date." 00:00:00.000";  
                   // $date  = "2020-10-10 00:00:00.000";  

                  //  $date = getdate();     
                    dump($value->date);
                    $gynamade_array['pat_nr']  =  $pat_nr_id->pat_nr;
                    $gynamade_array['dat'] =  DB::raw('GETDATE()');
                    $gynamade_array['sysdat'] = DB::raw('GETDATE()');
                    $gynamade_array['text']     = trim($value->document_name)."_".$value->comment;
                    $file = str_replace("/diagnostic_findings/", "",$new_entry->file);
                    $gynamade_array['datei']    = trim($file);                    
                    $gynamade_array['von_fremd'] =  $value->finding_type_id;

                   // dd($gynamade_array);
                    $status = '';
                    try 
                    {
                         $status = FremdbefundeSqlSrvModel::insertGetId($gynamade_array);
                    } 
                    catch (\PDOException $e) {
                        # do something or render a custom error page
                       //  dd($e->getMessage());
                    }
                   
                    

                    $document_data = DB::table('patients_has_diagnostic_findings')->where('id',$value->id)->update(['old_id'=>'-1']);
                 
                    dump("app server id=".$new_entry->id." updated on server");

                    $last_id_data = DB::table('finding_last_inserted')->select('last_id')->where('id',1)->first(); 

                    $last_id = $last_id_data->last_id;

                    DB::table('finding_last_inserted')->where('id',1)->update(['last_id'=>($last_id + 1)]);

                   // $gynamade_array['BenutzerID'] = NULL;
                   // $gynamade_array['gelesen'] = DB::raw("CAST(0 as int)");
                  //  $gynamade_array['entryUUID'] = NULL;
                    // $gynamade_array['homeCommunityID'] =NULL;
                    // $gynamade_array['repositoryUniqueID'] = NULL;
                    // $gynamade_array['uniqueID'] = NULL;

                  //  $gynamade_array['gelesen'] = 0;
//dd($gynamade_array);
                    // $lastInsertId = DB::table('fremdbefunde')

                    // $status = DB::connection('sqlsrv')
                    // ->create([
                    //     'pat_nr' => $pat_nr_id->pat_nr,
                    //      'dat' =>$value->date, 
                    //      'sysdat' => $value->date, 
                    //      'text' => trim($value->document_name), 
                    //      'datei' => trim($new_entry->file), 
                    //      'von_fremd' => $value->finding_type_id,
                    //      'gelesen' => 0,
                    // ]);


                    // $status = DB::connection('sqlsrv')
                    // ->insert('insert into fremdbefunde (pat_nr,dat,sysdat,text,datei,von_fremd,gelesen)
                    //     values (?, ?, ?, ?, ?, ?, ?)',
                    //     $gynamade_array);

                   // DB::connection('sqlsrv')->unprepared('SET IDENTITY_INSERT fremdbefunde ON');

                    // $insert_query = "INSERT INTO fremdbefunde(pat_nr,dat,sysdat,text,datei,von_fremd,gelesen) 
                    //     VALUES(
                    //     CAST(".$pat_nr_id->pat_nr." as int),
                    //     CAST(".$value->date." as datetime),
                    //     CAST(".$value->date." as datetime),
                    //     CAST('".trim($value->document_name)."' as char),
                    //     CAST('".trim($new_entry->file)."' as char),
                    //     CAST(".$value->finding_type_id." as int),
                    //     CAST(0 as int)
                    //     )";

                    // DB::connection('sqlsrv')
                    //         ->statement(DB::raw($insert_query));

                      

                    // $lastInsertId = DB::connection('sqlsrv')
                    //                 ->table('fremdbefunde')
                    //                 ->insert($gynamade_array);

                   // dd($status );

                 //   DB::connection('sqlsrv')->unprepared('SET IDENTITY_INSERT fremdbefunde OFF');
                    

                    //  dd('done');
                }           
            } 
        }        
    }
}

