<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureTenantScope;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Redirect unauthenticated users to login
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));

        // Add security headers to all responses
        $middleware->append(SecurityHeaders::class);

        // Tenant scope middleware alias
        $middleware->alias([
            'tenant'  => EnsureTenantScope::class,
            'role'    => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for API routes
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Não autenticado.'], 401);
            }
            return redirect()->route('login');
        });

        // Hide sensitive details in production
        $exceptions->render(function (\Exception $e, Request $request) {
            if (app()->environment('production') && $request->expectsJson()) {
                return response()->json(['error' => 'Erro interno do servidor.'], 500);
            }
        });
    })->create();
