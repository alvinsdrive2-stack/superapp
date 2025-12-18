<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DashboardService
{
    /**
     * Get total counts from all 3 databases
     */
    public function getTotalCounts()
    {
        try {
            $results = [];

            // Get counts from each database
            $results['balai'] = $this->getCountFromDatabase('mysql_balai');
            $results['reguler'] = $this->getCountFromDatabase('mysql_reguler');
            $results['fg'] = $this->getCountFromDatabase('mysql_fg');

            // Calculate total
            $results['total'] = $results['balai'] + $results['reguler'] + $results['fg'];

            return [
                'success' => true,
                'data' => $results
            ];

        } catch (Exception $e) {
            Log::error('Error getting total counts: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'total' => 0,
                    'balai' => 0,
                    'reguler' => 0,
                    'fg' => 0
                ]
            ];
        }
    }

    /**
     * Get pencatatan izin time series data
     */
    public function getPencatatanIzinTimeSeries($months = null)
    {
        try {
            // Default: ambil data dari sekarang ke tahun lalu (1 tahun ke belakang)
            if ($months === null) {
                // Ambil data dari Des 2024 sampai Dec 2025 (1 tahun terakhir)
                $endDate = Carbon::now()->endOfMonth(); // Dec 2025
                $startDate = Carbon::now()->subYear()->startOfMonth(); // Dec 2024
            } else {
                // Untuk filtering khusus (jika butuh)
                $endDate = Carbon::now()->endOfMonth();
                $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
            }

            $datasets = [];
            $labels = [];

            // Generate monthly labels
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $labels[] = $currentDate->format('M Y');
                $currentDate->addMonth();
            }

            // Get data from each database
            $datasets['balai'] = $this->getTimeSeriesFromDatabase('mysql_balai', $startDate, $endDate, $labels);
            $datasets['reguler'] = $this->getTimeSeriesFromDatabase('mysql_reguler', $startDate, $endDate, $labels);
            $datasets['fg'] = $this->getTimeSeriesFromDatabase('mysql_fg', $startDate, $endDate, $labels);

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => $datasets,
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error getting time series data: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'labels' => [],
                    'datasets' => [
                        'balai' => [],
                        'reguler' => [],
                        'fg' => []
                    ]
                ]
            ];
        }
    }

    /**
     * Get province ranking data
     */
    public function getProvinceRanking($limit = 10)
    {
        try {
            $endDate = Carbon::now()->endOfMonth();
            $startDate = Carbon::now()->startOfMonth();

            $allProvinces = [];

            // Collect data from all databases
            $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];

            foreach ($databases as $dbName) {
                try {
                    $provinces = DB::connection($dbName)
                        ->table('data_pencatatans')
                        ->select('propinsi', DB::raw('COUNT(*) as count'))
                        ->whereNotNull('propinsi')
                        ->whereBetween('tanggal_ditetapkan', [$startDate, $endDate])
                        ->groupBy('propinsi')
                        ->orderByDesc('count')
                        ->limit($limit)
                        ->get();

                    foreach ($provinces as $province) {
                        $provinceName = $province->propinsi;

                        if (!isset($allProvinces[$provinceName])) {
                            $allProvinces[$provinceName] = [
                                'provinsi' => $provinceName,
                                'balai' => 0,
                                'reguler' => 0,
                                'fg' => 0,
                                'total' => 0
                            ];
                        }

                        $allProvinces[$provinceName][$this->getDatabaseName($dbName)] = $province->count;
                        $allProvinces[$provinceName]['total'] += $province->count;
                    }

                } catch (Exception $e) {
                    Log::error("Error getting provinces from {$dbName}: " . $e->getMessage());
                    continue;
                }
            }

            // Sort by total count
            uasort($allProvinces, function ($a, $b) {
                return $b['total'] - $a['total'];
            });

            // Add rank and limit
            $rankedProvinces = [];
            $rank = 1;

            foreach (array_slice($allProvinces, 0, $limit) as $province) {
                $province['rank'] = $rank++;
                $rankedProvinces[] = $province;
            }

            return [
                'success' => true,
                'data' => [
                    'rankings' => $rankedProvinces,
                    'total_provinces' => count($allProvinces),
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error getting province ranking: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'rankings' => [],
                    'total_provinces' => 0
                ]
            ];
        }
    }

    /**
     * Get daily statistics
     */
    public function getDailyStats($days = 30)
    {
        try {
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

            $stats = [];
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->format('Y-m-d');
                $stats[$dateKey] = [
                    'balai' => 0,
                    'reguler' => 0,
                    'fg' => 0
                ];

                // Get data from all databases for this date
                $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];
                foreach ($databases as $dbName) {
                    try {
                        $count = DB::connection($dbName)
                            ->table('data_pencatatans')
                            ->whereDate('tanggal_ditetapkan', $dateKey)
                            ->count();

                        $stats[$dateKey][$this->getDatabaseName($dbName)] = $count;
                    } catch (Exception $e) {
                        Log::error("Error getting daily stats from {$dbName}: " . $e->getMessage());
                    }
                }

                $currentDate->addDay();
            }

            return [
                'success' => true,
                'data' => [
                    'daily_stats' => $stats,
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ],
                    'total_days' => count($stats)
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error getting daily stats: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'daily_stats' => []
                ]
            ];
        }
    }

    /**
     * Get comprehensive overview data
     */
    public function getOverview($months = 6)
    {
        try {
            // Parallel execution of all queries
            $results = [
                'summary' => $this->getTotalCounts(),
                'time_series' => $this->getPencatatanIzinTimeSeries($months),
                'province_ranking' => $this->getProvinceRanking(10),
                'daily_stats' => $this->getDailyStats(30)
            ];

            // Check if all operations were successful
            $allSuccessful = true;
            foreach ($results as $result) {
                if (!$result['success']) {
                    $allSuccessful = false;
                    break;
                }
            }

            return [
                'success' => $allSuccessful,
                'data' => array_merge(
                    $results['summary']['success'] ? ['summary' => $results['summary']['data']] : [],
                    $results['time_series']['success'] ? ['time_series' => $results['time_series']['data']] : [],
                    $results['province_ranking']['success'] ? ['province_ranking' => $results['province_ranking']['data']] : [],
                    $results['daily_stats']['success'] ? ['daily_stats' => $results['daily_stats']['data']] : []
                ),
                'errors' => array_filter([
                    $results['summary']['success'] ? null : $results['summary']['error'],
                    $results['time_series']['success'] ? null : $results['time_series']['error'],
                    $results['province_ranking']['success'] ? null : $results['province_ranking']['error'],
                    $results['daily_stats']['success'] ? null : $results['daily_stats']['error']
                ])
            ];

        } catch (Exception $e) {
            Log::error('Error getting overview: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Helper function to get correct table name for database
     */
    private function getTableNameForDatabase($connection)
    {
        return ($connection === 'mysql_fg') ? 'data_pencatatans' : 'data_pencatatans';
    }

    /**
     * Helper function to get count from specific database
     */
    private function getCountFromDatabase($connection)
    {
        try {
            $tableName = $this->getTableNameForDatabase($connection);
            return DB::connection($connection)
                ->table($tableName)
                ->count();
        } catch (Exception $e) {
            Log::error("Error counting records from {$connection}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Helper function to get time series data from specific database
     */
    private function getTimeSeriesFromDatabase($connection, $startDate, $endDate, $labels)
    {
        try {
            $tableName = $this->getTableNameForDatabase($connection);
            // Semua database menggunakan kolom tanggal_ditetapkan
            $dateColumn = 'tanggal_ditetapkan';

            $data = DB::connection($connection)
                ->table($tableName)
                ->selectRaw('DATE_FORMAT(' . $dateColumn . ', "%b %Y") as period, COUNT(*) as count')
                ->whereBetween($dateColumn, [$startDate, $endDate])
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('count', 'period')
                ->toArray();

            // Fill missing months with 0
            $filledData = [];
            foreach ($labels as $label) {
                $filledData[] = $data[$label] ?? 0;
            }

            return $filledData;
        } catch (Exception $e) {
            Log::error("Error getting time series from {$connection}: " . $e->getMessage());
            return array_fill(0, count($labels), 0);
        }
    }

    /**
     * Helper function to get daily time series data from specific database
     */
    private function getDailyTimeSeriesFromDatabase($connection, $startDate, $endDate)
    {
        try {
            $tableName = $this->getTableNameForDatabase($connection);
            // Semua database menggunakan kolom tanggal_ditetapkan
            $dateColumn = 'tanggal_ditetapkan';

            $data = DB::connection($connection)
                ->table($tableName)
                ->selectRaw('DATE(' . $dateColumn . ') as date, COUNT(*) as count')
                ->whereBetween($dateColumn, [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            // Fill missing dates with 0
            $filledData = [];
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $dateKey = $currentDate->format('Y-m-d');
                $filledData[] = $data[$dateKey] ?? 0;
                $currentDate->addDay();
            }

            return $filledData;
        } catch (Exception $e) {
            Log::error("Error getting daily time series from {$connection}: " . $e->getMessage());
            // Return array with zeros for each day in the range
            $days = $startDate->diffInDays($endDate) + 1;
            return array_fill(0, $days, 0);
        }
    }

    /**
     * Convert database connection name to display name
     */
    private function getDatabaseName($connection)
    {
        $mapping = [
            'mysql_balai' => 'balai',
            'mysql_reguler' => 'reguler',
            'mysql_fg' => 'fg'
        ];

        return $mapping[$connection] ?? $connection;
    }

    /**
     * Test database connections
     */
    public function testDatabaseConnections()
    {
        $results = [];

        $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];

        foreach ($databases as $dbName) {
            try {
                $test = DB::connection($dbName)
                    ->select('SELECT 1 as test');

                $results[$dbName] = [
                    'connected' => true,
                    'message' => 'Connection successful'
                ];

            } catch (Exception $e) {
                $results[$dbName] = [
                    'connected' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'data' => $results
        ];
    }

    /**
     * Get year vs year comparison data
     */
    public function getYearComparisonData($currentYear, $previousYear)
    {
        try {
            $datasets = [];
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            // Get data for current year from each database
            $datasets['currentYear'] = [
                'label' => (string)$currentYear,
                'data' => $this->getYearlyDataFromDatabases($currentYear, $labels),
                'borderColor' => '#667eea',
                'backgroundColor' => 'rgba(102, 126, 234, 0.1)',
                'borderWidth' => 3,
                'fill' => true,
                'tension' => 0.3
            ];

            // Get data for previous year from each database
            $datasets['previousYear'] = [
                'label' => (string)$previousYear,
                'data' => $this->getYearlyDataFromDatabases($previousYear, $labels),
                'borderColor' => '#f093fb',
                'backgroundColor' => 'rgba(240, 147, 251, 0.1)',
                'borderWidth' => 3,
                'fill' => true,
                'tension' => 0.3
            ];

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => array_values($datasets)
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error getting year comparison data: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'labels' => [],
                    'datasets' => []
                ]
            ];
        }
    }

    /**
     * Get monthly comparison data (current month vs previous month)
     */
    public function getMonthlyComparisonData()
    {
        try {
            $currentMonth = Carbon::now();
            $previousMonth = Carbon::now()->subMonth();

            $labels = [
                $previousMonth->format('F'),
                $currentMonth->format('F')
            ];

            $datasets = [];
            $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];
            $colors = [
                'mysql_balai' => [
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                ],
                'mysql_reguler' => [
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)'
                ],
                'mysql_fg' => [
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)'
                ]
            ];

            foreach ($databases as $database) {
                $previousMonthData = $this->getMonthDataFromDatabase($database, $previousMonth);
                $currentMonthData = $this->getMonthDataFromDatabase($database, $currentMonth);

                $datasets[] = [
                    'label' => $this->getDatabaseName($database),
                    'data' => [$previousMonthData, $currentMonthData],
                    'borderColor' => $colors[$database]['borderColor'],
                    'backgroundColor' => $colors[$database]['backgroundColor'],
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => $datasets
                ]
            ];

        } catch (Exception $e) {
            Log::error('Error getting monthly comparison data: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'labels' => [],
                    'datasets' => []
                ]
            ];
        }
    }

    /**
     * Get yearly data from all databases for each month
     */
    private function getYearlyDataFromDatabases($year, $labels)
    {
        $monthlyData = array_fill(0, count($labels), 0);

        foreach ($labels as $index => $monthName) {
            $month = Carbon::createFromFormat('M', $monthName)->month;
            $monthTotal = 0;

            // Get data from each database
            $monthTotal += $this->getMonthDataFromDatabases($year, $month);

            $monthlyData[$index] = $monthTotal;
        }

        return $monthlyData;
    }

    /**
     * Get month data from all databases
     */
    private function getMonthDataFromDatabases($year, $month)
    {
        $total = 0;
        $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];

        foreach ($databases as $database) {
            try {
                $tableName = $this->getTableNameForDatabase($database);
                // Semua database menggunakan kolom tanggal_ditetapkan
                $count = DB::connection($database)
                    ->table($tableName)
                    ->whereYear('tanggal_ditetapkan', $year)
                    ->whereMonth('tanggal_ditetapkan', $month)
                    ->count();

                $total += $count;
            } catch (Exception $e) {
                Log::error("Error getting month data from {$database}: " . $e->getMessage());
                // Continue with other databases
            }
        }

        return $total;
    }

    /**
     * Get data for a specific month from a database
     */
    private function getMonthDataFromDatabase($database, $date)
    {
        $tableName = $this->getTableNameForDatabase($database);

        try {
            // Semua database menggunakan kolom tanggal_ditetapkan
            $count = DB::connection($database)
                ->table($tableName)
                ->whereYear('tanggal_ditetapkan', $date->year)
                ->whereMonth('tanggal_ditetapkan', $date->month)
                ->count();

            return $count;
        } catch (Exception $e) {
            Log::error("Error getting month data from {$database}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get earliest date from all databases
     */
    private function getEarliestDate()
    {
        $earliestDates = [];
        $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];

        foreach ($databases as $database) {
            try {
                $tableName = $this->getTableNameForDatabase($database);
                $earliest = DB::connection($database)
                    ->table($tableName)
                    ->min('tanggal_ditetapkan');

                if ($earliest) {
                    $earliestDates[] = $earliest;
                }
            } catch (Exception $e) {
                Log::error("Error getting earliest date from {$database}: " . $e->getMessage());
            }
        }

        if (!empty($earliestDates)) {
            // Return the earliest date among all databases
            return min($earliestDates);
        }

        // Fallback: return 2 years ago if no data found
        return Carbon::now()->subYears(2)->startOfMonth();
    }
}