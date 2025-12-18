<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PencatatanIzinService
{
    /**
     * Get monthly count of data_pencatatan from all 3 databases
     *
     * @param int $months Number of months to fetch (default: 6)
     * @return array Formatted data for Chart.js
     */
    public function getMonthlyCount(int $months = 6): array
    {
        $cacheKey = 'dashboard.data_pencatatans.' . $months . 'months';

        return Cache::remember($cacheKey, 300, function () use ($months) {
            $endDate = now();
            $startDate = $endDate->copy()->subMonths($months - 1)->startOfMonth();

            $databases = [
                'balai' => [
                    'connection' => 'mysql_balai',
                    'label' => 'Balai',
                    'color' => '#4F46E5'
                ],
                'reguler' => [
                    'connection' => 'mysql_reguler',
                    'label' => 'Reguler',
                    'color' => '#10B981'
                ],
                'suisei' => [
                    'connection' => 'mysql_fg',
                    'label' => 'FG/Suisei',
                    'color' => '#F59E0B'
                ]
            ];

            $allMonths = [];
            $datasets = [];

            // Generate all month labels
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = $startDate->copy()->addMonths($i);
                $allMonths[] = $month->format('M Y');
            }

            foreach ($databases as $key => $db) {
                try {
                    Log::info("Querying {$db['connection']} from {$startDate} to {$endDate}");

                    // Check if table exists first
                    $tableExists = DB::connection($db['connection'])
                        ->select("SHOW TABLES LIKE 'data_pencatatans'");

                    if (empty($tableExists)) {
                        Log::error("Table 'data_pencatatans' not found in {$db['connection']}");
                        throw new \Exception("Table not found");
                    }

                    $data = DB::connection($db['connection'])
                        ->table('data_pencatatans')
                        ->selectRaw('DATE_FORMAT(tanggal_ditetapkan, "%b %Y") as month, COUNT(*) as count')
                        ->whereBetween('tanggal_ditetapkan', [$startDate, $endDate])
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get();

                    Log::info("Query result for {$db['connection']}: " . $data->count() . " rows");

                    // Initialize dataset with zeros
                    $monthlyData = array_fill(0, count($allMonths), 0);

                    // Fill in actual counts
                    foreach ($data as $row) {
                        $monthIndex = array_search($row->month, $allMonths);
                        if ($monthIndex !== false) {
                            $monthlyData[$monthIndex] = (int) $row->count;
                        }
                    }

                    $datasets[] = [
                        'label' => $db['label'],
                        'data' => $monthlyData,
                        'borderColor' => $db['color'],
                        'backgroundColor' => hex2rgba($db['color'], 0.1),
                        'fill' => true,
                        'tension' => 0.3
                    ];

                } catch (\Exception $e) {
                    Log::error("Error fetching data from {$db['connection']}: " . $e->getMessage());

                    // Add empty dataset if database is not accessible
                    $datasets[] = [
                        'label' => $db['label'],
                        'data' => array_fill(0, count($allMonths), 0),
                        'borderColor' => $db['color'],
                        'backgroundColor' => hex2rgba($db['color'], 0.1),
                        'fill' => true,
                        'tension' => 0.3
                    ];
                }
            }

            return [
                'labels' => $allMonths,
                'datasets' => $datasets
            ];
        });
    }

    /**
     * Get province statistics for a specific month
     *
     * @param string $month
     * @return array
     */
    public function getProvinceStats(string $month): array
    {
        $cacheKey = 'dashboard.province_stats.' . $month;

        return Cache::remember($cacheKey, 300, function () use ($month) {
            try {
                // Parse month (e.g., "December 2025")
                $date = \Carbon\Carbon::createFromFormat('F Y', $month);
                $startDate = $date->copy()->startOfMonth();
                $endDate = $date->copy()->endOfMonth();
            } catch (\Exception $e) {
                // If parsing fails, use current month
                Log::warning("Failed to parse month '{$month}', using current month: " . $e->getMessage());
                $date = now();
                $startDate = $date->copy()->startOfMonth();
                $endDate = $date->copy()->endOfMonth();
            }

            $provinceStats = [];

            $databases = [
                'balai' => ['connection' => 'mysql_balai', 'color' => '#4F46E5'],
                'reguler' => ['connection' => 'mysql_reguler', 'color' => '#10B981'],
                'suisei' => ['connection' => 'mysql_fg', 'color' => '#F59E0B']
            ];

            foreach ($databases as $key => $db) {
                try {
                    // Get province data from each database
                    $provinces = DB::connection($db['connection'])
                        ->table('data_pencatatans')
                        ->select('propinsi', DB::raw('COUNT(*) as count'))
                        ->whereBetween('tanggal_ditetapkan', [$startDate, $endDate])
                        ->groupBy('propinsi')
                        ->orderByDesc('count')
                        ->get();

                    // Merge data from all databases
                    foreach ($provinces as $province) {
                        $provinceName = $province->propinsi;
                        if (!isset($provinceStats[$provinceName])) {
                            $provinceStats[$provinceName] = [
                                'total' => 0,
                                'trend' => 0,
                                'databases' => []
                            ];
                        }

                        $provinceStats[$provinceName]['total'] += $province->count;
                        $provinceStats[$provinceName]['databases'][] = [
                            'name' => $key,
                            'count' => $province->count,
                            'color' => $db['color']
                        ];
                    }

                } catch (\Exception $e) {
                    Log::error("Error getting province stats from {$db['connection']}: " . $e->getMessage());
                }
            }

            // Calculate trend based on current phase
            $currentMonth = now()->month;

            if ($currentMonth >= 1 && $currentMonth <= 6) {
                // Phase 1 (Jan-Jun): Compare with previous year Phase 1 (Jan-Jun last year)
                $previousStartDate = $date->copy()->subYear()->startOfMonth();
                $previousEndDate = $date->copy()->subYear()->addMonths(5)->endOfMonth();
            } else {
                // Phase 2 (Jul-Dec): Compare with previous year Phase 2 (Jul-Dec last year)
                $previousStartDate = $date->copy()->subYear()->addMonths(6)->startOfMonth();
                $previousEndDate = $date->copy()->subYear()->addMonths(11)->endOfMonth();
            }

            // Get previous phase stats
            $previousStats = $this->getPreviousPhaseStats($previousStartDate, $previousEndDate);

            foreach ($provinceStats as $province => &$data) {
                if (isset($previousStats[$province])) {
                    $previousTotal = $previousStats[$province]['total'];
                    $currentTotal = $data['total'];

                    if ($previousTotal > 0) {
                        $data['trend'] = round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1);
                    } else {
                        $data['trend'] = $currentTotal > 0 ? 100 : 0;
                    }
                } else {
                    $data['trend'] = $data['total'] > 0 ? 100 : 0;
                }

                // Add breakdown data for display
                $data['balai'] = 0;
                $data['reguler'] = 0;
                $data['fg'] = 0;

                foreach ($data['databases'] as $db) {
                    if ($db['name'] === 'balai') $data['balai'] = $db['count'];
                    if ($db['name'] === 'reguler') $data['reguler'] = $db['count'];
                    if ($db['name'] === 'suisei') $data['fg'] = $db['count'];
                }

                // Remove databases array to clean up output
                unset($data['databases']);
            }

            return $provinceStats;
        });
    }

    /**
     * Get monthly province chart data for 6 months from provinsi column
     *
     * @return array
     */
    public function getMonthlyProvinceChart(): array
    {
        $cacheKey = 'dashboard.monthly_province_chart_2years';

        return Cache::remember($cacheKey, 300, function () {
            $endDate = now();
            $startDate = $endDate->copy()->subMonths(23)->startOfMonth(); // 2 years = 24 months

            // Get all unique provinces from all databases
            $allProvinces = [];
            $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];

            foreach ($databases as $connection) {
                try {
                    $provinces = DB::connection($connection)
                        ->table('data_pencatatans')
                        ->select('propinsi')
                        ->where('propinsi', '!=', '')
                        ->whereNotNull('propinsi')
                        ->distinct()
                        ->pluck('propinsi')
                        ->toArray();

                    $allProvinces = array_merge($allProvinces, $provinces);
                } catch (\Exception $e) {
                    Log::error("Error getting provinces from {$connection}: " . $e->getMessage());
                }
            }

            // Remove duplicates and sort
            $uniqueProvinces = array_unique($allProvinces);
            sort($uniqueProvinces);

            // Take top 10 provinces by total count
            $provinceCounts = [];
            foreach ($uniqueProvinces as $province) {
                $total = 0;
                foreach ($databases as $connection) {
                    try {
                        $count = DB::connection($connection)
                            ->table('data_pencatatans')
                            ->where('propinsi', $province)
                            ->whereBetween('tanggal_ditetapkan', [$startDate, $endDate])
                            ->count();
                        $total += $count;
                    } catch (\Exception $e) {
                        Log::error("Error counting province {$province} from {$connection}: " . $e->getMessage());
                    }
                }
                $provinceCounts[$province] = $total;
            }

            // Sort by total count and take top 10
            arsort($provinceCounts);
            $topProvinces = array_slice(array_keys($provinceCounts), 0, 10, true);

            // Generate month labels (Indonesian format) for 24 months (2 years)
            $allMonths = [];
            for ($i = 23; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $allMonths[] = $month->format('M Y');
            }

            // Get monthly data for each province
            $datasets = [];
            $colors = [
                'rgba(79, 70, 229, 0.8)',   // Blue
                'rgba(16, 185, 129, 0.8)',   // Green
                'rgba(245, 158, 11, 0.8)',   // Orange
                'rgba(236, 72, 153, 0.8)',   // Purple
                'rgba(239, 68, 68, 0.8)',    // Red
                'rgba(55, 48, 163, 0.8)',    // Indigo
                'rgba(107, 33, 168, 0.8)',   // Pink
                'rgba(14, 165, 233, 0.8)',   // Sky
                'rgba(251, 146, 60, 0.8)',   // Yellow
                'rgba(34, 197, 94, 0.8)'     // Emerald
            ];

            foreach ($topProvinces as $index => $province) {
                $provinceData = [];

                // Get data for each month
                for ($i = 5; $i >= 0; $i--) {
                    $monthDate = now()->subMonths($i);
                    $monthStartDate = $monthDate->copy()->startOfMonth();
                    $monthEndDate = $monthDate->copy()->endOfMonth();

                    $monthlyCount = 0;

                    foreach ($databases as $connection) {
                        try {
                            $count = DB::connection($connection)
                                ->table('data_pencatatans')
                                ->where('propinsi', $province)
                                ->whereBetween('tanggal_ditetapkan', [$monthStartDate, $monthEndDate])
                                ->count();

                            $monthlyCount += $count;
                        } catch (\Exception $e) {
                            Log::error("Error getting monthly data for {$province} from {$connection}: " . $e->getMessage());
                        }
                    }

                    $provinceData[] = $monthlyCount;
                }

                $datasets[] = [
                    'label' => $province,
                    'data' => $provinceData,
                    'backgroundColor' => $colors[$index % count($colors)],
                    'borderColor' => str_replace('0.8', '1', $colors[$index % count($colors)]),
                    'borderWidth' => 2,
                    'borderRadius' => 4
                ];
            }

            return [
                'labels' => $allMonths,
                'datasets' => $datasets
            ];
        });
    }

    /**
     * Get previous month stats for trend calculation
     *
     * @param string $month
     * @return array
     */
    private function getPreviousMonthStats(string $month): array
    {
        try {
            $date = \Carbon\Carbon::createFromFormat('F Y', $month);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();

            $provinceStats = [];

            $databases = ['mysql_balai', 'mysql_reguler', 'mysql_fg'];

            foreach ($databases as $connection) {
                try {
                    $provinces = DB::connection($connection)
                        ->table('data_pencatatans')
                        ->select('propinsi', DB::raw('COUNT(*) as count'))
                        ->whereBetween('tanggal_ditetapkan', [$startDate, $endDate])
                        ->groupBy('propinsi')
                        ->get();

                    foreach ($provinces as $province) {
                        $provinceName = $province->propinsi;
                        if (!isset($provinceStats[$provinceName])) {
                            $provinceStats[$provinceName] = ['total' => 0];
                        }
                        $provinceStats[$provinceName]['total'] += $province->count;
                    }
                } catch (\Exception $e) {
                    // Ignore errors for previous month
                }
            }

            return $provinceStats;

        } catch (\Exception $e) {
            // If parsing fails, return empty array
            Log::warning("Failed to parse previous month '{$month}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get summary statistics for all databases
     *
     * @return array Summary data
     */
    public function getSummaryStats(): array
    {
        $cacheKey = 'dashboard.data_pencatatans.summary';

        return Cache::remember($cacheKey, 300, function () {
            $stats = [];

            $databases = [
                'balai' => [
                    'connection' => 'mysql_balai',
                    'label' => 'Balai'
                ],
                'reguler' => [
                    'connection' => 'mysql_reguler',
                    'label' => 'Reguler'
                ],
                'suisei' => [
                    'connection' => 'mysql_fg',
                    'label' => 'FG/Suisei'
                ]
            ];

            foreach ($databases as $key => $db) {
                try {
                    $count = DB::connection($db['connection'])
                        ->table('data_pencatatans')
                        ->count();

                    $stats[$key] = [
                        'label' => $db['label'],
                        'count' => $count
                    ];
                } catch (\Exception $e) {
                    Log::error("Error getting summary stats from {$db['connection']}: " . $e->getMessage());
                    $stats[$key] = [
                        'label' => $db['label'],
                        'count' => 0
                    ];
                }
            }

            return $stats;
        });
    }

    /**
     * Get stats for previous phase
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getPreviousPhaseStats($startDate, $endDate): array
    {
        $previousStats = [];

        $databases = [
            'balai' => 'mysql_balai',
            'reguler' => 'mysql_reguler',
            'suisei' => 'mysql_fg'
        ];

        foreach ($databases as $key => $connection) {
            try {
                $provinces = DB::connection($connection)
                    ->table('data_pencatatans')
                    ->select('propinsi', DB::raw('COUNT(*) as count'))
                    ->whereBetween('tanggal_ditetapkan', [$startDate, $endDate])
                    ->groupBy('propinsi')
                    ->get();

                foreach ($provinces as $province) {
                    $provinceName = $province->propinsi;
                    if (!isset($previousStats[$provinceName])) {
                        $previousStats[$provinceName]['total'] = 0;
                    }
                    $previousStats[$provinceName]['total'] += $province->count;
                }
            } catch (\Exception $e) {
                Log::error("Error getting previous phase stats from {$connection}: " . $e->getMessage());
            }
        }

        return $previousStats;
    }
}

/**
 * Convert hex color to rgba
 */
if (!function_exists('hex2rgba')) {
    function hex2rgba($color, $opacity = 0) {
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
        return "rgba({$r}, {$g}, {$b}, {$opacity})";
    }
}