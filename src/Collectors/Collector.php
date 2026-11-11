<?php

namespace Coding1688\DevTools\Collectors;

interface Collector
{
    public function key(): string;

    public function label(): string;

    /** @param  array<string, mixed>  $profile */
    public function collect(array $profile): array;

    public function view(): string;
}
