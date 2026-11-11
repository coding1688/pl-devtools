<?php

namespace Coding1688\DevTools;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Coding1688\DevTools\Collectors\Collector;
use Coding1688\DevTools\Collectors\CollectorRegistry;
use Coding1688\DevTools\Collectors\RequestCollector;
use Coding1688\DevTools\Collectors\SqlCollector;
use Illuminate\Support\ServiceProvider;

class DevToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/devtools.php', 'pl.debug');

        $this->app->scoped(SqlQueryProfiler::class);

        $this->app->singleton(CollectorRegistry::class, fn (): CollectorRegistry => new CollectorRegistry(array_map(
            fn (string $class): Collector => $this->app->make($class),
            (array) config('pl.devtools.collectors', [RequestCollector::class, SqlCollector::class]),
        )));

        config(['pl.devtools.css' => $this->cssPath()]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pl-devtools');

        if ($this->app->runningInConsole()) {
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

        $this->app->make(Kernel::class)->prependMiddleware(DevToolsMid::class);

        Route::middleware(array_merge(['web'], (array) config('pl.devtools.middleware', [])))
            ->get((string) config('pl.devtools.path', '__debug/sql'), [DevToolsController::class, 'index'])
            ->name((string) config('pl.devtools.route_name', 'debug.sql'));

        Route::middleware(array_merge(['web'], (array) config('pl.devtools.middleware', [])))
            ->post((string) config('pl.devtools.path', '__debug/sql').'/clear', [DevToolsController::class, 'clear'])
            ->name((string) config('pl.devtools.route_name', 'debug.sql').'.clear');
    }

    private function cssPath(): string
    {
        $published = resource_path('css/sql-debug.css');

        return is_file($published) ? $published : __DIR__.'/../resources/css/sql-debug.css';
    }
}
