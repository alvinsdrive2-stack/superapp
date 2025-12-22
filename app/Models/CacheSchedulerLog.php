<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CacheSchedulerLog extends Model
{
    use HasFactory;

    protected $table = 'cache_scheduler_logs';

    protected $fillable = [
        'task_name',
        'status',
        'execution_time',
        'error_message',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'execution_time' => 'decimal:3'
    ];

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_PARTIAL = 'partial';

    /**
     * Log a successful task execution
     */
    public static function logSuccess(string $taskName, float $executionTime, array $metadata = null): self
    {
        return static::create([
            'task_name' => $taskName,
            'status' => self::STATUS_SUCCESS,
            'execution_time' => $executionTime,
            'metadata' => $metadata
        ]);
    }

    /**
     * Log a failed task execution
     */
    public static function logFailure(string $taskName, string $errorMessage, float $executionTime = null, array $metadata = null): self
    {
        return static::create([
            'task_name' => $taskName,
            'status' => self::STATUS_FAILED,
            'execution_time' => $executionTime,
            'error_message' => $errorMessage,
            'metadata' => $metadata
        ]);
    }

    /**
     * Log a partial task execution
     */
    public static function logPartial(string $taskName, float $executionTime, string $errorMessage = null, array $metadata = null): self
    {
        return static::create([
            'task_name' => $taskName,
            'status' => self::STATUS_PARTIAL,
            'execution_time' => $executionTime,
            'error_message' => $errorMessage,
            'metadata' => $metadata
        ]);
    }

    /**
     * Get recent logs for a specific task
     */
    public static function getRecent(string $taskName, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('task_name', $taskName)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Clear old logs (older than specified days)
     */
    public static function clearOld(int $days = 7): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
