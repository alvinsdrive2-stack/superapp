<?php

namespace App\Services\Dashboard\Interfaces;

interface ChartBuilderInterface
{
    /**
     * Build chart configuration for the specified type
     */
    public function buildChart(array $data, array $options = []): array;

    /**
     * Get chart type this builder handles
     */
    public function getChartType(): string;

    /**
     * Validate data for this chart type
     */
    public function validateData(array $data): bool;

    /**
     * Transform data for chart consumption
     */
    public function transformData(array $data): array;

    /**
     * Get default options for this chart type
     */
    public function getDefaultOptions(): array;

    /**
     * Apply theme/styling to chart
     */
    public function applyTheme(array $chartConfig, string $theme = 'default'): array;
}