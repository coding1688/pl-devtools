<?php

namespace Coding1688\DevTools\Collectors;

final class RequestCollector implements Collector
{
    public function key(): string
    {
        return 'request';
    }

    public function label(): string
    {
        return 'Request';
    }

    public function collect(array $profile): array
    {
        $request = (array) ($profile['request'] ?? []);

        return [
            'method' => $profile['method'] ?? null,
            'path' => $profile['path'] ?? null,
            'host' => $profile['host'] ?? null,
            'status' => $profile['status'] ?? null,
            'type' => $profile['type'] ?? 'html',
            'route' => $profile['route'] ?? null,
            'controller' => $profile['controller'] ?? null,
            'user' => $profile['user'] ?? null,
            'created_at' => $profile['created_at'] ?? null,
            'duration_ms' => $profile['duration_ms'] ?? null,
            'sql_ms' => $profile['sql_ms'] ?? null,
            'query_count' => $profile['query_count'] ?? 0,
            'ip' => $request['ip'] ?? null,
            'headers' => (array) ($request['headers'] ?? []),
            'middleware' => (array) ($request['middleware'] ?? []),
            'query_keys' => (array) ($request['query_keys'] ?? []),
            'input_keys' => (array) ($request['input_keys'] ?? []),
        ];
    }

    public function view(): string
    {
        return 'pl-devtools::panels.request';
    }
}
