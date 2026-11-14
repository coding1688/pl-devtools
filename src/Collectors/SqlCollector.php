<?php

namespace Coding1688\DevTools\Collectors;

final class SqlCollector implements Collector
{
    public function key(): string
    {
        return 'sql';
    }

    public function label(): string
    {
        return 'SQL';
    }

    public function collect(array $profile): array
    {
        return [
            'slow_ms' => (int) config('devtools.slow_ms', 100),
            'duration_ms' => (float) ($profile['duration_ms'] ?? 0),
            'query_count' => (int) ($profile['query_count'] ?? 0),
            'sql_ms' => (float) ($profile['sql_ms'] ?? 0),
            'collection_ms' => $profile['collection_ms'] ?? null,
            'stages' => (array) ($profile['stages'] ?? []),
            'queries' => (array) ($profile['queries'] ?? []),
            'duplicates' => (array) ($profile['duplicates'] ?? []),
            'hotspots' => (array) ($profile['hotspots'] ?? []),
            'stage_labels' => (array) config('devtools.stage_labels', []),
        ];
    }

    public function view(): string
    {
        return 'pl-devtools::panels.sql';
    }
}
