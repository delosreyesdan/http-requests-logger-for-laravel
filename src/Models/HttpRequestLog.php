<?php

declare(strict_types=1);

namespace Ddelosreyes\HttpRequestsLogger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HttpRequestLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'headers'     => 'array',
        'body'        => 'array',
        'status'      => 'integer',
        'duration_ms' => 'integer',
        'user_id'     => 'integer',
    ];

    public function getTable(): string
    {
        return config('http-request-logger.table', 'http_request_logs');
    }

    public function scopeIncoming(Builder $query): Builder
    {
        return $query->where('direction', 'in');
    }

    public function scopeOutgoing(Builder $query): Builder
    {
        return $query->where('direction', 'out');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', '>=', 400);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', '<', 400);
    }

    public function scopeSlow(Builder $query, int $thresholdMs = 1000): Builder
    {
        return $query->where('duration_ms', '>=', $thresholdMs);
    }

    public function scopeWithStatus(Builder $query, int $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFromIp(Builder $query, string $ip): Builder
    {
        return $query->where('ip', $ip);
    }

    public function scopeForUrl(Builder $query, string $url): Builder
    {
        return $query->where('url', 'like', "%{$url}%");
    }

    public function scopeWithin(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithRequestId(Builder $query, string $requestId): Builder
    {
        return $query->where('request_id', $requestId);
    }
}
