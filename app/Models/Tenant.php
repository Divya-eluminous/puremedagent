<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\InvalidatesTenantsResolverCache;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'tenant_id',
        'ordination_id',
        'ordination_name',
        'calendar_id',
        'data',
        'uuid',
        'tenancy_db_name',
    ];

    protected $casts = [
        'data' => 'array',
        'uuid' => 'string',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'tenant_id',
            'ordination_id',
            'ordination_name', 
            'calendar_id',
        ];
    }
    
    /**
     * Override the cache invalidation to prevent errors
     */
    protected static function invalidateTenantsResolverCache($tenant = null)
    {
        // Disable cache invalidation to prevent the TypeError
        // This is a workaround for the Stancl Tenancy cache invalidation bug
        return;
    }
} 