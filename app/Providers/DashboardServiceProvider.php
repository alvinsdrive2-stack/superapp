<?php

namespace App\Providers;

use App\Services\Dashboard\AutoDiscoveryService;
use App\Services\Dashboard\DataSourceManager;
use App\Services\Dashboard\Processors\TimeSeriesProcessor;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register DataSource Manager as singleton
        $this->app->singleton(DataSourceManager::class, function ($app) {
            $manager = new DataSourceManager();

            // Auto-discover new data sources
            if (config('dashboard.auto_discovery.enabled', false)) {
                $discoveryService = new AutoDiscoveryService();
                $discoveryService->discoverAndRegister($manager);
            }

            return $manager;
        });

        // Register Time Series Processor
        $this->app->singleton(TimeSeriesProcessor::class);

        // Merge default configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/dashboard.php',
            'dashboard'
        );
    }

    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/dashboard.php' => config_path('dashboard.php'),
            ], 'dashboard-config');
        }
    }
}