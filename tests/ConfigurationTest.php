<?php

use Coding1688\DevTools\DevToolsServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function test_defaults_register_without_host_configuration(): void
    {
        $app = new Application;
        $app->instance('config', new Repository);
        (new DevToolsServiceProvider($app))->register();
        self::assertIsArray(config('devtools'));
        self::assertNotEmpty(config('devtools'));
    }

    public function test_canonical_config_wins_and_empty_lists_replace_defaults(): void
    {
        $app = new Application;
        $app->instance('config', new Repository([
            'pl' => ['devtools' => ['test' => ['legacy' => true, 'value' => 'old'], 'locales' => ['ja']]],
            'devtools' => ['test' => ['value' => 'new'], 'locales' => []],
        ]));
        (new DevToolsServiceProvider($app))->register();
        self::assertSame('new', config('devtools.test.value'));
        self::assertTrue(config('devtools.test.legacy'));
        self::assertSame([], config('devtools.locales'));
    }

    public function test_legacy_configuration_is_accepted(): void
    {
        $app = new Application;
        $app->instance('config', new Repository(['pl' => ['devtools' => ['locales' => ['ko']]]]));
        (new DevToolsServiceProvider($app))->register();
        self::assertSame(['ko'], config('devtools.locales'));
    }

    public function test_config_can_be_published(): void
    {
        $app = new Application;
        $app->instance('config', new Repository);
        $provider = new DevToolsServiceProvider($app);
        $provider->register();
        $provider->boot();
        self::assertNotEmpty(ServiceProvider::pathsToPublish(DevToolsServiceProvider::class, 'devtools-config'));
    }
}
