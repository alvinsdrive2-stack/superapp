<?php

namespace App\Services\Dashboard\DataSources;

use App\Services\Dashboard\Abstractions\BaseDataSource;

class FGSuiseiDataSource extends BaseDataSource
{
    protected function getDefaultConnection(): string
    {
        return 'mysql_fg';
    }

    protected function getDefaultTable(): string
    {
        return 'data_pencatatans';
    }

    protected function getDefaultIdentifier(): string
    {
        return 'fg_suisei';
    }

    protected function getDefaultDisplayName(): string
    {
        return 'FG/Suisei';
    }

    protected function getDefaultColor(): string
    {
        return '#F59E0B';
    }
}