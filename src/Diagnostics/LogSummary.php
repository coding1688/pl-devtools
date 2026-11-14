<?php

namespace Coding1688\DevTools\Diagnostics;

use Coding1688\DevTools\Redactor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reads the tail of the application log and keeps level + first line only.
 */
final class LogSummary
{
    /** @return array<string, mixed> */
    public function report(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('pl-devtools:logs:v2');
        }
        $ttl = max(10, (int) config('devtools.diagnostics_ttl', 300));

        return Cache::remember('pl-devtools:logs:v2', $ttl, function (): array {
            $path = (string) (config('devtools.logs.path') ?: storage_path('logs/laravel.log'));
            if (! is_file($path) || ! is_readable($path)) {
                return ['path' => $path, 'exists' => false, 'size' => null, 'counts' => [], 'entries' => []];
            }
            $maxBytes = max(65536, (int) config('devtools.logs.max_bytes', 524288));
            $size = (int) (filesize($path) ?: 0);
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return ['path' => $path, 'exists' => false, 'size' => $size, 'counts' => [], 'entries' => []];
            }
            fseek($handle, max(0, $size - $maxBytes));
            $content = (string) stream_get_contents($handle);
            fclose($handle);

            $levels = array_map('strtoupper', (array) config('devtools.logs.levels', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING']));
            $limit = max(1, (int) config('devtools.logs.limit', 50));
            $entries = [];
            $counts = [];
            foreach (preg_split('/\R/', $content) ?: [] as $line) {
                if (preg_match('/^\[(?<time>[^\]]+)\]\s+(?<env>[^.]+)\.(?<level>[A-Z]+):\s+(?<message>.*)$/', $line, $m) !== 1) {
                    continue;
                }
                $level = strtoupper($m['level']);
                if (! in_array($level, $levels, true)) {
                    continue;
                }
                $counts[$level] = ($counts[$level] ?? 0) + 1;
                $entries[] = ['time' => $m['time'], 'level' => $level, 'message' => Str::limit(Redactor::text(trim($m['message'])), 300)];
            }

            return [
                'path' => $path,
                'exists' => true,
                'size' => number_format($size / 1048576, 1).' MB',
                'counts' => $counts,
                'entries' => array_reverse(array_slice($entries, -$limit)),
            ];
        });
    }
}
