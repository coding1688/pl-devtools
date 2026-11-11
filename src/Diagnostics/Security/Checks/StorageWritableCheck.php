<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class StorageWritableCheck implements Check
{
    public function id(): string
    {
        return 'storage.writable';
    }

    public function title(): string
    {
        return 'Storage or bootstrap cache is not writable';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        $missing = [];
        foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            if (! is_writable($path)) {
                $missing[] = str_replace(base_path(), '', $path);
            }
        }

        return $missing === []
            ? ['status' => 'pass', 'detail' => 'writable']
            : ['status' => 'fail', 'detail' => 'not writable: '.implode(', ', $missing), 'fix' => 'Fix ownership/permissions for the web user.'];
    }
}
