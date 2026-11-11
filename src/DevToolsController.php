<?php

namespace Coding1688\DevTools;

use Coding1688\DevTools\Collectors\CollectorRegistry;
use Coding1688\DevTools\Diagnostics\ServerInfo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevToolsController extends \Illuminate\Routing\Controller
{
    public function __construct(
        private readonly SqlQueryProfiler $profiler,
        private readonly CollectorRegistry $collectors,
        private readonly ServerInfo $server,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($this->profiler->enabledFor($request), 404);
        if ($request->boolean('fresh')) {
            $this->server->flush();
        }
        $id = $request->query('request');
        abort_unless($id === null || (is_string($id) && preg_match('/^[a-f0-9-]{36}$/D', $id) === 1), 404);
        $profiles = $this->profiler->recent($id);
        $counts = ['all' => count($profiles), 'html' => 0, 'ajax' => 0];
        foreach ($profiles as $profile) {
            $type = (string) ($profile['type'] ?? 'html');
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        $refresh = (int) $request->query('refresh', config('pl.devtools.refresh_seconds', 0));
        $refresh = max(0, min(60, $refresh));
        $type = $request->query('type');
        if (in_array($type, ['html', 'ajax'], true)) {
            $profiles = array_values(array_filter($profiles, fn (array $profile): bool => ($profile['type'] ?? 'html') === $type));
        }
        if ($request->query('export') === 'json') {
            return response()->json([
                'exported_at' => now()->toIso8601String(),
                'profiles' => $profiles,
            ], 200, [
                'Content-Disposition' => 'attachment; filename="devtools-profile-'.now()->format('Ymd-His').'.json"',
                'Cache-Control' => 'private, no-store',
            ]);
        }
        $response = $request->expectsJson() ? response()->json(['profiles' => $profiles, 'counts' => $counts])
            : response()->view('pl-devtools::index', ['profiles' => $profiles, 'counts' => $counts, 'standalone' => true, 'refresh' => $refresh, 'collectors' => $this->collectors->all()]);

        return $response->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function clear(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($this->profiler->enabledFor($request), 404);
        $this->profiler->clear();

        return redirect()->to(url((string) config('pl.devtools.path', '__debug/sql')));
    }
}
