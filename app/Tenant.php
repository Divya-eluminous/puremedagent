<?php
namespace App;

use Illuminate\Support\Facades\Artisan;
use DB;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\Tenant as TenantModel;
use App\Models\Domain;
use App\Models\AdminUserModel;
use App\Mail\SendOrdinationUrlForOrdination;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Exception;

/**
 * @property TenantModel tenant
 * @property Domain domain
 */
 
class Tenant
{
    public function __construct(TenantModel $tenant = null, Domain $domain = null)
    {
        $this->tenant = $tenant;
        $this->domain = $domain;
    }
    
    public function delete()
    {
        if ($this->tenant) {
            $this->tenant->delete();
        }
    }
    
    public static function create($fqdn, $ordination_id, $ordination_name, $calender_id, $OrdinationUrl, $email = null, $mobile_no = null, $logo_path = '/images/default-logo.png', $uuid = null): Tenant
    {
        // Debug: Check the parameters
        // echo "Debug - ordination_id: " . $ordination_id . "\n";
        // echo "Debug - uuid parameter: " . ($uuid ?: 'NULL') . "\n";
        
        // Use provided uuid or generate default
        if (!$ordination_id) {
            throw new Exception("ordination_id cannot be null or empty");
        }
        
        $dbName = $uuid ?: 'ordination_' . $ordination_id . '_' .  rand(1000, 9999);
        
        // echo "Debug - dbName generated: " . $dbName . "\n";
        
        // Create new tenant with unique tenant_id and uuid
        try {
            // Temporarily disable cache invalidation
            // echo "Debug - About to create tenant with uuid: " . $dbName . "\n";
            
            // Use raw SQL to bypass Stancl Tenancy's model events
            $tenantId = 'ordination_' . $ordination_id . '_' . time();
            
            DB::connection('system')->table('tenants')->insert([
                'tenant_id' => $tenantId,
                'ordination_id' => $ordination_id,
                'ordination_name' => $ordination_name,
                'calendar_id' => $calender_id,
                'uuid' => $dbName, // Set uuid during creation
                'tenancy_db_name' => $dbName,
                'data' => json_encode(['ordination_url' => $OrdinationUrl]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Get the created tenant
            $tenant = DB::connection('system')->table('tenants')->where('tenant_id', $tenantId)->first();
            
            // echo "Debug - Tenant created, checking uuid field: " . $tenant->uuid . "\n";
            
            // Double-check what's actually in the database
            $dbTenant = DB::connection('system')->table('tenants')->where('id', $tenant->id)->first();
            // echo "Debug - Database uuid field: " . ($dbTenant->uuid ?? 'NULL') . "\n";
            
        } catch (Exception $e) {
            echo " Tenant creation failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        // Create domain for the tenant
        try {
            $domain = Domain::create([
                'domain' => $OrdinationUrl,
                'fqdn' => $OrdinationUrl,
                'ordination_id' => $ordination_id,
                'tenant_id' => $tenant->id,
            ]);
        } catch (Exception $e) {
            echo " Domain creation failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        try {
            // Drop database if it exists to ensure clean slate
            DB::statement('DROP DATABASE IF EXISTS `' . $dbName . '`');
            // echo "✓ Dropped existing database: " . $dbName . "\n";
            
            // Wait a moment to ensure database is fully dropped
            sleep(2);
            
            // Create fresh database
            DB::statement('CREATE DATABASE `' . $dbName . '`');
            // echo "✓ Database created for tenant: " . $tenant->id . "\n";
            
            // Verify database is empty and clean any leftover tables
            $tables = DB::select("SHOW TABLES FROM `{$dbName}`");
            if (!empty($tables)) {
                echo " Found " . count($tables) . " existing tables, cleaning database...\n";
                // Drop all existing tables
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    DB::statement("DROP TABLE IF EXISTS `{$dbName}`.`{$tableName}`");
                }
                // echo "✓ Cleaned all existing tables\n";
                
                // Double-check that all tables are gone
                $remainingTables = DB::select("SHOW TABLES FROM `{$dbName}`");
                if (!empty($remainingTables)) {
                    throw new Exception("Failed to clean database. Still has " . count($remainingTables) . " tables.");
                }
            }
            // echo "✓ Verified database is empty\n";
            
        } catch (Exception $e) {
            echo " Database creation failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        // Debug: Check tenant before initialization
        // echo "✓ Tenant before initialization: " . ($tenant ? $tenant->id : 'NULL') . "\n";

        // Initialize tenant context
        try {
            // Clear any existing tenant context first
            tenancy()->end();
            
            // Initialize the new tenant
            tenancy()->initialize($tenant);
            // echo "✓ Tenant context initialized successfully\n";
        } catch (Exception $e) {
            echo " Tenant initialization failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        // Debug: Check tenant after initialization
        // echo "✓ Tenant after initialization: " . ($tenant ? $tenant->id : 'NULL') . "\n";

        // Run migrations for the new tenant using direct database connection
        try {
            // Double-check database is empty before running migrations
            $tables = DB::select("SHOW TABLES FROM `{$dbName}`");
            if (!empty($tables)) {
                // echo " Database {$dbName} has existing tables, dropping them first\n";
                foreach($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    DB::statement("DROP TABLE IF EXISTS `{$dbName}`.`{$tableName}`");
                    // echo "Dropped table: {$tableName}\n";
                }
                echo " Dropped all existing tables\n";
            }
            
            // Switch to tenant database context
            DB::statement("USE `{$dbName}`");
            
            // Clear any existing migration records
            DB::statement("DROP TABLE IF EXISTS `{$dbName}`.`migrations`");
            
           // Create migrations table first
            DB::statement("CREATE TABLE `{$dbName}`.`migrations` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `migration` varchar(255) NOT NULL,
                `batch` int(11) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // echo "✓ Created migrations table\n";
            
            // Run all migrations from tenant folder in tenant context
            // echo "Running all tenant migrations for database: {$dbName}\n";
            
            // Set tenant as default connection for migration
            \Illuminate\Support\Facades\Config::set('database.default', 'tenant');
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Ensure we're connected to the correct database
            DB::statement("USE `{$dbName}`");
            
            $exitCode = Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true
            ]);
            
            // Reset to system connection
            \Illuminate\Support\Facades\Config::set('database.default', 'system');
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            if ($exitCode === 0) {
                // echo "✓ All tenant migrations completed for tenant: " . $tenant->id . "\n";
            } else {
                echo "⚠ Migration failed for tenant: " . $tenant->id . "\n";
                throw new Exception("Migration failed with exit code: " . $exitCode);
            }
            
        } catch (Exception $e) {
            echo "⚠ Migration failed: " . $e->getMessage() . "\n";
            throw $e;
        }

        // Create ordination record using tenant context
        try {
            // Get ordination data from system database
            $ordinationData = DB::connection('system')
                ->table('ordination')
                ->where('id', $ordination_id)
                ->first();

            if ($ordinationData) {
                // Insert directly into tenant database using full database name
                DB::statement("USE `{$dbName}`");
                
                DB::table('ordination')->insert([
                    'id' => $ordinationData->id,
                    'name' => $ordinationData->name,
                    'text_color_code' => $ordinationData->text_color_code ?? '',
                    'background_color' => $ordinationData->background_color ?? '',
                    'logo' => $ordinationData->logo ?? '',
                    'logo_path' => $ordinationData->logo_path ?? '',
                    'status' => $ordinationData->status ?? 1,
                    'email' => $ordinationData->email ?? $email,
                    'address' => $ordinationData->address ?? '',
                    'postal_code' => $ordinationData->postal_code ?? '',
                    'mobile_no' => $ordinationData->mobile_no ?? $mobile_no,
                    'button_colors' => $ordinationData->button_colors ?? '',
                    'screen_bg_color' => $ordinationData->screen_bg_color ?? '',
                    'app_bar_color' => $ordinationData->app_bar_color ?? '',
                    'tabs_selection_color' => $ordinationData->tabs_selection_color ?? '',
                    'home_screen_options_color' => $ordinationData->home_screen_options_color ?? '',
                    'menu_header_colors' => $ordinationData->menu_header_colors ?? '',
                    'menu_bg_color' => $ordinationData->menu_bg_color ?? '',
                    'dark_text_color' => $ordinationData->dark_text_color ?? '',
                    'light_text_color' => $ordinationData->light_text_color ?? '',
                    'header_text_color' => $ordinationData->header_text_color ?? '',
                    'latitude' => $ordinationData->latitude ?? '',
                    'longitude' => $ordinationData->longitude ?? '',
                    'country' => $ordinationData->country ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // echo "✓ Ordination record created in tenant database: " . $tenant->id . "\n";
            } else {
                // echo "⚠ Ordination data not found in system database for ID: " . $ordination_id . "\n";
                // Create basic ordination record if not found in system
                DB::statement("USE `{$dbName}`");
                DB::table('ordination')->insert([
                    'id' => $ordination_id,
                    'name' => $ordination_name,
                    'text_color_code' => '#000000',
                    'background_color' => '#ffffff',
                    'logo' => '',
                    'logo_path' => '',
                    'status' => 1,
                    'email' => $email,
                    'address' => '',
                    'postal_code' => '',
                    'mobile_no' => $mobile_no,
                    'button_colors' => '#007bff',
                    'screen_bg_color' => '#f8f9fa',
                    'app_bar_color' => '#343a40',
                    'tabs_selection_color' => '#007bff',
                    'home_screen_options_color' => '#6c757d',
                    'menu_header_colors' => '#495057',
                    'menu_bg_color' => '#ffffff',
                    'dark_text_color' => '#212529',
                    'light_text_color' => '#6c757d',
                    'header_text_color' => '#495057',
                    'latitude' => '',
                    'longitude' => '',
                    'country' => 'Austria',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // echo "✓ Created basic ordination record in tenant database: " . $tenant->id . "\n";
            }
        } catch (Exception $e) {
            echo " Ordination record creation failed: " . $e->getMessage() . "\n";
        }

        // User creation and email sending will be handled by OrdinationsController
        // This keeps the working email functionality in the controller



        // End tenant context to prevent cache issues
        try {
            tenancy()->end();
            // echo "✓ Tenant context ended successfully\n";
        } catch (Exception $e) {
            echo " Tenant context ending failed: " . $e->getMessage() . "\n";
        }

        // Convert stdClass to TenantModel object
        $tenantModel = new TenantModel();
        $tenantModel->id = $tenant->id;
        $tenantModel->tenant_id = $tenant->tenant_id;
        $tenantModel->ordination_id = $tenant->ordination_id;
        $tenantModel->ordination_name = $tenant->ordination_name;
        $tenantModel->calendar_id = $tenant->calendar_id;
        $tenantModel->uuid = $tenant->uuid;
        $tenantModel->data = json_decode($tenant->data, true);
        $tenantModel->created_at = $tenant->created_at;
        $tenantModel->updated_at = $tenant->updated_at;
        
        return new Tenant($tenantModel, $domain);
    }
    
    public static function tenantExists($name)
    {
        return Domain::where('domain', $name)->exists();
    }
    

    public static function getCurrentTenant()
    {
        return Tenancy::tenant();
    }

    public static function getCurrentDomain()
    {
        return Tenancy::domain();
    }
    
    private static function generateRandomPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

}