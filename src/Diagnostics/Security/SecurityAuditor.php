<?php

namespace Coding1688\DevTools\Diagnostics\Security;

use Illuminate\Support\Facades\Cache;
use Throwable;

final class SecurityAuditor
{
    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    /** @param  list<class-string<Check>>  $checks */
    public function __construct(private readonly array $checks) {}

    /** @return array{generated_at: string, counts: array<string, int>, checks: list<array<string, mixed>>} */
    public function report(bool $fresh = false): array
    {
        if ($fresh) {
            $this->memo = null;
            Cache::forget('pl-devtools:security');
        }
        if ($this->memo !== null) {
            return $this->memo;
        }
        $ttl = max(10, (int) config('pl.devtools.security_ttl', (int) config('pl.devtools.diagnostics_ttl', 300)));

        return $this->memo = Cache::remember('pl-devtools:security', $ttl, function (): array {
            $rows = [];
            foreach ($this->checks as $class) {
                try {
                    $check = app($class);
                    $outcome = $check->run();
                    $rows[] = [
                        'id' => $check->id(),
                        'title' => $check->title(),
                        'severity' => $check->severity()->value,
                        'status' => $outcome['status'],
                        'detail' => $outcome['detail'] ?? '',
                        'fix' => $outcome['fix'] ?? null,
                    ];
                } catch (Throwable $exception) {
                    $rows[] = [
                        'id' => basename(str_replace('\\', '/', $class)),
                        'title' => $class,
                        'severity' => Severity::Info->value,
                        'status' => 'skip',
                        'detail' => $exception->getMessage(),
                        'fix' => null,
                    ];
                }
            }
            usort($rows, function (array $first, array $second): int {
                $status = ($first['status'] === 'fail' ? 0 : 1) <=> ($second['status'] === 'fail' ? 0 : 1);

                return $status !== 0 ? $status : Severity::from($second['severity'])->weight() <=> Severity::from($first['severity'])->weight();
            });
            $counts = ['pass' => 0, 'fail' => 0, 'skip' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
            foreach ($rows as $row) {
                $counts[$row['status']] = ($counts[$row['status']] ?? 0) + 1;
                if ($row['status'] === 'fail') {
                    $counts[$row['severity']] = ($counts[$row['severity']] ?? 0) + 1;
                }
            }

            return ['generated_at' => now()->toIso8601String(), 'counts' => $counts, 'checks' => $rows];
        });
    }
}
