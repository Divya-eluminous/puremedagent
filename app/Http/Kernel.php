<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Localization::class,
            \App\Http\Middleware\EnforceTenancy::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
            \App\Http\Middleware\EnforceTenancy::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        // Admin
        'AdminGeneral' => \App\Http\Middleware\Admin\General::class,
        'AdminAuthenticate' => \App\Http\Middleware\Admin\Authenticate::class,
        'AdminRedirectIfAuthenticated' => \App\Http\Middleware\Admin\RedirectIfAuthenticated::class,
        'SessionTimeout' => \App\Http\Middleware\Admin\SessionTimeout::class,
        'EnforceTenancy' => \App\Http\Middleware\EnforceTenancy::class,
        // Spati Role and permissions
        'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
        // 'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,//Roshani added this when laravel version upgraded on 18-08-2025 remove s of middleware bcz after upgrade it is not working properly.
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class, 

        // API MIDDELWARE
        'ApiCustomKeyToken' => \App\Http\Middleware\Api\ApiCustomKeyToken::class,
        'jwt.verify' => \App\Http\Middleware\Api\JwtMiddleware::class,
       

    ];
}
