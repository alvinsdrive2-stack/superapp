<?php

namespace App\Services\Dashboard\DataSources;

use App\Services\Dashboard\Abstractions\BaseDataSource;

class GenericDataSource extends BaseDataSource
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        parent::__construct($config);
    }

    protected function getDefaultConnection(): string
    {
        return $this->config['connection'] ?? 'mysql';
    }

    protected function getDefaultTable(): string
    {
        return $this->config['table'] ?? 'data_pencatatans';
    }

    protected function getDefaultIdentifier(): string
    {
        return $this->config['identifier'] ?? 'generic';
    }

    protected function getDefaultDisplayName(): string
    {
        return $this->config['display_name'] ?? 'Generic Data Source';
    }

    protected function getDefaultColor(): string
    {
        return $this->config['color'] ?? '#6B7280';
    }
}