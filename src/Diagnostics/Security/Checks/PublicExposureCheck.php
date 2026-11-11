<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;

final class PublicExposureCheck implements Check
{
    public function id(): string
    {
        return 'public.exposure';
    }

    public function title(): string
    {
        return 'Sensitive files are inside the public directory';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        $found = [];
        foreach (['.env', '.git', 'composer.json', '.env.example'] as $name) {
            if (file_exists(public_path($name))) {
                $found[] = $name;
            }
        }

        return $found === []
            ? ['status' => 'pass', 'detail' => 'public directory clean']
            : ['status' => 'fail', 'detail' => 'found in public/: '.implode(', ', $found), 'fix' => 'Remove the files and block dotfiles at the web server.'];
    }
}
