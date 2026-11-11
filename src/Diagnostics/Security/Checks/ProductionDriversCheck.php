<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class ProductionDriversCheck implements Check
{
    public function id(): string
    {
        return 'production.drivers';
    }

    public function title(): string
    {
        return 'Production uses synchronous queue or file cache';
    }

    public function severity(): Severity
    {
        return Severity::Low;
    }

    public function run(): array
    {
        if (app()->environment(['local', 'testing'])) {
            return ['status' => 'skip', 'detail' => 'not a production environment'];
        }
        $issues = [];
        if (config('queue.default') === 'sync') {
            $issues[] = 'QUEUE_CONNECTION=sync';
        }
        if (config('cache.default') === 'file') {
            $issues[] = 'CACHE_STORE=file';
        }

        return $issues === []
            ? ['status' => 'pass', 'detail' => 'queue and cache are external']
            : ['status' => 'fail', 'detail' => implode(', ', $issues), 'fix' => 'Use a real queue worker and a shared cache store in production.'];
    }
}
