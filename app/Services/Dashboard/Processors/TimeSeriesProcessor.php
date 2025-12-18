<?php

namespace App\Services\Dashboard\Processors;

use App\Services\Dashboard\Interfaces\TimeSeriesProcessorInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class TimeSeriesProcessor implements TimeSeriesProcessorInterface
{
    /**
     * Process raw time series data into chart-ready format
     */
    public function processTimeSeries(array $rawData, array $options = []): array
    {
        $period = $options['period'] ?? null;
        $groupBy = $options['group_by'] ?? 'day';
        $fillMissing = $options['fill_missing'] ?? true;
        $accumulate = $options['accumulate'] ?? false;

        if (!$period) {
            return $rawData;
        }

        // Create date labels
        $labels = $this->generateLabels($period, $this->getLabelFormat($groupBy));
        $processed = [];

        foreach ($rawData as $source => $data) {
            $processedData = [];
            $accumulated = 0;

            foreach ($labels as $date) {
                $value = $data[$date]['count'] ?? 0;

                if ($accumulate) {
                    $accumulated += $value;
                    $processedData[] = $accumulated;
                } else {
                    $processedData[] = $value;
                }
            }

            $processed[$source] = $processedData;
        }

        return [
            'labels' => $labels,
            'datasets' => $processed
        ];
    }

    /**
     * Calculate trend for time series data
     */
    public function calculateTrend(array $data, ?string $compareWith = null): array
    {
        $trends = [];

        foreach ($data as $source => $values) {
            $trend = [
                'current_value' => end($values),
                'previous_value' => count($values) > 1 ? $values[count($values) - 2] : 0,
                'percentage_change' => 0,
                'direction' => 'stable',
                'slope' => 0
            ];

            if ($trend['previous_value'] > 0) {
                $change = (($trend['current_value'] - $trend['previous_value']) / $trend['previous_value']) * 100;
                $trend['percentage_change'] = round($change, 2);
                $trend['direction'] = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable');
            }

            // Calculate linear trend (slope)
            if (count($values) > 1) {
                $n = count($values);
                $x = array_keys($values);
                $sumX = array_sum($x);
                $sumY = array_sum($values);
                $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $values));
                $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));

                $trend['slope'] = round(($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX), 4);
            }

            $trends[$source] = $trend;
        }

        return $trends;
    }

    /**
     * Aggregate data by specified period
     */
    public function aggregateByPeriod(array $data, string $period): array
    {
        $aggregated = [];
        $format = $this->getDateFormat($period);

        foreach ($data as $source => $values) {
            $aggregatedData = [];

            foreach ($values as $date => $value) {
                $key = date($format, strtotime($date));
                if (!isset($aggregatedData[$key])) {
                    $aggregatedData[$key] = 0;
                }
                $aggregatedData[$key] += $value['count'] ?? $value;
            }

            $aggregated[$source] = $aggregatedData;
        }

        return $aggregated;
    }

    /**
     * Generate forecast based on historical data
     */
    public function generateForecast(array $data, int $periods = 3): array
    {
        $forecast = [];

        foreach ($data as $source => $values) {
            $forecastData = $this->linearRegressionForecast($values, $periods);
            $forecast[$source] = $forecastData;
        }

        return $forecast;
    }

    /**
     * Detect anomalies in time series data
     */
    public function detectAnomalies(array $data, float $threshold = 2.0): array
    {
        $anomalies = [];

        foreach ($data as $source => $values) {
            $mean = array_sum($values) / count($values);
            $stdDev = $this->calculateStandardDeviation($values, $mean);

            $sourceAnomalies = [];
            foreach ($values as $date => $value) {
                $zScore = abs($value - $mean) / $stdDev;
                if ($zScore > $threshold) {
                    $sourceAnomalies[] = [
                        'date' => $date,
                        'value' => $value,
                        'z_score' => $zScore,
                        'severity' => $zScore > 3 ? 'high' : 'medium'
                    ];
                }
            }

            if (!empty($sourceAnomalies)) {
                $anomalies[$source] = $sourceAnomalies;
            }
        }

        return $anomalies;
    }

    /**
     * Calculate moving average
     */
    public function calculateMovingAverage(array $data, int $window = 3): array
    {
        $movingAverage = [];
        $values = array_values($data);

        for ($i = 0; $i < count($values); $i++) {
            $start = max(0, $i - $window + 1);
            $windowValues = array_slice($values, $start, $window);
            $movingAverage[] = array_sum($windowValues) / count($windowValues);
        }

        return $movingAverage;
    }

    /**
     * Calculate growth rates
     */
    public function calculateGrowthRates(array $data): array
    {
        $growthRates = [0]; // First period has no growth

        for ($i = 1; $i < count($data); $i++) {
            if ($data[$i - 1] == 0) {
                $growthRates[] = $data[$i] > 0 ? 100 : 0;
            } else {
                $growth = (($data[$i] - $data[$i - 1]) / $data[$i - 1]) * 100;
                $growthRates[] = round($growth, 2);
            }
        }

        return $growthRates;
    }

    /**
     * Generate labels for time series
     */
    public function generateLabels(CarbonPeriod $period, string $format = 'M Y'): array
    {
        $labels = [];

        foreach ($period as $date) {
            $labels[] = $date->format($format);
        }

        return $labels;
    }

    /**
     * Helper methods
     */
    private function getLabelFormat(string $groupBy): string
    {
        return match($groupBy) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'M Y',
            'quarter' => 'Y-Q',
            'year' => 'Y',
            default => 'M Y'
        };
    }

    private function getDateFormat(string $period): string
    {
        return match($period) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'quarter' => 'Y',
            'year' => 'Y',
            default => 'Y-m'
        };
    }

    private function calculateStandardDeviation(array $values, float $mean): float
    {
        $variance = array_sum(array_map(fn($value) => pow($value - $mean, 2), $values)) / count($values);
        return sqrt($variance);
    }

    private function linearRegressionForecast(array $values, int $periods): array
    {
        $n = count($values);
        if ($n < 2) {
            return array_fill(0, $periods, end($values) ?? 0);
        }

        $x = range(0, $n - 1);
        $y = $values;

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($xi, $yi) => $xi * $yi, $x, $y));
        $sumX2 = array_sum(array_map(fn($xi) => $xi * $xi, $x));

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        $forecast = [];
        for ($i = 0; $i < $periods; $i++) {
            $forecast[] = max(0, round($intercept + $slope * ($n + $i)));
        }

        return $forecast;
    }
}