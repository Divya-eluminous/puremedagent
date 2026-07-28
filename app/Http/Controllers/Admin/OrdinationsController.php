<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Filesystem\Filesystem;

// Models
use App\Models\OrdinationsModel;
use App\Models\OrdinationHasSpecialistModel;
use App\Models\SpecialistModel;
use App\Models\AdminUserModel;
use Spatie\Permission\Models\Role;
use thiagoalessio\TesseractOCR\TesseractOCR;
// Request
use App\Http\Requests\Admin\OrdinationsRequest;

// plugins
use File;
use Mail;
use Hash;
use DB;
use Auth;
use Storage;
use PDF;
use App\Tenant;
use App\Traits\GeneralTrait;

use App\Mail\SendOrdinationUrlForOrdination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;//Roshani added this when laravel version upgraded on 13-06-2024

use App\Models\SettingsModel; ////This code added by roshani for CR #126 on 6-nov-24


class OrdinationsController extends Controller
{
    use GeneralTrait;
    private $BaseModel;

    public function __construct(
        OrdinationsModel $OrdinationsModel,
        AdminUserModel $AdminUserModel,
        OrdinationHasSpecialistModel $OrdinationHasSpecialistModel,
        SpecialistModel $SpecialistModel,
        Role $RoleModel,
        // Website $website, // REMOVED - No longer needed with Stancl tenancy
        SettingsModel $SettingsModel //This code added by roshani for CR #126 on 6-nov-24

    )
    {
        $this->BaseModel   = $OrdinationsModel;
        $this->AdminUserModel = $AdminUserModel;
        $this->RoleModel            = $RoleModel;
        $this->OrdinationHasSpecialistModel  = $OrdinationHasSpecialistModel;
        $this->SpecialistModel = $SpecialistModel;
        // $this->website  = $website; // REMOVED - No longer needed with Stancl tenancy
        $this->SettingsModel  = $SettingsModel; //This code added by roshani for CR #126 on 6-nov-24


        $this->ViewData = [];
        $this->JsonData = [];

        $this->ModuleTitle  = __('admin.TITLE_CHECKLIST_TEXT');
        $this->ModuleView   = 'admin.ordination.';
        $this->ModulePath   = 'admin.ordination';

        // Permission Middleware
        $this->middleware(['permission:ordination-listing'], ['only' => ['index','getRecords']]);
        $this->middleware(['permission:ordination-add'], ['only' => ['create','store']]);
    }

    public function index()
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_ORDINATION_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['addButton']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_ADD_BUTTON');

