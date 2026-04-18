<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{URL, Schema};

class AppServiceProvider extends ServiceProvider {
    public function register(): void {}
    public function boot(): void {
        // Force HTTPS in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        // Default string length for MySQL compatibility
        Schema::defaultStringLength(191);
    }
}
