<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class MigrationHelper
{
    /**
     * Ensure system database connection points to the correct database
     * This is needed because Stancl tenancy affects all database connections
     */
    public static function ensureSystemConnection()
    {
        // Get the correct system database name
        $systemDbName = env('DB_DATABASE', 'puredoc_stage');
        
        // Set the system connection to use the correct database
        Config::set('database.connections.system.database', $systemDbName);
        
        // Purge and reconnect to ensure the connection is refreshed
        DB::purge('system');
        DB::reconnect('system');
        
        return $systemDbName;
    }
    
    /**
     * Get data from system database with proper connection handling
     */
    public static function getFromSystem($table, $conditions = [])
    {
        // Ensure system connection is correct
        self::ensureSystemConnection();
        
        $query = DB::connection('system')->table($table);
        
        // Apply conditions if provided
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }
        
        return $query->get();
    }
    
    /**
     * Get single record from system database with proper connection handling
     */
    public static function getFirstFromSystem($table, $conditions = [])
    {
        // Ensure system connection is correct
        self::ensureSystemConnection();
        
        $query = DB::connection('system')->table($table);
        
        // Apply conditions if provided
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }
        
        return $query->first();
    }
}
