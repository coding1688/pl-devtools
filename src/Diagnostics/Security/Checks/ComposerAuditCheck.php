<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;
use Symfony\Component\Process\Process;
use Throwable;

final class ComposerAuditCheck implements Check
{
    public function id(): string
    {
        return 'composer.audit';
    }

    public function title(): string
    {
        return 'Composer reports vulnerable dependencies';
    }

    public function severity(): Severity
    {
        return Severity::High;
    }

    public function run(): array
    {
        if (! config('pl.devtools.security.composer_audit', false)) {
            return ['status' => 'skip', 'detail' => 'disabled; enable pl.devtools.security.composer_audit'];
        }
        if (! class_exists(Process::class)) {
            return ['status' => 'skip', 'detail' => 'symfony/process is not available'];
        }
        try {
            $process = new Process(['composer', 'audit', '--format=json', '--no-interaction'], base_path());
            $process->setTimeout(60);
            $process->run();
            $payload = json_decode($process->getOutput(), true);
            $count = is_array($payload) ? count((array) ($payload['advisories'] ?? [])) : 0;

            return $count > 0
                ? ['status' => 'fail', 'detail' => $count.' advisories', 'fix' => 'Run composer update for the affected packages.']
                : ['status' => 'pass', 'detail' => 'no advisories'];
        } catch (Throwable $exception) {
            return ['status' => 'skip', 'detail' => 'composer audit failed: '.$exception->getMessage()];
        }
    }
}
