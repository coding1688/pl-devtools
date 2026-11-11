<?php

namespace Coding1688\DevTools\Diagnostics\Security;

interface Check
{
    public function id(): string;

    public function title(): string;

    public function severity(): Severity;

    /** @return array{status: 'pass'|'fail'|'skip', detail: string, fix?: string} */
    public function run(): array;
}
