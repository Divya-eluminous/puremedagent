<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang; 
use Illuminate\Support\Arr;
// use \Dimsav\Translatable\Translatable;  

// Models
use App\Models\ActivityLogModel;  
use App\Models\AdminUserModel;   

// exports 
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CollectionExport;

// plugins
use Hash;
use Mail;
use DB; 
use Auth;
use URL;
use App\Traits\GeneralTrait;
class ActivityLogsController extends Controller
{
    use GeneralTrait;
    private $BaseModel;
    // public $translatedAttributes = ['name'];

    public function __construct(
        // array $attributes = [],
        ActivityLogModel $ActivityLogModel
    )
    {
        $this->BaseModel = $ActivityLogModel; 

        $this->ViewData = [];
        $this->JsonData = [];   

        $this->ModuleTitle  = __('admin.TITLE_ACTIVITY_LOG_TEXT'); 
        $this->ModuleView   = 'admin.activity-logs.';
        $this->ModulePath   = 'admin.activity-logs.'; 
        
        // Permission Middleware
        $this->middleware(['permission:activity-logs'], ['only' => ['index']]);
       
        $this->defaultLocale = 'en';
    }

    public function index()  
    { 

      //  phpinfo();
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_ACTIVITY_LOG_TEXT');
        $this->ViewData['moduleTitle']  = __('admin.TITLE_MANAGE_TEXT').' '.$this->ModuleTitle;
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

   
    public function getRecords(Request $request)
    {
        /*--------------------------------------
        |  Variables
        ------------------------------*/
  
            // skip and limit
            $start  = $request->start;
            $length = $request->length;

            // serach value
            $search = $request->search['value']; 

            // order
            $column = $request->order[0]['column'];
            $dir    = $request->order[0]['dir'];

            // filter columns
            $filter = array(
                0 => 'id',
                1 => 'activity_logs.module',
                2 => 'activity_logs.message', 
                3 => 'activity_logs.method',
                4 => 'activity_logs.url',
                5 => 'activity_logs.ip',
                6 => 'activity_logs.agent',
                7 => 'activity_logs.user_id',
                8 => 'activity_logs.created_at', 
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel
                                ->leftjoin('users', 'users.id' , '=', 'activity_logs.user_id')
                                ->leftjoin('patients', 'patients.id' , '=', 'activity_logs.patient_id'); 

            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {

                if (!empty($request->custom['module'])) 
                {
                    $custom_search = true;
                    $key = $request->custom['module'];                
                    $modelQuery = $modelQuery
                    ->where('activity_logs.module','LIKE','%'.$key.'%');
                }

                if (!empty($request->custom['message'])) 
                {
                    $custom_search = true;
                    $key = $request->custom['message']; 
                    $modelQuery = $modelQuery
                    ->where('activity_logs.message','LIKE','%'.$key.'%');
                }  

                if (!empty($request->custom['method'])) 
                {
                    $custom_search = true;
                    $key = $request->custom['method'];                
                    $modelQuery = $modelQuery
                    ->where('activity_logs.method','LIKE','%'.$key.'%');
                }

                if (isset($request->custom['url'])) 
                {
                    $custom_search = true;
                    $key = $request->custom['url'];
                    $modelQuery = $modelQuery
                    ->where('activity_logs.url', $key);
                }

                if (isset($request->custom['ip'])) 
                {
                    $custom_search = true;
                    $key = $request->custom['ip'];
                    $modelQuery = $modelQuery
                    ->where('activity_logs.ip', '=', $key);
                }

                if (isset($request->custom['agent'])) 
                {
                    $custom_search = true;
                    $key = $request->custom['agent'];
                    $modelQuery = $modelQuery
                    ->where('activity_logs.agent', $key);
                }

                if (!empty($request->custom['name'])) 
                {
                    $name = explode(" ", $request->custom['name']);

                    if(!empty($name[1])){
                        $key[0] = $name[0];
                        $key[1] = $name[1];
                        $custom_search = true;                
                        $modelQuery = $modelQuery
                        ->where('users.first_name','LIKE','%'.$key[0].'%')
                        ->orWhere('users.last_name','LIKE','%'.$key[1].'%');
                    } else{
                        $key[0] = $name[0];
                        $custom_search = true;                
                        $modelQuery = $modelQuery
                        ->where('users.first_name','LIKE','%'.$key[0].'%');
                    }                    
                }
                // if (isset($request->custom['agent'])) 
                // {
                //     $custom_search = true;
                //     $key = $request->custom['agent'];
                //     $modelQuery = $modelQuery
                //     ->where('activity_logs.agent', $key);
                // }
            }

            //filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('activity_logs.module', 'LIKE', '%'.$search.'%'); 
                        $query->orwhere('activity_logs.message', 'LIKE', '%'.$search.'%');
                        $query->orwhere('activity_logs.method', 'LIKE', '%'.$search.'%');
                        $query->orwhere('activity_logs.url', 'LIKE', '%'.$search.'%');  
                        $query->orwhere('activity_logs.ip', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('activity_logs.agent', 'LIKE', '%'.$search.'%');  
                        $query->orwhere('activity_logs.user_id', 'LIKE', '%'.$search.'%'); 
                    }); 
                }
            }  

