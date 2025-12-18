<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\PencatatanIzinService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $pencatatanService;

    public function __construct(DashboardService $dashboardService, PencatatanIzinService $pencatatanService = null)
    {
        $this->dashboardService = $dashboardService;
        $this->pencatatanService = $pencatatanService;
    }

    /**
     * Get pencatatan izin data for dashboard chart
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPencatatanIzinData(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 6);

            // Validate months parameter
            if (!is_numeric($months) || $months < 1 || $months > 12) {
                $months = 6;
            }

            Log::info("Fetching pencatatan izin data for {$months} months");
            $data = $this->pencatatanService->getMonthlyCount((int) $months);
            Log::info("Successfully fetched pencatatan izin data");

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching pencatatan izin data: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Get summary statistics for pencatatan izin
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPencatatanIzinSummary(Request $request): JsonResponse
    {
        try {
            $stats = $this->pencatatanService->getSummaryStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching pencatatan izin summary: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary data'
            ], 500);
        }
    }

    /**
     * Get province statistics for current month
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvinceStats(Request $request)
    {
        try {
            $month = $request->get('month');

            // Validate month parameter
            if (empty($month)) {
                $month = now()->format('F Y');
            }

            // Validate month format (e.g., "December 2025")
            if (!preg_match('/^[A-Za-z]+ \d{4}$/', $month)) {
                Log::warning("Invalid month format: {$month}, using current month");
                $month = now()->format('F Y');
            }

            $stats = $this->pencatatanService->getProvinceStats($month);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'available_months' => $this->getAvailableMonths()
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting province stats: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data provinsi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly province chart data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyProvinceChart()
    {
        try {
            Log::info("Fetching monthly province chart data");
            $chartData = $this->pencatatanService->getMonthlyProvinceChart();
            Log::info("Successfully fetched monthly province chart data");

            return response()->json([
                'success' => true,
                'data' => $chartData
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting monthly province chart: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data chart provinsi: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Get available months for dropdown
     *
     * @return array
     */
    private function getAvailableMonths()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('F Y');
        }
        return $months;
    }

    /**
     * Get comprehensive dashboard overview
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 6);

            $result = $this->dashboardService->getOverview($months);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Dashboard overview retrieved successfully' : 'Some dashboard data could not be retrieved',
                'data' => $result['data'],
                'errors' => $result['errors'] ?? []
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
     * Get total counts from all databases
     */
    public function totalCounts(): JsonResponse
    {
        try {
            $result = $this->dashboardService->getTotalCounts();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Total counts retrieved successfully' : 'Failed to retrieve some counts',
                'data' => $result['data']
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
     * Get pencatatan izin time series data
     */
    public function pencatatanIzinTimeSeries(Request $request): JsonResponse
    {
        try {
            // Jika parameter 'months' ada, gunakan itu. Jika tidak, pass null untuk default behavior
            $months = $request->has('months') ? max(1, min(12, (int)$request->get('months'))) : null;

            $result = $this->dashboardService->getPencatatanIzinTimeSeries($months);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Time series data retrieved successfully' : 'Failed to retrieve some time series data',
                'data' => $result['data']
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve time series data: ' . $e->getMessage(),
                'data' => [
                    'labels' => [],
                    'datasets' => [
                        'balai' => [],
                        'reguler' => [],
                        'fg' => []
                    ]
                ]
            ], 500);
        }
    }

    /**
     * Get province ranking data
     */
    public function provinceRanking(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $limit = max(1, min(50, (int)$limit)); // Validate between 1-50 provinces

            $result = $this->dashboardService->getProvinceRanking($limit);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Province ranking retrieved successfully' : 'Failed to retrieve some province data',
                'data' => $result['data']
            ], $result['success'] ? 200 : 207);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve province ranking: ' . $e->getMessage(),
                'data' => [
                    'rankings' => [],
                    'total_provinces' => 0
                ]
            ], 500);
        }
    }

    /**
     * Get daily statistics
     */
    public function dailyStats(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $days = max(1, min(90, (int)$days)); // Validate between 1-90 days

            $result = $this->dashboardService->getDailyStats($days);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Daily statistics retrieved successfully' : 'Failed to retrieve some daily statistics',
                'data' => $result['data']
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
     * Test database connections
     */
    public function testConnections(): JsonResponse
    {
        try {
            $result = $this->dashboardService->testDatabaseConnections();

            return response()->json([
                'success' => true,
                'message' => 'Database connection test completed',
                'data' => $result['data']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test database connections: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get chart data formatted for frontend
     */
    public function chartData(Request $request): JsonResponse
    {
        try {
            $months = $request->get('months', 6);
            $months = max(1, min(12, (int)$months));

            $timeSeriesResult = $this->dashboardService->getPencatatanIzinTimeSeries($months);
            $totalResult = $this->dashboardService->getTotalCounts();

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
                'data' => $chartData
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
     * Get year vs year comparison data
     */
    public function yearComparison(Request $request): JsonResponse
    {
        try {
            $currentYear = $request->get('year', date('Y'));
            $previousYear = $request->get('previousYear', (int)$currentYear - 1);

            // Validate years
            $currentYear = max(2020, min(date('Y'), (int)$currentYear));
            $previousYear = max(2020, min($currentYear - 1, (int)$previousYear));

            $result = $this->dashboardService->getYearComparisonData($currentYear, $previousYear);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Year comparison data retrieved successfully' : 'Failed to retrieve year comparison data',
                'data' => $result['data']
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
     * Get monthly comparison data (current month vs previous month)
     */
    public function monthlyComparison(Request $request): JsonResponse
    {
        try {
            $result = $this->dashboardService->getMonthlyComparisonData();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Monthly comparison data retrieved successfully' : 'Failed to retrieve monthly comparison data',
                'data' => $result['data']
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
}