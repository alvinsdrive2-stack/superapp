<?php

namespace App\Console\Commands;

use App\Services\DashboardCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DashboardCacheRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:cache-refresh {--force : Force refresh even if cache is not expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh all dashboard cache data for better performance';

    /**
     * The cache service instance.
     */
    protected DashboardCacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(DashboardCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $force = $this->option('force');

        $this->info('Starting dashboard cache refresh...');
        $this->info('Force mode: ' . ($force ? 'Yes' : 'No'));

        try {
            // Display cache stats before refresh
            $stats = $this->cacheService->getCacheStats();
            $this->info('Current cache status:');
            $this->info("  Total entries: {$stats['total']}");
            $this->info("  Valid entries: {$stats['valid']}");
            $this->info("  Expired entries: {$stats['expired']}");

            if ($force) {
                $this->info('Force clearing all cache...');
                $this->cacheService->clearAllCache();
            }

            // Refresh all cache
            $result = $this->cacheService->refreshAll();

            if ($result['success']) {
                $this->info('✓ Dashboard cache refreshed successfully!');
                $this->info('Execution time: ' . $result['execution_time'] . ' seconds');

                // Display refreshed items
                if (isset($result['results'])) {
                    $this->info('Refreshed items:');
                    foreach ($result['results'] as $item => $status) {
                        $this->info("  ✓ {$item}: {$status}");
                    }
                }
            } else {
                $this->error('✗ Cache refresh completed with errors:');
                $this->error('Execution time: ' . $result['execution_time'] . ' seconds');

                if (isset($result['results'])) {
                    $this->info('Successfully refreshed:');
                    foreach ($result['results'] as $item => $status) {
                        $this->info("  ✓ {$item}: {$status}");
                    }
                }

                if (isset($result['errors'])) {
                    $this->error('Failed to refresh:');
                    foreach ($result['errors'] as $item => $error) {
                        $this->error("  ✗ {$item}: {$error}");
                    }
                }
            }

            // Display cache stats after refresh
            $newStats = $this->cacheService->getCacheStats();
            $this->info('New cache status:');
            $this->info("  Total entries: {$newStats['total']}");
            $this->info("  Valid entries: {$newStats['valid']}");
            $this->info("  Expired entries: {$newStats['expired']}");

            $totalTime = round(microtime(true) - $startTime, 3);
            $this->info("Total command execution time: {$totalTime} seconds");

            // Log to application logs
            Log::info('DashboardCacheRefreshCommand executed', [
                'success' => $result['success'],
                'execution_time' => $totalTime,
                'force' => $force,
                'results' => $result['results'] ?? [],
                'errors' => $result['errors'] ?? []
            ]);

            return $result['success'] ? 0 : 1;

        } catch (\Exception $e) {
            $totalTime = round(microtime(true) - $startTime, 3);
            $this->error("✗ Cache refresh failed: " . $e->getMessage());
            $this->error("Execution time: {$totalTime} seconds");

            // Log error
            Log::error('DashboardCacheRefreshCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time' => $totalTime
            ]);

            return 1;
        }
    }
}
