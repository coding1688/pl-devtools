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
        $response = $request->expectsJson() ? response()->json(['profiles' => $profiles])
            : response()->view('pl-debug::sql-debug', ['profiles' => $profiles, 'standalone' => true]);

        return $response->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
