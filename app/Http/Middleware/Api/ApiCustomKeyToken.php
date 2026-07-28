<?php

namespace App\Http\Middleware\Api;

use Closure;
use Session;
use Artisan;
//use Teepluss\Restable\Facades\Restable;

class ApiCustomKeyToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //==============================================
        // TODO: Replace with Stancl tenancy logic for getting current tenant
        // Get current Website (Tenant)
  
        // ==================================================
        ## CHECK API TOKEN
        if (is_null($request->header('APP-TOKEN')) || $request->header('APP-TOKEN') != config('constants.APP_TOKEN')) {
            $arrResult['status'] = 'Unauthorized';
            $arrResult['status_code'] = '401';            
            $arrResult['message'] = __('api.MSG_DIRECT_SCRIPT_ACCESS');
            return response()->json($arrResult,401);
        }



        return $next($request);
    }
}
