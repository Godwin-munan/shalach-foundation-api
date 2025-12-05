<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure web.php is still registered (Laravel normally does this elsewhere,
        // but adding it here is safe and explicit)
        Route::middleware('web')
            ->group(base_path('routes/web.php'));

        // Register the API routes with 'api' middleware and '/api' prefix
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }
}
