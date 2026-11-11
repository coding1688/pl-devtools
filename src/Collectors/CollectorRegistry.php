<?php

namespace Coding1688\DevTools\Collectors;

final class CollectorRegistry
{
    /** @param  list<Collector>  $collectors */
    public function __construct(private readonly array $collectors) {}

    /** @return list<Collector> */
    public function all(): array
    {
        return $this->collectors;
    }
}