        // view file with data
        return view($this->ModuleView.'index', $this->ViewData);
    }

    public function create()
    {
        //Default site settings
        $this->ModuleTitle              = __('admin.TITLE_ORDINATION_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_CREATE_TEXT');

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;
        $this->ViewData['login_user'] = $this->AdminUserModel
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'Ordination');
                                        })
                                        ->get();

        //Specialist for ordination
        $this->ViewData['specialist'] = $this->SpecialistModel
                                        ->where('status','1')
                                        ->get();


        //This code added by roshani for CR #126 on 6-nov-24

        $this->ViewData['settings'] = $this->SettingsModel
                                        ->where('setting_key','ORDINATION_MOBILE')
                                        ->orWhere('setting_key', 'ORDINATION_EMAIL')
                                        ->get();
        //This code added by roshani for CR #126 on 6-nov-24


        // view file with data
        return view($this->ModuleView.'create', $this->ViewData);
    }
    // public function slugify($text, $divider = '-')
    // {
    //   // replace non letter or digits by divider
    //   $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

    //   // transliterate
    //   $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    //   // remove unwanted characters
    //   $text = preg_replace('~[^-\w]+~', '', $text);

    //   // trim
    //   $text = trim($text, $divider);

    //   // remove duplicate divider
    //   $text = preg_replace('~-+~', $divider, $text);

    //   // lowercase
    //   $text = strtolower($text);

    //   if (empty($text)) {
    //     return 'n-a';
    //   }

    //   return $text;
    // }
    // public function slugify($text, $divider = '-')
    // // public function slugify()
    // {
    //     // $text = 'ÜhßysÖöcoysÜ'; // Change input for testing
    //     // Step 1: Protect German characters (case-sensitive)
    //     $protected = [
    //         'Ä' => '__Ae__',
    //         'Ö' => '__Oe__',
    //         'Ü' => '__Ue__',
    //         'ä' => '__ae__',
    //         'ö' => '__oe__',
    //         'ü' => '__ue__',
    //         'ß' => '__ss__',
    //     ];
    //     $text = strtr($text, $protected);
    //     // Step 2: Remove all unwanted characters, but keep letters, digits and underscores
    //     // Since protected codes are wrapped in `__`, we keep `_` to avoid breaking them
    //     $text = preg_replace('~[^a-zA-Z0-9_]+~u', '', $text);
    //     // Step 3: Restore protected characters
    //     $restore = [
    //         '__ae__' => 'ae',
    //         '__oe__' => 'oe',
    //         '__ue__' => 'ue',
    //         '__ss__' => 'ss',
    //         '__Ae__' => 'Ae',
    //         '__Oe__' => 'Oe',
    //         '__Ue__' => 'Ue',
    //     ];
    //     $text = strtr($text, $restore);
    //     // Final output
    //     $final = $text ?: 'n-a';
    //     return $final;
    // }
    public function slugify($text, $divider = '-')
    {
        // Step 1: Protect German characters (case-sensitive)
        $protected = [
            'Ä' => '__Ae__', 'Ö' => '__Oe__', 'Ü' => '__Ue__',
            'ä' => '__ae__', 'ö' => '__oe__', 'ü' => '__ue__',
            'ß' => '__ss__',
        ];
        $text = strtr($text, $protected);

        // Step 2: Replace spaces and non-alphanumeric chars (except underscores) with divider
        $text = preg_replace('~[^a-zA-Z0-9_]+~u', $divider, $text);

        // Step 3: Restore protected German characters
        $restore = [
            '__ae__' => 'ae', '__oe__' => 'oe', '__ue__' => 'ue', '__ss__' => 'ss',
            '__Ae__' => 'Ae', '__Oe__' => 'Oe', '__Ue__' => 'Ue',
        ];
        $text = strtr($text, $restore);

        // Step 4: Remove duplicate dividers and trim from start/end
        $text = preg_replace('~-+~', '-', $text);
        $text = trim($text, $divider);

        // Step 5: Convert to lowercase for URL consistency
        $text = strtolower($text);

        // Step 6: Fallback if string becomes empty
        return $text ?: 'n-a';
    }
        function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

        public function store(OrdinationsRequest $request)
        {
            //DB::beginTransaction();
        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_CREATE');
        $request->mobile_no = ltrim($request->mobile_no, '0');
        $flag = 0;
        // try {
            $collection =  new $this->BaseModel;
            $collection = self::_storeOrUpdate($collection,$request);

            if ($collection)
            {

                $fqdn= '';
                Session::put('insert_ordination_id',$collection->id);
                $name = self::slugify($request->name);
                $input = $_SERVER['HTTP_HOST'];
                $input = trim($input, '/');
                // dd($name);
                Log::info('here');
                // If not have http:// or https:// then prepend it
                if (!preg_match('#^http(s)?://#', $input)) {
                $input = 'http://' . $input;
                }
                $urlParts = parse_url($input);
                // Remove www.
                $domain_name = preg_replace('/^www\./', '', $urlParts['host']);

                $OrdinationUrl = $name.".".$domain_name;
                $fqdn = "https://".$name.".".$domain_name;
                Log::info("OrdinationUrl");
                Log::info($OrdinationUrl);
                Log::info("fqdn");
                Log::info($fqdn);

                // Before creating tenant
                Log::info("Creating tenant with FQDN: ".$fqdn);

                try{
                    Log::info("tenant create");
                    // Create tenant using our Tenant class (with cache bypass)
                    $tenantInstance = Tenant::create(
                        $fqdn,
                        $collection->id,
                        $collection->name,
                        $request->calendar_id,
                        $OrdinationUrl,
                        $collection->email, // Pass email for admin user creation
                        $request->mobile_no,
                        $collection->logo_path
                    );
                    
                    Log::info("Tenant created successfully: " . $tenantInstance->tenant->id);
                    
                    // Get the tenant for further operations
                    $tenant = $tenantInstance->tenant;
                    
                }
                catch(\Exception $e) {
                    Log::info("catch part");
                    $this->JsonData['msg']        = __('admin.ERR_SOMETHING_WRONG');
                    Log::error("Tenant creation failed: ".$e->getMessage()." in ".$e->getFile()." on line ".$e->getLine());
                    
                    // Return error response
                    return response()->json($this->JsonData);
                }
                //DB::commit();

                // Random Generate Password
                $password = self::randomPassword();
                Log::info("password");
                Log::info($password);
                //
                $collection->url       = $fqdn;
                $collection->password  = $password;
                $collection->mobile_no = $collection->mobile_no;

                // OLD CODE - Commented out for Stancl tenancy migration
                // Insert data to user table
                // $getDataBaseName = $this->website
                //                    ->where('ordination_id',$collection->id)
                //                    ->first();
                //                    Log::info("getDataBaseName");
                //                    Log::info($getDataBaseName);
                // $device_data[] = array(
                //                     'first_name'=> $collection->name,
                //                     'email'=> $collection->email,
                //                     'password'=> Hash::make($password),
                //                     'str_password'=> $password,
                //                     'country_code'=> "+43",
                //                     'mobile_number'=> $request->mobile_no
                //                 );

                // $masterSpecilistDocument = DB::table($getDataBaseName->uuid.".users")
                //                            ->insert($device_data);

                // NEW CODE - Stancl tenancy approach
                // Admin user creation is now handled in the Tenant::create method
                // The Tenant class will create the admin user in the tenant database
                Log::info("Admin user creation handled by Tenant class");
                                           // OLD CODE - Commented out
                                           // Log::info("masterSpecilistDocument");
                                           // Log::info($masterSpecilistDocument);
                                           
                                           // NEW CODE - Stancl tenancy approach
                                           Log::info("Admin user creation completed");
                                           Log::info("send email");
                                           Log::info($collection->email);
                // Create admin user and send email
                if ($collection->email) {
                    $password = $this->generateRandomPassword();
                    
                    // Debug: Log the collection object to see available fields
                    Log::info("Collection object fields: " . print_r($collection, true));
                    
                    try {
                        // Get the actual database name for the tenant
                        // The database name is stored in the uuid field of the tenant
                        $dbName = $tenant->uuid;
                        
                        // Get the ordination name from available fields
                        $ordinationName = $collection->ordination_name ?? $collection->name ?? 'Admin User';
                        
                        // Insert user into tenant database
                        DB::statement("USE `{$dbName}`");
                        DB::table('users')->insert([
                            'first_name' => $ordinationName,
                            'email' => $collection->email,
                            'password' => Hash::make($password),
                            'str_password' => $password,
                            'country_code' => "+43",
                            'mobile_number' => $collection->mobile_no,
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        Log::info("Admin user created successfully for tenant: " . $tenant->id);
                        
                        // Send email with login details
                        // Use tenant ordination name for domain (simplified approach)
                        $domain = $tenant->ordination_name ?? 'localhost';
                        $url = 'https://' . $domain . '.localhost';
                        
                        // Debug: Log the domain lookup
                        // Log::info("Domain lookup - tenant_id: " . $tenant->id . ", domain: " . $domain . ", url: " . $url);
                        
                        $emailData = (object) [
                            'name' => $ordinationName,
                            'email' => $collection->email,
                            'mobile_no' => $collection->mobile_no ?: '+43123456789',
                            'url' => $fqdn,
                            'password' => $password,
                            'logo_path' => $collection->logo_path
                        ];
                        
                        Mail::to($collection->email)->bcc(['eluminous.se65@gmail.com'])->send(new SendOrdinationUrlForOrdination($emailData));
                        Log::info("Email sent successfully with password: " . $password);
                        
                    } catch (Exception $e) {
                        Log::error("User creation or email sending failed: " . $e->getMessage());
                    }
                }


                if($tenant)
                {
                        // Log::info("email_result");
                    // Log::info($email_result);

                    // dump($email_result);
                    /* Store the path of source file */
                    $filePath = self::getFilePath_for_ordination_create($collection->logo_path);
                    // dump($tenant);
                    // // $getDataBaseName = $tenant->uuid; // Get the tenant's UUID for the database name
                    // dump($tenant->uuid);
                    // $getDataBaseName = DB::connection('system')
                    //                 ->table("tenants")
                    //                 ->where('ordination_id',$collection->id)
                    //                 ->first();
                    //$filePath = $new_logo_path;
                    /* Store the path of destination file */
                    // $ordination_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDataBaseName->uuid;
                    // if(!File::isDirectory($ordination_path))
                    // {
                    //     File::makeDirectory($ordination_path, 0777, true, true);
                    // }
                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $ordination_logo_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDataBaseName->uuid.'/ordination-logo';
                    
                    // NEW CODE - Stancl tenancy approach
                    $ordination_logo_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/ordination-logo';
                    if(!File::isDirectory($ordination_logo_path))
                    {
                        File::makeDirectory($ordination_logo_path, 0777, true, true);
                    }

                    // $destinationFilePath = $ordination_logo_path.'/'.$collection->logo;
                    /* Copy File from images to copyImages folder */
                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $ordination_logo_path = Storage::path('public/tenancy/tenants/'.$tenant->uuid.'/ordination-logo/'.$collection->logo);
                    
                    // NEW CODE - Stancl tenancy approach
                    $ordination_logo_path = Storage::path('public/tenancy/tenants/'.$tenant->uuid.'/ordination-logo/'.$collection->logo);
                    //if(copy($filePath, Storage::path('tenancy/tenants/'.$tenant->uuid.'/ordination-logo/').$collection->logo) )
                    //  dd($filePath,"123", $ordination_logo_path);
                    // if(copy($filePath, $ordination_logo_path)){
                    //     // dump("File can't be copied!");
                    // }
                    // else{
                    //      // dump("File has been copied!");
                    // }

                    // Before copying file
                    Log::info("Copying file from ".$filePath." to ".$ordination_logo_path);
                    if (file_exists($filePath)) {
                        if (!copy($filePath, $ordination_logo_path)) {
                            Log::error("File copy failed from ".$filePath." to ".$ordination_logo_path);
                        } else {
                            Log::info("File copied successfully");
                        }
                    } else {
                        Log::error("Source file not found: ".$filePath);
                    }

                    Log::info("isDirectory1");

                    /*******ordination*folders**code added on 14-march-24**************/
                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $appointment_type_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/appointment-type';
                    
                    // NEW CODE - Stancl tenancy approach
                    $appointment_type_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/appointment-type';
                    if(!File::isDirectory($appointment_type_path))
                    {
                        File::makeDirectory($appointment_type_path, 0777, true, true);
                    }
                    Log::info("isDirectory2");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $check_list_pdf_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/check_list_pdf';
                    
                    // NEW CODE - Stancl tenancy approach
                    $check_list_pdf_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/check_list_pdf';
                    if(!File::isDirectory($check_list_pdf_path))
                    {
                        File::makeDirectory($check_list_pdf_path, 0777, true, true);
                    }
                    Log::info("isDirectory3");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $diagnostic_findings_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/diagnostic_findings';
                    
                    // NEW CODE - Stancl tenancy approach
                    $diagnostic_findings_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/diagnostic_findings';
                    if(!File::isDirectory($diagnostic_findings_path))
                    {
                        File::makeDirectory($diagnostic_findings_path, 0777, true, true);
                    }
                    Log::info("isDirectory3");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $diagnostic_findings_crop_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/diagnostic_findings/crop';
                    
                    // NEW CODE - Stancl tenancy approach
                    $diagnostic_findings_crop_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/diagnostic_findings/crop';
                    if(!File::isDirectory($diagnostic_findings_crop_path))
                    {
                        File::makeDirectory($diagnostic_findings_crop_path, 0777, true, true);
                    }


                    Log::info("isDirectory4");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $document_pdf_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/document_pdf';
                    
                    // NEW CODE - Stancl tenancy approach
                    $document_pdf_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/document_pdf';
                    if(!File::isDirectory($document_pdf_path))
                    {
                        File::makeDirectory($document_pdf_path, 0777, true, true);
                    }
                    Log::info("isDirectory5");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $exam_doc_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/exam-doc';
                    
                    // NEW CODE - Stancl tenancy approach
                    $exam_doc_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/exam-doc';
                    if(!File::isDirectory($exam_doc_path))
                    {
                        File::makeDirectory($exam_doc_path, 0777, true, true);
                    }
                    Log::info("isDirectory6");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $guideline_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/guideline-manual';
                    
                    // NEW CODE - Stancl tenancy approach
                    $guideline_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/guideline-manual';
                    if(!File::isDirectory($guideline_path))
                    {
                        File::makeDirectory($guideline_path, 0777, true, true);
                    }
                    Log::info("isDirectory7");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $notes_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/notes';
                    
                    // NEW CODE - Stancl tenancy approach
                    $notes_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/notes';
                    if(!File::isDirectory($notes_path))
                    {
                        File::makeDirectory($notes_path, 0777, true, true);
                    }
                    Log::info("isDirectory8");

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $sign_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/sign';
                    
                    // NEW CODE - Stancl tenancy approach
                    $sign_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/sign';
                    if(!File::isDirectory($sign_path))
                    {
                        File::makeDirectory($sign_path, 0777, true, true);
                    }

                    // OLD CODE - Commented out for Stancl tenancy migration
                    // $specialist_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/specialist_document';
                    
                    // NEW CODE - Stancl tenancy approach
                    $specialist_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$tenant->uuid.'/specialist_document';
                    if(!File::isDirectory($specialist_path))
                    {
                        File::makeDirectory($specialist_path, 0777, true, true);
                    }
                }
                

                /*****ordination*folders**code added on 14-march-24****************/

                /**********start******18-sept-24*added code for waiting no symbol*******/

                    /*$fileNamesArr = [
                        'geldbaum.png', 'pusteblume.png', 'vergissmeinnicht.png', 'frauenzunge.png', 'wunderblume.png',
                        'zwetschke.png', 'banane.png', 'birne.png', 'kiwi.png', 'apfel.png', 'chamaleon.png', 'gepard.png',
                        'kolibri.png', 'schwertfisch.png', 'schmetterling.png', 'aloevera.png', 'mohnblume.png', 'kanne.png',
                        'facherblatt.png', 'kaktus.png', 'kirsche.png', 'heidelbeere.png', 'melone.png', 'ananas.png',
                        'erdbeere.png', 'schildkrote.png', 'katze.png', 'gibbon.png', 'tucan.png', 'flamingo.png'
                    ];*/


                    $fileNamesArr = [
                        'flamingo.png','tucan.png','gibbon.png','katze.png','schildkrote.png','erdbeere.png','ananas.png','melone.png','heidelbeere.png','kirsche.png','kaktus.png','facherblatt.png','kanne.png','mohnblume.png','aloevera.png','schmetterling.png','schwertfisch.png','kolibri.png','gepard.png','chamaleon.png','apfel.png',
                        'kiwi.png','birne.png','banane.png','zwetschke.png','wunderblume.png','frauenzunge.png','vergissmeinnicht.png','pusteblume.png','geldbaum.png'

                    ];

                    $baseUrlForImages = $fqdn.'/assets/admin/images/waiting-number-symbols/';

                    foreach ($fileNamesArr as $filename) {
                        $fullUrl = $baseUrlForImages . $filename;  // Concatenate base URL with the filename

                        $data = [
                            'name' => strtoupper(pathinfo($filename, PATHINFO_FILENAME)),
                            'url' => $fullUrl,
                            'created_at'=>date('Y-m-d H:i:s')

                        ];

                        DB::table('waiting_number_symbols')->insert($data);
                    }//foreach

                /******end****18-sept-24*added code for waiting no symbol***************/


                if(empty($email_result))
                {
                    $url = config('app.url').'admin/ordination';
                    $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                    // $this->JsonData['url']    =  route($this->ModulePath.'.index');
                    // $this->JsonData['url']    =  $url;
                    // $this->JsonData['url']    =  'https://puremed.biz/admin/ordination';
                     // Redirect to the new tenant's admin page
                    $this->JsonData['url'] = $fqdn . '/admin/login'; // or /admin depending on your app
                    $this->JsonData['msg']    = __('admin.ORDINATION_CREATED');
                }
                else
                {
                    $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                    $this->JsonData['error_msg'] = $e->getMessage();
                }
                // End
            }
            else
            {
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
               // DB::rollback();
            }
        // }
        // catch(\Exception $e) {

        //     $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }
        return response()->json($this->JsonData);
    }

    public function show($id)
    {
        dd('show');
    }

    public function edit($encID)
    {
        // Default site settings
        $this->ModuleTitle              = __('admin.TITLE_ORDINATION_TEXT');
        $this->ViewData['moduleTitle']  = $this->ModuleTitle.' '.__('admin.TITLE_MANAGE_TEXT');
        $this->ViewData['moduleAction'] =  \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_EDIT_TEXT');

        $this->ViewData['modulePath']   = $this->ModulePath;

        $this->ViewData['formTitle']    = \Illuminate\Support\Str::singular($this->ModuleTitle).' '.__('admin.TITLE_INFORMATION_TEXT');
        $this->ViewData['login_user'] = $this->AdminUserModel
                                        ->whereHas('roles',function($query){
                                           $query->where('name', 'Ordination');
                                        })
                                        ->get();

        // All examsdata
        $id = base64_decode(base64_decode($encID));
        $colection= $this->BaseModel->find($id);
        $this->ViewData['colection'] = $colection;
        // $this->ViewData['calender_id'] = $this->website
        //                                 ->where('ordination_id',$id)
        //                                 ->first();
        //Swati 13-Apr-2023==============================
        // Hyn tenancy code (commented out)
        // if(!empty(Config('ordination_id'))){
        //     $dbName=DB::connection()->getDatabaseName();
        //     $this->ViewData['calender_id'] = DB::connection('system')
        //                         ->table("websites")
        //                         ->where('uuid',$dbName)
        //                         ->first();
        // }
        // else{
        //     $this->ViewData['calender_id'] = DB::connection('system')
        //                     ->table("websites")
        //                     ->where('ordination_id',$id)
        //                     ->first();
        // }
        
        // Stancl tenancy code
        $this->ViewData['calender_id'] = null;
        try {
            $tenant = tenancy()->tenant;
            if ($tenant) {
                $this->ViewData['calender_id'] = $tenant;
            }
        } catch (\Exception $e) {
            // Tenant not found
        }
        //================================================

        $logo_path = self::getFilePath($this->ViewData['colection']->logo_path);
        $this->ViewData['logo_path'] =  $logo_path;
        //Specialist for ordination
        $this->ViewData['specialist'] = $this->SpecialistModel
                                        ->where('status','1')
                                        ->get();

        //Special Has Ordinations

        $this->ViewData['ordinationHasspecialist'] = $this->OrdinationHasSpecialistModel
                                        ->where('ordination_id',$id)
                                        ->where('status','1')
                                        ->pluck('specialist_id')
                                        ->toArray();
        // view file with data
        return view($this->ModuleView.'edit', $this->ViewData);
    }

    public function update(OrdinationsRequest $request, $encID)
    {
        DB::beginTransaction();
        $id = base64_decode(base64_decode($encID));

        $this->JsonData['status'] = __('admin.RESP_ERROR');
        $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_UPDATE');
        $flag = 0;
        $request->mobile_no = ltrim($request->mobile_no, '0');
        // try {

            $collection = $this->BaseModel->find($id);
            $old_name = $collection->name;
            $collection = self::_storeOrUpdate($collection,$request);

            if($collection)
            {

                self::updateToOrdination($request,$collection,$old_name);

                //Swati 13-Apr-2023=================================
                // Hyn tenancy code (commented out)
                // if(!empty(Config('ordination_id'))){
                //      $dbName=DB::connection()->getDatabaseName();
                //      $updateCalendarId= DB::connection('system')
                //                     ->table("websites")
                //                     ->where('uuid',$dbName)
                //                     ->update(
                //                     [
                //                         'calendar_id'=> $request->calendar_id
                //                     ]);
                // }
                // else{
                //     $updateCalendarId= DB::connection('system')
                //                     ->table("websites")
                //                     ->where('ordination_id',$id)
                //                     ->update(
                //                     [
                //                         'calendar_id'=> $request->calendar_id
                //                     ]);
                // }
                
                // Stancl tenancy code
                $updateCalendarId = null;
                try {

                    $tenant = tenancy()->tenant;
                    if ($tenant) {
                        // Update tenant calendar_id if needed
                        $tenant->calendar_id = $request->calendar_id;
                        $tenant->save();
                        $updateCalendarId = true;
                    }
                } catch (\Exception $e) {

                    // Tenant not found or update failed
                }
                //=================================================
                // Check calender
                // $gethostnames = DB::connection('system')
                //                 ->table("websites")
                //                 ->where('ordination_id',$collection->id)
                //                 ->where('calendar_id',$request->calendar_id)
                //                 ->whereNull('deleted_at')
                //                 ->first();

                // if(empty($gethostnames))
                // {
                //     $gethostnames = DB::connection('system')
                //                     ->table("websites")
                //                     ->where('calendar_id',$request->calendar_id)
                //                     ->whereNull('deleted_at')
                //                     ->get();

                //     if(sizeof($gethostnames)>0)
                //     {


                //         $this->JsonData['status'] = __('admin.RESP_ERROR');
                //         $this->JsonData['url']    =  route($this->ModulePath.'.index');
                //         $this->JsonData['msg']    = __('admin.ERR_ORDINATION_CALENDAR_ID_DUP');
                //     }
                //     else
                //     {
                //         $gethostnames = DB::connection('system')
                //                         ->table("websites")
                //                         ->where('ordination_id',$collection->fk_ordination_id)
                //                         ->update(
                //                         [
                //                             'calendar_id'=> $request->calendar_id
                //                         ]);

                //     }

                // }
                // End
                DB::commit();
                $this->JsonData['status'] = __('admin.RESP_SUCCESS');
                $this->JsonData['url']    =  route($this->ModulePath.'.index');
                $this->JsonData['msg']    = __('admin.ORDINATION_UPDATED');
            }
            else
            {
                $this->JsonData['msg'] = __('admin.ERR_SOMETHING_WRONG');
                $this->JsonData['error_msg'] = $e->getMessage();
                DB::rollback();
            }
        // }
        // catch(\Exception $e) {

        //     $this->JsonData['msg']       = __('admin.ERR_SOMETHING_WRONG');
        //     $this->JsonData['error_msg'] = $e->getMessage();
        // }
        return response()->json($this->JsonData);
    }

    public function updateToOrdination($request,$collection,$old_name)
    {
        $logo      = $collection->logo;
        $logo_path = $collection->logo_path;
        // Hyn tenancy code (commented out)
        $getDBName = DB::connection('system')
                    ->table("tenants")
                    ->where('ordination_id',$collection->id)
                    ->first();
        $getDataBaseName = $getDBName->uuid;
        
        // Stancl tenancy code
        // $getDataBaseName = null;
        // try {
        //     $getDBName = DB::connection('system')
        //             ->table("tenants")
        //             ->where('ordination_id',$collection->id)
        //             ->first();
        //     dump($getDBName);
        //     $getDataBaseName = $getDBName->uuid;
        //     dump($getDataBaseName);
        //     $tenant = tenancy()->tenant;
        //     dump($tenant);
        //     if ($tenant) {
        //         $getDataBaseName = $tenant->id;
        //         dump($getDataBaseName);
        //     }
        // } catch (\Exception $e) {
        //     dump($e->getMessage());
        //     // Tenant not found
        // }
        // if(!empty(Config('ordination_id')))
        // {
        //     $filePath = self::getFilePath($collection->logo_path);
        //     // echo gethostbyname("host.name.tld");
        //     // gethostbyname("host.name.tld");
        //     // dump($filePath);
        //     //dd(storage_path().'/app/ordination-logo/'.$collection->logo);
        //     if(copy($filePath, storage_path().'/app/ordination-logo/'.$collection->logo))
        //     {
        //         // dump("File can't be copied!");
        //     }
        //     else
        //     {
        //         // dump("File has been copied!");
        //     }
        // }
        // else
        // {
        //     $filePath = self::getFilePath($collection->logo_path);
        //     // dump($filePath);
        //     // dd(Storage::path('tenancy/tenants/'.$getDataBaseName.'/ordination-logo/').$collection->logo);
        //     if(copy($filePath, Storage::path('tenancy/tenants/'.$getDataBaseName.'/ordination-logo/').$collection->logo))
        //     {
        //         // dump("File can't be copied!");
        //     }
        //     else
        //     {
        //         // dump("File has been copied!");
        //     }
        // }

        if (!empty($request->logo))
        {


            if(!empty(Config('ordination_id')))
            {
                // $path = 'ordination-logo';

                // $objDocument = $request->logo;

                // $original_file  = $objDocument->getClientOriginalName();
                // $extension      = strtolower($objDocument->getClientOriginalExtension());
                // $filename       = date('YmdHis').'-'.$original_file;
                // $filePath = Storage::putFileAs($path, $objDocument, $filename);
                // $aceesPath  = "ordination-logo/".$filename;
                // $logo      = $filename;
                // $logo_path = $aceesPath;

                $filePath = self::getFilePath('ordination-logo/'.$collection->logo);
              //  $filePath =  Storage::path('public/'.$collection->logo);

                $upload_path = '/opt/app-shared/php/data/storage/app/public/ordination-logo/';

                // dump($filePath);
                // dump($upload_path.$collection->logo);

                if(!File::isDirectory($upload_path))
                {
                    File::makeDirectory($upload_path, 0777, true, true);
                }
                // new code added by vijay 19/3/24
                $contextOptions = array(
                    "ssl" => array(
                        "verify_peer"      => false,
                        "verify_peer_name" => false,
                    ),
                );

                // end
                if (copy($filePath, $upload_path . $collection->logo, stream_context_create($contextOptions))) {
                     //dump("File can't be copied!");
                }
                else
                {
                     //dump("File has been copied!");
                }
                $logo      = $collection->logo;
                $logo_path = $collection->logo_path;
            }
            else
            {
                $filePath = self::getFilePath($collection->logo_path);
                $filePath =  Storage::path('public/'.$collection->logo_path);

                $upload_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/'.$getDataBaseName.'/ordination-logo/';

                // dump($filePath);
                // dump($upload_path.$collection->logo);

                if(!File::isDirectory($upload_path))
                {
                    File::makeDirectory($upload_path, 0777, true, true);
                }
                log::info("Else-Image");
                // new code added by vijay 19/3/24
                $contextOptions = array(
                    "ssl" => array(
                        "verify_peer"      => false,
                        "verify_peer_name" => false,
                    ),
                );

                // end
                if (copy($filePath, $upload_path . $collection->logo, stream_context_create($contextOptions))) {
                     //dump("File can't be copied!");
                }
                else
                {
                     //dump("File has been copied!");
                }
                $logo      = $collection->logo;
                $logo_path = $collection->logo_path;

            }
            $file = self::unlinkFilePath($request->old_logo);
            if(!empty($request->old_logo) && is_file($file))
            {
                unlink($file);
                //unlink(storage_path().$request->old_logo);
            }


        }


        $ordination_data = array(
                        'name' => $collection->name,
                        'text_color_code' => $collection->text_color_code,
                        'background_color' => $collection->background_color,
                        'logo' => $logo,
                        'logo_path' => $logo_path,
                        'status' => $collection->status,
                        'email' => $collection->email,
                        'mobile_no' => $collection->mobile_no,
                        'address' => $collection->address,
                        'postal_code' => $collection->postal_code,
                        'button_colors' => $collection->button_colors,
                        'screen_bg_color' => $collection->screen_bg_color,
                        'app_bar_color' => $collection->app_bar_color,
                        'tabs_selection_color' => $collection->tabs_selection_color,
                        'home_screen_options_color' => $collection->home_screen_options_color,
                        'menu_header_colors' => $collection->menu_header_colors,
                        'menu_bg_color' => $collection->menu_bg_color,
                            'dark_text_color' => $collection->dark_text_color,
                        'light_text_color' => $collection->light_text_color,
                        'latitude' => $collection->latitude,
                        'longitude' => $collection->longitude,
                        'country' => $collection->country,
                        );
        //dd($ordination_data);
        if(empty(Config('ordination_id')))
        {
            $updateQry = DB::table($getDataBaseName.'.ordination')
                ->where('name', $old_name)
                ->update($ordination_data);
        }
        else
        {
            $updateQry = DB::connection('system')
                ->table('ordination')
                ->where('name', $old_name)
                ->update($ordination_data);
        }
        // dd($logo_path);
        if($updateQry)
        {
            return true;
        }
        else
        {
            return false;
        }

    }

    public function destroy($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_DELETE');

        $id = base64_decode(base64_decode($encID));
        /*Examinations*/

        $BaseModel = $this->BaseModel->find($id);
        if($BaseModel->delete())
        {
             // Update tenant table
            \DB::table('tenants')
                ->where('ordination_id', $id)
                ->update([
                    'deleted_at' => now()
                ]);

            // Update domain table
            \DB::table('domains')
                ->where('ordination_id', $id)
                ->update([
                    'deleted_at' => now()
                ]);
            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.ORDINATION_DELETED');
        }
        return response()->json($this->JsonData);
    }

    public function hardDestroy($encID)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_DELETE');
        $id = base64_decode(base64_decode($encID));
        /*Examinations*/
        $BaseModel = $this->BaseModel->withTrashed()->find($id);
        // Hyn tenancy code (commented out)
        // $getDataBaseName = DB::connection('system')
        //                     ->table("websites")
        //                     ->where('ordination_id',$id)
        //                     ->first(['uuid']);
        // log::info("Ordination");
        // log::info($id);
        // log::info($getDataBaseName->uuid);
        
        // Stancl tenancy code
        $getDataBaseName = null;
        try {
            $tenant = tenancy()->tenant;
            if ($tenant) {
                $getDataBaseName = $tenant;
        log::info("Ordination");
        log::info($id);
                log::info($tenant->id);
            }
        } catch (\Exception $e) {
            log::info("Ordination - Tenant not found");
            log::info($id);
        }
        $this->JsonData['status'] = 'success';
        $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_DELETE');
        // if($BaseModel->forceDelete())
        // {
        //     DB::statement("DROP DATABASE `{$getDataBaseName->uuid}`");
        //     $this->JsonData['status'] = 'success';
        //     $this->JsonData['msg']    = __('admin.FAIL_ORDINATION_DELETE');
        // }
        return response()->json($this->JsonData);
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
                0 => 'ordination.id',
                1 => 'ordination.name',
                2 => 'ordination.link',
                3 => 'ordination.logo',
                4 => 'ordination.status',
            );

        /*--------------------------------------
        |  Model query and filter
        ------------------------------*/

            // start model query
            $modelQuery =  $this->BaseModel
                            ->select('ordination.id','ordination.name','ordination.text_color_code','ordination.background_color','ordination.logo','ordination.logo_path','ordination.status','ordination.deleted_at');

            // get total count
            $countQuery = clone($modelQuery);
            // $totalData  = $countQuery->withTrashed()->count(); //commented on 5-aug-24
            $totalData  = $countQuery->count();//changed on 5-aug-24

            ## FILTER OPTIONS for specific field
            $custom_search = false;
            if (!empty($request->custom))
            {
                if (!empty($request->custom['name']))
                {
                    $custom_search = true;
                    $key           = $request->custom['name'];
                    $modelQuery    = $modelQuery
                    ->where('ordination.name', 'LIKE', '%'.$key.'%');
                }

                // if (!empty($request->custom['login_user']))
                // {
                //     $custom_search = true;
                //     $key           = $request->custom['login_user'];

                //     $searchexpolade = explode(" ",$key);
                //     $firstName = $lastName = '';
                //     if(count($searchexpolade)>1)
                //     {
                //         if(isset($searchexpolade[0]) && isset($searchexpolade[1]))
                //         {
                //             $firstName = $searchexpolade[0];
                //             $lastName  = $searchexpolade[1];
                //             $modelQuery    = $modelQuery
                //             ->where('users.first_name', 'like', "$firstName")
                //                   ->where('users.last_name', 'like', "$lastName%");
                //         }
                //     }
                //     else
                //     {
                //         $modelQuery    = $modelQuery
                //         ->where('users.first_name', 'LIKE','%'.$key.'%')
                //               ->orWhere('users.last_name', 'LIKE','%'.$key.'%');
                //     }
                // }

                if (isset($request->custom['status']))
                {
                    $custom_search  = true;
                    $key            = $request->custom['status'];
                    $modelQuery     = $modelQuery
                    ->where('ordination.status', $key);
                }
            }

            ## filter options for commen search box
            if (!empty($request->search))
            {
                if (!empty($request->search['value']))
                {
                    $search = $request->search['value'];

                    $modelQuery = $modelQuery->where(function ($query) use($search)
                    {
                        $query->orwhere('ordination.name', 'LIKE', '%'.$search.'%');

                    });
                }
            }

            // get total filtered
            $filteredQuery = clone($modelQuery);
            // $totalFiltered  = $filteredQuery->withTrashed()->count();//commented on 5-aug-24
            $totalFiltered  = $filteredQuery->count(); //changed on 5-aug-24

            // offset and limit
            // $object = $modelQuery->withTrashed()->orderBy($filter[$column], $dir) //commented on 5-aug-24
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
                  //  dd($tenancy = app('tenancy.disk'));
                    $data[$key]['id']       = $row->id;
                   
                    // $data[$key]['name']   = '<span title="'.ucfirst($row->name).'">'.ucfirst($row->name).'</span>';
                    $data[$key]['name']   = '<span title="'.$row->name.'">'.$row->name.'</span>';
                    // Roshani Removed ucfirst for trello point 328 (b(ii)) on 9 april 2025

                    // Hyn tenancy code (commented out)
                    // $ordination_id = DB::connection('system')
                    //                 ->table("ordination")
                    //                 ->where('name',$row->name)
                    //                 ->whereNull('deleted_at')
                    //                 ->first(['id']);
                    
                    // Stancl tenancy code - no need to query system database
                    $ordination_id = null;
                    // Hyn tenancy code (commented out)
                    // $data[$key]['link']   = '';
                    // if($ordination_id){
                    //     $gethostnames = DB::connection('system')
                    //                 ->table("domains")
                    //                 ->where('ordination_id',$ordination_id->id)
                    //                 ->whereNull('deleted_at')
                    //                 ->first(['fqdn']);
                    //     if(!empty($gethostnames))
                    //     {
                    //         $fqdn = "https://".$gethostnames->fqdn;
                    //         $data[$key]['link']   = '<a target="_new" href="'.$fqdn.'">'.$fqdn.'</a>';
                    //     }
                    // }
                    
                    // Stancl tenancy code
                    $data[$key]['link'] = '';
                    try {
                        // Find tenant that belongs to this ordination
                        $tenant = \App\Models\Tenant::where('ordination_id', $row->id)->first();
                        if ($tenant && $tenant->domains) {
                            $domain = $tenant->domains->first();
                            if ($domain) {
                                $fqdn = "https://".$domain->domain;
                                $data[$key]['link'] = '<a target="_new" href="'.$fqdn.'">'.$fqdn.'</a>';
                            }
                        }
                        
                        // Fallback: if no tenant found, try to get from current request
                        if (empty($data[$key]['link'])) {
                            $currentTenant = tenancy()->tenant;
                            if ($currentTenant && $currentTenant->domains) {
                                $domain = $currentTenant->domains->first();
                                if ($domain) {
                                    $fqdn = "https://".$domain->domain;
                                    $data[$key]['link'] = '<a target="_new" href="'.$fqdn.'">'.$fqdn.'</a>';
                                }
                            }
                        }
                        
                        // Second fallback: use current request host
                        if (empty($data[$key]['link'])) {
                            $host = request()->getHost();
                            if ($host && $host !== 'localhost' && $host !== '127.0.0.1') {
                                $fqdn = "https://".$host;
                                $data[$key]['link'] = '<a target="_new" href="'.$fqdn.'">'.$fqdn.'</a>';
                            }
                        }
                        
                        // Debug logging
                        \Log::info('Ordination ' . $row->id . ' - Tenant found: ' . ($tenant ? 'Yes' : 'No') . ' - Link: ' . $data[$key]['link']);
                        
                    } catch (\Exception $e) {
                        // Tenant or domain not found
                        \Log::info('Error getting domain for ordination ' . $row->id . ': ' . $e->getMessage());
                    }
                    $logo_path = self::getFilePath($row->logo_path);
                    $data[$key]['logo']   = '<img style="height: 50px; width: 50px;" src="'.$logo_path.'">';

                    // $data[$key]['login_user'] = '<span title="'.ucfirst($row->first_name).' '.$row->last_name.'">'.ucfirst($row->first_name).' '.$row->last_name.'</span>';

                    $data[$key]['status']   = $row->status==1 ?__('admin.TITLE_STATUS_ACTIVE_TEXT'):__('admin.TITLE_STATUS_INACTIVE_TEXT');

                    $edit="";
                    $delete="";

                    // Check Permission
                    if(auth()->user()->can('ordination-add')){
                        $edit = '<a href="'.route('admin.ordination.edit', [ base64_encode(base64_encode($row->id))]).'" class="edit-user action-icon" title="'.__('admin.TITLE_EDIT_TEXT').'"><span class="fas fa-edit"></span></a>&nbsp&nbsp';

                        //check new ordination have appointment added by swati 17-Apr-23========================
                        $checkAppointments=array();
                        if(empty(Config('ordination_id')))
                        {
                            $BaseModel = $this->BaseModel->withTrashed()->find($row->id);
                            // Hyn tenancy code (commented out)
                            // $getDataBaseName = DB::connection('system')
                            //                         ->table("websites")
                            //                         ->where('ordination_id',$row->id)
                            //                         ->first(['uuid']);
                            // $checkAppointments = DB::table($getDataBaseName->uuid.'.appointment')->first();
                            
                            // Stancl tenancy code
                            $getDataBaseName = null;
                            $checkAppointments = null;
                            try {
                                $tenant = tenancy()->tenant;
                                if ($tenant) {
                                    $getDataBaseName = $tenant;
                                    $checkAppointments = DB::table($tenant->id.'.appointment')->first();
                                }
                            } catch (\Exception $e) {
                                // Tenant not found or database doesn't exist
                                $checkAppointments = null;
                            }
                        }
                        //======================================================================
                        if(empty(Config('ordination_id')) && empty($checkAppointments))
                        {
                            if($row->deleted_at)
                                $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollectionNew(this)" style="color:red !important;" data-href="'.route('admin.ordination.hardDestroy', [base64_encode(base64_encode($row->id))]).'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                            else
                                $delete = '<a href="javascript:void(0)" class="delete-user action-icon" title="'.__('admin.TITLE_DELETE_BUTTON').'" onclick="return deleteCollection(this)" data-href="'.route('admin.ordination.destroy', [base64_encode(base64_encode($row->id))]) .'" ><span class="fas fa-trash"></span></a>&nbsp&nbsp';
                        }


                    }

                    $data[$key]['actions'] = '<div class="text-center">'.$edit.$delete.'</div>';
                }
            }

            ## SEARCH HTML
            $searchHTML['id']               =  '';
            $searchHTML['name']             =  '<input type="text" class="form-control" id="name" value="'.($request->custom['name'] ?? '').'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';
            $searchHTML['link']             =  '';
             $searchHTML['logo']             =  '';
            // $searchHTML['login_user']       =  '<input type="text" class="form-control" id="login_user" value="'.($request->custom['login_user']).'" placeholder='.__('admin.TITLE_SEARCH_TEXT').'>';;


            $searchHTML['status']   =  '<select name="status" id="status" class="form-control my-select">
                    <option class="theme-black blue-select" value="">'.__('admin.TITLE_EXAMINATION_STATUS').'</option>
                    <option class="theme-black blue-select" value="1" '.(!empty($request->custom['status']) && $request->custom['status'] == "1" ? 'selected' : '').' >'.__('admin.TITLE_STATUS_ACTIVE_TEXT').'</option>
                    <option class="theme-black blue-select" value="0" '.( !empty($request->custom['status']) && $request->custom['status'] == "0" ? 'selected' : '').'>'.__('admin.TITLE_STATUS_INACTIVE_TEXT').'</option>
                    </select>';

            $seachAction  =  '<div class="text-center"><a style="cursor:pointer;" onclick="return doSearch(this)" class="btn btn-primary"><span class="fa fa-search"></span></a></div>';

            $searchHTML['actions'] = $seachAction;
            array_unshift($data, $searchHTML);

            // wrapping up
            $this->JsonData['draw']             = intval($request->draw);
            $this->JsonData['recordsTotal']     = intval($totalData);
            $this->JsonData['recordsFiltered']  = intval($totalFiltered);
            $this->JsonData['data']             = $data;

        return response()->json($this->JsonData);
    }

    public function _storeOrUpdate($collection, $request)
    {
        $collection->name             = $request->name;
        $collection->text_color_code  = $request->text_color_code;
        $collection->background_color = $request->background_color;
        $collection->status           = !empty($request->status)?1:0;
        $collection->address          = $request->address;
        $collection->postal_code      = $request->postal_code;
        $collection->email            = $request->email;
        $collection->mobile_no        = $request->mobile_no;
        $collection->button_colors    = $request->button_colors_code;
        $collection->screen_bg_color  = $request->screen_bg_color;
        $collection->app_bar_color    = $request->app_bar_color;
        $collection->tabs_selection_color      = $request->tabs_selection_color;
        $collection->home_screen_options_color = $request->home_screen_options_color;
        $collection->menu_header_colors        = $request->menu_header_colors;
        $collection->menu_bg_color   = $request->menu_bg_color;
        $collection->dark_text_color = $request->dark_text_color;
        $collection->light_text_color= $request->light_text_color;
        $collection->header_text_color= $request->header_text_color;  //added on 30-july-24

        //
        $code = $request->postal_code;

         // Roshani added for CR #102 on 10-sept-24
        $collection->country   = $request->country; 
        $country = strtoupper($collection->country);

        //commented for testing on 22-feb-24
        // $mapsApiKey = 'AIzaSyC9d8HGhVWo-FS5rTVgXB8ezuwK_mNvbjg';

        $mapsApiKey = Config('mapsApiKey'); //added on 22-feb-24


        //$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."&sensor=false";
        $address_spec = str_replace(' ', '', $collection->address);

        //$url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".trim($address_spec)."+AUSTRIA&sensor=false";// Roshani hidden for CR #102 on 10-sept-24
         $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".$mapsApiKey."&address=".$code."+".$country."&sensor=false";// Roshani added for CR #102 on 10-sept-24

        $data = file_get_contents($url);
        if($data)
        {
            // convert into readable format
            $decode_data = json_decode($data);
            //dd($decode_data->results);
            if(!empty($decode_data->results))
            {
                $collection->latitude = $decode_data->results[0]->geometry->location->lat ?? '' ;
                $collection->longitude = $decode_data->results[0]->geometry->location->lng ?? '';
            }
        }

        if (!empty($request->logo))
        {
            $path = 'ordination-logo';

            $objDocument = $request->logo;
            $imagename=$objDocument->getClientOriginalName();
            $original_file  = str_replace( " ", "-", $imagename);
            $extension      = strtolower($objDocument->getClientOriginalExtension());

            $filename       = date('YmdHis').'-'.$original_file;


            //$file           = Storage::putFileAs($path, $objDocument, $filename);

            //$file           = Storage::disk('tenant')->putFileAs($path, $objDocument, $filename);

            $filePath = self::putFilePath($path, $objDocument, $filename);

            //$file           = Storage::disk('tenant')->putFileAs($path, $objDocument, $filename);


            $aceesPath  = "ordination-logo/".$filename;

            $collection->logo      = $filename;
            $collection->logo_path = $aceesPath;
            $file = self::unlinkFilePath($request->old_logo);
            if(!empty($request->old_logo) && is_file($file))
            {


                $file = self::unlinkFilePath($request->old_logo);
                @unlink($file);
            }


        }
        if($collection->save())
        {
            // dd($request->specialist,$collection->id);
            // $OrdinationHasSpecialistModel = $this->OrdinationHasSpecialistModel
            //                                 ->where('ordination_id',$collection->id)
            //                                 ->delete();

            // if(!empty($request->specialist) && sizeof($request->specialist)>0)
            // {
            //     foreach ($request->specialist as $key => $value)
            //     {
            //         $SpecialistModel =  new $this->OrdinationHasSpecialistModel;
            //         $SpecialistModel->ordination_id = $collection->id;
            //         $SpecialistModel->specialist_id = $value;
            //         $SpecialistModel->status        = '1';
            //         $SpecialistModel->created_at    = date('Y-m-d');
            //         $SpecialistModel->save();
            //     }
            // }
        }

        return $collection;
    }

    public function check_list_delete(Request $request)
    {
        $this->JsonData['status'] = 'error';
        $this->JsonData['msg']    = __('admin.FAIL_EXAM_DELETE');

        $BaseModel = $this->CheckListHasHeadingSectionModel->find($request->id);

        if($BaseModel->delete())
        {
            $deleteQue = $this->HeadingSectionHasQuestionModel
                        ->where('fk_check_list_heading_section_id',$request->id)
                        ->delete();

            $this->JsonData['status'] = 'success';
            $this->JsonData['msg']    = __('admin.CHECK_LIST_HEADING_SECTION_DELETED');
        }
        return response()->json($this->JsonData);
    }

    public function uploadOCR(Request $request)
    {
        //dd($request->all());

        $path           = 'ordination-logo';
        $objDocument    = $request->logo;
        $original_file  = $objDocument->getClientOriginalName();
        $extension      = strtolower($objDocument->getClientOriginalExtension());
        $filename       = date('YmdHis').'-'.$original_file;
        //$filePath       = self::putFilePath($path, $objDocument, $filename);
        $fileStorePath  = Storage::putFileAs($path, $objDocument, $filename);
        $aceesPath      = "ordination-logo/".$filename;

        // $aa = shell_exec('"C:\\Program Files (x86)\\Tesseract-OCR\\tesseract" "D:\wamp64\\www\\puregyn-generic\\storage\\app\\ordination-logo\\20211005152238-Biermann_781_BLU_10-20.jpg" pooja1');
        // $a = '<img src="'.url('storage/app/ordination-logo/20211005151404-Mustermann_129_STD_07-13.pdf').'"/>';

        // ============================================================================
        $server_file =storage_path().'/app/'.$aceesPath;

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($server_file);
        if ($pdf != "") {
            $original_text = $pdf->getText();
            if ($original_text != "") {
                $text = nl2br($original_text); // Paragraphs and line break formatting
                $text = clean_ascii_characters($text); // Check special characters
                $text = str_replace(array("<br /> <br /> <br />", "<br> <br> <br>"), "<br /> <br />", $text); // Optional
                $text = addslashes($text); // Backslashes for single quotes
                $text = stripslashes($text);
                $text = strip_tags($text);

                /**********************************************/
                /* Additional step to check formatting issues */
                // There may be some PDF formatting issues. I'm trying to check if the words are:
                // (a) Join. E.g., HelloWorld!Thereisnospacingbetweenwords
                // (b) splitted. E.g., H e l l o W o r l d ! E x c e s s i v e s p a c i n g
                $check_text = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

                $no_spacing_error = 0;
                $excessive_spacing_error = 0;
                foreach($check_text as $word_key => $word) {
                    if (strlen($word) >= 30) { // 30 is a limit that I set for a word length, assuming that no word would be 30 length long
                        $no_spacing_error++;
                    } else if (strlen($word) == 1) { // To check if the word is 1 word length
                        if (preg_match('/^[A-Za-z]+$/', $word)) { // Only consider alphabetical words and ignore numbers.
                            $excessive_spacing_error++;
                        }
                    }
                }
                // Set the boundaries of errors you can accept
                // E.g., we reject the change if there are 30 or more $no_spacing_error or 150 or more $excessive_spacing_error issues
                if ($no_spacing_error >= 30 || $excessive_spacing_error >= 150) {
                    echo "Too many formatting issues<br />";
                    echo $text;
                } else {
                    echo "Success!<br />";
                    echo $text;
                }
                /* End of additional step */
                /**************************/

            } else {
                echo "No text extracted from PDF.";
            }
        } else {
            echo "parseFile fns failed. Not a PDF.";
        }
        // ============================================================================
        dump($aa);
        dd("--->");

        die;
    }

     //Test mail when create ordination
    //public function newordination(){
        // $collection = $this->BaseModel->find(1);
        // $email_result = Mail::to('eluminous.se65@gmail.com')->send(new SendOrdinationUrlForOrdination($collection));

        // $ordination_path = '/opt/app-shared/php/data/storage/app/public/tenancy/tenants/1f1514d45174447688ea621c365bbae1';
        // File::makeDirectory($ordination_path, 0777, true, true);
        // $ordination_public_path = public_path().'/storage/app/public/tenancy/tenants/1f1514d45174447688ea621c365bbae1';
        // File::makeDirectory($ordination_public_path, 0777, true, true);
        // echo "Send";

    //}

    /**
     * Generate a random password
     */
    private function generateRandomPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

}
