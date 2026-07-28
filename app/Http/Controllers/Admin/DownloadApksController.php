<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Traits\GeneralTrait;

//Models
use App\Models\DownloadApksModel;

//Request
use App\Http\Requests\Admin\DownloadApksRequest;

//Plugins
use File, DB, Storage, Log;

class DownloadApksController extends Controller
{
    use GeneralTrait;

    public function __construct(

        DownloadApksModel $DownloadApksModel

    )
    {

        $this->BaseModel = $DownloadApksModel;

        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle = __('admin.TITLE_ROLES_MODULE');
        $this->ModuleView  = 'admin.apks.';
        $this->ModulePath = 'admin.apks.';

       // Permission Middleware
        $this->middleware(['permission:exams-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:exams-add'], ['only' => ['create','store']]);

    }

    // public function index(Request $request)
    // {
    //     // Default site settings
    //     $this->ModuleTitle = __('admin.TITLE_ROLES_MODULE');
    //     $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
    //     $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
    //     $this->ViewData['modulePath']   = $this->ModulePath;
    //     $this->ViewData['addButton']    = str_singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

    //     return view($this->ModuleView . 'index', $this->ViewData);
    // }

     public function create() 
    {

        // // Fetch all ordinations
        //         $getAllOrdination = DB::connection('system')->table("tenants")->get();
        //         dump("getAllOrdination");

        //         dump($getAllOrdination);
        //         foreach ($getAllOrdination as $ordination) {
        //         dump("ordination");

        //         dump($ordination);

        //             $ordinationUuid = $ordination->uuid;
        //         dump("ordinationUuid");

        //         dump($ordinationUuid);

        //             // Set up a dynamic database connection
        //             config()->set("database.connections.{$ordinationUuid}", [
        //                 'driver' => 'mysql',
        //                 'host' => env('DB_HOST', '127.0.0.1'),
        //                 'database' => $ordinationUuid,
        //                 'username' => env('DB_USERNAME', 'root'),
        //                 'password' => env('DB_PASSWORD', ''),
        //                 'charset' => 'utf8mb4',
        //                 'collation' => 'utf8mb4_unicode_ci',
        //                 'prefix' => '',
        //                 'strict' => true,
        //                 'engine' => null,
        //             ]);

        //             // Clear and reconnect to apply the new configuration
        //             DB::purge($ordinationUuid);//if needed
        //             DB::reconnect($ordinationUuid);//if needed

        //             // Dynamic path for each ordination
        //             $apkStoragePath = "/opt/app-shared/php/data/storage/app/public/tenancy/tenants/{$ordinationUuid}/apks_download/";
        //             dump("apkStoragePath");
        //             dump($apkStoragePath);

        //             if (!File::isDirectory($apkStoragePath)) {
        //                                 dump("Apk path storage");

        //                 File::makeDirectory($apkStoragePath, 0777, true, true);
        //             }
        //         }
                // dd("hiiiii");
ini_set('post_max_size', '100M');ini_set('upload_max_filesize', '100M');
        
        $this->ModuleTitle              = __('admin.TITLE_APKS');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_UPLOAD_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // view file with data
        return view($this->ModuleView.'create', $this->ViewData); 
    }

