<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\Tenant;
use App\Models\Domain;

class TenancyProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure tenancy
        Tenancy::hook('bootstrapped', function (Tenancy $tenancy) {
            // Any additional bootstrapping logic
        });

        // Configure tenant identification
        Tenancy::identifyTenantUsing(function ($request) {
            $host = $request->getHost();
            
            // Check if it's a central domain
            if (in_array($host, config('tenancy.central_domains'))) {
                return null;
            }

            // Find tenant by domain
            $domain = Domain::where('domain', $host)->first();
            return $domain ? $domain->tenant : null;
        });
    }
}