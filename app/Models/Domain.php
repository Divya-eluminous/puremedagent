<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

class Domain extends BaseDomain
{
    protected $fillable = [
        'domain',
        'tenant_id',
        'ordination_id',
        'fqdn',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
} 