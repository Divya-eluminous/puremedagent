<?php

namespace App\Http\Middleware\Admin;

use Closure;

use Session;
use DB;
use Config;
use App\Models\OrdinationsModel;
use App\Models\OrdinationHasSpecialistModel;

// Models
use Spatie\Permission\Models\Role;
//Trait
use App\Traits\GeneralTrait;
use Artisan;


class Authenticate
{   
    use GeneralTrait;

    public function __construct(Role $RoleModel, OrdinationsModel $OrdinationsModel,OrdinationHasSpecialistModel $OrdinationHasSpecialistModel)
    {
        $this->RoleModel  = $RoleModel; 
        $this->OrdinationsModel  = $OrdinationsModel; 
        $this->OrdinationHasSpecialistModel  = $OrdinationHasSpecialistModel;
    }

    public function handle($request, Closure $next)
    {
       
             
        if(auth()->check())
        {
            if (auth()->user()->roles->pluck('guard_name')->first() === 'admin') 
            {
                if (auth()->user()->status) 
                {
                    $allRoles = $this->RoleModel
                            ->where('guard_name', 'admin')
                            //->where('name', '!=', 'super-admin')
                            ->orderBy('name', 'ASC')
                            ->get();
                   
                   
                    view()->share('roles', $allRoles);
                    // dd($company);     
                    return $next($request);      
                }
                else
                {
                    auth()->logout();
                    return redirect('/admin/login');
                }
            }
            else
            {
                auth()->logout();
                return redirect('/admin/login');
            }
        }
        else
        {
            return redirect('/admin/login');
        }
    }
}
