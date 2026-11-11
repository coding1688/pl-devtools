<?php

namespace Coding1688\DevTools\Console;

use Coding1688\DevTools\Diagnostics\Security\SecurityAuditor;
use Coding1688\DevTools\Diagnostics\Security\Severity;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuditCommand extends Command
{
    protected $signature = 'devtools:audit {--json} {--fresh} {--fail-on=high : none|info|low|medium|high}';

    protected $description = 'Run read-only security checks and fail when a finding reaches the given severity';

    public function handle(SecurityAuditor $auditor): int
    {
        $report = $auditor->report((bool) $this->option('fresh'));

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['check', 'severity', 'status', 'detail'],
                array_map(fn (array $row): array => [
                    Str::limit($row['title'], 56),
                    $row['severity'],
                    $row['status'],
                    Str::limit((string) $row['detail'], 72),
                ], $report['checks']),
            );
            $counts = $report['counts'];
            $this->line(sprintf('pass %d · fail %d · skip %d · high %d / medium %d / low %d', $counts['pass'], $counts['fail'], $counts['skip'], $counts['high'], $counts['medium'], $counts['low']));
        }

        $threshold = (string) $this->option('fail-on');
        if ($threshold === 'none') {
            return self::SUCCESS;
        }
        $minimum = Severity::tryFrom($threshold) ?? Severity::High;
        foreach ($report['checks'] as $row) {
            if ($row['status'] === 'fail' && Severity::from($row['severity'])->weight() >= $minimum->weight()) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
