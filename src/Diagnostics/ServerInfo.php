<?php

namespace Coding1688\DevTools\Diagnostics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Read-only host and framework diagnostics. Computed once, cached for a short
 * TTL, and never included in the per-request profile.
 */
final class ServerInfo
{
    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }
        $ttl = max(10, (int) config('devtools.diagnostics_ttl', 300));

        return $this->memo = Cache::remember('pl-devtools:server', $ttl, fn (): array => $this->collect());
    }

    public function flush(): void
    {
        $this->memo = null;
        Cache::forget('pl-devtools:server');
    }

    /** @return array<string, mixed> */
    private function collect(): array
    {
        return [
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'os' => PHP_OS_FAMILY,
                'bits' => PHP_INT_SIZE * 8,
                'hostname' => gethostname() ?: null,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? null,
                'memory_limit' => (string) ini_get('memory_limit'),
                'max_execution_time' => (string) ini_get('max_execution_time'),
                'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                'post_max_size' => (string) ini_get('post_max_size'),
                'max_input_vars' => (string) ini_get('max_input_vars'),
                'timezone' => date_default_timezone_get(),
                'load' => $this->load(),
            ],
            'laravel' => [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'url' => (string) config('app.url'),
                'locale' => app()->getLocale(),
                'timezone' => (string) config('app.timezone'),
                'locales' => count((array) (config('devtools.locales') ?? [])),
                'phpunit' => app()->runningUnitTests(),
            ],
            'database' => $this->database(),
            'services' => [
                'cache' => (string) config('cache.default'),
                'queue' => (string) config('queue.default'),
                'session' => (string) config('session.driver'),
                'session_secure' => (bool) config('session.secure'),
                'filesystem' => (string) config('filesystems.default'),
                'broadcast' => (string) config('broadcasting.default'),
                'mail' => (string) config('mail.default'),
            ],
            'storage' => $this->storage(),
            'extensions' => $this->extensions(),
            'opcache' => $this->opcache(),
        ];
    }

    /** @return list<array{path: string, free: string, total: string, used: string, writable: bool}> */
    private function storage(): array
    {
        $paths = array_unique([base_path(), storage_path(), base_path('bootstrap/cache')]);
        $rows = [];
        foreach ($paths as $path) {
            $free = @disk_free_space($path);
            $total = @disk_total_space($path);
            $used = is_float($free) && is_float($total) && $total > 0 ? round(($total - $free) / $total * 100, 1) : null;
            $rows[] = [
                'path' => str_replace(base_path(), '', $path) === '' ? '/' : str_replace(base_path(), '', $path),
                'free' => is_float($free) ? $this->bytes($free) : '—',
                'total' => is_float($total) ? $this->bytes($total) : '—',
                'used' => $used === null ? '—' : $used.'%',
                'writable' => is_writable($path),
            ];
        }

        return $rows;
    }

    /** @return array<string, bool> */
    private function extensions(): array
    {
        $names = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'sqlite3', 'mbstring', 'intl', 'curl', 'openssl', 'zip', 'bcmath', 'sodium', 'gd', 'imagick', 'redis', 'opcache', 'pcntl', 'fileinfo'];
        $rows = [];
        foreach ($names as $name) {
            $rows[$name] = extension_loaded($name);
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function opcache(): array
    {
        if (! function_exists('opcache_get_status')) {
            return ['enabled' => false];
        }
        $status = @opcache_get_status(false);
        if (! is_array($status) || ! ($status['opcache_enabled'] ?? false)) {
            return ['enabled' => false];
        }
        $memory = (array) ($status['memory_usage'] ?? []);
        $stats = (array) ($status['opcache_statistics'] ?? []);
        $used = (float) ($memory['used_memory'] ?? 0);
        $free = (float) ($memory['free_memory'] ?? 0);

        return [
            'enabled' => true,
            'hit_rate' => round((float) ($stats['opcache_hit_rate'] ?? 0), 1).'%',
            'cached_scripts' => (int) ($stats['num_cached_scripts'] ?? 0),
            'memory_used' => $this->bytes($used),
            'memory_free' => $this->bytes($free),
            'memory_total' => $this->bytes($used + $free),
            'memory' => $used + $free > 0 ? round($used / ($used + $free) * 100, 1).'%' : '—',
            'restarts' => (int) ($stats['oom_restarts'] ?? 0) + (int) ($stats['hash_restarts'] ?? 0) + (int) ($stats['manual_restarts'] ?? 0),
        ];
    }

    /** @return array<string, string|null> */
    private function database(): array
    {
        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}", []);
        $rows = ['connection' => $connection, 'driver' => (string) ($config['driver'] ?? '—'), 'version' => null];

        try {
            $rows['version'] = match ($rows['driver']) {
                'mysql', 'mariadb' => (string) DB::selectOne('select version() as version')->version,
                'pgsql' => (string) DB::selectOne('select version() as version')->version,
                'sqlite' => (string) DB::selectOne('select sqlite_version() as version')->version,
                default => null,
            };
        } catch (Throwable) {
            $rows['version'] = null;
        }

        return $rows;
    }

    /** @return array<string, mixed>|null */
    private function load(): ?array
    {
        if (! function_exists('sys_getloadavg')) {
            return null;
        }
        $load = @sys_getloadavg();

        return is_array($load) ? ['1' => round((float) $load[0], 2), '5' => round((float) $load[1], 2), '15' => round((float) $load[2], 2)] : null;
    }

    private function bytes(float $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 1).' KB';
    }
}
