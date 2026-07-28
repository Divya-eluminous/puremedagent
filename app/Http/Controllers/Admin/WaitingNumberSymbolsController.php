<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\Lang; 

// Models
use App\Models\AppointmentHasQueueNumberModel;
use App\Models\PatientsModel; 
use App\Models\AdminUserModel;
use App\Models\AppointmentTypesModel; 
use App\Models\WaitingNumberSymbolsModel;

// Request
// use App\Http\Requests\Admin\PatientsRequest;

// plugins
// use Hash;
// use Mail;
use DB; 
// use Auth;

class WaitingNumberSymbolsController extends Controller
{
    private $BaseModel;

    public function __construct(
        AppointmentHasQueueNumberModel $AppointmentHasQueueNumberModel,
        PatientsModel $PatientsModel,
        AdminUserModel $AdminUserModel,
        AppointmentTypesModel $AppointmentTypesModel,
        WaitingNumberSymbolsModel $WaitingNumberSymbolsModel
    )
    {
        $this->BaseModel                = $WaitingNumberSymbolsModel;
        $this->PatientsModel            = $PatientsModel;
        $this->AdminUserModel           = $AdminUserModel;
        $this->AppointmentTypesModel    = $AppointmentTypesModel;
        $this->AppointmentHasQueueNumberModel = $AppointmentHasQueueNumberModel;

        $this->ViewData = [];
        $this->JsonData = []; 

        $this->ModuleTitle  =  __('admin.TITLE_WAITING_NUMBER_SYMBOLS_TEXT');
        $this->ModuleView   = 'admin.waiting-number-symbols.';
        $this->ModulePath   = 'admin.waiting-number-symbols.';

        // Permission Middleware
        // $this->middleware(['permission:patients-listing'], ['only' => ['index','getRecords']]);
        // $this->middleware(['permission:patients-add'], ['only' => ['create','store']]);
    }

    public function index() 
    { 
        // Default site patients
        $this->ModuleTitle              =  __('admin.TITLE_WAITING_NUMBER_SYMBOLS_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
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
                1 => 'waiting_number_symbols.name',
                2 => 'waiting_number_symbols.url',
                // 3 => 'waiting_number_symbols.appointment_type_id',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel;

            // get total count 
            $countQuery = clone($modelQuery);             
            $totalData  = $countQuery->count();

            # FILTER OPTIONS for specific field 
            $custom_search = false;
            if (!empty($request->custom))
            {
                // if (!empty($request->custom['name']))  
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['name']; 
                //     $modelQuery     = $modelQuery
                //     ->where('waiting_number_symbols.name','LIKE','%'.$key.'%');
                // } 

                // if (!empty($request->custom['url']))  
                // {
                //     $custom_search  = true;
                //     $key            = $request->custom['url'];              
                //     $modelQuery     = $modelQuery
                //     ->where('waiting_number_symbols.url','LIKE','%'.$key.'%');
                // }
            }

            // Common filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {   
                        $query->orwhere('waiting_number_symbols.name', 'LIKE', '%'.$search.'%');  
                        $query->orwhere('waiting_number_symbols.url', '=', $search);
                    });
                }
            }

            // get total filtered
            $filteredQuery  = clone($modelQuery);            
            $totalFiltered  = $filteredQuery->count(); 
            
            // offset and limit
            $object = $modelQuery->orderBy($filter[$column], $dir)
                                ->skip($start)
                                ->take($length)
                                ->get();            
            
            // dd($object);
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = []; 
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row)  
                {  

                        $data[$key]['id']           = $row->id; 
                        // $url = $row->url.'/'.$row->name;
                        $data[$key]['name'] =  "<span title='".$row->name."'>".$row->name."</span>";
                        // $data[$key]['url']  =  "<span title='".$row->url."'>".$row->url."</span>";
                        $data[$key]['url']  =  '<a href="'.$row->url.'">'.$row->url.'</a>';
       
                } 
            }

            // // Patient
            // $patient = $this->PatientsModel
            //                 ->where('status', 1)
            //                 ->get();
            
            ## SEARCH HTML
            
            $searchHTML['id']               =  ''; 
 
            $searchHTML['name']   =  '';

            $searchHTML['url']    =  ''; 
            
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

} 
