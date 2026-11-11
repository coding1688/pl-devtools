<?php

namespace Coding1688\DevTools\Collectors;

use Coding1688\DevTools\Diagnostics\ServerInfo;

final class ServerCollector implements Collector
{
    public function __construct(private readonly ServerInfo $server) {}

    public function key(): string
    {
        return 'server';
    }

    public function label(): string
    {
        return 'Server';
    }

    public function collect(array $profile): array
    {
        return $this->server->snapshot();
    }

    public function view(): string
    {
        return 'pl-devtools::panels.server';
    }
}
