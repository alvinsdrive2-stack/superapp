<?php

namespace App\Services\Dashboard\DataSources;

use App\Services\Dashboard\Abstractions\BaseDataSource;

class BalaiDataSource extends BaseDataSource
{
    protected function getDefaultConnection(): string
    {
        return 'mysql_balai';
    }

    protected function getDefaultTable(): string
    {
        return 'data_pencatatans';
    }

    protected function getDefaultIdentifier(): string
    {
        return 'balai';
    }

    protected function getDefaultDisplayName(): string
    {
        return 'Balai';
    }

    protected function getDefaultColor(): string
    {
        return '#4F46E5';
    }
}