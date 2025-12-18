<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoDiscoveryService
{
    /**
     * Discover and register new data sources automatically
     */
    public function discoverAndRegister(DataSourceManager $manager): array
    {
        $discovered = [];
        $config = Config::get('dashboard.auto_discovery', []);

        if (!($config['enabled'] ?? false)) {
            return $discovered;
        }

        $pattern = $config['connection_pattern'] ?? 'mysql_%';
        $exclude = $config['exclude'] ?? ['mysql', 'mysql_testing'];
        $defaultTable = $config['default_table'] ?? 'data_pencatatans';

        // Get all database connections
        $connections = array_keys(Config::get('database.connections', []));

        foreach ($connections as $connection) {
            // Check if connection matches pattern and not excluded
            if ($this->matchesPattern($connection, $pattern) && !in_array($connection, $exclude)) {
                $source = $this->discoverConnection($connection, $defaultTable);

                if ($source) {
                    $manager->addDataSource($source);
                    $discovered[] = $source;

                    Log::info("Auto-discovered data source: {$source['identifier']}");
                }
            }
        }

        return $discovered;
    }

    /**
     * Discover a single connection
     */
    protected function discoverConnection(string $connection, string $table): ?array
    {
        try {
            // Test connection
            DB::connection($connection)->getPdo();

            // Check if table exists
            $tables = DB::connection($connection)->getDoctrineSchemaManager()->listTableNames();

            if (!in_array($table, $tables)) {
                return null;
            }

            // Check if table has required columns
            $columns = DB::connection($connection)
                ->getDoctrineSchemaManager()
                ->listTableColumns($table);

            if (!$this->hasRequiredColumns($columns)) {
                return null;
            }

            // Extract identifier from connection name
            $identifier = $this->extractIdentifier($connection);

            return [
                'class' => \App\Services\Dashboard\DataSources\GenericDataSource::class,
                'connection' => $connection,
                'table' => $table,
                'identifier' => $identifier,
                'display_name' => $this->generateDisplayName($identifier),
                'color' => $this->generateColor($identifier)
            ];

        } catch (\Exception $e) {
            Log::debug("Could not discover connection {$connection}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if connection name matches pattern
     */
    protected function matchesPattern(string $connection, string $pattern): bool
    {
        // Convert wildcard to regex
        $regex = '/^' . str_replace('*', '.*', $pattern) . '$/';
        return preg_match($regex, $connection);
    }

    /**
     * Check if table has required columns
     */
    protected function hasRequiredColumns(array $columns): bool
    {
        $required = ['tanggal_ditetapkan', 'propinsi'];

        foreach ($required as $column) {
            if (!isset($columns[$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract identifier from connection name
     */
    protected function extractIdentifier(string $connection): string
    {
        // Remove 'mysql_' prefix and convert to lowercase
        return str_replace('mysql_', '', $connection);
    }

    /**
     * Generate display name from identifier
     */
    protected function generateDisplayName(string $identifier): string
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $identifier));
    }

    /**
     * Generate a unique color for the data source
     */
    protected function generateColor(string $identifier): string
    {
        // Generate color based on identifier hash
        $hash = crc32($identifier);
        $hue = $hash % 360;

        // Convert HSL to hex with good saturation and lightness
        return $this->hslToHex($hue, 70, 50);
    }

    /**
     * Convert HSL to hex color
     */
    protected function hslToHex(int $h, int $s, int $l): string
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        $r = $g = $b = 0;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $hue2rgb = function($p, $q, $t) {
                if ($t < 0) $t += 1;
                if ($t > 1) $t -= 1;
                if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
                if ($t < 1/2) return $q;
                if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
                return $p;
            };

            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $hue2rgb($p, $q, $h + 1/3);
            $g = $hue2rgb($p, $q, $h);
            $b = $hue2rgb($p, $q, $h - 1/3);
        }

        return sprintf("#%02x%02x%02x", round($r * 255), round($g * 255), round($b * 255));
    }
}