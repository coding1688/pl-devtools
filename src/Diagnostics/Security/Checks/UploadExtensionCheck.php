<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class UploadExtensionCheck implements Check
{
    public function id(): string
    {
        return 'upload.extensions';
    }

    public function title(): string
    {
        return 'Upload whitelist allows executable web extensions';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        $key = (string) config('devtools.security.upload_extensions_config', 'files.uploads.allowed_extensions');
        if (! config()->has($key)) {
            return ['status' => 'skip', 'detail' => 'not configured: '.$key];
        }
        $allowed = array_map('strtolower', (array) config($key, []));
        $dangerous = array_values(array_intersect($allowed, ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'html', 'htm', 'js', 'svg']));

        return $dangerous === []
            ? ['status' => 'pass', 'detail' => count($allowed).' extensions allowed']
            : ['status' => 'fail', 'detail' => 'allowed: '.implode(', ', $dangerous), 'fix' => 'Remove executable extensions from '.$key.'.'];
    }
}
