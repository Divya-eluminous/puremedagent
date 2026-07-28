<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{    
    public function handle($request, Closure $next, $guard = null)
    {

        if(auth()->check())
        {            
    		//dd(auth());
            return redirect('/admin/dashboard');
        }

        return $next($request);
    }
}
