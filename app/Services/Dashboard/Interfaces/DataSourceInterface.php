<?php

namespace App\Services\Dashboard\Interfaces;

use Carbon\CarbonPeriod;

interface DataSourceInterface
{
    /**
     * Get unique identifier for this data source
     */
    public function getIdentifier(): string;

    /**
     * Get display name for this data source
     */
    public function getDisplayName(): string;

    /**
     * Get color scheme for this data source
     */
    public function getColor(): string;

    /**
     * Check if this data source is available/healthy
     */
    public function isAvailable(): bool;

    /**
     * Get time series data for the specified period
     */
    public function getTimeSeriesData(CarbonPeriod $period): array;

    /**
     * Get province breakdown for the specified period
     */
    public function getProvinceData(CarbonPeriod $period): array;

    /**
     * Get summary statistics
     */
    public function getSummaryStats(CarbonPeriod $period): array;

    /**
     * Get total count for the specified period
     */
    public function getTotalCount(CarbonPeriod $period): int;

    /**
     * Get raw data with optional filters
     */
    public function getRawData(array $filters = []): \Illuminate\Support\Collection;

    /**
     * Get available date range for this data source
     */
    public function getDateRange(): ?array;
}