    public function store(DownloadApksRequest $request) 
    {
        $this->JsonData['status']   = __('admin.RESP_ERROR');
        $this->JsonData['msg']      = __('admin.FAIL_SETTING_CREATE');

        try {

            $collection     = new $this->BaseModel;  

            $collection     = self::_storeOrUpdate($collection,$request);
            if ($collection) 
            {
                $this->JsonData['status']   = __('admin.RESP_SUCCESS');
                $this->JsonData['url']      =  route($this->ModulePath.'index');
                $this->JsonData['msg']      = __('admin.APK_UPLOADED');
            }
        }
        catch(\Exception $e) {

            $this->JsonData['msg'] = Lang::get('admin.ERR_SOMETHING_WRONG');
            $this->JsonData['error_msg'] = $e->getMessage();
        }

        return response()->json($this->JsonData);
    }

public function _storeOrUpdate($collection, $request)
{
    // File data array to loop through
    $files = [
        'signdoc_app' => 'SignDoc App',
        'master_data_app' => 'Master Data App',
        'wating_num_app' => 'Waiting Number App',
    ];

    // Determine which files are being uploaded
    $uploadedApps = [];
    foreach ($files as $key => $appName) {
        if ($request->hasFile($key)) {
            $uploadedApps[] = $appName;
        }
    }

    // Delete existing APKs for only the uploaded apps
    $this->deleteExistingAPK($uploadedApps);
    foreach ($files as $key => $appName) {
        if (in_array($appName, $uploadedApps)) {
            // Check if the file exists in the request
            try {
                // File data
                $file = $request->file($key);
                $originalFileName = $file->getClientOriginalName();

                // Extract version using regex
                preg_match('/-(\d+\.\d+)-/', $originalFileName, $matches);
                $version = $matches[1] ?? null;

                // Generate a unique file name
                $uniqueFileName = time() . '_' . $key . '_' . $originalFileName;

                // Store file using Laravel Storage
                $apkStoragePathForMaster = 'apks_download';
                $filePath = Storage::disk('public')->putFileAs($apkStoragePathForMaster, $file, $uniqueFileName);

                // Insert into master table
                DB::connection('system')->table('tablet_apks')->insert([
                    'app_name' => $appName,
                    'apk_file_name' => $uniqueFileName,
                    'apk_file_path' => $filePath,
                    'apk_version' => $version,
                ]);

                // Fetch all ordinations
                $getAllOrdination = DB::connection('system')->table("tenants")->get();
                // $getAllOrdination = DB::connection('system')->table("tenants")
                //     ->where('ordination_id', '9')
                //     ->whereNull('deleted_at')
                //     ->get();


               foreach ($getAllOrdination as $ordination) {
                    $ordinationUuid = $ordination->uuid;
                    // Set up a dynamic database connection
                    config()->set("database.connections.{$ordinationUuid}", [
                        'driver' => 'mysql',
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'database' => $ordinationUuid,
                        'username' => env('DB_USERNAME', 'root'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => '',
                        'strict' => true,
                        'engine' => null,
                    ]);

                    // Clear and reconnect to apply the new configuration
                    DB::purge($ordinationUuid);
                    DB::reconnect($ordinationUuid);

                    // // Dynamic path for each ordination
                    // $apkStoragePath = "/opt/app-shared/php/data/storage/app/public/tenancy/tenants/{$ordinationUuid}/apks_download";
                    // if (!File::isDirectory($apkStoragePath)) {
                    //     File::makeDirectory($apkStoragePath, 0777, true, true);
                    // }

                    // // Store the file using move() to the custom path
                    // // Path where master copy was stored
                    // $masterFilePath = storage_path("app/public/apks_download/{$uniqueFileName}");
                    // Log::info("Master file path: {$masterFilePath}");
                    // // Path for ordination folder
                    // $ordinationFilePath = $apkStoragePath . '/' . $uniqueFileName;
                    // Log::info("Ordination file path: {$ordinationFilePath}");
                    // // Copy instead of move
                    // File::copy($masterFilePath, $ordinationFilePath);
                    // Get master file absolute path using Storage
                    // $masterFilePath = Storage::disk('public')->path("apks_download/{$uniqueFileName}");
                    // Log::info("Master file path: {$masterFilePath}");
                    // $apkStoragePath = "/opt/app-shared/php/data/storage/app/public/tenancy/tenants/{$ordinationUuid}/apks_download";
                    // Log::info("Ordination APK storage path: {$apkStoragePath}");
                    // if (!File::isDirectory($apkStoragePath)) {
                    //     File::makeDirectory($apkStoragePath, 0777, true, true);
                    // }
                    // // Store the file in ordination-specific path
                    // $ordinationFilePath = $apkStoragePath . '/' . $uniqueFileName;
                    // Log::info("Ordination file path: {$ordinationFilePath}");
                    // Log::info("Copying file from {$masterFilePath} to {$ordinationFilePath}");

                    // File::copy($masterFilePath, $ordinationFilePath);
                    // Log::info("File copied successfully for ordination: {$ordinationUuid}");
                    // Insert into ordination-specific database using the dynamic connection
                    DB::connection($ordinationUuid)->table('tablet_apks')->insert([
                        'app_name' => $appName,
                        'apk_file_name' => $uniqueFileName,
                        'apk_file_path' => $filePath,
                        'apk_version' => $version,
                    ]);
                }

            } catch (\Exception $e) {
                \Log::error("Error uploading file for {$appName}: " . $e->getMessage());

                $this->JsonData['status'] = 'error';
                $this->JsonData['msg'] = "Failed to upload {$appName}.";
                $this->JsonData['error_msg'] = $e->getMessage();
                return response()->json($this->JsonData);
            }
        }
    }

    return $collection;
}

public function deleteExistingAPK($appsToDelete)
{
    try {
        // Delete files and soft delete records in the master table
        $apkEntries = DB::connection('system')->table('tablet_apks')
            ->whereIn('app_name', $appsToDelete)
            ->whereNull('deleted_at')
            ->get();
        foreach ($apkEntries as $apkEntry) {
            $masterFilePath = "/opt/app-shared/php/data/storage/app/public/apks_download/{$apkEntry->apk_file_name}";

            if (File::exists($masterFilePath)) {
                File::delete($masterFilePath);
            }
        }

        // Soft delete records in the master table
        DB::connection('system')->table('tablet_apks')
            ->whereIn('app_name', $appsToDelete)
            ->update(['deleted_at' => now(), 'is_new' => '0']);

        // Fetch all ordinations
        $getAllOrdination = DB::connection('system')->table("tenants")->get();
    //         // $getAllOrdination = DB::connection('system')->table("tenants")
    //         //     ->whereIn('ordination_id', [10, 9])
    //         //     ->get();
    //          $getAllOrdination = DB::connection('system')->table("tenants")
    // ->where('ordination_id', '9')
    // ->whereNull('deleted_at')
    // ->get();

        foreach ($getAllOrdination as $ordination) {

            $ordinationUuid = $ordination->uuid;

            // Configure dynamic connection for ordination
            config()->set("database.connections.{$ordinationUuid}", [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'database' => $ordinationUuid,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Clear and reconnect to apply the new configuration
            DB::purge($ordinationUuid);
            DB::reconnect($ordinationUuid);

            // Retrieve ordination-specific entries
            $ordinationEntries = DB::connection($ordinationUuid)->table('tablet_apks')
                ->whereIn('app_name', $appsToDelete)
                ->whereNull('deleted_at')
                ->get();

            // foreach ($ordinationEntries as $ordinationEntry) {
            //     $ordinationFilePath = "/opt/app-shared/php/data/storage/app/public/tenancy/tenants/{$ordinationUuid}/apks_download/{$ordinationEntry->apk_file_name}";
            //     if (File::exists($ordinationFilePath)) {
            //         File::delete($ordinationFilePath);
            //     }
            // }

            // Soft delete records in ordination-specific tables
            DB::connection($ordinationUuid)->table('tablet_apks')
                ->whereIn('app_name', $appsToDelete)
                ->update(['deleted_at' => now(), 'is_new' => '0']);
        }
    } catch (\Exception $e) {
        \Log::error("Error deleting existing APKs: " . $e->getMessage());
    }
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
                1 => 'tablet_apks.app_name',
                2 => 'tablet_apks.apk_version',
                3 => 'status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel;

            // get total count 
            $countQuery = clone($modelQuery);            
            $totalData  = $countQuery->count();

            // Common filter options
            if (!empty($request->search))
            {
                if (!empty($request->search['value'])) 
                {
                    $search = $request->search['value'];

                     $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('tablet_apks.app_name', 'LIKE', '%'.$search.'%');   
                        $query->orwhere('tablet_apks.apk_version', 'LIKE', '%'.$search.'%');    
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
            
        /*--------------------------------------
        |  data binding
        ------------------------------*/
            $data = [];
            if (!empty($object) && sizeof($object) > 0) 
            {
                foreach ($object as $key => $row) 
                { 
                        $data[$key]['id']           = $row->id;

                        $data[$key]['app_name']         = '<span title="'.$row->app_name.'">'.$row->app_name.'</span>';   

                        $data[$key]['apk_version']          =  "<span title='".$row->apk_version."'>".$row->apk_version."</span>";

                        // $edit="";
                        // $delete="";
                        $view = "";
                        // Check Permission
                        if(auth()->user()->can('menu-setting-add')){
                            $view = '<a href="'.route($this->ModulePath.'view', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-eye"></span></a>&nbsp&nbsp';
                        }

                        $data[$key]['actions'] = '<div class="text-center">'.$view.'</div>';                   
                } 
            }
            // 
            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData);
    }    
    
    public function view_old($encID)
    {
        // Default site settings
        $apkPath = '';
        $this->ModuleTitle              = __('admin.TITLE_APKS');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        
        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $this->ViewData['apk'] = $this->BaseModel
                                       ->find($id);

        if(!empty(Config('ordination_id')))
        {
            $getDatabaseName = DB::connection('system')
                        ->table("tenants")
                        ->where('ordination_id',Config('ordination_id'))
                        ->first(['uuid']);

             $this->ViewData['apkPath'] = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/apks_download/';
        }
        else
        {
             $this->ViewData['apkPath'] = '/opt/app-shared/php/data/storage/app/public/apks_download/';
        }
        // view file with data
        return view($this->ModuleView.'view', $this->ViewData);
    }
     public function index()
    {
        // Default site settings
        $apkPath = '';
        $this->ModuleTitle              = __('admin.TITLE_APKS');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['moduleAction'] = __('admin.TITLE_APKS');

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        // All examsdata
        // $id = base64_decode(base64_decode($encID));
        $this->ViewData['apks'] = $this->BaseModel
                                       ->all();

        // if(!empty(Config('ordination_id')))
        // {
        //     Log::info("Fetching database name for ordination_id: " . Config('ordination_id'));
        //     dump("Fetching database name for ordination_id: " . Config('ordination_id'));
        //      // Get the database name for the current ordination
        //     $getDatabaseName = DB::connection('system')
        //                 ->table("websites")
        //                 ->where('ordination_id',Config('ordination_id'))
        //                 ->first(['uuid']);

        //      $this->ViewData['apkPath'] = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDatabaseName->uuid.'/apks_download/';
        // }
        // else
        // {
        //     Log::info("Fetching default APK path");
        //     dump("Fetching default APK path");
        //      $this->ViewData['apkPath'] = '/opt/app-shared/php/data/storage/app/public/apks_download/';
        // }
        // view file with data
        return view($this->ModuleView.'view', $this->ViewData);
    }
    public function markAsDownloaded($id, Request $request)
    {
        $path = $request->path;
        // $path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/9eef6c3f8bcf468ca31741eafdb6fb20/apks_download/1754392684_wating_num_app_Dev-SignDoc-1.0-250721-release.apk';

        // $path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/419a6c15d37a4384be94d3b452424a9e/apks_download/1754392684_wating_num_app_Dev-SignDoc-1.0-250721-release.apk';

        try {
            // $path = 'https://puregyn-stage.puregyn.puredoc.biz/storage/tenancy/tenants/9eef6c3f8bcf468ca31741eafdb6fb20/apks_download/1731503822_signdoc_app_Stage-PureMed-4.6-241105-releasedownload.apk';
           
            // Get the connection and check if ordination_id is set in the configuration
            $connection = self::getDatabaseConnection();
            // Attempt to find the APK record and update it
            $apk = DB::connection($connection)->table("tablet_apks")->where('id', $id)->first();
            
            if ($apk) {
                // Update the APK's is_downloaded field
                $updateApk = DB::connection($connection)->table("tablet_apks")->where('id', $id)->update(['is_downloaded' => '1']);
                $checkAllApkDownloadOrNot = self::checkForNewApps();
                return response()->file($path ,[
                'Content-Type'=>'application/vnd.android.package-archive',
                'Content-Disposition'=> 'attachment; filename="android.apk"',
            ]);
            } 

            // return response()->json(['success' => true, 'apk' => $checkAllApkDownloadOrNot]);
        } catch (\Exception $e) {
        }
    }


   

}
