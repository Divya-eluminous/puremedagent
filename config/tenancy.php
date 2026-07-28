<?php

return [
    'central_domains' => [
        'localhost',
        '127.0.0.1',
        env('APP_DOMAIN', 'localhost'),
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class, // Disabled to prevent cache invalidation errors
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Optional
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),

        'template_tenant_connection' => null,

        'prefix' => 'tenant',
        'suffix' => '',

        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        // Disks which should be suffixed with the tenant key.
        'disks' => [
            'local',
            'public',
            // 's3',
        ],

        'root_override' => [
            // Disks whose roots should be overriden after storage_path() is suffixed.
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'asset_helper_tenancy' => true,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            'default',
            'cache',
        ],
    ],

    'routes' => [
        'path' => base_path('routes/tenant.php'),

        'middleware' => [
            'web',
            'tenancy.enforce',
        ],
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder',
        // '--force' => true,
    ],

    'queue' => [
        'central_connection' => env('QUEUE_CONNECTION', 'sync'),

        'unique_handler' => Stancl\Tenancy\Jobs\TenantAware::class,
    ],

    'storage_drivers' => [
        'db' => [
            'data_column' => 'data',
            'connection' => null,
        ],
    ],

    'domain_model' => App\Models\Domain::class,

    'tenant_model' => App\Models\Tenant::class,

    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'exempt_domains' => [
        // 'api.example.com',
    ],

    'database_managers' => [
        'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
        'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
    ],

    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
        // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
    ],
]; 