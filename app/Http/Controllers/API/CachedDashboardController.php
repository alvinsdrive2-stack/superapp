<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\DashboardController;
use App\Services\DashboardCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CachedDashboardController extends DashboardController
{
    protected $cacheService;

    public function __construct(
        \App\Services\DashboardService $dashboardService,
        DashboardCacheService $cacheService,
        \App\Services\PencatatanIzinService $pencatatanService = null
    ) {
        parent::__construct($dashboardService, $cacheService, $pencatatanService);
        $this->cacheService = $cacheService;
    }

    /**
     * Get year comparison data (with cache)
     */
    public function yearComparison(Request $request): JsonResponse
    {
        try {
            $currentYear = $request->get('year', date('Y'));
            $previousYear = $request->get('previousYear', (int)$currentYear - 1);

            // Validate years
            $currentYear = max(2020, min(date('Y'), (int)$currentYear));
            $previousYear = max(2020, min($currentYear - 1, (int)$previousYear));

            // Get from cache service
            $result = $this->cacheService->getYearComparison($currentYear, $previousYear);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Year comparison data retrieved successfully' : 'Failed to retrieve year comparison data',
                'data' => $result['data'],
                'from_cache' => $result['from_cache'] ?? false
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve year comparison data: ' . $e->getMessage(),
                'data' => [
                    'labels' => [],
                    'datasets' => []
                ]
            ], 500);
        }
    }

    /**
     * Get monthly comparison data (with cache)
     */
    public function monthlyComparison(Request $request): JsonResponse
    {
        try {
            // Get from cache service
            $result = $this->cacheService->getMonthlyComparison();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Monthly comparison data retrieved successfully' : 'Failed to retrieve monthly comparison data',
                'data' => $result['data'],
                'from_cache' => $result['from_cache'] ?? false
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve monthly comparison data: ' . $e->getMessage(),
                'data' => [
                    'labels' => [],
                    'datasets' => []
                ]
            ], 500);
        }
    }

    /**
     * Get comprehensive dashboard overview (with cache)
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 6);

            // Get from cache service
            $result = $this->cacheService->getOverview($months);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Dashboard overview retrieved successfully' : 'Some dashboard data could not be retrieved',
                'data' => $result['data'],
                'errors' => $result['errors'] ?? [],
                'from_cache' => $result['from_cache'] ?? false
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard overview: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get total counts from all databases (with cache)
     */
    public function totalCounts(): JsonResponse
    {
        try {
            // Get from cache service
            $result = $this->cacheService->getTotals();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Total counts retrieved successfully' : 'Failed to retrieve some counts',
                'data' => $result['data'],
                'from_cache' => $result['from_cache'] ?? false
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total counts: ' . $e->getMessage(),
                'data' => [
                    'total' => 0,
                    'balai' => 0,
                    'reguler' => 0,
                    'fg' => 0
                ]
            ], 500);
        }
    }

    /**
     * Get daily statistics (with cache)
     */
    public function dailyStats(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $days = max(1, min(90, (int)$days)); // Validate between 1-90 days

            // Get from cache service
            $result = $this->cacheService->getDailyStats($days);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Daily statistics retrieved successfully' : 'Failed to retrieve some daily statistics',
                'data' => $result['data'],
                'from_cache' => $result['from_cache'] ?? false
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve daily statistics: ' . $e->getMessage(),
                'data' => [
                    'daily_stats' => []
                ]
            ], 500);
        }
    }

    /**
     * Get chart data formatted for frontend (with cache)
     */
    public function chartData(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 6);
            $months = max(1, min(12, (int)$months));

            // Get data from cache services
            $timeSeriesResult = $this->cacheService->getTimeSeries($months);
            $totalResult = $this->cacheService->getTotals();

            if (!$timeSeriesResult['success'] || !$totalResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve chart data',
                    'data' => []
                ], 207);
            }

            // Format data for Chart.js
            $chartData = [
                'timeSeries' => [
                    'labels' => $timeSeriesResult['data']['labels'],
                    'datasets' => [
                        [
                            'label' => 'Balai',
                            'data' => $timeSeriesResult['data']['datasets']['balai'],
                            'borderColor' => '#3B82F6',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                            'borderWidth' => 2,
                            'fill' => true,
                            'tension' => 0.4
                        ],
                        [
                            'label' => 'Reguler',
                            'data' => $timeSeriesResult['data']['datasets']['reguler'],
                            'borderColor' => '#10B981',
                            'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                            'borderWidth' => 2,
                            'fill' => true,
                            'tension' => 0.4
                        ],
                        [
                            'label' => 'FG',
                            'data' => $timeSeriesResult['data']['datasets']['fg'],
                            'borderColor' => '#F59E0B',
                            'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                            'borderWidth' => 2,
                            'fill' => true,
                            'tension' => 0.4
                        ]
                    ]
                ],
                'summary' => [
                    'total' => $totalResult['data']['total'],
                    'byDatabase' => [
                        'balai' => $totalResult['data']['balai'],
                        'reguler' => $totalResult['data']['reguler'],
                        'fg' => $totalResult['data']['fg']
                    ]
                ],
                'dateRange' => $timeSeriesResult['data']['date_range']
            ];

            return response()->json([
                'success' => true,
                'message' => 'Chart data retrieved successfully',
                'data' => $chartData,
                'from_cache' => ($timeSeriesResult['from_cache'] ?? false) && ($totalResult['from_cache'] ?? false)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chart data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Clear all dashboard cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $cleared = $this->cacheService->clearAllCache();

            return response()->json([
                'success' => $cleared,
                'message' => $cleared ? 'Dashboard cache cleared successfully' : 'Failed to clear dashboard cache'
            ], $cleared ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): JsonResponse
    {
        try {
            $stats = $this->cacheService->getCacheStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cache stats: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}