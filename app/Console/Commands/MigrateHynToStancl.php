<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateHynToStancl extends Command
{
    protected $signature = 'tenancy:migrate-hyn';
    protected $description = 'Migrate data from hyn tables (websites, hostnames) to stancl tables (tenants, domains) with incremental tenant IDs';

    public function handle()
    {
        $currentConnection = DB::connection()->getDatabaseName();
        dump("🔍 Using database: " . $currentConnection);

        // DB::beginTransaction();

        try {
            // Clear target tables first (optional: comment out if not needed)
            DB::table('tenants')->truncate();
            DB::table('domains')->truncate();

            // 1. Insert tenants from websites
            $websites = DB::table('websites')->get();
            $counter = 1;

            foreach ($websites as $website) {
                // $tenantId = (string) $counter; // incremental ID
                $tenantId = 'ordination_' . $website->ordination_id . '_' . time();

                DB::table('tenants')->insert([
                    'id' => $counter,
                    'ordination_id' => $website->ordination_id,
                    'tenant_id' => $tenantId, // match id
                    'ordination_name' => null,
                    'calendar_id' => $website->calendar_id,
                    'data' => json_encode([
                        'hyn_website_id' => $website->id,
                        'db_connection' => $website->managed_by_database_connection,
                        'original_uuid' => $website->uuid,
                    ]),
                    'created_at' => $website->created_at,
                    'updated_at' => $website->updated_at,
                    'deleted_at' => $website->deleted_at,
                    'uuid' => $website->uuid,
                    'tenancy_db_name' => $website->uuid,
                ]);

                $counter++;
            }

            // $this->info("✅ Inserted " . count($websites) . " tenants");

            // 2. Insert domains from hostnames
            $hostnames = DB::table('hostnames')
                ->join('websites', 'hostnames.website_id', '=', 'websites.id')
                ->select('hostnames.*', 'websites.id as website_id')
                ->get();

            $domainCounter = 1;

            foreach ($hostnames as $hostname) {
                // Match website -> tenant by order (same sequence)
                $tenantId = DB::table('tenants')
                    ->whereJsonContains('data->hyn_website_id', $hostname->website_id)
                    ->value('id');

                DB::table('domains')->insert([
                    'id' => $domainCounter,
                    'domain' => $hostname->fqdn,
                    'fqdn' => $hostname->fqdn,
                    'ordination_id' => $hostname->ordination_id,
                    'tenant_id' => $tenantId, // match tenants.id
                    'created_at' => $hostname->created_at,
                    'updated_at' => $hostname->updated_at,
                    'deleted_at' => $hostname->deleted_at,
                ]);

                $domainCounter++;
            }

            // $this->info/("✅ Inserted " . count($hostnames) . " domains");

            // DB::commit();
            // $this->info('🎉 Migration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Migration failed: " . $e->getMessage());
        }
    }
}
