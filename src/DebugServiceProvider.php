<?php

namespace Coding1688\Debug;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/debug.php', 'pl.debug');

        $this->app->scoped(SqlQueryProfiler::class);

        config(['pl.debug.css' => $this->cssPath()]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pl-debug');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pl-debug'),
            ], 'pl-debug-views');

            $this->publishes([
                __DIR__.'/../resources/css' => resource_path('css'),
            ], 'pl-debug-assets');
        }

        if (! config('pl.debug.enabled') || ! config('app.debug')) {
            return;
        }

        DB::listen(static function (QueryExecuted $query): void {
            app(SqlQueryProfiler::class)->record($query);
        });

        $this->app->make(Kernel::class)->prependMiddleware(SqlQueryDebugMid::class);

        Route::middleware(array_merge(['web'], (array) config('pl.debug.middleware', [])))
            ->get((string) config('pl.debug.path', '__debug/sql'), [SqlDebugController::class, 'index'])
            ->name((string) config('pl.debug.route_name', 'debug.sql'));

        Route::middleware(array_merge(['web'], (array) config('pl.debug.middleware', [])))
            ->post((string) config('pl.debug.path', '__debug/sql').'/clear', [SqlDebugController::class, 'clear'])
            ->name((string) config('pl.debug.route_name', 'debug.sql').'.clear');
    }

    private function cssPath(): string
    {
        $published = resource_path('css/sql-debug.css');

        return is_file($published) ? $published : __DIR__.'/../resources/css/sql-debug.css';
    }
}
