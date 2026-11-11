<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class AppDebugCheck implements Check
{
    public function id(): string
    {
        return 'app.debug';
    }

    public function title(): string
    {
        return 'APP_DEBUG is enabled outside local/testing';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        $enabled = (bool) config('app.debug');
        $local = app()->environment(['local', 'testing']);

        return $enabled && ! $local
            ? ['status' => 'fail', 'detail' => 'APP_DEBUG=true in '.app()->environment().'.', 'fix' => 'Set APP_DEBUG=false and clear config cache in this environment.']
            : ['status' => 'pass', 'detail' => $enabled ? 'debug enabled in local/testing' : 'debug disabled'];
    }
}
