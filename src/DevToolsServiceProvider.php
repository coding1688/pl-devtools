<?php

namespace Coding1688\DevTools;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Coding1688\DevTools\Collectors\Collector;
use Coding1688\DevTools\Collectors\CollectorRegistry;
use Coding1688\DevTools\Collectors\RequestCollector;
use Coding1688\DevTools\Collectors\LogsCollector;
use Coding1688\DevTools\Collectors\PerformanceCollector;
use Coding1688\DevTools\Collectors\TelemetryCollector;
use Coding1688\DevTools\Collectors\SecurityCollector;
use Coding1688\DevTools\Collectors\ServerCollector;
use Coding1688\DevTools\Diagnostics\LogSummary;
use Coding1688\DevTools\Telemetry\RequestTelemetry;
use Coding1688\DevTools\Diagnostics\PerformanceAggregator;
use Coding1688\DevTools\Diagnostics\Security\SecurityAuditor;
use Coding1688\DevTools\Diagnostics\ServerInfo;
use Coding1688\DevTools\Collectors\SqlCollector;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DevToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeDevToolsConfig();

        $this->app->scoped(SqlQueryProfiler::class);
        $this->app->singleton(ServerInfo::class);
        $this->app->singleton(PerformanceAggregator::class);
        $this->app->singleton(LogSummary::class);
        $this->app->scoped(RequestTelemetry::class);
        $this->app->singleton(SecurityAuditor::class, fn (): SecurityAuditor => new SecurityAuditor((array) config('pl.devtools.security.checks', [])));

        $this->app->singleton(CollectorRegistry::class, fn (): CollectorRegistry => new CollectorRegistry(array_map(
            fn (string $class): Collector => $this->app->make($class),
            (array) config('pl.devtools.collectors', [
                RequestCollector::class,
                SqlCollector::class,
                ServerCollector::class,
                SecurityCollector::class,
                TelemetryCollector::class,
                PerformanceCollector::class,
                LogsCollector::class,
            ]),
        )));

        config(['pl.devtools.css' => $this->cssPath()]);
    }

    private function mergeDevToolsConfig(): void
    {
        $defaults = require __DIR__.'/../config/devtools.php';
        $override = (array) config('pl.devtools', []);
        config(['pl.devtools' => $this->mergeConfigMap($defaults, $override)]);
    }

    /**
     * Deep-merge maps but replace lists, so partial nested overrides keep the defaults.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function mergeConfigMap(array $defaults, array $override): array
    {
        foreach ($override as $key => $value) {
            $current = $defaults[$key] ?? null;
            if (is_array($value) && is_array($current) && ! array_is_list($value) && ! array_is_list($current)) {
                $defaults[$key] = $this->mergeConfigMap($current, $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pl-devtools');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'devtools');

        if ($this->app->runningInConsole()) {
            $this->commands([\Coding1688\DevTools\Console\AuditCommand::class]);

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pl-devtools'),
            ], 'pl-devtools-views');

            $this->publishes([
                __DIR__.'/../resources/css' => resource_path('css'),
            ], 'pl-devtools-assets');
        }

        if (! config('pl.devtools.enabled') || ! config('app.debug')) {
            return;
        }

        DB::listen(static function (QueryExecuted $query): void {
            app(SqlQueryProfiler::class)->record($query);
        });

        $this->listenTelemetry();

        $this->app->make(Kernel::class)->prependMiddleware(DevToolsMid::class);

        Route::middleware(array_merge(['web'], (array) config('pl.devtools.middleware', [])))
            ->get((string) config('pl.devtools.path', '__debug/sql'), [DevToolsController::class, 'index'])
            ->name((string) config('pl.devtools.route_name', 'debug.sql'));

        Route::middleware(array_merge(['web'], (array) config('pl.devtools.middleware', [])))
            ->post((string) config('pl.devtools.path', '__debug/sql').'/clear', [DevToolsController::class, 'clear'])
            ->name((string) config('pl.devtools.route_name', 'debug.sql').'.clear');
    }

    private function listenTelemetry(): void
    {
        if (! config('pl.devtools.telemetry.enabled', true)) {
            return;
        }
        $events = [];
        if (config('pl.devtools.telemetry.http', true)) {
            $events = [...$events,
                \Illuminate\Http\Client\Events\RequestSending::class,
                \Illuminate\Http\Client\Events\ResponseReceived::class,
                \Illuminate\Http\Client\Events\ConnectionFailed::class,
            ];
        }
        if (config('pl.devtools.telemetry.cache', true)) {
            $events = [...$events,
                \Illuminate\Cache\Events\CacheHit::class,
                \Illuminate\Cache\Events\CacheMissed::class,
                \Illuminate\Cache\Events\KeyWritten::class,
                \Illuminate\Cache\Events\KeyForgotten::class,
            ];
        }
        foreach ($events as $event) {
            if (class_exists($event)) {
                Event::listen($event, static function (object $payload): void {
                    app(RequestTelemetry::class)->handle($payload);
                });
            }
        }
    }

    private function cssPath(): string
    {
        $published = resource_path('css/sql-debug.css');

        return is_file($published) ? $published : __DIR__.'/../resources/css/sql-debug.css';
    }
}
