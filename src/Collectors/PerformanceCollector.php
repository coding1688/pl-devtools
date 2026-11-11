<?php

namespace Coding1688\DevTools\Collectors;

use Coding1688\DevTools\Diagnostics\PerformanceAggregator;

final class PerformanceCollector implements Collector
{
    public function __construct(private readonly PerformanceAggregator $performance) {}

    public function key(): string
    {
        return 'performance';
    }

    public function label(): string
    {
        return 'Performance';
    }

    public function collect(array $profile): array
    {
        return $this->performance->report();
    }

    public function view(): string
    {
        return 'pl-devtools::panels.performance';
    }
}
