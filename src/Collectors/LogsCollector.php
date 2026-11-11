<?php

namespace Coding1688\DevTools\Collectors;

use Coding1688\DevTools\Diagnostics\LogSummary;

final class LogsCollector implements Collector
{
    public function __construct(private readonly LogSummary $logs) {}

    public function key(): string
    {
        return 'logs';
    }

    public function label(): string
    {
        return 'Logs';
    }

    public function collect(array $profile): array
    {
        return $this->logs->report();
    }

    public function view(): string
    {
        return 'pl-devtools::panels.logs';
    }
}
