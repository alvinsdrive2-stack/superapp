<?php

namespace App\Services\Dashboard;

use App\Services\Dashboard\Interfaces\DataSourceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class DataSourceManager
{
    protected Collection $dataSources;
    protected array $registeredSources = [];

    public function __construct()
    {
        $this->dataSources = collect();
        $this->autoDiscoverDataSources();
    }

    /**
     * Auto-discover data sources from configuration
     */
    protected function autoDiscoverDataSources(): void
    {
        // Get data sources from config
        $sources = Config::get('dashboard.data_sources', []);

        foreach ($sources as $sourceConfig) {
            $this->register($sourceConfig['class'], $sourceConfig);
        }

        // Register default sources if not configured
        if ($this->dataSources->isEmpty()) {
            $this->registerDefaultSources();
        }
    }

    /**
     * Register default data sources
     */
    protected function registerDefaultSources(): void
    {
        $this->register(\App\Services\Dashboard\DataSources\BalaiDataSource::class);
        $this->register(\App\Services\Dashboard\DataSources\RegulerDataSource::class);
        $this->register(\App\Services\Dashboard\DataSources\FGSuiseiDataSource::class);
    }

    /**
     * Register a new data source
     */
    public function register(string $className, array $config = []): self
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Data source class {$className} not found");
        }

        $instance = new $className($config);

        if (!$instance instanceof DataSourceInterface) {
            throw new \InvalidArgumentException("Data source must implement DataSourceInterface");
        }

        $this->dataSources->put($instance->getIdentifier(), $instance);
        $this->registeredSources[$instance->getIdentifier()] = $className;

        return $this;
    }

    /**
     * Get all data sources
     */
    public function all(): Collection
    {
        return $this->dataSources;
    }

    /**
     * Get available data sources
     */
    public function getAvailable(): Collection
    {
        return $this->dataSources->filter(fn($source) => $source->isAvailable());
    }

    /**
     * Get a specific data source
     */
    public function get(string $identifier): ?DataSourceInterface
    {
        return $this->dataSources->get($identifier);
    }

    /**
     * Get data sources as array for API response
     */
    public function toArray(): array
    {
        return $this->dataSources->map(function ($source) {
            return [
                'id' => $source->getIdentifier(),
                'name' => $source->getDisplayName(),
                'color' => $source->getColor(),
                'available' => $source->isAvailable()
            ];
        })->values()->toArray();
    }

    /**
     * Add a new data source dynamically
     */
    public function addDataSource(array $config): self
    {
        // Support for adding new databases without code changes
        $sourceClass = $config['class'] ?? \App\Services\Dashboard\DataSources\GenericDataSource::class;
        $this->register($sourceClass, $config);

        return $this;
    }

    /**
     * Remove a data source
     */
    public function removeDataSource(string $identifier): self
    {
        $this->dataSources->forget($identifier);
        unset($this->registeredSources[$identifier]);

        return $this;
    }

    /**
     * Get registered sources configuration
     */
    public function getRegisteredSources(): array
    {
        return $this->registeredSources;
    }

    /**
     * Check if data source exists
     */
    public function has(string $identifier): bool
    {
        return $this->dataSources->has($identifier);
    }

    /**
     * Execute callback on all available data sources
     */
    public function onAvailable(callable $callback): array
    {
        $results = [];

        foreach ($this->getAvailable() as $source) {
            try {
                $results[$source->getIdentifier()] = $callback($source);
            } catch (\Exception $e) {
                \Log::error("Error processing {$source->getIdentifier()}: " . $e->getMessage());
                $results[$source->getIdentifier()] = null;
            }
        }

        return $results;
    }
}