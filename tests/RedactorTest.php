<?php

use Coding1688\DevTools\Redactor;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class RedactorTest extends TestCase
{
    public function test_secrets_are_removed_before_truncation(): void
    {
        Container::setInstance(new Container);
        app()->instance('config', new Repository(['devtools' => ['redact_keys' => ['custom_key']]]));
        $input = 'failed https://user:pass@example.com/path?token=abc Authorization: Bearer xyz password="secret value" custom_key=private';
        $result = Redactor::text($input);
        foreach (['user:pass', 'token=abc', 'xyz', 'secret value', 'private'] as $secret) {
            self::assertStringNotContainsString($secret, $result);
        }
        self::assertStringContainsString('example.com/path', $result);
        Container::setInstance(null);
    }
}
