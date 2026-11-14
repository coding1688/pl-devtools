<?php

use Coding1688\DevTools\Collectors\CollectorRegistry;
use Coding1688\DevTools\DevToolsMid;
use Coding1688\DevTools\ProfiledBladeEngine;
use Coding1688\DevTools\SqlQueryProfiler;
use Illuminate\Auth\Access\Gate;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class AccessAndExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        $app = new Application;
        $app->instance('config', new Repository([
            'app' => ['debug' => true],
            'devtools' => ['enabled' => true, 'environments' => [], 'ignore_routes' => ['ignored']],
        ]));
        $engine = (new ReflectionClass(ProfiledBladeEngine::class))->newInstanceWithoutConstructor();
        $app->instance('view.engine.resolver', new class($engine)
        {
            public function __construct(private object $engine) {}

            public function resolve(string $name): object
            {
                return $this->engine;
            }
        });
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Container::setInstance(null);
    }

    public function test_ignored_route_does_not_replace_original_exception(): void
    {
        $request = Request::create('/ignored');
        $exception = new RuntimeException('original');
        $mid = new DevToolsMid(new SqlQueryProfiler, new CollectorRegistry([]));
        try {
            $mid->handle($request, function ($request) use ($exception) {
                $route = (new Route('GET', 'ignored', fn () => null))->name('ignored');
                $request->setRouteResolver(fn () => $route);
                throw $exception;
            });
            self::fail('Expected original exception');
        } catch (RuntimeException $actual) {
            self::assertSame($exception, $actual);
        }
    }

    public function test_authorization_gate_can_deny_and_allow_profiling(): void
    {
        $gate = new Gate(app(), fn () => null);
        app()->instance(Illuminate\Contracts\Auth\Access\Gate::class, $gate);
        Facade::setFacadeApplication(app());
        config(['devtools.authorization_gate' => 'viewProfiler']);
        $gate->define('viewProfiler', fn ($user = null) => false);
        self::assertFalse((new SqlQueryProfiler)->enabledFor(Request::create('/')));
        $gate->define('viewProfiler', fn ($user = null) => true);
        self::assertTrue((new SqlQueryProfiler)->enabledFor(Request::create('/')));
    }

    public function test_collection_failure_preserves_original_exception(): void
    {
        $exception = new RuntimeException('business failure');
        $mid = new DevToolsMid(new SqlQueryProfiler, new CollectorRegistry([]));
        try {
            $mid->handle(Request::create('/'), function () use ($exception) {
                throw $exception;
            });
            self::fail('Expected exception');
        } catch (RuntimeException $actual) {
            self::assertSame($exception, $actual);
        }
    }

    public function test_collection_failure_preserves_successful_response(): void
    {
        $response = new Response('original');
        $mid = new DevToolsMid(new SqlQueryProfiler, new CollectorRegistry([]));
        self::assertSame($response, $mid->handle(Request::create('/'), fn () => $response));
    }

    public function test_disabled_profiler_calls_business_code_only_once(): void
    {
        config(['devtools.enabled' => false]);
        $calls = 0;
        $mid = new DevToolsMid(new SqlQueryProfiler, new CollectorRegistry([]));
        try {
            $mid->handle(Request::create('/'), function () use (&$calls) {
                $calls++;
                throw new RuntimeException('business');
            });
        } catch (RuntimeException) {
            self::assertSame(1, $calls);
        }
        self::assertSame(1, $calls);
    }

    public function test_collection_can_be_disabled_independently(): void
    {
        config(['devtools.enabled' => true, 'devtools.collect' => false]);
        self::assertFalse((new SqlQueryProfiler)->enabledFor(Request::create('/')));
    }
}