            // get total filtered
            $filteredQuery = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->count(); 
            
            // offset and limit
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                 ->skip($start)
                                 ->take($length) 
                                 ->get([
                                    'users.first_name as user_first_name',
                                    'users.last_name as user_last_name',
                                    'patients.first_name as patient_first_name',
                                    'patients.family_name as patient_last_name',
                                    'activity_logs.id',
                                    'activity_logs.user_id',
                                    'activity_logs.patient_id',
                                    'activity_logs.module',
                                    'activity_logs.action',
                                    'activity_logs.message',
                                    'activity_logs.method',
                                    'activity_logs.url',
                                    'activity_logs.ip',
                                    'activity_logs.agent',
                                    'activity_logs.created_at',
                                'activity_logs.old_data',
                                'activity_logs.new_data'
                            ]); 
            // dd($object);  
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row)  
                { 
                            
                    if($row->user_id != null){
                        $fname = $row->user_first_name;
                        $lname = $row->user_last_name;
                        $name  = $fname .' '. $lname;
                    }elseif ($row->user_id <= 0 && $row->patient_id <= 0) {
                        $name  = "User not login.";
                    }else{
                        $fname = $row->patient_first_name;
                        $lname = $row->patient_last_name;
                        $name  = $fname .' '. $lname;
                    }
                        // print_r($row->id); die;

                        $data[$key]['id']       = $row->id;

                        $data[$key]['module']   = '<span title="'.$row->module.'">'.$row->module.'</span>';

                        $data[$key]['message']  = '<span title="'.$row->message.'">'.$row->message.'</span>';

                        $data[$key]['method']   = '<span title="'.$row->method.'">'.$row->method.'</span>';   

                        $data[$key]['url']      =  "<span title='".$row->url."'>".$row->url."</span>";

                        $data[$key]['ip']       =  "<span title='".$row->ip."'>".$row->ip."</span>";

                        $data[$key]['agent']    =  "<span title='".$row->agent."'>".$row->agent."</span>";

                        // $data[$key]['user_id']    =  "<span title='".$row->user_id."'>".$row->user_id."</span>";

                        $data[$key]['name']     =  "<span title='".$name."'>".$name."</span>";  

                        $data[$key]['created_at']  = '<span title="'.$row->created_at.'">'.$row->created_at.'</span>';

                        // $data[$key]['actions']  = '<a data-href="'.URL::to('admin/activity-logs/getdata/'.$row['id']).'" data-toggle="modal" data-target="#updateUserPassword" class="dropdown-item">'.$row->action.'</a>';
                        $data[$key]['actions']  = '<a data-toggle="modal" id="getDeatil" data-target="#activityLogDetail" data-url="'.url('admin/activity-logs/getdata',['id'=>$row->id]).'" href="#."> '.$row->action.' </a>';    



                     // $data[$key]['actions'] = '<a href="javascript:void(0)" onclick="return editCollection(this)" data-edit="'.__('admin.TITLE_EDIT_TEXT').' ' .__('admin.TITLE_ROLE').'"  data-href="'.route('admin.roles.updateRole', [ base64_encode(base64_encode($row->id))]).'" role-name="'.$data[$key]['name'].'" role-identifier="'.$data[$key]['identifier'].'"class="edit-user action-icon" title="Edit"><span class="fas fa-edit"></span></a>&nbsp&nbsp';   


                        // $data[$key]['actions']  = '<span title="'.$row->action.'">'.$row->action.'</span>'; 

                        // $data[$key]['actions']  = '<button type="button"  data-toggle="modal" data-target="#myModal" onclick="showDtails({{$post->id }})">Get more weapon detials</button>';


                                          
                } 
            }

            ## SEARCH HTML  
            
            $searchHTML['id']       =  '';     
            $searchHTML['module']   =  '<input type="text" class="form-control" id="module" value="'.($request->custom['module'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['message']  =  '<input type="text" class="form-control" id="message" value="'.($request->custom['message'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';  

            $searchHTML['method']   =  '<input type="text" class="form-control" id="method" value="'.($request->custom['method'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['url']      =  '<input type="text" class="form-control" id="url" value="'.($request->custom['url'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['ip']       =  '<input type="text" class="form-control" id="ip" value="'.($request->custom['ip'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['agent']    =  '<input type="text" class="form-control" id="agent" value="'.($request->custom['agent'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>'; 

            // $searchHTML['user_id']     =  '';

            $searchHTML['name']     =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';

            $searchHTML['created_at']     =  '<input type="text" class="form-control" id="created_at" value="'.($request->custom['created_at'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>'; 
            
            $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';
            /*}*/   

            $searchHTML['actions'] = $seachAction;
            array_unshift($data, $searchHTML);

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData); 
    }

    public function getdata(Request $request, $id){
        // dd('tttttttttt');
        $id = $request->id; 
        // $collection = $this->BaseModel
        //                     ->where('id', $id)
        //                     ->get([
        //                         'activity_logs.id',
        //                         'activity_logs.old_data',
        //                         'activity_logs.new_data'
                            // ]);
        $collections = $this->BaseModel
                            ->where('id', $id)
                            ->first([
                                'id',
                                'old_data',
                                'new_data'
                            ]);  
                            // dd($collections); 
                          
        
            // dd($collections); 
            
                // dd($row);
                // $data[$key]['module']
                $collectionOldData = '-';
                // if($collections->old_data != null)
                // {
                    $collectionOldData = $collections->old_data;
                // }
                // $collectionOldData = $collections->old_data;
                $collectionNewData = $collections->new_data; 
                // dd($collectionOldData);

                $formatOldData = json_decode($collectionOldData);
                $formatNewData = json_decode($collectionNewData);
                if((is_array($formatNewData)) || (is_array($formatOldData))){
                    // dd('test');
                    $oldDataArray = (array)$formatOldData[0];
                    $newDataArray = (array)$formatNewData[0];
                } else{
                    $oldDataArray = (array)$formatOldData;
                    $newDataArray = (array)$formatNewData;
                }
                // dd($data);
                // dd($formatNewData);
              
                // dd($newDataArray);

                $keysData = array_keys($newDataArray);
                // dd($keysData);
                // dd(array_values($data2));
                $olaDatavalue = array_values($oldDataArray);
               
                $newDatavalue = array_values($newDataArray);
                 
                // dd(json_decode($oldData));
                $data = '';
                // $data = "<thead><tr><th></th><th>".$data1."</th></tr></thead><tbody><tr><th>Old Value</th><td>".$data2."</td></tr><tr><th>New Value</th><td>".$data3."</td></tr></tbody>";
                $data = '';
                $data = "<thead><tr><th></th>";
                foreach ($keysData as $keyData){
                $data .= "<th>".$keyData."</th>";
                }
                $data .= "</tr></thead><tbody><tr><th>New Value</th>";
                foreach ($newDatavalue as $newData)
                {
                    $data  .="<td>".$newData."</td>";
                }
                $data .= "</tr><tr><th>Old Value</th>";

                foreach ($olaDatavalue as $oldData) {
                    // dd($oldData);
                    $data .= "<td>".$oldData."</td>";
                }
                $data .= "</tr></tbody>";  
                echo $data;
             // dd($oldData);
            // $this->JsonData='<tr><td>'.$oldData.'</td><td>'.$newData.'</td></tr>'; 
            // $this->JsonData='<tr><td>test1</td><td>test2</td></tr>';

            // $this->JsonData['newData'] = json_decode ($collection->new_data);

       
        // dd($this->JsonData['oldData']);                    
        // return response()->json($this->JsonData);
        // return view('admin.activity-logs.activity-action-details-model',['collections'=>$collection]);     
    } 


    public function exportActivityLogs(Request $request)
    {

        // $report_title   = "Activity_Logs.xls";//commented on 12-june-25 
         $report_title   = "Activity_Logs.xlsx"; //added on 12-june-25
        /*--------------------------------------
        |  Model query
        ------------------------------*/
        $object =  $this->BaseModel
                            ->leftjoin('users', 'users.id' , '=', 'activity_logs.user_id')
                            ->orderBy("activity_logs.id","DESC")
                            ->get([
                                    'users.first_name',
                                    'users.last_name',
                                    'activity_logs.id',
                                    'activity_logs.module',
                                    'activity_logs.action',
                                    'activity_logs.message',
                                    'activity_logs.method',
                                    'activity_logs.url',
                                    'activity_logs.ip',
                                    'activity_logs.agent'
                                ]);  

       
        //dd($object);                                    
        /*--------------------------------------
        |  data binding
        ------------------------------*/
         
        $data = [];

        if (!empty($object) && sizeof($object) > 0) 
        {
            foreach ($object as $key => $row)  
            { 
                    $fname = $row->first_name;
                    $lname = $row->last_name;
                    $name  = $fname .' '. $lname;
                    // print_r($row->id); die;

                    //$data[$key]['id']       = $row->id;

                    $data[$key]['module']   = $row->module;

                    $data[$key]['message']  = $row->message;

                    $data[$key]['method']   = $row->method;   

                    $data[$key]['url']      =  $row->url;

                    $data[$key]['ip']       =  $row->ip;

                    $data[$key]['agent']    =  $row->agent;

                    $data[$key]['name']     =  $name;

                    $data[$key]['actions']  = $row->action;                  
            } 
        }

        $headings =[
                        'Module',
                        'Message',
                        'Method',
                        'Url',
                        'Ip',
                        'Agent',
                        'Name',
                        'Actions',
                    ];   

                  
        // return (new CollectionExport($data,$headings))->download($report_title, \Maatwebsite\Excel\Excel::XLS);   //commented on 12-june-25

           ob_end_clean(); // Clean any accidental output buffer             
         return (new CollectionExport($data,$headings))->download($report_title, \Maatwebsite\Excel\Excel::XLSX); //Changed on 12-june-25

        // $this->_exportCsv($report_title,$data);

    } 

    //Export Data in Csv Format
    public function _exportCsv($report_title,$data){

        \Excel::create($report_title, function ($excel) use ($data,$report_title) {

                   $excel->setTitle($report_title);
                   $excel->setCreator('puregyn');
                   //$excel->setDescription('CUSTOMER Summary description');

                  $excel->sheet('test', function ($sheet) use ($data) {
                      $sheet->with($data, null, 'A1', false, false);
                  });

              })->download('csv');
    }   

   

} 
