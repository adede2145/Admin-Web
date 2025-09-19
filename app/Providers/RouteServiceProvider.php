<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Default home path (fallback).
     *
     * @var string
     */
    public const HOME = '/dashboard'; // ✅ fixes "undefined constant"

    /**
     * Role-based redirect after login.
     */
    public static function redirectPath()
    {
        $user = auth()->user();

        // Check if user has a role and is a superadmin
        if ($user && $user->role && $user->role->role_name === 'superadmin') {
            return route('admin.panel'); // Use named route for admin panel
        }

        return self::HOME; // Regular users go to dashboard
    }

    /**
     * Register routes.
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure rate limiting.
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
