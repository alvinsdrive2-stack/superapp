<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DashboardCache extends Model
{
    use HasFactory;

    protected $table = 'dashboard_cache';

    protected $fillable = [
        'cache_key',
        'cache_data',
        'expires_at'
    ];

    protected $casts = [
        'cache_data' => 'array',
        'expires_at' => 'datetime'
    ];

    /**
     * Check if cache is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get cache if not expired
     */
    public static function getValid(string $key): ?self
    {
        $cache = static::where('cache_key', $key)
            ->where('expires_at', '>', now())
            ->first();

        return $cache;
    }

    /**
     * Store or update cache
     */
    public static function put(string $key, array $data, int $minutes = 10): self
    {
        return static::updateOrCreate(
            ['cache_key' => $key],
            [
                'cache_data' => $data,
                'expires_at' => now()->addMinutes($minutes)
            ]
        );
    }

    /**
     * Clear expired cache entries
     */
    public static function clearExpired(): int
    {
        return static::where('expires_at', '<=', now())->delete();
    }
}
