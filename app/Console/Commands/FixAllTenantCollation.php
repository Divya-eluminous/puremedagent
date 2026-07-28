<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\Tenant;

class FixAllTenantCollation extends Command
{
    // php artisan tenants:fix-all-collation
    protected $signature = 'tenants:fix-all-collation';
    protected $description = 'Convert ALL tenant databases & tables to utf8mb4_unicode_ci safely';

    public function handle()
    {
        $this->info('🚀 Starting charset + collation fix for ALL tenant databases...');

        \App\Models\Tenant::all()->runForEach(function (Tenant $tenant) {
            $db = $tenant->database()->getName();
            $this->info("🔹 Fixing tenant DB: {$db}");

            try {
                // Step 1: Fix database collation
                DB::statement("ALTER DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Step 2: Loop all tables
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $tableName = array_values((array) $table)[0];

                    $current = DB::selectOne("
                        SELECT CCSA.character_set_name, CCSA.collation_name
                        FROM information_schema.TABLES T
                        JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA 
                          ON CCSA.collation_name = T.table_collation
                        WHERE T.table_schema = ? AND T.table_name = ?
                    ", [$db, $tableName]);

                    if (strpos($current->collation_name, 'utf8mb4') === false) {
                        $this->line("   ➡️ Converting $tableName from {$current->collation_name} to utf8mb4_unicode_ci");
                        DB::statement("ALTER TABLE `$tableName` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    } else {
                        $this->line("   ✅ $tableName already uses utf8mb4 ({$current->collation_name})");
                    }
                }

            } catch (\Exception $e) {
                $this->error("❌ Failed to fix DB {$db}: " . $e->getMessage());
            }
        });

        $this->info("🎉 DONE: All tenant DBs checked and converted where necessary.");
    }
}
