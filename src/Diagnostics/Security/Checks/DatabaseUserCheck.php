<?php

namespace Coding1688\DevTools\Diagnostics\Security\Checks;

use Coding1688\DevTools\Diagnostics\Security\Check;
use Coding1688\DevTools\Diagnostics\Security\Severity;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DatabaseUserCheck implements Check
{
    public function id(): string
    {
        return 'database.user';
    }

    public function title(): string
    {
        return 'Application connects with an over-privileged database user';
    }

    public function severity(): Severity
    {
        return Severity::Medium;
    }

    public function run(): array
    {
        $driver = DB::connection()->getDriverName();
        try {
            $user = match ($driver) {
                'mysql', 'mariadb' => (string) DB::selectOne('select current_user() as user')->user,
                'pgsql' => (string) DB::selectOne('select current_user as user')->user,
                default => null,
            };
        } catch (Throwable) {
            $user = null;
        }
        if ($user === null) {
            return ['status' => 'skip', 'detail' => 'unsupported driver: '.$driver];
        }
        $name = strtolower((string) strstr($user.'@', '@', true));

        return in_array($name, ['root', 'postgres', 'sa'], true)
            ? ['status' => 'fail', 'detail' => 'connected as '.$user, 'fix' => 'Use a dedicated database user limited to this schema.']
            : ['status' => 'pass', 'detail' => 'connected as '.$user];
    }
}
