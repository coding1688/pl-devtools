<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class SessionCookieCheck implements Check
{
    public function id(): string
    {
        return 'session.cookie';
    }

    public function title(): string
    {
        return 'Session cookie lacks secure / http_only / same_site hardening';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function run(): array
    {
        $issues = [];
        if (! config('session.secure') && ! app()->environment(['local', 'testing'])) {
            $issues[] = 'secure=false';
        }
        if (! config('session.http_only', true)) {
            $issues[] = 'http_only=false';
        }
        if (in_array(config('session.same_site'), [null, '', 'none'], true) && ! config('session.secure')) {
            $issues[] = 'same_site='.var_export(config('session.same_site'), true);
        }

        return $issues === []
            ? ['status' => 'pass', 'detail' => 'cookie flags look sane']
            : ['status' => 'fail', 'detail' => implode(', ', $issues), 'fix' => 'Set SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true and SESSION_SAME_SITE=lax.'];
    }
}
