<?php

namespace App\Services\Dashboard\Interfaces;

interface TimeSeriesProcessorInterface
{
    /**
     * Process raw time series data into chart-ready format
     */
    public function processTimeSeries(array $rawData, array $options = []): array;

    /**
     * Calculate trend for time series data
     */
    public function calculateTrend(array $data, ?string $compareWith = null): array;

    /**
     * Aggregate data by specified period (day, week, month, quarter, year)
     */
    public function aggregateByPeriod(array $data, string $period): array;

    /**
     * Generate forecast based on historical data
     */
    public function generateForecast(array $data, int $periods = 3): array;

    /**
     * Detect anomalies in time series data
     */
    public function detectAnomalies(array $data, float $threshold = 2.0): array;

    /**
     * Calculate moving average
     */
    public function calculateMovingAverage(array $data, int $window = 3): array;

    /**
     * Calculate growth rates
     */
    public function calculateGrowthRates(array $data): array;

    /**
     * Generate labels for time series
     */
    public function generateLabels(\Carbon\CarbonPeriod $period, string $format = 'M Y'): array;
}