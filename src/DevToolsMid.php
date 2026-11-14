<?php

namespace Coding1688\DevTools;

use Closure;
use Coding1688\DevTools\Collectors\CollectorRegistry;
use Coding1688\DevTools\Telemetry\RequestTelemetry;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevToolsMid
{
    public function __construct(
        private readonly SqlQueryProfiler $profiler,
        private readonly CollectorRegistry $collectors,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $enabled = $this->profiler->enabledFor($request) && ! $request->is(trim((string) config('devtools.path', '__debug/sql'), '/').'*');
        } catch (\Throwable) {
            $enabled = false;
        }
        if (! $enabled) {
            return $next($request);
        }
        try {
            $this->profiler->begin();
            $resolver = app('view.engine.resolver');
            if (! $resolver->resolve('blade') instanceof ProfiledBladeEngine) {
                $engine = new ProfiledBladeEngine(app('blade.compiler'), app('files'));
                $resolver->register('blade', static fn (): ProfiledBladeEngine => $engine);
                app()->terminating(static fn () => $engine->forgetCompiledOrNotExpired());
            }
        } catch (\Throwable) {
            return $next($request);
        }
        try {
            $response = $next($request);
        } finally {
            try {
                $profile = $this->profiler->finish($request, isset($response) ? $response->getStatusCode() : 500, app(RequestTelemetry::class)->snapshot());
            } catch (\Throwable) {
                $profile = [];
            }
        }
        try {
            if ($profile === []) {
                return $response;
            }
            $response->headers->set('X-Debug-SQL-Request', $profile['id']);
            $response->headers->set('X-Debug-SQL-Url', url((string) config('devtools.path', '__debug/sql')).'?request='.$profile['id']);
            $response->headers->set('X-Debug-SQL-Count', (string) $profile['query_count']);
            $response->headers->set('X-Debug-SQL-Time', (string) $profile['sql_ms']);
            $response->headers->set('Server-Timing', 'sql;dur='.$profile['sql_ms'].', app;dur='.$profile['duration_ms'].', non_sql;dur='.$profile['non_sql_ms'], false);
            $response->headers->set('Cache-Control', 'private, no-store');
            if (config('devtools.toolbar', true) && ! $response instanceof StreamedResponse && ! $response instanceof BinaryFileResponse && ! $request->isMethod('HEAD')
                && str_contains((string) $response->headers->get('Content-Type'), 'text/html') && $response->isSuccessful()
                && is_string($content = $response->getContent()) && str_contains(strtolower($content), '</body>')) {
                $toolbar = view('pl-devtools::index', [
                    'profiles' => [$profile],
                    'counts' => ['all' => 1, 'html' => 1, 'ajax' => 0],
                    'standalone' => false,
                    'refresh' => 0,
                    'collectors' => $this->collectors->all(),
                    'recent' => array_slice($this->profiler->recent(), 0, max(1, (int) config('devtools.toolbar_history', 20))),
                    'selected' => $profile['id'],
                ])->render();
                $position = strripos($content, '</body>');
                $response->setContent(substr($content, 0, $position).$toolbar.substr($content, $position));
                $response->headers->remove('Content-Length');
            }

        } catch (\Throwable) {
            // Diagnostic failures must not replace the application response.
        }

        return $response;
    }
}
