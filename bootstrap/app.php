<?php

use App\Http\Middleware\AnyRole;
use Illuminate\Foundation\Application;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IsUser;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\IsAdmin;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            \Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            \Illuminate\Http\Middleware\TrustProxies::class,
            HandleCors::class, // <-- ini yang penting
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \App\Http\Middleware\AssetLinksMiddleware::class,
        ]);

        $middleware->alias([
            'is_user' => IsUser::class,
            'checkRole' => CheckRole::class,
            'is_superadmin' => IsSuperAdmin::class,
            'is_admin' => IsAdmin::class,
            'anyrole' => AnyRole::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
