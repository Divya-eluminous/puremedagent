<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Support\Facades\Auth;

class General
{
    public function __construct()
    {
        auth()->setDefaultDriver('admin');
    }
    
    public function handle($request, Closure $next, $guard = 'admin')
    {
        return $next($request);
    }
}
