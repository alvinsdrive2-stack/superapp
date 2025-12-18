<?php

namespace App\Services\Dashboard\DataSources;

use App\Services\Dashboard\Abstractions\BaseDataSource;

class RegulerDataSource extends BaseDataSource
{
    protected function getDefaultConnection(): string
    {
        return 'mysql_reguler';
    }

    protected function getDefaultTable(): string
    {
        return 'data_pencatatans';
    }

    protected function getDefaultIdentifier(): string
    {
        return 'reguler';
    }

    protected function getDefaultDisplayName(): string
    {
        return 'Reguler';
    }

    protected function getDefaultColor(): string
    {
        return '#10B981';
    }
}