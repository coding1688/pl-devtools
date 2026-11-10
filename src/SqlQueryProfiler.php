<?php

namespace Coding1688\Debug;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SqlQueryProfiler
{
    private bool $recording = false;

    private int $startedAt = 0;

    private int $count = 0;

    private int $slowCount = 0;

    private float $milliseconds = 0;

    private array $queries = [];

    private array $patterns = [];

    private array $stages = [];

    private array $activeStages = [];

    private float $collectionMilliseconds = 0;

    /** Measure inclusive wall time; recursive calls with the same name count only once. */
    public function measure(string $name, callable $callback): mixed
    {
        if (! $this->recording || isset($this->activeStages[$name])) {
            return $callback();
        }
        $this->activeStages[$name] = true;
        $start = hrtime(true);
        $sqlStart = $this->milliseconds;
        try {
            return $callback();
        } finally {
            $stage = $this->stages[$name] ?? ['name' => $name, 'duration_ms' => 0, 'sql_ms' => 0, 'calls' => 0];
            $stage['duration_ms'] += (hrtime(true) - $start) / 1e6;
            $stage['sql_ms'] += $this->milliseconds - $sqlStart;
            $stage['calls']++;
            $this->stages[$name] = $stage;
            unset($this->activeStages[$name]);
        }
    }

    public function enabledFor(Request $request): bool
    {
        if (! config('pl.debug.enabled') || ! config('app.debug')) {
            return false;
        }
        $environments = array_values(array_filter((array) config('pl.debug.environments', ['local', 'testing']), 'is_string'));
        if ($environments !== [] && ! app()->environment($environments)) {
            return false;
        }
        $hosts = array_values(array_filter((array) config('pl.debug.hosts', []), 'is_string'));

        return $hosts === [] || in_array($request->getHost(), $hosts, true);
    }

    public function begin(): void
    {
        $this->recording = true;
        $this->startedAt = hrtime(true);
        $this->count = $this->slowCount = 0;
        $this->milliseconds = 0;
        $this->queries = $this->patterns = $this->stages = $this->activeStages = [];
        $this->collectionMilliseconds = 0;
    }

    public function record(QueryExecuted $event): void
    {
        if (! $this->recording) {
            return;
        }
        $startedAt = hrtime(true);
        try {
            $this->count++;
            $this->milliseconds += (float) $event->time;
            $this->slowCount += $event->time >= config('pl.debug.slow_ms') ? 1 : 0;
            $fingerprint = hash('sha256', $event->sql);
            $this->patterns[$fingerprint] = ($this->patterns[$fingerprint] ?? 0) + 1;
            if (count($this->queries) >= config('pl.debug.max_queries')) {
                return;
            }
            $source = null;
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 60) as $frame) {
                $file = $frame['file'] ?? '';
                if (str_starts_with($file, app_path().'/') && ! $this->isExcludedSource($file)) {
                    $source = str_replace(base_path().'/', '', $file).':'.($frame['line'] ?? 0);
                    break;
                }
            }
            $this->queries[] = [
                'number' => $this->count, 'sql' => Str::limit($event->sql, config('pl.debug.max_sql_length'), ' …'),
                'ms' => round((float) $event->time, 3), 'connection' => $event->connectionName,
                'parameter_count' => count($event->bindings), 'fingerprint' => $fingerprint, 'source' => $source,
            ];
        } finally {
            $this->collectionMilliseconds += (hrtime(true) - $startedAt) / 1e6;
        }
    }

    private function isExcludedSource(string $file): bool
    {
        foreach ((array) config('pl.debug.exclude_paths', []) as $path) {
            if (is_string($path) && $path !== '' && str_starts_with($file, $path)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function finish(Request $request, int $status): array
    {
        $duration = (hrtime(true) - $this->startedAt) / 1e6;
        $this->recording = false;
        $stages = array_map(static function (array $stage): array {
            return [...$stage, 'duration_ms' => round($stage['duration_ms'], 2), 'sql_ms' => round($stage['sql_ms'], 2),
                'non_sql_ms' => round(max(0, $stage['duration_ms'] - $stage['sql_ms']), 2)];
        }, array_values($this->stages));
        $queries = array_map(function (array $query): array {
            $query['repetitions'] = $this->patterns[$query['fingerprint']];
            unset($query['fingerprint']);

            return $query;
        }, $this->queries);
        usort($queries, fn (array $first, array $second): int => $second['ms'] <=> $first['ms']);
        $path = $request->getPathInfo();
        if (preg_match('~/(auth|account)(/|$)~', $path) === 1) {
            $path = '/'.($request->route()?->uri() ?? '[private]');
        }
        $profile = [
            'id' => (string) Str::uuid(), 'created_at' => now()->toIso8601String(), 'host' => $request->getHost(),
            'method' => $request->method(), 'path' => $path, 'route' => $request->route()?->getName(), 'status' => $status,
            'duration_ms' => round($duration, 2), 'query_count' => $this->count,
            'non_sql_ms' => round(max(0, $duration - $this->milliseconds), 2),
            'collection_ms' => round($this->collectionMilliseconds, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'stages' => $stages,
            'sql_ms' => round($this->milliseconds, 2), 'slow_count' => $this->slowCount,
            'repeated_count' => array_sum(array_map(fn (int $count): int => max(0, $count - 1), $this->patterns)),
            'queries' => $queries,
        ];
        $this->persist($profile, $queries);

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array<string, mixed>>  $queries
     */
    private function persist(array $profile, array $queries): void
    {
        try {
            if (config('pl.debug.store_cache', true)) {
                $cache = Cache::store(config('pl.debug.cache_store'));
                $cache->put('sql-debug:'.$profile['id'], $profile, config('pl.debug.ttl_seconds'));
                $cache->lock('sql-debug:history-lock', 5)->block(1, function () use ($cache, $profile): void {
                    $ids = [$profile['id'], ...$cache->get('sql-debug:history', [])];
                    $limit = max(1, (int) config('pl.debug.history_limit'));
                    $cache->put('sql-debug:history', array_slice($ids, 0, $limit), config('pl.debug.ttl_seconds'));
                    foreach (array_slice($ids, $limit) as $expired) {
                        $cache->forget('sql-debug:'.$expired);
                    }
                });
            }
            if (config('pl.debug.log', false)) {
                Log::channel((string) config('pl.debug.log_channel', 'single'))->info('HTTP SQL profile', [
                    ...array_diff_key($profile, ['queries' => true]),
                    'slow_queries' => array_slice(array_values(array_filter($queries, fn (array $query): bool => $query['ms'] >= config('pl.debug.slow_ms'))), 0, 10),
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /** @return list<array<string, mixed>> */
    public function recent(?string $id = null): array
    {
        $cache = Cache::store(config('pl.debug.cache_store'));
        $ids = $id !== null ? [$id] : $cache->get('sql-debug:history', []);
        $profiles = [];
        foreach ($ids as $key) {
            if (is_array($profile = $cache->get('sql-debug:'.$key))) {
                $profiles[] = $profile;
            }
        }

        return $profiles;
    }
}
