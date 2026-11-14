<?php

namespace Coding1688\DevTools\Diagnostics;

use Coding1688\DevTools\SqlQueryProfiler;
use Illuminate\Support\Facades\Cache;

/**
 * Aggregates the cached request profiles into cross-request rankings.
 */
final class PerformanceAggregator
{
    public function __construct(private readonly SqlQueryProfiler $profiler) {}

    /** @return array<string, mixed> */
    public function report(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('pl-devtools:performance');
        }
        $ttl = max(10, (int) config('devtools.diagnostics_ttl', 300));

        return Cache::remember('pl-devtools:performance', $ttl, function (): array {
            $top = max(3, (int) config('devtools.performance.top', 10));
            $slowMs = (int) config('devtools.slow_ms', 100);
            $profiles = $this->profiler->recent();
            $requests = [];
            $queries = [];
            $repeated = [];
            $slow = 0;

            foreach ($profiles as $profile) {
                $requests[] = [
                    'method' => $profile['method'] ?? null,
                    'path' => $profile['path'] ?? null,
                    'type' => $profile['type'] ?? 'html',
                    'status' => $profile['status'] ?? null,
                    'duration_ms' => (float) ($profile['duration_ms'] ?? 0),
                    'sql_ms' => (float) ($profile['sql_ms'] ?? 0),
                    'query_count' => (int) ($profile['query_count'] ?? 0),
                    'created_at' => $profile['created_at'] ?? null,
                ];
                foreach ((array) ($profile['queries'] ?? []) as $query) {
                    $ms = (float) ($query['ms'] ?? 0);
                    if ($ms >= $slowMs) {
                        $slow++;
                    }
                    $queries[] = [
                        'sql' => (string) ($query['sql'] ?? ''),
                        'ms' => $ms,
                        'source' => $query['source'] ?? null,
                        'path' => $profile['path'] ?? null,
                        'created_at' => $profile['created_at'] ?? null,
                    ];
                }
                foreach ((array) ($profile['duplicates'] ?? []) as $group) {
                    $key = (string) ($group['sql'] ?? '');
                    $repeated[$key] ??= ['sql' => $key, 'count' => 0, 'ms' => 0.0, 'requests' => 0];
                    $repeated[$key]['count'] += (int) ($group['count'] ?? 0);
                    $repeated[$key]['ms'] += (float) ($group['ms'] ?? 0);
                    $repeated[$key]['requests']++;
                }
            }

            usort($requests, fn (array $first, array $second): int => $second['duration_ms'] <=> $first['duration_ms']);
            usort($queries, fn (array $first, array $second): int => $second['ms'] <=> $first['ms']);
            $repeated = array_values($repeated);
            usort($repeated, fn (array $first, array $second): int => $second['ms'] <=> $first['ms']);

            return [
                'generated_at' => now()->toIso8601String(),
                'totals' => ['requests' => count($profiles), 'slow_queries' => $slow],
                'requests' => array_slice($requests, 0, $top),
                'queries' => array_slice($queries, 0, $top),
                'repeated' => array_slice($repeated, 0, $top),
            ];
        });
    }
}
