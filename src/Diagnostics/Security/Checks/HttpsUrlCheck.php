<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class HttpsUrlCheck implements Check
{
    public function id(): string
    {
        return 'app.url.https';
    }

    public function title(): string
    {
        return 'APP_URL is not HTTPS outside local/testing';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        $url = (string) config('app.url');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme !== 'https' && ! app()->environment(['local', 'testing'])
            ? ['status' => 'fail', 'detail' => "APP_URL={$url}", 'fix' => 'Force HTTPS and set APP_URL to the https:// origin.']
            : ['status' => 'pass', 'detail' => 'APP_URL='.$url];
    }
}
