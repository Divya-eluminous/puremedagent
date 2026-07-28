<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class EnforceTenancy
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
        // Get the host from the request
        $host = $request->getHost();
        
        // Debug logging
        // Log::info("EnforceTenancy middleware called for host: " . $host);
        
        // Clear any existing tenant context to prevent cache issues
        try {
            Tenancy::end();
        } catch (Exception $e) {
            Log::warning("Failed to end existing tenant context: " . $e->getMessage());
        }
        
        // Try to find tenant by domain using system connection
        $tenant = \App\Models\Tenant::on('system')->whereHas('domains', function ($query) use ($host) {
            $query->where('domain', $host);
        })->first();
        
        if ($tenant) {
            // Log::info("Tenant found: " . $tenant->id);
            
            // Get the tenant database name from the uuid field
            $dbName = null;
            
            // Use the uuid field as the database name (based on Tenant creation logic)
            if (!empty($tenant->uuid)) {
                $dbName = $tenant->uuid;
                // Log::info("Using uuid as database name: " . $dbName);
            }
            // Fallback: Try JSON data field
            elseif (!empty($tenant->data)) {
                $tenantData = json_decode($tenant->data, true);
                $dbName = $tenantData['tenancy_db_name'] ?? null;
                Log::info("Using JSON data tenancy_db_name: " . $dbName);
            }
            
            // Log::info("Final Tenant DB Name: " . $dbName);
            
            if ($dbName) {
                // Update the tenant connection configuration
                Config::set('database.connections.tenant.database', $dbName);
                Config::set('database.default', 'tenant');
                
                // Purge and reconnect to ensure the new configuration is used
                DB::purge('tenant');
                DB::reconnect('tenant');
                
                // Initialize the tenant context
                Tenancy::initialize($tenant);
                
                // Set the default connection to tenant for this request
                DB::setDefaultConnection('tenant');
                
                // Set up tenant configuration immediately after initialization
                $this->setupTenantConfig($tenant);
                
                // Verify tenant connection is working
                $this->verifyTenantConnection();
                
                Log::info("Successfully switched to tenant database: " . $dbName);
            } else {
                Log::warning("Could not get tenant database name");
            }
        } else {
            Log::warning("No tenant found for host: " . $host);
        }
        
        return $next($request);
    }

    /**
     * Sets up tenant-specific configuration.
     *
     * @param  \App\Models\Tenant  $tenant
     * @return void
     */
    protected function setupTenantConfig($tenant)
    {
        // Set up tenant configuration
        config(['google_calendar_id' => $tenant->calendar_id ?? 'primary']);
        config(['ordination_id' => $tenant->ordination_id ?? 'primary']);
        config(['website_id' => $tenant->id ?? 'primary']);

        // Get ordination details from the central (system) DB
        $getOrdination = DB::connection('system')
            ->table("ordination")
            ->where('id', $tenant->ordination_id ?? 'primary')
            ->first();

        if ($getOrdination) {
            $getweburl = DB::connection('tenant')
                ->table('settings')
                ->where(['setting_key' => 'ORDINATION_WEBPAGE', 'status' => '1'])
                ->first();

            $weburl = ($getweburl) ? $getweburl->setting_value : "https://puremed.biz/";

            config([
                'ordination_url'   => $weburl,
                'ordination_name'  => $getOrdination->name,
                'menu_bg_color'    => $getOrdination->menu_bg_color,
                'light_text_color' => $getOrdination->light_text_color,
                'dark_text_color'  => $getOrdination->dark_text_color,
                'screen_bg_color'  => $getOrdination->screen_bg_color,
                'button_colors_code' => $getOrdination->button_colors,
                'menu_header_colors' => $getOrdination->menu_header_colors,
                'ordination_logo'  => url('storage/tenancy/tenants/' . $tenant->uuid . '/' . $getOrdination->logo_path)
            ]);
        }
    }

    /**
     * Verifies that the tenant connection is working by attempting to fetch a user.
     *
     * @return void
     */
    protected function verifyTenantConnection()
    {
        try {
            // Attempt to fetch a user from the tenant database
            $user = DB::connection('tenant')->table('users')->first();

            if ($user) {
                Log::info("Tenant connection verified. User found: " . $user->id);
            } else {
                Log::warning("Tenant connection verified. No user found in tenant database.");
            }
        } catch (\Exception $e) {
            Log::error("Tenant connection verification failed: " . $e->getMessage());
            // Optionally, you might want to throw an exception or redirect to an error page
            // For now, we'll just log the error.
        }
    }
}
