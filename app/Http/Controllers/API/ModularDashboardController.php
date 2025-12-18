<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardAggregator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ModularDashboardController extends Controller
{
    protected DashboardAggregator $aggregator;

    public function __construct(DashboardAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * Get all dashboard data in a single call
     */
    public function getData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_type' => 'string|in:today,week,month,quarter,year,6_months,12_months',
            'start_date' => 'date|date_format:Y-m-d',
            'end_date' => 'date|date_format:Y-m-d|after_or_equal:start_date',
            'group_by' => 'string|in:day,week,month,quarter,year',
            'include_comparison' => 'boolean',
            'comparison_type' => 'string|in:previous_period,previous_year',
            'comparison_periods' => 'integer|min:1|max:12',
            'max_provinces' => 'integer|min:1|max:50',
            'cache_duration' => 'integer|min:0|max:3600'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $params = array_merge($this->getDefaultParams(), $request->all());
            $data = $this->aggregator->getDashboardData($params);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available data sources
     */
    public function getDataSources(): JsonResponse
    {
        try {
            $manager = app(\App\Services\Dashboard\DataSourceManager::class);
            $sources = $manager->toArray();

            return response()->json([
                'success' => true,
                'data' => $sources
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get data sources'
            ], 500);
        }
    }

    /**
     * Get time series data only
     */
    public function getTimeSeries(Request $request): JsonResponse
    {
        $params = array_merge($this->getDefaultParams(), $request->all());

        // Force include only time series data
        $params['include_only'] = ['time_series'];

        $data = $this->aggregator->getDashboardData($params);

        return response()->json([
            'success' => true,
            'data' => $data['time_series'] ?? []
        ]);
    }

    /**
     * Get province data only
     */
    public function getProvinces(Request $request): JsonResponse
    {
        $params = array_merge($this->getDefaultParams(), $request->all());
        $params['max_provinces'] = $request->get('limit', 10);

        // Force include only province data
        $params['include_only'] = ['provinces'];

        $data = $this->aggregator->getDashboardData($params);

        return response()->json([
            'success' => true,
            'data' => $data['provinces'] ?? []
        ]);
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request): JsonResponse
    {
        $params = array_merge($this->getDefaultParams(), $request->all());

        // Force include only summary data
        $params['include_only'] = ['summary'];

        $data = $this->aggregator->getDashboardData($params);

        return response()->json([
            'success' => true,
            'data' => $data['summary'] ?? []
        ]);
    }

    /**
     * Get forecast data
     */
    public function getForecast(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'periods' => 'integer|min:1|max:12'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters'
            ], 422);
        }

        $params = array_merge($this->getDefaultParams(), $request->all());
        $params['forecast_periods'] = $request->get('periods', 3);

        // Force include forecast
        $params['include_forecast'] = true;

        $data = $this->aggregator->getDashboardData($params);

        return response()->json([
            'success' => true,
            'data' => $data['time_series']['forecast'] ?? []
        ]);
    }

    /**
     * Get anomalies
     */
    public function getAnomalies(Request $request): JsonResponse
    {
        $params = array_merge($this->getDefaultParams(), $request->all());

        // Force include anomalies
        $params['include_anomalies'] = true;

        $data = $this->aggregator->getDashboardData($params);

        return response()->json([
            'success' => true,
            'data' => $data['anomalies'] ?? []
        ]);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            \Cache::tags(['dashboard'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache'
            ], 500);
        }
    }

    /**
     * Get default parameters
     */
    protected function getDefaultParams(): array
    {
        return [
            'period_type' => '6_months',
            'group_by' => 'month',
            'include_comparison' => false,
            'max_provinces' => 10,
            'cache_duration' => 300
        ];
    }
}