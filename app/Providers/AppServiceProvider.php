<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

// use Hyn\Tenancy\Environment;
// use Hyn\Tenancy\Models\Hostname;
// use Hyn\Tenancy\Models\Website;
// use Hyn\Tenancy\Contracts\CurrentHostname;
// use Hyn\Tenancy\Contracts\Tenant;
// use Hyn\Tenancy\Contracts\Repositories\CustomerRepository;
// use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
// use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use DB;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {  
        // dump("in appServeicePRovider");
        // dump("db connection provider======>".DB::connection('tenant')->getDatabaseName());
        // Fix for undefined constant HEADER_X_FORWARDED_ALL in older Laravel versions
        if (!defined('Request::HEADER_X_FORWARDED_ALL')) {
            // Fallback for older Laravel versions
            // Manually handle cases where HEADER_X_FORWARDED_ALL is not defined
            $requestHeaders = Request::HEADER_X_FORWARDED_FOR 
                | Request::HEADER_X_FORWARDED_HOST 
                | Request::HEADER_X_FORWARDED_PORT 
                | Request::HEADER_X_FORWARDED_PROTO;
            // You might need to handle this fallback differently based on your specific needs
            // For example, use $requestHeaders instead of the constant if needed
        }
                  
        //CustomValidationRules::init();
        config(['mapsApiKey' => 'AIzaSyAZlUm6ZfRn-ljTE4GB8MKXUamh9hwLZw4']);

        if(config('app.env') === 'production') {
            $this->app['request']->server->set('HTTPS', true);
        }
        Schema::defaultStringLength(125);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
