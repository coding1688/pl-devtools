<?php

namespace Coding1688\DevTools;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevToolsMid
{
    public function __construct(
        private readonly SqlQueryProfiler $profiler,
        private readonly \Coding1688\DevTools\Collectors\CollectorRegistry $collectors,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->profiler->enabledFor($request) || $request->is(trim((string) config('pl.devtools.path', '__debug/sql'), '/').'*')) {
            return $next($request);
        }
        $this->profiler->begin();
        $resolver = app('view.engine.resolver');
        if (! $resolver->resolve('blade') instanceof ProfiledBladeEngine) {
            $engine = new ProfiledBladeEngine(app('blade.compiler'), app('files'));
            $resolver->register('blade', static fn (): ProfiledBladeEngine => $engine);
            app()->terminating(static fn () => $engine->forgetCompiledOrNotExpired());
        }
        try {
            $response = $next($request);
        } finally {
            $profile = $this->profiler->finish($request, isset($response) ? $response->getStatusCode() : 500);
        }
        $response->headers->set('X-Debug-SQL-Request', $profile['id']);
        $response->headers->set('X-Debug-SQL-Url', url((string) config('pl.devtools.path', '__debug/sql')).'?request='.$profile['id']);
        $response->headers->set('X-Debug-SQL-Count', (string) $profile['query_count']);
        $response->headers->set('X-Debug-SQL-Time', (string) $profile['sql_ms']);
        $response->headers->set('Server-Timing', 'sql;dur='.$profile['sql_ms'].', app;dur='.$profile['duration_ms'].', non_sql;dur='.$profile['non_sql_ms'], false);
        $response->headers->set('Cache-Control', 'private, no-store');
        if (! $response instanceof StreamedResponse && ! $response instanceof BinaryFileResponse && ! $request->isMethod('HEAD')
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html') && $response->isSuccessful()
            && is_string($content = $response->getContent()) && str_contains(strtolower($content), '</body>')) {
            $toolbar = view('pl-devtools::index', [
                'profiles' => [$profile],
                'counts' => ['all' => 1, 'html' => 1, 'ajax' => 0],
                'standalone' => false,
                'refresh' => 0,
                'collectors' => $this->collectors->all(),
            ])->render();
            $position = strripos($content, '</body>');
            $response->setContent(substr($content, 0, $position).$toolbar.substr($content, $position));
            $response->headers->remove('Content-Length');
        }

        return $response;
    }
}
