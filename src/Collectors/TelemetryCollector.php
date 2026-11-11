<?php

namespace Coding1688\DevTools\Collectors;

final class TelemetryCollector implements Collector
{
    public function key(): string
    {
        return 'telemetry';
    }

    public function label(): string
    {
        return 'HTTP / Cache';
    }

    public function collect(array $profile): array
    {
        return (array) ($profile['telemetry'] ?? []);
    }

    public function view(): string
    {
        return 'pl-devtools::panels.telemetry';
    }
}
