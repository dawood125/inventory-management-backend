<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadModuleRoutes();
    }

    /**
     * Load routes from all modules
     */
    protected function loadModuleRoutes(): void
    {
        $modules = [
            'Auth',
            'Product',
            'Category',
            'Supplier',
            'Order',
            'Stock',
            'Report',
        ];

        foreach ($modules as $module) {
            $routePath = app_path("Modules/{$module}/Routes/api.php");

            if (file_exists($routePath)) {
                Route::prefix('api')
                    ->middleware('api')
                    ->group($routePath);
            }
        }
    }
}
