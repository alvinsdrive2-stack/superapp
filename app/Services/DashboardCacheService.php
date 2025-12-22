<?php

namespace App\Services;

use App\Models\DashboardCache;
use App\Models\CacheSchedulerLog;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class DashboardCacheService
{
    protected DashboardService $dashboardService;

    // Cache keys
    const CACHE_KEYS = [
        'kpis' => 'dashboard_kpis',
        'kpis_previous' => 'dashboard_kpis_previous',
        'time_series' => 'dashboard_time_series',
        'year_comparison' => 'dashboard_year_comparison',
        'monthly_comparison' => 'dashboard_monthly_comparison',
        'province_ranking' => 'dashboard_province_ranking',
        'daily_stats' => 'dashboard_daily_stats',
        'totals' => 'dashboard_totals',
        'overview' => 'dashboard_overview'
    ];

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get cached data or fetch from source
     */
    public function get(string $key, callable $fetcher, int $minutes = 10): array
    {
        try {
            // Try to get from cache first
            $cached = DashboardCache::getValid($key);

            if ($cached) {
                Log::info("Cache hit for key: {$key}");
                return [
                    'success' => true,
                    'data' => $cached->cache_data,
                    'from_cache' => true
                ];
            }

            // Cache miss, fetch fresh data
            Log::info("Cache miss for key: {$key}, fetching fresh data");
            $result = $fetcher();

            if ($result['success']) {
                // Store in cache
                DashboardCache::put($key, $result['data'], $minutes);
                Log::info("Data cached for key: {$key}");
            }

            return $result;

        } catch (Exception $e) {
            Log::error("Error getting data for key {$key}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Refresh all cache data
     */
    public function refreshAll(): array
    {
        $startTime = microtime(true);
        $results = [];
        $errors = [];

        try {
            Log::info("Starting dashboard cache refresh");

            // 1. Refresh KPIs (current year)
            try {
                $kpisData = $this->fetchKpisData();
                DashboardCache::put(self::CACHE_KEYS['kpis'], $kpisData['data'], 10);
                $results['kpis'] = 'success';
                Log::info("KPIs cache refreshed");
            } catch (Exception $e) {
                $errors['kpis'] = $e->getMessage();
                Log::error("Failed to refresh KPIs cache: " . $e->getMessage());
            }

            // 2. Refresh KPIs previous year
            try {
                $kpisPreviousData = $this->fetchKpisPreviousYearData();
                DashboardCache::put(self::CACHE_KEYS['kpis_previous'], $kpisPreviousData['data'], 10);
                $results['kpis_previous'] = 'success';
                Log::info("KPIs previous year cache refreshed");
            } catch (Exception $e) {
                $errors['kpis_previous'] = $e->getMessage();
                Log::error("Failed to refresh KPIs previous year cache: " . $e->getMessage());
            }

            // 3. Refresh Time Series
            try {
                $timeSeriesData = $this->dashboardService->getPencatatanIzinTimeSeries(12);
                DashboardCache::put(self::CACHE_KEYS['time_series'], $timeSeriesData['data'], 10);
                $results['time_series'] = 'success';
                Log::info("Time series cache refreshed");
            } catch (Exception $e) {
                $errors['time_series'] = $e->getMessage();
                Log::error("Failed to refresh time series cache: " . $e->getMessage());
            }

            // 4. Refresh Year Comparison
            try {
                $currentYear = now()->year;
                $previousYear = $currentYear - 1;
                $yearComparisonData = $this->dashboardService->getYearComparisonData($currentYear, $previousYear);
                DashboardCache::put(self::CACHE_KEYS['year_comparison'], $yearComparisonData['data'], 10);
                $results['year_comparison'] = 'success';
                Log::info("Year comparison cache refreshed");
            } catch (Exception $e) {
                $errors['year_comparison'] = $e->getMessage();
                Log::error("Failed to refresh year comparison cache: " . $e->getMessage());
            }

            // 5. Refresh Monthly Comparison
            try {
                $monthlyComparisonData = $this->dashboardService->getMonthlyComparisonData();
                DashboardCache::put(self::CACHE_KEYS['monthly_comparison'], $monthlyComparisonData['data'], 10);
                $results['monthly_comparison'] = 'success';
                Log::info("Monthly comparison cache refreshed");
            } catch (Exception $e) {
                $errors['monthly_comparison'] = $e->getMessage();
                Log::error("Failed to refresh monthly comparison cache: " . $e->getMessage());
            }

            // 6. Refresh Province Ranking
            try {
                $provinceRankingData = $this->dashboardService->getProvinceRanking(10);
                DashboardCache::put(self::CACHE_KEYS['province_ranking'], $provinceRankingData['data'], 10);
                $results['province_ranking'] = 'success';
                Log::info("Province ranking cache refreshed");
            } catch (Exception $e) {
                $errors['province_ranking'] = $e->getMessage();
                Log::error("Failed to refresh province ranking cache: " . $e->getMessage());
            }

            // 7. Refresh Daily Stats
            try {
                $dailyStatsData = $this->dashboardService->getDailyStats(30);
                DashboardCache::put(self::CACHE_KEYS['daily_stats'], $dailyStatsData['data'], 10);
                $results['daily_stats'] = 'success';
                Log::info("Daily stats cache refreshed");
            } catch (Exception $e) {
                $errors['daily_stats'] = $e->getMessage();
                Log::error("Failed to refresh daily stats cache: " . $e->getMessage());
            }

            // 8. Refresh Totals
            try {
                $totalsData = $this->dashboardService->getTotalCounts();
                DashboardCache::put(self::CACHE_KEYS['totals'], $totalsData['data'], 10);
                $results['totals'] = 'success';
                Log::info("Totals cache refreshed");
            } catch (Exception $e) {
                $errors['totals'] = $e->getMessage();
                Log::error("Failed to refresh totals cache: " . $e->getMessage());
            }

            // 9. Refresh Overview
            try {
                $overviewData = $this->dashboardService->getOverview(6);
                DashboardCache::put(self::CACHE_KEYS['overview'], $overviewData['data'], 10);
                $results['overview'] = 'success';
                Log::info("Overview cache refreshed");
            } catch (Exception $e) {
                $errors['overview'] = $e->getMessage();
                Log::error("Failed to refresh overview cache: " . $e->getMessage());
            }

            $executionTime = round(microtime(true) - $startTime, 3);
            $success = count($results) === count(self::CACHE_KEYS);
            $status = $success ? CacheSchedulerLog::STATUS_SUCCESS :
                     (count($results) > 0 ? CacheSchedulerLog::STATUS_PARTIAL : CacheSchedulerLog::STATUS_FAILED);

            // Log the execution
            CacheSchedulerLog::logSuccess('dashboard_cache_refresh', $executionTime, [
                'refreshed_items' => $results,
                'errors' => $errors
            ]);

            Log::info("Dashboard cache refresh completed in {$executionTime}s. Success: " . count($results) . ", Errors: " . count($errors));

            // Clear expired cache entries
            $this->clearExpiredCache();

            return [
                'success' => $success,
                'results' => $results,
                'errors' => $errors,
                'execution_time' => $executionTime
            ];

        } catch (Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 3);
            CacheSchedulerLog::logFailure('dashboard_cache_refresh', $e->getMessage(), $executionTime);

            Log::error("Dashboard cache refresh failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime
            ];
        }
    }

    /**
     * Get KPIs data (with cache)
     */
    public function getKpis(): array
    {
        return $this->get(self::CACHE_KEYS['kpis'], [$this, 'fetchKpisData']);
    }

    /**
     * Get Time Series data (with cache)
     */
    public function getTimeSeries(int $months = null): array
    {
        return $this->get(self::CACHE_KEYS['time_series'], function() use ($months) {
            return $this->dashboardService->getPencatatanIzinTimeSeries($months);
        });
    }

    /**
     * Get Year Comparison data (with cache)
     */
    public function getYearComparison(int $currentYear = null, int $previousYear = null): array
    {
        $currentYear = $currentYear ?? now()->year;
        $previousYear = $previousYear ?? $currentYear - 1;

        return $this->get(self::CACHE_KEYS['year_comparison'], function() use ($currentYear, $previousYear) {
            return $this->dashboardService->getYearComparisonData($currentYear, $previousYear);
        });
    }

    /**
     * Get Monthly Comparison data (with cache)
     */
    public function getMonthlyComparison(): array
    {
        return $this->get(self::CACHE_KEYS['monthly_comparison'], [$this->dashboardService, 'getMonthlyComparisonData']);
    }

    /**
     * Get Province Ranking data (with cache)
     */
    public function getProvinceRanking(int $limit = 10): array
    {
        return $this->get(self::CACHE_KEYS['province_ranking'], function() use ($limit) {
            return $this->dashboardService->getProvinceRanking($limit);
        });
    }

    /**
     * Get Daily Stats data (with cache)
     */
    public function getDailyStats(int $days = 30): array
    {
        return $this->get(self::CACHE_KEYS['daily_stats'], function() use ($days) {
            return $this->dashboardService->getDailyStats($days);
        });
    }

    /**
     * Get Totals data (with cache)
     */
    public function getTotals(): array
    {
        return $this->get(self::CACHE_KEYS['totals'], [$this->dashboardService, 'getTotalCounts']);
    }

    /**
     * Get Overview data (with cache)
     */
    public function getOverview(int $months = 6): array
    {
        return $this->get(self::CACHE_KEYS['overview'], function() use ($months) {
            return $this->dashboardService->getOverview($months);
        });
    }

    /**
     * Fetch KPIs data (raw)
     */
    protected function fetchKpisData(): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        // Get counts from each database like the dashboard does
        $currentCounts = $this->getCountsFromAllDatabases($currentYear);
        $previousCounts = $this->getCountsFromAllDatabases($previousYear);

        $kpiData = [
            'total_pencatatan' => [
                'current' => $currentCounts['total'],
                'previous' => $previousCounts['total'],
                'change' => $this->calculateChange($currentCounts['total'], $previousCounts['total']),
                'change_type' => $this->getChangeType($currentCounts['total'], $previousCounts['total'])
            ],
            'balai' => [
                'current' => $currentCounts['balai'],
                'previous' => $previousCounts['balai'],
                'change' => $this->calculateChange($currentCounts['balai'], $previousCounts['balai']),
                'change_type' => $this->getChangeType($currentCounts['balai'], $previousCounts['balai'])
            ],
            'reguler' => [
                'current' => $currentCounts['reguler'],
                'previous' => $previousCounts['reguler'],
                'change' => $this->calculateChange($currentCounts['reguler'], $previousCounts['reguler']),
                'change_type' => $this->getChangeType($currentCounts['reguler'], $previousCounts['reguler'])
            ],
            'fg' => [
                'current' => $currentCounts['fg'],
                'previous' => $previousCounts['fg'],
                'change' => $this->calculateChange($currentCounts['fg'], $previousCounts['fg']),
                'change_type' => $this->getChangeType($currentCounts['fg'], $previousCounts['fg'])
            ]
        ];

        return [
            'success' => true,
            'data' => $kpiData
        ];
    }

    /**
     * Fetch KPIs previous year data (raw)
     */
    protected function fetchKpisPreviousYearData(): array
    {
        $currentYear = now()->year - 1;
        $previousYear = $currentYear - 1;

        // Get counts from each database for previous year
        $currentCounts = $this->getCountsFromAllDatabases($currentYear);
        $previousCounts = $this->getCountsFromAllDatabases($previousYear);

        $kpiData = [
            'total_pencatatan' => [
                'current' => $currentCounts['total'],
                'previous' => $previousCounts['total'],
                'change' => $this->calculateChange($currentCounts['total'], $previousCounts['total']),
                'change_type' => $this->getChangeType($currentCounts['total'], $previousCounts['total'])
            ],
            'balai' => [
                'current' => $currentCounts['balai'],
                'previous' => $previousCounts['balai'],
                'change' => $this->calculateChange($currentCounts['balai'], $previousCounts['balai']),
                'change_type' => $this->getChangeType($currentCounts['balai'], $previousCounts['balai'])
            ],
            'reguler' => [
                'current' => $currentCounts['reguler'],
                'previous' => $previousCounts['reguler'],
                'change' => $this->calculateChange($currentCounts['reguler'], $previousCounts['reguler']),
                'change_type' => $this->getChangeType($currentCounts['reguler'], $previousCounts['reguler'])
            ],
            'fg' => [
                'current' => $currentCounts['fg'],
                'previous' => $previousCounts['fg'],
                'change' => $this->calculateChange($currentCounts['fg'], $previousCounts['fg']),
                'change_type' => $this->getChangeType($currentCounts['fg'], $previousCounts['fg'])
            ]
        ];

        return [
            'success' => true,
            'data' => $kpiData
        ];
    }

    /**
     * Get counts from all 3 databases
     */
    protected function getCountsFromAllDatabases($year): array
    {
        try {
            $results = [
                'balai' => 0,
                'reguler' => 0,
                'fg' => 0
            ];

            // Get counts from each database
            $results['balai'] = $this->getCountFromDatabase('mysql_balai', $year);
            $results['reguler'] = $this->getCountFromDatabase('mysql_reguler', $year);
            $results['fg'] = $this->getCountFromDatabase('mysql_fg', $year);

            // Calculate total
            $results['total'] = $results['balai'] + $results['reguler'] + $results['fg'];

            return $results;

        } catch (Exception $e) {
            Log::error('Error getting counts from databases: ' . $e->getMessage());

            return [
                'balai' => 0,
                'reguler' => 0,
                'fg' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Get count from specific database
     */
    protected function getCountFromDatabase($database, $year): int
    {
        try {
            return DB::connection($database)
                ->table('data_pencatatans')
                ->whereYear('tanggal_ditetapkan', $year)
                ->count();
        } catch (Exception $e) {
            Log::error("Error getting count from {$database}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate percentage change
     */
    protected function calculateChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get change type
     */
    protected function getChangeType($current, $previous): string
    {
        if ($current > $previous) return 'increase';
        if ($current < $previous) return 'decrease';
        return 'no_change';
    }

    /**
     * Clear expired cache entries
     */
    protected function clearExpiredCache(): void
    {
        try {
            $cleared = DashboardCache::clearExpired();
            if ($cleared > 0) {
                Log::info("Cleared {$cleared} expired cache entries");
            }
        } catch (Exception $e) {
            Log::error("Error clearing expired cache: " . $e->getMessage());
        }
    }

    /**
     * Clear specific cache key
     */
    public function clearCache(string $key): bool
    {
        try {
            DashboardCache::where('cache_key', $key)->delete();
            Log::info("Cache cleared for key: {$key}");
            return true;
        } catch (Exception $e) {
            Log::error("Error clearing cache for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all dashboard cache
     */
    public function clearAllCache(): bool
    {
        try {
            $deleted = DashboardCache::whereIn('cache_key', array_values(self::CACHE_KEYS))->delete();
            Log::info("Cleared {$deleted} dashboard cache entries");
            return true;
        } catch (Exception $e) {
            Log::error("Error clearing all dashboard cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $total = DashboardCache::whereIn('cache_key', array_values(self::CACHE_KEYS))->count();
            $expired = DashboardCache::whereIn('cache_key', array_values(self::CACHE_KEYS))
                ->where('expires_at', '<=', now())
                ->count();
            $valid = $total - $expired;

            return [
                'total' => $total,
                'valid' => $valid,
                'expired' => $expired,
                'cache_keys' => self::CACHE_KEYS
            ];
        } catch (Exception $e) {
            Log::error("Error getting cache stats: " . $e->getMessage());
            return [
                'total' => 0,
                'valid' => 0,
                'expired' => 0,
                'cache_keys' => self::CACHE_KEYS
            ];
        }
    }
}