<?php

namespace App\Services\Dashboard\Abstractions;

use App\Services\Dashboard\Interfaces\DataSourceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonPeriod;

abstract class BaseDataSource implements DataSourceInterface
{
    protected $connection;
    protected $table;
    protected $identifier;
    protected $displayName;
    protected $color;
    protected $dateColumn = 'tanggal_ditetapkan';
    protected $provinceColumn = 'propinsi';

    public function __construct(array $config = [])
    {
        $this->configure($config);
    }

    /**
     * Configure the data source
     */
    protected function configure(array $config): void
    {
        $this->connection = $config['connection'] ?? $this->getDefaultConnection();
        $this->table = $config['table'] ?? $this->getDefaultTable();
        $this->identifier = $config['identifier'] ?? $this->getDefaultIdentifier();
        $this->displayName = $config['display_name'] ?? $this->getDefaultDisplayName();
        $this->color = $config['color'] ?? $this->getDefaultColor();
        $this->dateColumn = $config['date_column'] ?? 'tanggal_ditetapkan';
        $this->provinceColumn = $config['province_column'] ?? 'propinsi';
    }

    /**
     * Get default connection name
     */
    abstract protected function getDefaultConnection(): string;

    /**
     * Get default table name
     */
    abstract protected function getDefaultTable(): string;

    /**
     * Get default identifier
     */
    abstract protected function getDefaultIdentifier(): string;

    /**
     * Get default display name
     */
    abstract protected function getDefaultDisplayName(): string;

    /**
     * Get default color
     */
    abstract protected function getDefaultColor(): string;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isAvailable(): bool
    {
        try {
            DB::connection($this->connection)
                ->table($this->table)
                ->selectRaw('1')
                ->limit(1)
                ->get();
            return true;
        } catch (\Exception $e) {
            Log::error("Data source {$this->identifier} unavailable: " . $e->getMessage());
            return false;
        }
    }

    public function getTimeSeriesData(CarbonPeriod $period): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        try {
            $query = DB::connection($this->connection)
                ->table($this->table)
                ->selectRaw("
                    DATE_FORMAT({$this->dateColumn}, '%Y-%m-%d') as date,
                    COUNT(*) as count
                ")
                ->whereBetween($this->dateColumn, [
                    $period->getStart()->format('Y-m-d'),
                    $period->getEnd()->format('Y-m-d')
                ])
                ->groupBy('date')
                ->orderBy('date');

            return $query->get()->keyBy('date')->toArray();
        } catch (\Exception $e) {
            Log::error("Error getting time series from {$this->identifier}: " . $e->getMessage());
            return [];
        }
    }

    public function getProvinceData(CarbonPeriod $period): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        try {
            return DB::connection($this->connection)
                ->table($this->table)
                ->select("{$this->provinceColumn} as province", DB::raw('COUNT(*) as count'))
                ->where($this->provinceColumn, '!=', '')
                ->whereNotNull($this->provinceColumn)
                ->whereBetween($this->dateColumn, [
                    $period->getStart()->format('Y-m-d'),
                    $period->getEnd()->format('Y-m-d')
                ])
                ->groupBy($this->provinceColumn)
                ->orderByDesc('count')
                ->get()
                ->keyBy('province')
                ->toArray();
        } catch (\Exception $e) {
            Log::error("Error getting province data from {$this->identifier}: " . $e->getMessage());
            return [];
        }
    }

    public function getSummaryStats(CarbonPeriod $period): array
    {
        if (!$this->isAvailable()) {
            return [
                'total' => 0,
                'unique_provinces' => 0,
                'avg_per_day' => 0,
                'peak_day' => null,
                'peak_count' => 0
            ];
        }

        try {
            $summary = DB::connection($this->connection)
                ->table($this->table)
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(DISTINCT {$this->provinceColumn}) as unique_provinces,
                    COUNT(*) / DATEDIFF(?, ?) as avg_per_day
                ", [
                    $period->getEnd()->format('Y-m-d'),
                    $period->getStart()->format('Y-m-d')
                ])
                ->whereBetween($this->dateColumn, [
                    $period->getStart()->format('Y-m-d'),
                    $period->getEnd()->format('Y-m-d')
                ])
                ->first();

            // Get peak day
            $peak = DB::connection($this->connection)
                ->table($this->table)
                ->selectRaw("
                    DATE({$this->dateColumn}) as date,
                    COUNT(*) as count
                ")
                ->whereBetween($this->dateColumn, [
                    $period->getStart()->format('Y-m-d'),
                    $period->getEnd()->format('Y-m-d')
                ])
                ->groupBy('date')
                ->orderByDesc('count')
                ->first();

            return [
                'total' => (int) $summary->total,
                'unique_provinces' => (int) $summary->unique_provinces,
                'avg_per_day' => round($summary->avg_per_day, 2),
                'peak_day' => $peak ? $peak->date : null,
                'peak_count' => $peak ? (int) $peak->count : 0
            ];
        } catch (\Exception $e) {
            Log::error("Error getting summary stats from {$this->identifier}: " . $e->getMessage());
            return [
                'total' => 0,
                'unique_provinces' => 0,
                'avg_per_day' => 0,
                'peak_day' => null,
                'peak_count' => 0
            ];
        }
    }

    public function getTotalCount(CarbonPeriod $period): int
    {
        if (!$this->isAvailable()) {
            return 0;
        }

        try {
            return DB::connection($this->connection)
                ->table($this->table)
                ->whereBetween($this->dateColumn, [
                    $period->getStart()->format('Y-m-d'),
                    $period->getEnd()->format('Y-m-d')
                ])
                ->count();
        } catch (\Exception $e) {
            Log::error("Error getting total count from {$this->identifier}: " . $e->getMessage());
            return 0;
        }
    }

    public function getRawData(array $filters = []): \Illuminate\Support\Collection
    {
        if (!$this->isAvailable()) {
            return collect([]);
        }

        try {
            $query = DB::connection($this->connection)
                ->table($this->table);

            // Apply filters
            if (isset($filters['start_date'])) {
                $query->where($this->dateColumn, '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where($this->dateColumn, '<=', $filters['end_date']);
            }
            if (isset($filters['province'])) {
                $query->where($this->provinceColumn, $filters['province']);
            }
            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error("Error getting raw data from {$this->identifier}: " . $e->getMessage());
            return collect([]);
        }
    }

    public function getDateRange(): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $range = DB::connection($this->connection)
                ->table($this->table)
                ->selectRaw("
                    MIN({$this->dateColumn}) as min_date,
                    MAX({$this->dateColumn}) as max_date
                ")
                ->first();

            return [
                'start' => $range->min_date,
                'end' => $range->max_date
            ];
        } catch (\Exception $e) {
            Log::error("Error getting date range from {$this->identifier}: " . $e->getMessage());
            return null;
        }
    }
}