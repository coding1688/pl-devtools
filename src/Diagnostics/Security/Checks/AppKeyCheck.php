<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class AppKeyCheck implements Check
{
    public function id(): string
    {
        return 'app.key';
    }

    public function title(): string
    {
        return 'APP_KEY is missing or too short';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        $key = (string) config('app.key');
        if ($key === '') {
            return ['status' => 'fail', 'detail' => 'APP_KEY is empty.', 'fix' => 'Run php artisan key:generate.'];
        }
        $raw = str_starts_with($key, 'base64:') ? (string) base64_decode(substr($key, 7), true) : $key;

        return strlen($raw) < 32
            ? ['status' => 'fail', 'detail' => 'APP_KEY is shorter than 32 bytes.', 'fix' => 'Generate a new key and rotate credentials that depend on it.']
            : ['status' => 'pass', 'detail' => 'APP_KEY present'];
    }
}
