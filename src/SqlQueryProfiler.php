<?php

namespace Coding1688\DevTools;

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

    private array $patternSql = [];

    private array $patternMs = [];

    private array $sourceCount = [];

    private array $sourceMs = [];

    private array $sourceSql = [];

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
        if (! config('pl.devtools.enabled') || ! config('app.debug')) {
            return false;
        }
        $environments = array_values(array_filter((array) config('pl.devtools.environments', ['local', 'testing']), 'is_string'));
        if ($environments !== [] && ! app()->environment($environments)) {
            return false;
        }
        $hosts = array_values(array_filter((array) config('pl.devtools.hosts', []), 'is_string'));

        return $hosts === [] || in_array($request->getHost(), $hosts, true);
    }

    public function begin(): void
    {
        $this->recording = true;
        $this->startedAt = hrtime(true);
        $this->count = $this->slowCount = 0;
        $this->milliseconds = 0;
        $this->queries = $this->patterns = $this->patternSql = $this->patternMs = $this->sourceCount = $this->sourceMs = $this->sourceSql = $this->stages = $this->activeStages = [];
        $this->collectionMilliseconds = 0;
    }

    public function record(QueryExecuted $event): void
    {
        if (! $this->recording) {
            return;
        }
        if (config('pl.devtools.ignore_impossible_queries', false) && preg_match('/where\s+0\s*=\s*1/i', $event->sql) === 1) {
            return;
        }
        $startedAt = hrtime(true);
        try {
            $this->count++;
            $this->milliseconds += (float) $event->time;
            $this->slowCount += $event->time >= config('pl.devtools.slow_ms') ? 1 : 0;
            $fingerprint = hash('sha256', $event->sql);
            $this->patterns[$fingerprint] = ($this->patterns[$fingerprint] ?? 0) + 1;
            $this->patternSql[$fingerprint] ??= $event->sql;
            $this->patternMs[$fingerprint] = ($this->patternMs[$fingerprint] ?? 0) + (float) $event->time;
            if (count($this->queries) >= config('pl.devtools.max_queries')) {
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
            $offset = (hrtime(true) - $this->startedAt) / 1e6;
            $sourceKey = $source ?? '(unknown)';
            $this->sourceCount[$sourceKey] = ($this->sourceCount[$sourceKey] ?? 0) + 1;
            $this->sourceMs[$sourceKey] = ($this->sourceMs[$sourceKey] ?? 0) + (float) $event->time;
            $this->sourceSql[$sourceKey] ??= $event->sql;
            $this->queries[] = [
                'number' => $this->count, 'sql' => Str::limit($event->sql, config('pl.devtools.max_sql_length'), ' …'),
                'ms' => round((float) $event->time, 3), 'connection' => $event->connectionName,
                'parameter_count' => count($event->bindings), 'fingerprint' => $fingerprint, 'source' => $source,
                'start_ms' => round($offset, 3),
            ];
        } finally {
            $this->collectionMilliseconds += (hrtime(true) - $startedAt) / 1e6;
        }
    }

    /** @return array<string, mixed> */
    private function requestDetails(Request $request): array
    {
        if (! config('pl.devtools.collect_request', true)) {
            return [];
        }
        $headers = [];
        foreach (['accept', 'content-type', 'x-requested-with', 'referer', 'user-agent'] as $name) {
            $value = $request->headers->get($name);
            if (is_string($value) && $value !== '') {
                $headers[$name] = Str::limit($value, 160);
            }
        }

        return [
            'ip' => $request->ip(),
            'headers' => $headers,
            'middleware' => array_values(array_map('strval', (array) $request->route()?->gatherMiddleware())),
            'query_keys' => array_keys($request->query()),
            'input_keys' => array_keys(array_diff_key($request->all(), $request->allFiles())),
        ];
    }

    private function requestType(Request $request): string
    {
        return $request->expectsJson() || $request->ajax() ? 'ajax' : 'html';
    }

    private function controllerName(Request $request): ?string
    {
        if (! config('pl.devtools.show_controller', true)) {
            return null;
        }
        $action = $request->route()?->getActionName();

        if (! is_string($action) || $action === '' || $action === 'Closure') {
            return null;
        }
        $parts = explode('@', $action);
        $controller = class_basename($parts[0]);

        return count($parts) > 1 ? $controller.'@'.$parts[1] : $controller;
    }

    /** @return array{guard: string, id: int|string}|null */
    private function actor(Request $request): ?array
    {
        if (! config('pl.devtools.track_user', true)) {
            return null;
        }
        foreach ((array) config('pl.devtools.user_guards', ['backend', 'web', 'partner']) as $guard) {
            if (! is_string($guard) || $guard === '' || ! array_key_exists($guard, (array) config('auth.guards', []))) {
                continue;
            }
            $id = $request->user($guard)?->getAuthIdentifier();
            if ($id !== null) {
                return ['guard' => $guard, 'id' => $id];
            }
        }

        return null;
    }

    /** @return list<array{sql: string, count: int, ms: float}> */
    private function duplicateGroups(): array
    {
        $groups = [];
        foreach ($this->patterns as $fingerprint => $count) {
            if ($count < 2) {
                continue;
            }
            $groups[] = [
                'sql' => Str::limit((string) ($this->patternSql[$fingerprint] ?? ''), 240, ' …'),
                'count' => $count,
                'ms' => round((float) ($this->patternMs[$fingerprint] ?? 0), 2),
            ];
        }
        usort($groups, fn (array $first, array $second): int => $second['ms'] <=> $first['ms']);

        return array_slice($groups, 0, (int) config('pl.devtools.duplicate_groups', 5));
    }

    /** @return list<array{source: string, count: int, ms: float, sql: string}> */
    private function hotspots(): array
    {
        $groups = [];
        foreach ($this->sourceCount as $source => $count) {
            if ($count < 2) {
                continue;
            }
            $groups[] = [
                'source' => (string) $source,
                'count' => $count,
                'ms' => round((float) ($this->sourceMs[$source] ?? 0), 2),
                'sql' => Str::limit((string) ($this->sourceSql[$source] ?? ''), 240, ' …'),
            ];
        }
        usort($groups, fn (array $first, array $second): int => $second['ms'] <=> $first['ms']);

        return array_slice($groups, 0, (int) config('pl.devtools.hotspots', 5));
    }

    private function isExcludedSource(string $file): bool
    {
        foreach ((array) config('pl.devtools.exclude_paths', []) as $path) {
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
            'type' => $this->requestType($request), 'controller' => $this->controllerName($request), 'user' => $this->actor($request),
            'request' => $this->requestDetails($request),
            'duration_ms' => round($duration, 2), 'query_count' => $this->count,
            'non_sql_ms' => round(max(0, $duration - $this->milliseconds), 2),
            'collection_ms' => round($this->collectionMilliseconds, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'stages' => $stages,
            'sql_ms' => round($this->milliseconds, 2), 'slow_count' => $this->slowCount,
            'repeated_count' => array_sum(array_map(fn (int $count): int => max(0, $count - 1), $this->patterns)),
            'queries' => $queries, 'duplicates' => $this->duplicateGroups(), 'hotspots' => $this->hotspots(),
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
            if (config('pl.devtools.store_cache', true)) {
                $cache = Cache::store(config('pl.devtools.cache_store'));
                $cache->put('sql-debug:'.$profile['id'], $profile, config('pl.devtools.ttl_seconds'));
                $cache->lock('sql-debug:history-lock', 5)->block(1, function () use ($cache, $profile): void {
                    $ids = [$profile['id'], ...$cache->get('sql-debug:history', [])];
                    $limit = max(1, (int) config('pl.devtools.history_limit'));
                    $cache->put('sql-debug:history', array_slice($ids, 0, $limit), config('pl.devtools.ttl_seconds'));
                    foreach (array_slice($ids, $limit) as $expired) {
                        $cache->forget('sql-debug:'.$expired);
                    }
                });
            }
            if (config('pl.devtools.log', false)) {
                Log::channel((string) config('pl.devtools.log_channel', 'single'))->info('HTTP SQL profile', [
                    ...array_diff_key($profile, ['queries' => true]),
                    'slow_queries' => array_slice(array_values(array_filter($queries, fn (array $query): bool => $query['ms'] >= config('pl.devtools.slow_ms'))), 0, 10),
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function clear(): void
    {
        $cache = Cache::store(config('pl.devtools.cache_store'));
        foreach ((array) $cache->get('sql-debug:history', []) as $id) {
            $cache->forget('sql-debug:'.$id);
        }
        $cache->forget('sql-debug:history');
    }

    /** @return list<array<string, mixed>> */
    public function recent(?string $id = null): array
    {
        $cache = Cache::store(config('pl.devtools.cache_store'));
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
