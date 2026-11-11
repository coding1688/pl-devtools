<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class DebugToolsExposureCheck implements Check
{
    public function id(): string
    {
        return 'devtools.exposure';
    }

    public function title(): string
    {
        return 'Developer tools are enabled outside local/testing';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        if (! config('pl.devtools.enabled')) {
            return ['status' => 'pass', 'detail' => 'devtools disabled'];
        }
        if (app()->environment(['local', 'testing'])) {
            return ['status' => 'pass', 'detail' => 'enabled in local/testing'];
        }
        $hosts = (array) config('pl.devtools.hosts', []);
        $middleware = (array) config('pl.devtools.middleware', []);

        return ['status' => 'fail', 'detail' => 'enabled in '.app()->environment().' with hosts='.count($hosts).' middleware='.count($middleware), 'fix' => 'Disable PL_DEVTOOLS_ENABLED outside local, or restrict hosts and add an auth middleware.'];
    }
}
