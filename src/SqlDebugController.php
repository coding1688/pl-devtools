<?php

namespace Coding1688\Debug;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SqlDebugController extends \Illuminate\Routing\Controller
{
    public function __construct(private readonly SqlQueryProfiler $profiler) {}

    public function index(Request $request): Response
    {
        abort_unless($this->profiler->enabledFor($request), 404);
        $id = $request->query('request');
        abort_unless($id === null || (is_string($id) && preg_match('/^[a-f0-9-]{36}$/D', $id) === 1), 404);
        $profiles = $this->profiler->recent($id);
        $counts = ['all' => count($profiles), 'html' => 0, 'ajax' => 0];
        foreach ($profiles as $profile) {
            $type = (string) ($profile['type'] ?? 'html');
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        $refresh = (int) $request->query('refresh', config('pl.debug.refresh_seconds', 0));
        $refresh = max(0, min(60, $refresh));
        $type = $request->query('type');
        if (in_array($type, ['html', 'ajax'], true)) {
            $profiles = array_values(array_filter($profiles, fn (array $profile): bool => ($profile['type'] ?? 'html') === $type));
        }
        $response = $request->expectsJson() ? response()->json(['profiles' => $profiles, 'counts' => $counts])
            : response()->view('pl-debug::sql-debug', ['profiles' => $profiles, 'counts' => $counts, 'standalone' => true, 'refresh' => $refresh]);

        return $response->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function clear(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($this->profiler->enabledFor($request), 404);
        $this->profiler->clear();

        return redirect()->to(url((string) config('pl.debug.path', '__debug/sql')));
    }
}
