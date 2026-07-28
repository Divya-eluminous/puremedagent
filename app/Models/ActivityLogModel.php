<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Request;

class ActivityLogModel extends Model 
{
    use SoftDeletes;

    protected $table = 'activity_logs';

    protected $fillable = [
        'id', 
        'module', 
        'method', 
        'action', 
        'old_data',
        'new_data',
        'message', 
        'url', 
        'ip', 
        'agent',  
        'user_id',
        'patient_id'
    ];

    public function addLog($module='',$message='',$action='',$old_data='',$new_data='')
    {
        //dd("ppppp");
        try  
        {
            // $pageDetail = PermissionsModel::where('action', $currentUrl = Route::getFacadeRoot()->current()->uri())->first();
           // $module = $pageDetail->parent->name;
            // dd($old_data);
            // dd($new_data);
            // $test = ['a','b']; 
            // $log = [];
            $log['message'] = '"' . Auth::user()->first_name." ". Auth::user()->last_name . '" ' . $message;
            $log['module'] = $module;
            $log['old_data'] = json_encode($old_data);
            $log['new_data'] = json_encode($new_data);
            $log['action'] = $action;
            $log['url'] = Request::fullUrl();
            $log['method'] = Request::method();
            $log['ip'] = Request::ip(); 
            $log['agent'] = Request::header('user-agent');
            $log['user_id'] = Auth::check() ? Auth::id() : 0;
            //dd($log);
            $this->create($log);
            //dd($log);
        } 
        catch (\Exception $exception) 
        {
            Log::error(__CLASS__ . "::" . __METHOD__ . ' : ' . $exception->getMessage() . " : Message => " . Auth::user()->name . " " . $message);
        }
    }

    public function addApiLog($name='',$message='',$action='',$old_data='',$new_data='') 
    {
     
      $log = [];
      // dd(Auth::user()->id);
      $userInfo=auth('api')->user();
      if (Auth::guard('api')->check()) {
        // dd('if');
          $log['message'] = '"' . $userInfo->first_name." ". $userInfo->family_name . '" ' . $message;
          $log['patient_id'] = $userInfo->id;
          // dd($log['message']);
      }else{
        // dd('else');
          $log['message'] = $message;
          $log['patient_id'] = 0;
      }
    // print_r($userInfo->first_name);
    // die;
      $log['old_data'] = json_encode($old_data);
      $log['new_data'] = json_encode($new_data);
      $log['module'] = $name; 
      $log['action'] = $action;
      $log['url'] = Request::fullUrl();
      $log['method'] = Request::method();
      $log['ip'] = Request::ip(); 
      $log['agent'] = Request::header('user-agent');
      
      // dd($log);
      $this->create($log);
    } 
}
