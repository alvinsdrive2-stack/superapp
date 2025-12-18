<?php

namespace App\Services\Dashboard;

use App\Services\Dashboard\DataSourceManager;
use App\Services\Dashboard\Processors\TimeSeriesProcessor;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardAggregator
{
    protected DataSourceManager $dataSourceManager;
    protected TimeSeriesProcessor $timeSeriesProcessor;
    protected array $defaultOptions = [
        'cache_duration' => 300, // 5 minutes
        'max_provinces' => 10,
        'include_comparison' => false,
        'comparison_periods' => 1,
        'forecast_periods' => 3
    ];

    public function __construct(
        DataSourceManager $dataSourceManager,
        TimeSeriesProcessor $timeSeriesProcessor
    ) {
        $this->dataSourceManager = $dataSourceManager;
        $this->timeSeriesProcessor = $timeSeriesProcessor;
    }

    /**
     * Get complete dashboard data
     */
    public function getDashboardData(array $params = []): array
    {
        $cacheKey = $this->generateCacheKey($params);
        $cacheDuration = $params['cache_duration'] ?? $this->defaultOptions['cache_duration'];

        return Cache::remember($cacheKey, $cacheDuration, function () use ($params) {
            return $this->buildDashboardData($params);
        });
    }

    /**
     * Build dashboard data from all sources
     */
    protected function buildDashboardData(array $params): array
    {
        // Parse parameters
        $period = $this->parsePeriod($params);
        $comparison = $params['include_comparison'] ?? $this->defaultOptions['include_comparison'];
        $maxProvinces = $params['max_provinces'] ?? $this->defaultOptions['max_provinces'];
        $cacheDuration = $params['cache_duration'] ?? $this->defaultOptions['cache_duration'];

        // Get available data sources
        $availableSources = $this->dataSourceManager->getAvailable();

        if ($availableSources->isEmpty()) {
            return $this->emptyResponse();
        }

        // Collect data from all sources
        $rawTimeSeries = $this->collectTimeSeriesData($availableSources, $period);
        $rawProvinceData = $this->collectProvinceData($availableSources, $period);
        $summaryStats = $this->collectSummaryStats($availableSources, $period);

        // Process time series
        $processedTimeSeries = $this->timeSeriesProcessor->processTimeSeries(
            $rawTimeSeries,
            ['period' => $period, 'group_by' => $params['group_by'] ?? 'month']
        );

        // Calculate trends
        $trends = $this->timeSeriesProcessor->calculateTrend($processedTimeSeries['datasets']);

        // Process province data
        $processedProvinces = $this->processProvinceData($rawProvinceData, $maxProvinces);

        // Get comparison data if requested
        $comparisonData = null;
        if ($comparison) {
            $comparisonPeriod = $this->getComparisonPeriod($period, $params);
            $comparisonData = $this->getComparisonData($availableSources, $comparisonPeriod);
        }

        // Build response
        return [
            'metadata' => [
                'period_type' => $params['period_type'] ?? 'month',
                'start_date' => $period->getStart()->format('Y-m-d'),
                'end_date' => $period->getEnd()->format('Y-m-d'),
                'data_sources' => $availableSources->count(),
                'generated_at' => now()->toISOString(),
                'comparison_enabled' => $comparison,
                'cache_duration' => $cacheDuration
            ],
            'time_series' => [
                'labels' => $processedTimeSeries['labels'],
                'datasets' => $this->buildDatasets($processedTimeSeries['datasets'], $availableSources),
                'trends' => $trends,
                'forecast' => $this->generateForecast($processedTimeSeries['datasets'])
            ],
            'provinces' => [
                'top_10' => $processedProvinces['top_10'],
                'top_8_for_cards' => $processedProvinces['top_8_for_cards'],
                'top_3_for_progress' => $processedProvinces['top_3_for_progress']
            ],
            'summary' => [
                'total_izin' => array_sum(array_column($summaryStats, 'total')),
                'unique_provinces' => $this->getUniqueProvinceCount($rawProvinceData),
                'peak_day' => $this->getPeakDay($summaryStats),
                'averages' => $this->calculateAverages($summaryStats),
                'data_quality' => $this->assessDataQuality($availableSources)
            ],
            'comparison' => $comparisonData,
            'anomalies' => $this->detectAnomalies($processedTimeSeries['datasets']),
            'key_insights' => $this->generateKeyInsights($processedTimeSeries, $processedProvinces, $trends)
        ];
    }

    /**
     * Parse period parameters
     */
    protected function parsePeriod(array $params): CarbonPeriod
    {
        $periodType = $params['period_type'] ?? 'month';
        $customStart = $params['start_date'] ?? null;
        $customEnd = $params['end_date'] ?? null;

        if ($customStart && $customEnd) {
            return CarbonPeriod::create($customStart, $customEnd);
        }

        return match($periodType) {
            'today' => CarbonPeriod::create(now()->startOfDay(), now()->endOfDay()),
            'week' => CarbonPeriod::create(now()->startOfWeek(), now()->endOfWeek()),
            'month' => CarbonPeriod::create(now()->startOfMonth(), now()->endOfMonth()),
            'quarter' => CarbonPeriod::create(now()->startOfQuarter(), now()->endOfQuarter()),
            'year' => CarbonPeriod::create(now()->startOfYear(), now()->endOfYear()),
            '6_months' => CarbonPeriod::create(now()->subMonths(5)->startOfMonth(), now()->endOfMonth()),
            '12_months' => CarbonPeriod::create(now()->subMonths(11)->startOfMonth(), now()->endOfMonth()),
            default => CarbonPeriod::create(now()->subMonths(5)->startOfMonth(), now()->endOfMonth())
        };
    }

    /**
     * Collect time series data from all sources
     */
    protected function collectTimeSeriesData($sources, CarbonPeriod $period): array
    {
        $data = [];

        foreach ($sources as $source) {
            try {
                $data[$source->getIdentifier()] = $source->getTimeSeriesData($period);
            } catch (\Exception $e) {
                Log::error("Error collecting time series from {$source->getIdentifier()}: " . $e->getMessage());
                $data[$source->getIdentifier()] = [];
            }
        }

        return $data;
    }

    /**
     * Collect province data from all sources
     */
    protected function collectProvinceData($sources, CarbonPeriod $period): array
    {
        $data = [];

        foreach ($sources as $source) {
            try {
                $data[$source->getIdentifier()] = $source->getProvinceData($period);
            } catch (\Exception $e) {
                Log::error("Error collecting province data from {$source->getIdentifier()}: " . $e->getMessage());
                $data[$source->getIdentifier()] = [];
            }
        }

        return $data;
    }

    /**
     * Collect summary statistics
     */
    protected function collectSummaryStats($sources, CarbonPeriod $period): array
    {
        $stats = [];

        foreach ($sources as $source) {
            try {
                $stats[$source->getIdentifier()] = $source->getSummaryStats($period);
            } catch (\Exception $e) {
                Log::error("Error collecting summary stats from {$source->getIdentifier()}: " . $e->getMessage());
                $stats[$source->getIdentifier()] = ['total' => 0, 'peak_day' => null, 'avg_per_day' => 0];
            }
        }

        return $stats;
    }

    /**
     * Process province data into rankings
     */
    protected function processProvinceData(array $rawProvinceData, int $maxProvinces): array
    {
        // Aggregate data from all sources
        $aggregated = [];
        foreach ($rawProvinceData as $source => $provinces) {
            foreach ($provinces as $province => $data) {
                if (!isset($aggregated[$province])) {
                    $aggregated[$province] = ['total' => 0, 'sources' => []];
                }
                $aggregated[$province]['total'] += $data['count'];
                $aggregated[$province]['sources'][$source] = $data['count'];
            }
        }

        // Sort by total and take top N
        arsort($aggregated);
        $topProvinces = array_slice($aggregated, 0, $maxProvinces, true);

        // Format for different UI components
        $top_10 = [];
        $rank = 1;
        foreach ($topProvinces as $province => $data) {
            $top_10[] = [
                'rank' => $rank++,
                'name' => $province,
                'total' => $data['total'],
                'breakdown' => $data['sources']
            ];
        }

        return [
            'top_10' => $top_10,
            'top_8_for_cards' => array_slice($top_10, 0, 8),
            'top_3_for_progress' => array_slice($top_10, 0, 3)
        ];
    }

    /**
     * Build datasets for chart.js
     */
    protected function buildDatasets(array $data, $sources): array
    {
        $datasets = [];

        foreach ($data as $sourceId => $values) {
            $source = $sources->get($sourceId);
            if ($source) {
                $datasets[] = [
                    'label' => $source->getDisplayName(),
                    'data' => $values,
                    'borderColor' => $source->getColor(),
                    'backgroundColor' => $this->hex2rgba($source->getColor(), 0.1),
                    'fill' => true,
                    'tension' => 0.3
                ];
            }
        }

        return $datasets;
    }

    /**
     * Additional helper methods
     */

    protected function getComparisonData($sources, CarbonPeriod $period): ?array
    {
        // Similar to buildDashboardData but for comparison period
        // Implementation left for brevity
        return null;
    }

    protected function generateForecast(array $datasets): array
    {
        return $this->timeSeriesProcessor->generateForecast($datasets, 3);
    }

    protected function detectAnomalies(array $datasets): array
    {
        return $this->timeSeriesProcessor->detectAnomalies($datasets);
    }

    protected function generateKeyInsights(array $timeSeries, array $provinces, array $trends): array
    {
        $insights = [];

        // Top performing source
        $topSource = array_key_first($trends);
        if ($topSource && $trends[$topSource]['percentage_change'] > 10) {
            $insights[] = [
                'type' => 'positive',
                'title' => 'Pertumbuhan Positif',
                'message' => ucfirst($topSource) . " mengalami pertumbuhan {$trends[$topSource]['percentage_change']}%"
            ];
        }

        // Top province
        if (!empty($provinces['top_10'])) {
            $topProvince = $provinces['top_10'][0];
            $insights[] = [
                'type' => 'info',
                'title' => 'Provinsi Teraktif',
                'message' => "{$topProvince['name']} dengan {$topProvince['total']} izin"
            ];
        }

        return $insights;
    }

    // Additional helper methods
    protected function getComparisonPeriod(CarbonPeriod $current, array $params): CarbonPeriod
    {
        $comparisonType = $params['comparison_type'] ?? 'previous_period';
        $periods = $params['comparison_periods'] ?? 1;

        if ($comparisonType === 'previous_year') {
            $start = $current->getStart()->copy()->subYear();
            $end = $current->getEnd()->copy()->subYear();
        } else {
            $start = $current->getStart()->copy()->subMonths($periods);
            $end = $current->getEnd()->copy()->subMonths($periods);
        }

        return CarbonPeriod::create($start, $end);
    }

    
    protected function getUniqueProvinceCount($rawProvinceData): int
    {
        $provinces = [];
        foreach ($rawProvinceData as $sourceData) {
            foreach ($sourceData as $province => $data) {
                $provinces[] = $province;
            }
        }
        return count(array_unique($provinces));
    }

    protected function getPeakDay($summaryStats): ?string
    {
        $peakDay = null;
        $maxCount = 0;

        foreach ($summaryStats as $stats) {
            if ($stats['peak_day'] && $stats['peak_count'] > $maxCount) {
                $maxCount = $stats['peak_count'];
                $peakDay = $stats['peak_day'];
            }
        }

        return $peakDay;
    }

    protected function calculateAverages($summaryStats): array
    {
        $totals = array_map(fn($s) => $s['total'] ?? 0, $summaryStats);
        $averages = array_map(fn($s) => $s['avg_per_day'] ?? 0, $summaryStats);

        return [
            'total_average' => count($totals) > 0 ? array_sum($totals) / count($totals) : 0,
            'daily_average' => count($averages) > 0 ? array_sum($averages) / count($averages) : 0
        ];
    }

    protected function assessDataQuality($availableSources): array
    {
        $totalSources = count($this->dataSourceManager->all());
        $activeSources = $availableSources->count();

        return [
            'total_sources' => $totalSources,
            'active_sources' => $activeSources,
            'availability_percentage' => $totalSources > 0 ? round(($activeSources / $totalSources) * 100, 1) : 0
        ];
    }

    protected function emptyResponse(): array
    {
        return [
            'metadata' => ['error' => 'No data sources available'],
            'time_series' => ['labels' => [], 'datasets' => []],
            'provinces' => ['top_10' => [], 'top_8_for_cards' => [], 'top_3_for_progress' => []],
            'summary' => ['total_izin' => 0]
        ];
    }

    protected function generateCacheKey(array $params): string
    {
        ksort($params);
        return 'dashboard.data.' . md5(json_encode($params));
    }

    protected function hex2rgba(string $color, float $opacity): string
    {
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
        return "rgba({$r}, {$g}, {$b}, {$opacity})";
    }

    // Additional helper methods for province count, peak day, averages, etc.
    // Implementation left for brevity
}