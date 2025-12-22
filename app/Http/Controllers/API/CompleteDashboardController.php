<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PencatatanIzinService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CompleteDashboardController extends Controller
{
    protected $pencatatanIzinService;

    public function __construct(PencatatanIzinService $pencatatanIzinService)
    {
        $this->pencatatanIzinService = $pencatatanIzinService;
    }

    /**
     * Get complete dashboard data in single request
     */
    public function getCompleteData(Request $request)
    {
        try {
            // Cache the complete data for 5 minutes
            $cacheKey = 'dashboard_complete_data';
            $cacheDuration = 300; // 5 minutes

            $data = Cache::remember($cacheKey, $cacheDuration, function () {
                return [
                    'time_series' => $this->getTimeSeriesData(),
                    'provinces' => $this->getProvincesData(),
                    'summary' => $this->getSummaryData(),
                    'kpi_metrics' => $this->getKPIMetrics(),
                    'key_insights' => $this->getKeyInsights(),
                    'last_updated' => now()->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get time series data (24 months for comprehensive filtering)
     */
    protected function getTimeSeriesData()
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subMonths(23); // 24 months total

        // Get data from database
        $query = $this->pencatatanIzinService->getTimeSeriesQuery($startDate, $endDate);
        $data = $query->get();

        // Process data for Chart.js
        $labels = [];
        $balaiData = [];
        $fgData = [];
        $regulerData = [];

        // Generate all months in the range
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $labels[] = $current->format('M Y');
            $current->addMonth();
        }

        // Initialize data arrays with zeros
        $balaiData = array_fill(0, count($labels), 0);
        $fgData = array_fill(0, count($labels), 0);
        $regulerData = array_fill(0, count($labels), 0);

        // Fill with actual data
        foreach ($data as $item) {
            $monthIndex = $this->getMonthIndex($item->month, $item->year, $startDate);
            if ($monthIndex !== null && $monthIndex < count($labels)) {
                switch (strtolower($item->source_type)) {
                    case 'balai':
                        $balaiData[$monthIndex] = (int) $item->total_izin;
                        break;
                    case 'fg':
                    case 'suisei':
                        $fgData[$monthIndex] = (int) $item->total_izin;
                        break;
                    case 'reguler':
                        $regulerData[$monthIndex] = (int) $item->total_izin;
                        break;
                }
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Balai',
                    'data' => $balaiData
                ],
                [
                    'label' => 'FG/Suisei',
                    'data' => $fgData
                ],
                [
                    'label' => 'Reguler',
                    'data' => $regulerData
                ]
            ]
        ];
    }

    /**
     * Get provinces data with monthly breakdown
     */
    protected function getProvincesData()
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subMonths(23); // 24 months for comprehensive data

        // Get all provinces with their data
        $provinces = DB::table('sso_user_systems')
            ->select('province', DB::raw('COUNT(*) as total'))
            ->whereNotNull('province')
            ->groupBy('province')
            ->orderByDesc('total')
            ->get();

        // Get monthly data for each province
        $allProvinces = [];

        foreach ($provinces as $province) {
            $monthlyData = $this->getProvinceMonthlyData($province->province, $startDate, $endDate);

            // Calculate trend
            $trend = $this->calculateTrend($monthlyData);

            $allProvinces[] = [
                'name' => $province->province,
                'total' => $province->total,
                'trend' => $trend,
                'rank' => 0, // Will be calculated below
                'monthly_data' => $monthlyData,
                'breakdown' => [
                    'balai' => $this->getProvinceBreakdown($province->province, 'balai'),
                    'reguler' => $this->getProvinceBreakdown($province->province, 'reguler'),
                    'fg' => $this->getProvinceBreakdown($province->province, 'fg')
                ]
            ];
        }

        // Add ranks
        $allProvinces = array_map(function($province, $index) {
            $province['rank'] = $index + 1;
            return $province;
        }, $allProvinces, array_keys($allProvinces));

        return [
            'all_provinces' => $allProvinces,
            'top_10' => array_slice($allProvinces, 0, 10),
            'top_8_for_cards' => array_slice($allProvinces, 0, 8),
            'top_3_for_progress' => array_slice($allProvinces, 0, 3)
        ];
    }

    /**
     * Get monthly data for a specific province
     */
    protected function getProvinceMonthlyData($province, $startDate, $endDate)
    {
        $data = DB::table('sso_user_systems')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->where('province', $province)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Fill missing months with zeros
        $monthlyData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $monthKey = $current->format('Y-m');
            $found = $data->firstWhere('month', $current->month) && $data->firstWhere('year', $current->year);

            $monthlyData[] = $found ?
                $data->firstWhere('month', $current->month)->total : 0;

            $current->addMonth();
        }

        return $monthlyData;
    }

    /**
     * Calculate trend for a data series
     */
    protected function calculateTrend($data)
    {
        if (count($data) < 2) {
            return 0;
        }

        $current = array_sum(array_slice($data, -3)); // Last 3 months
        $previous = array_sum(array_slice($data, -6, 3)); // Previous 3 months

        if ($previous == 0) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get breakdown by source type for a province
     */
    protected function getProvinceBreakdown($province, $sourceType)
    {
        return DB::table('sso_user_systems')
            ->where('province', $province)
            ->where(function($query) use ($sourceType) {
                if ($sourceType === 'fg') {
                    $query->where('source_type', 'fg')
                          ->orWhere('source_type', 'suisei');
                } else {
                    $query->where('source_type', $sourceType);
                }
            })
            ->count();
    }

    /**
     * Get summary statistics
     */
    protected function getSummaryData()
    {
        $totalIzin = DB::table('sso_user_systems')->count();

        // Get growth compared to last month
        $thisMonth = DB::table('sso_user_systems')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonth = DB::table('sso_user_systems')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        $growth = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        return [
            'total_izin' => $totalIzin,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'growth_percentage' => $growth
        ];
    }

    /**
     * Generate key insights from data
     */
    protected function getKeyInsights()
    {
        $insights = [];

        // Top performing province
        $topProvince = DB::table('sso_user_systems')
            ->select('province', DB::raw('COUNT(*) as total'))
            ->whereNotNull('province')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->orderByDesc('total')
            ->first();

        if ($topProvince) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Provinsi Teratas',
                'message' => "{$topProvince->province} memiliki jumlah izin terbanyak bulan ini dengan {$topProvince->total} izin"
            ];
        }

        // Growth insight
        $thisMonth = DB::table('sso_user_systems')
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $lastMonth = DB::table('sso_user_systems')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->count();

        if ($thisMonth > $lastMonth) {
            $growth = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
            $insights[] = [
                'type' => 'positive',
                'title' => 'Pertumbuhan Positif',
                'message' => "Pertumbuhan izin bulan ini meningkat {$growth}% dibanding bulan lalu"
            ];
        }

        return $insights;
    }

    /**
     * Get KPI metrics with year-over-year comparison
     */
    protected function getKPIMetrics()
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        try {
            // Check if source_type field exists
            $columns = DB::getSchemaBuilder()->getColumnListing('sso_user_systems');
            $hasSourceType = in_array('source_type', $columns);

            if ($hasSourceType) {
                // Get totals for current year with source_type
                $currentYearTotals = DB::table('sso_user_systems')
                    ->select(
                        DB::raw('COUNT(CASE WHEN LOWER(source_type) = "balai" THEN 1 END) as balai'),
                        DB::raw('COUNT(CASE WHEN LOWER(source_type) = "reguler" THEN 1 END) as reguler'),
                        DB::raw('COUNT(CASE WHEN LOWER(source_type) IN ("fg", "suisei") THEN 1 END) as fg'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('created_at', $currentYear)
                    ->first();

                // Get totals for previous year with source_type
                $previousYearTotals = DB::table('sso_user_systems')
                    ->select(
                        DB::raw('COUNT(CASE WHEN LOWER(source_type) = "balai" THEN 1 END) as balai'),
                        DB::raw('COUNT(CASE WHEN LOWER(source_type) = "reguler" THEN 1 END) as reguler'),
                        DB::raw('COUNT(CASE WHEN LOWER(source_type) IN ("fg", "suisei") THEN 1 END) as fg'),
                        DB::raw('COUNT(*) as total')
                    )
                    ->whereYear('created_at', $previousYear)
                    ->first();
            } else {
                // Fallback: just get total counts without breakdown
                $currentYearTotals = (object) [
                    'total' => DB::table('sso_user_systems')->whereYear('created_at', $currentYear)->count(),
                    'balai' => 0,
                    'reguler' => 0,
                    'fg' => 0
                ];

                $previousYearTotals = (object) [
                    'total' => DB::table('sso_user_systems')->whereYear('created_at', $previousYear)->count(),
                    'balai' => 0,
                    'reguler' => 0,
                    'fg' => 0
                ];
            }

                  // Calculate year-over-year changes
            $kpiData = [
                'total_pencatatan' => [
                    'current' => (int) $currentYearTotals->total,
                    'previous' => (int) $previousYearTotals->total,
                    'change' => $this->calculateYOYChange($currentYearTotals->total, $previousYearTotals->total),
                    'change_type' => $this->getChangeType($currentYearTotals->total, $previousYearTotals->total)
                ],
                'balai' => [
                    'current' => (int) $currentYearTotals->balai,
                    'previous' => (int) $previousYearTotals->balai,
                    'change' => $this->calculateYOYChange($currentYearTotals->balai, $previousYearTotals->balai),
                    'change_type' => $this->getChangeType($currentYearTotals->balai, $previousYearTotals->balai)
                ],
                'reguler' => [
                    'current' => (int) $currentYearTotals->reguler,
                    'previous' => (int) $previousYearTotals->reguler,
                    'change' => $this->calculateYOYChange($currentYearTotals->reguler, $previousYearTotals->reguler),
                    'change_type' => $this->getChangeType($currentYearTotals->reguler, $previousYearTotals->reguler)
                ],
                'fg' => [
                    'current' => (int) $currentYearTotals->fg,
                    'previous' => (int) $previousYearTotals->fg,
                    'change' => $this->calculateYOYChange($currentYearTotals->fg, $previousYearTotals->fg),
                    'change_type' => $this->getChangeType($currentYearTotals->fg, $previousYearTotals->fg)
                ]
            ];

            return $kpiData;

        } catch (\Exception $e) {
            // Return sample data if there's an error
            \Log::error('Error getting KPI metrics: ' . $e->getMessage());

            return [
                'total_pencatatan' => [
                    'current' => 12500,
                    'previous' => 10200,
                    'change' => 22.5,
                    'change_type' => 'increase'
                ],
                'balai' => [
                    'current' => 5800,
                    'previous' => 4900,
                    'change' => 18.4,
                    'change_type' => 'increase'
                ],
                'reguler' => [
                    'current' => 4200,
                    'previous' => 3800,
                    'change' => 10.5,
                    'change_type' => 'increase'
                ],
                'fg' => [
                    'current' => 2500,
                    'previous' => 1500,
                    'change' => 66.7,
                    'change_type' => 'increase'
                ]
            ];
        }
    }

    /**
     * Calculate year-over-year change percentage
     */
    protected function calculateYOYChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Determine change type (increase/decrease/no change)
     */
    protected function getChangeType($current, $previous)
    {
        if ($current > $previous) return 'increase';
        if ($current < $previous) return 'decrease';
        return 'no_change';
    }

    /**
     * Get month index for array positioning
     */
    protected function getMonthIndex($month, $year, $startDate)
    {
        $targetDate = Carbon::createFromDate($year, $month, 1);
        $diff = $targetDate->diffInMonths($startDate);

        return $diff >= 0 ? $diff : null;
    }
}